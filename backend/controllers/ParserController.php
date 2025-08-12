<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use backend\services\DumpManager;
use backend\services\NewsExtractionService;
use backend\services\Export\ExportService;
use backend\repositories\DumpRepository;

class ParserController extends Controller
{
    public function actionIndex(): string
    {
        /** @var DumpRepository $repo */
        $repo  = Yii::$container->get(DumpRepository::class);
        $dumps = $repo->list();

        return $this->render('index', ['databases' => $dumps]);
    }

    public function actionUpload(): Response
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('POST required');
        }

        /** @var DumpManager $manager */
        $manager = Yii::$container->get(DumpManager::class);

        if ($manager->handleUpload('sqlFile')) {
            Yii::$app->session->setFlash('success', 'Файл завантажено.');
        } else {
            Yii::$app->session->setFlash('error', 'Не вдалося завантажити файл.');
        }
        return $this->redirect(['index']);
    }

    public function actionDelete(string $file): Response
    {
        /** @var DumpRepository $repo */
        $repo = Yii::$container->get(DumpRepository::class);

        if ($repo->delete($file)) {
            Yii::$app->session->setFlash('success', "Видалено: {$file}");
        } else {
            Yii::$app->session->setFlash('error', "Не знайдено або не видалено: {$file}");
        }
        return $this->redirect(['index']);
    }

    public function actionSchema(string $db = null, string $file = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $dumpFile = $db ?: $file;
        if (!$dumpFile) {
            return ['ok' => false, 'error' => 'missing param'];
        }

        /** @var NewsExtractionService $svc */
        $svc = Yii::$container->get(NewsExtractionService::class);

        try {
            $schema = $svc->getSchema(pathinfo($dumpFile, PATHINFO_FILENAME));
            return ['ok' => true, 'schema' => $schema];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Показ прев’ю або віддача файлу експорту */
    public function actionExport(): Response|string
    {
        $selected = Yii::$app->request->post('selectedDatabases', []);
        $action   = Yii::$app->request->post('action', Yii::$app->request->post('format'));
        $tables   = Yii::$app->request->post('table', []);
        $titles   = Yii::$app->request->post('titleField', []);
        $contents = Yii::$app->request->post('contentField', []);

        if (empty($selected)) {
            Yii::$app->session->setFlash('error', 'Не вибрано жодної БД.');
            return $this->redirect(['index']);
        }

        /** @var NewsExtractionService $extractor */
        $extractor = Yii::$container->get(NewsExtractionService::class);
        $result = [];

        foreach ($selected as $sqlFile) {
            $db = pathinfo($sqlFile, PATHINFO_FILENAME);
            $mapping = [
                'table'   => $tables[$sqlFile]   ?? null,
                'title'   => $titles[$sqlFile]   ?? null,
                'content' => $contents[$sqlFile] ?? null,
            ];
            $result[$db] = $extractor->getNews($db, $mapping) ?? [];
        }

        if ($action === 'view' || $action === 'preview') {
            return $this->render('preview', ['result' => $result]); // string
        }

        /** @var ExportService $export */
        $export = Yii::$container->get(ExportService::class);

        return match ($action) {
            'csv'       => $export->asCsv($result),
            'txt'       => $export->asTxt($result),
            'xml'       => $export->asXml($result, false),
            'xml-merge' => $export->asXml($result, true),
            default     => $this->redirect(['index']),
        };
    }
}
