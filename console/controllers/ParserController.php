<?php

namespace console\controllers;

use yii\console\Controller;
use yii\helpers\FileHelper;

class ParserController extends Controller
{
    public function actionList()
    {
        $path = \Yii::getAlias('@app/../storage/databases');
        $files = FileHelper::findFiles($path, ['only' => ['*.db']]);

        foreach ($files as $file) {
            echo basename($file) . PHP_EOL;
        }

        return Controller::EXIT_CODE_NORMAL;
    }

    public function actionParse($file)
    {
        $dbPath = \Yii::getAlias("@app/../storage/databases/$file");

        if (!file_exists($dbPath)) {
            echo \"File not found: $dbPath\" . PHP_EOL;
            return Controller::EXIT_CODE_ERROR;
        }

        $pdo = new \PDO(\"sqlite:$dbPath\");

        $rows = $pdo->query('SELECT title, content FROM news')->fetchAll(\PDO::FETCH_ASSOC);

        $output = \"Title,Content\\n\";

        foreach ($rows as $row) {
            $title = addslashes(strip_tags($row['title']));
            $content = addslashes(strip_tags($row['content']));
            $output .= \"$title,$content\\n\";
        }

        file_put_contents(\"runtime/{$file}.csv\", $output);

        echo \"Exported to runtime/{$file}.csv\" . PHP_EOL;
        return Controller::EXIT_CODE_NORMAL;
    }
}
