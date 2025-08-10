<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;

class ParserController extends Controller
{
    public function actionIndex()
    {
        $databasePath = Yii::getAlias('@storage/databases');
        FileHelper::createDirectory($databasePath);

        $files = FileHelper::findFiles($databasePath, [
            'only' => ['*.sql']
        ]);

        $databases = ArrayHelper::map($files, function ($file) {
            return basename($file);
        }, function ($file) {
            return basename($file);
        });

        return $this->render('index', [
            'databases' => $databases,
        ]);
    }

    public function actionUpload()
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('POST required');
        }

        $ok = Yii::$app->parser->upload();
        Yii::$app->session->setFlash($ok ? 'success' : 'error', $ok ? 'Файл успішно завантажено.' : 'Помилка при збереженні файлу.');

        return $this->redirect(['index']);
    }

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

    public function actionExport()
    {
        $selected = Yii::$app->request->post('selectedDatabases', []);
        $action = Yii::$app->request->post('action');

        if (empty($selected)) {
            Yii::$app->session->setFlash('error', 'Не вибрано жодної бази даних.');
            return $this->redirect(['index']);
        }

        $result = [];
        foreach ($selected as $sqlFileName) {
            $dbName = pathinfo($sqlFileName, PATHINFO_FILENAME);
            $dbData = Yii::$app->parser->getNewsFromDatabase($dbName);
            $result[$dbName] = $dbData;
        }

        switch ($action) {
            case 'view':
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
    }
}
