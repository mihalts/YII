<?php

namespace console\controllers;

use yii\console\Controller;
use yii\helpers\FileHelper;

class ParserController extends Controller
{
    public function actionIndex()
    {
        $availableDbs = ['db1', 'db2', 'db3', 'db4'];

        $selectedDb = Yii::$app->request->get('db', 'all');

        return $this->render('index', [
           'availableDbs' => $availableDbs,
           'selectedDb' => $selectedDb,
        ]);
    }

    public function actionExportXml($db = 'all')
    {
        $databases = ($db === 'all') ? ['db1', 'db2', 'db3', 'db4'] : [$db];

        foreach ($databases as $dbname) {
            // Виклик сервісу або логіки експорту для кожної БД
            Yii::info("Експортуємо XML з $dbname");
        }

        Yii::$app->session->setFlash('success', 'Експорт завершено.');

        return $this->redirect(['parser/index', 'db' => $db]);
    }

    public function actionList()
    {
        $path = \Yii::getAlias("@app/../storage/mysql-dumps");
        $files = FileHelper::findFiles($path, ['only' => ['*.sql']]);

        foreach ($files as $file) {
            echo basename($file, '.sql') . PHP_EOL;
        }

        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionParse($file)
    {
        $dbName = pathinfo($file, PATHINFO_FILENAME);

        try {
            $pdo = new \PDO("mysql:host=db;dbname=$dbName", 'root', 'root');
            $pdo->exec("SET NAMES utf8mb4");
        } catch (\PDOException $e) {
            echo "Connection failed: " . $e->getMessage() . PHP_EOL;
            return Controller::EXIT_CODE_ERROR;
        }

        $rows = $pdo->query("SELECT title, content FROM news")->fetchAll(\PDO::FETCH_ASSOC);

        $output = "Title,Content\n";

        foreach ($rows as $row) {
            $title = addslashes(strip_tags($row['title']));
            $content = addslashes(strip_tags($row['content']));
            $output .= "\"$title\",\"$content\"\n";
        }

        $filename = "runtime/{$dbName}.csv";
        file_put_contents($filename, $output);

        echo "Exported to $filename" . PHP_EOL;
        return Controller::EXIT_CODE_NORMAL;
    }
}

