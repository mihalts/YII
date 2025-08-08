<?php

namespace backend\components;

use Yii;
use yii\base\Component;
use yii\helpers\HtmlPurifier;
use PDO;
use yii\web\UploadedFile;

class ParserComponent extends Component
{
    public $dbUser = 'root';
    public $dbPass = 'root';
    public $dbHost = 'db'; // Docker Compose service name
    public $dbPort = '3306';
    protected $cacheDuration = 300; // seconds

    public function getNewsFromDatabase(string $dbName): array
    {
        $cacheKey = "news_{$dbName}";
        $cache = Yii::$app->cache;

        if ($cache->exists($cacheKey)) {
            return $cache->get($cacheKey);
        }

        try {
            $this->ensureDatabaseImported($dbName);

            $pdo = new PDO("mysql:host={$this->dbHost};port={$this->dbPort};dbname={$dbName}", $this->dbUser, $this->dbPass);
            $pdo->exec("SET NAMES utf8mb4");

            $table = $this->findNewsTable($pdo);
            if (!$table) {
                Yii::error("Не знайдено таблицю з полями title/content в [$dbName]");
                return [];
            }

            $stmt = $pdo->query("SELECT title, content FROM `{$table}`");

            $news = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $news[] = [
                    'title' => $this->sanitize($row['title'] ?? ''),
                    'text'  => $this->sanitize($row['content'] ?? ''),
                ];
            }

            $cache->set($cacheKey, $news, $this->cacheDuration);
            return $news;
        } catch (\Throwable $e) {
            Yii::error("Помилка при читанні БД [$dbName]: " . $e->getMessage());
            return [];
        }
    }

    protected function sanitize(string $text): string
    {
        $text = preg_replace('#<a[^>]*>.*?</a>#is', '', $text);
        $text = preg_replace('#<img[^>]*>#is', '', $text);

        return HtmlPurifier::process($text, [
            'HTML.Allowed' => 'p,b,strong,i,em,ul,ol,li,br'
        ]);
    }

    protected function ensureDatabaseImported(string $dbName): void
    {
        $pdo = new PDO("mysql:host={$this->dbHost};port={$this->dbPort}", $this->dbUser, $this->dbPass);

        $exists = $pdo->query("SHOW DATABASES LIKE '{$dbName}'")->fetch();
        if ($exists) return;

        $sqlPath = Yii::getAlias("@storage/databases/{$dbName}.sql");
        if (!file_exists($sqlPath)) {
            throw new \RuntimeException("SQL-файл не знайдено: {$sqlPath}");
        }

        $importCmd = sprintf(
            'mysql -h%s -P%s -u%s -p%s -e "CREATE DATABASE IF NOT EXISTS `%s`;" && mysql -h%s -P%s -u%s -p%s `%s` < %s',
            escapeshellarg($this->dbHost),
            escapeshellarg($this->dbPort),
            escapeshellarg($this->dbUser),
            escapeshellarg($this->dbPass),
            escapeshellarg($dbName),
            escapeshellarg($this->dbHost),
            escapeshellarg($this->dbPort),
            escapeshellarg($this->dbUser),
            escapeshellarg($this->dbPass),
            escapeshellarg($dbName),
            escapeshellarg($sqlPath)
        );

        exec($importCmd, $output, $code);
        if ($code !== 0) {
            throw new \RuntimeException("Помилка імпорту SQL: {$sqlPath}");
        }
    }

    protected function findNewsTable(PDO $pdo): ?string
    {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (!$tables) return null;

        foreach ($tables as $table) {
            $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('title', $columns) && in_array('content', $columns)) {
                return $table;
            }
        }

        return null;
    }

    public function actionDelete($file)
    {
        $filePath = Yii::getAlias('@storage/databases/' . $file);

        if (!preg_match('/\.sql$/', $file)) {
            Yii::$app->session->setFlash('error', 'Недопустиме розширення файлу.');
            return $this->redirect(['index']);
        }

        if (file_exists($filePath)) {
            if (@unlink($filePath)) {
                Yii::$app->session->setFlash('success', "Файл '$file' успішно видалено.");
            } else {
                Yii::$app->session->setFlash('error', "Не вдалося видалити файл '$file'.");
            }
        } else {
            Yii::$app->session->setFlash('error', "Файл '$file' не знайдено.");
        }

        return $this->redirect(['index']);
    }

    public function actionUpload()
    {
        $uploadedFile = UploadedFile::getInstanceByName('sqlFile');

        if (!$uploadedFile) {
            Yii::$app->session->setFlash('error', 'Файл не вибрано.');
            return $this->redirect(['index']);
        }

        if ($uploadedFile->extension !== 'sql') {
            Yii::$app->session->setFlash('error', 'Дозволено лише .sql файли.');
            return $this->redirect(['index']);
        }

        $targetPath = Yii::getAlias('@storage/databases/' . $uploadedFile->name);

        if ($uploadedFile->saveAs($targetPath)) {
            Yii::$app->session->setFlash('success', 'Файл успішно завантажено.');
        } else {
            Yii::$app->session->setFlash('error', 'Помилка при збереженні файлу.');
        }

        return $this->redirect(['index']);
    }

}
