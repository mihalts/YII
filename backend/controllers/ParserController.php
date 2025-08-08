<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\helpers\FileHelper;
use yii\helpers\ArrayHelper;

class ParserController extends Controller
{
    public function actionIndex()
    {
        $databasePath = Yii::getAlias('@storage/databases');
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

        default:
            Yii::$app->session->setFlash('error', 'Невідома дія.');
            return $this->redirect(['index']);
    }

    return Yii::$app->end(); // Завершує запит після виводу файлу
}

}
