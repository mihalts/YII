<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;

class ParserController extends Controller
{
    /**
     * Головна сторінка: показує список .sql з @storage/databases.
     */
    public function actionIndex()
    {
        $databasePath = Yii::getAlias('@storage/databases');
        FileHelper::createDirectory($databasePath);

        $files = FileHelper::findFiles($databasePath, ['only' => ['*.sql']]);
        $databases = ArrayHelper::map($files, static fn($f) => basename($f), static fn($f) => basename($f));

        return $this->render('index', [
            'databases' => $databases,
        ]);
    }

    /**
     * Завантаження .sql (POST).
     */
    public function actionUpload()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('POST required');
        }
        $ok = Yii::$app->parser->upload();
        Yii::$app->session->setFlash($ok ? 'success' : 'error', $ok ? 'Файл успішно завантажено.' : 'Помилка при збереженні файлу.');
        return $this->redirect(['index']);
    }

    /**
     * Видалення .sql.
     */
    public function actionDelete(string $file)
    {
        $filename = basename($file);
        if (!preg_match('/\.sql$/i', $filename)) {
            Yii::$app->session->setFlash('error', 'Недопустиме розширення файлу.');
            return $this->redirect(['index']);
        }
        $path = Yii::getAlias('@storage/databases/' . $filename);
        if (is_file($path) && @unlink($path)) {
            Yii::$app->session->setFlash('success', "Файл видалено: {$filename}");
        } else {
            Yii::$app->session->setFlash('error', "Файл не знайдено або не видалений: {$filename}");
        }
        return $this->redirect(['index']);
    }

    /**
     * AJAX: повертає схему БД (таблиці+колонки) для селектів.
     * Приймає ?db= або ?file= (db1.sql).
     */
    public function actionSchema(string $db = null, string $file = null): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $arg = $db ?: $file;
        if (!$arg) return ['ok' => false, 'error' => 'missing param'];
        $dbName = pathinfo($arg, PATHINFO_FILENAME);

        try {
            $schema = Yii::$app->parser->getSchema($dbName); // [['table'=>..., 'columns'=>[...]], ...]
            return ['ok' => true, 'schema' => $schema];
        } catch (\Throwable $e) {
            Yii::error($e->getMessage(), __METHOD__);
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Експорт/перегляд результату.
     * Кнопки у view передають name="action" (view/csv/txt/xml/xml-merge).
     */
    public function actionExport()
    {
        $selected = Yii::$app->request->post('selectedDatabases', []);
        $action   = Yii::$app->request->post('action', Yii::$app->request->post('format')); // сумісність
        $tables   = Yii::$app->request->post('table', []);
        $titles   = Yii::$app->request->post('titleField', []);
        $contents = Yii::$app->request->post('contentField', []);

        if (empty($selected)) {
            Yii::$app->session->setFlash('error', 'Не вибрано жодної бази даних.');
            return $this->redirect(['index']);
        }

        $result = [];
        foreach ($selected as $sqlFileName) {
            $dbName  = pathinfo($sqlFileName, PATHINFO_FILENAME);
            $mapping = [
                'table'   => $tables[$sqlFileName]   ?? null,
                'title'   => $titles[$sqlFileName]   ?? null,
                'content' => $contents[$sqlFileName] ?? null,
            ];
            $result[$dbName] = Yii::$app->parser->getNewsFromDatabaseWithMap($dbName, $mapping);
        }

        switch ($action) {
            case 'view':
            case 'preview':
                return $this->render('preview', ['result' => $result]);

            case 'csv':
                Yii::$app->export->exportToCsv($result);
                break;

            case 'txt':
                Yii::$app->export->exportToTxt($result);
                break;

            case 'xml':
                Yii::$app->export->exportToXml($result, false);
                break;

            case 'xml-merge':
                Yii::$app->export->exportToXml($result, true);
                break;

            default:
                Yii::$app->session->setFlash('error', 'Невідома дія.');
                return $this->redirect(['index']);
        }

        Yii::$app->end();
        return null;
    }
}
