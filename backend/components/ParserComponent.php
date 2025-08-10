<?php

namespace backend\components;

use Yii;
use yii\base\Component;
use yii\helpers\HtmlPurifier;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use PDO;

class ParserComponent extends Component
{
    public $dbUser = 'root';
    public $dbPass = 'root';
    public $dbHost = 'db';   // Docker Compose service name
    public $dbPort = '3306';
    protected $cacheDuration = 300; // seconds

    /**
     * Завантаження .sql у @storage/databases (без редіректів).
     */
    public function upload(): bool
    {
        $uploadedFile = UploadedFile::getInstanceByName('sqlFile');
        if (!$uploadedFile || strtolower($uploadedFile->extension) !== 'sql') {
            return false;
        }
        $dir = Yii::getAlias('@storage/databases');
        FileHelper::createDirectory($dir);
        return $uploadedFile->saveAs($dir . '/' . $uploadedFile->name);
    }

    /**
     * Схема БД: [['table'=>'news','columns'=>['id','title','content',...]], ...]
     * 1) SHOW TABLES/COLUMNS з реальної БД; 2) fallback — парсинг CREATE TABLE із .sql.
     */
    public function getSchema(string $dbName): array
    {
        // 1) напряму з БД
        try {
            $this->ensureDatabaseImported($dbName);
            $pdo = new PDO("mysql:host={$this->dbHost};port={$this->dbPort};dbname={$dbName}", $this->dbUser, $this->dbPass);
            $pdo->exec("SET NAMES utf8mb4");

            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $out = [];
            foreach ($tables as $t) {
                $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN);
                $out[] = ['table' => $t, 'columns' => $cols];
            }
            if ($out) return $out;
        } catch (\Throwable $e) {
            Yii::warning("getSchema DB failed: " . $e->getMessage(), __METHOD__);
        }

        // 2) fallback: парсимо .sql
        $sqlPath = Yii::getAlias("@storage/databases/{$dbName}.sql");
        if (!is_file($sqlPath)) {
            throw new \RuntimeException("SQL-файл не знайдено: {$sqlPath}");
        }
        $sql = file_get_contents($sqlPath);

        $tables = [];
        if (preg_match_all('/CREATE\\s+TABLE\\s+`([^`]+)`\\s*\\((.*?)\\)\\s*;/is', $sql, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $table = $match[1];
                $body  = $match[2];
                $cols  = [];
                foreach (preg_split('/,\\s*\\n|,\\n|,\\s/', $body) as $line) {
                    if (preg_match('/^\\s*`([^`]+)`\\s+/u', $line, $cm)) {
                        $cols[] = $cm[1];
                    }
                }
                $tables[] = ['table' => $table, 'columns' => $cols];
            }
        }
        return $tables;
    }

    /**
     * Читання новин за явним маппінгом (таблиця/поля) або автопошук title/content.
     * $map: table|title|content (із селектів або ручного вводу).
     */
    public function getNewsFromDatabaseWithMap(string $dbName, array $map): array
    {
        $table = $map['table']   ?? null;
        $title = $map['title']   ?? 'title';
        $text  = $map['content'] ?? 'content';

        if (!$table) {
            return $this->getNewsFromDatabase($dbName);
        }

        try {
            $this->ensureDatabaseImported($dbName);
            $pdo = new PDO("mysql:host={$this->dbHost};port={$this->dbPort};dbname={$dbName}", $this->dbUser, $this->dbPass);
            $pdo->exec("SET NAMES utf8mb4");

            // простий захист від ін’єкцій у назвах
            $qTable = str_replace('`', '', $table);
            $qTitle = str_replace('`', '', $title);
            $qText  = str_replace('`', '', $text);

            $sql = "SELECT `{$qTitle}` AS title, `{$qText}` AS content FROM `{$qTable}`";
            $stmt = $pdo->query($sql);

            $news = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $news[] = [
                    'title' => $this->sanitize($row['title'] ?? ''),
                    'text'  => $this->sanitize($row['content'] ?? ''),
                ];
            }
            return $news;
        } catch (\Throwable $e) {
            Yii::error("Помилка map-читання БД [$dbName]: " . $e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * Автопошук таблиці з полями title/content.
     */
    public function getNewsFromDatabase(string $dbName): array
    {
        $cacheKey = "news_{$dbName}";
        $cache = Yii::$app->cache;

        if (($cached = $cache->get($cacheKey)) !== false) {
            return $cached;
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

    /**
     * Очищення контенту: прибираємо <a> та <img>, залишаємо базові теги.
     */
    protected function sanitize(string $text): string
    {
        $text = preg_replace('#<a[^>]*>.*?</a>#is', '', $text);
        $text = preg_replace('#<img[^>]*>#is', '', $text);
        return HtmlPurifier::process($text, [
            'HTML.Allowed' => 'p,b,strong,i,em,ul,ol,li,br',
        ]);
    }

    /**
     * Якщо БД не існує — створює і імпортує з @storage/databases/{db}.sql (вимагає mysql CLI).
     */
    protected function ensureDatabaseImported(string $dbName): void
    {
        $pdo = new PDO("mysql:host={$this->dbHost};port={$this->dbPort}", $this->dbUser, $this->dbPass);
        if ($pdo->query("SHOW DATABASES LIKE '{$dbName}'")->fetch()) {
            return;
        }

        $sqlPath = Yii::getAlias("@storage/databases/{$dbName}.sql");
        if (!is_file($sqlPath)) {
            throw new \RuntimeException("SQL-файл не знайдено: {$sqlPath}");
        }

        $cmd = sprintf(
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

        exec($cmd, $out, $code);
        if ($code !== 0) {
            throw new \RuntimeException("Помилка імпорту SQL: {$sqlPath}");
        }
    }

    /**
     * Шукає таблицю з полями title/content.
     */
    protected function findNewsTable(PDO $pdo): ?string
    {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (!$tables) return null;

        foreach ($tables as $table) {
            $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('title', $columns, true) && in_array('content', $columns, true)) {
                return $table;
            }
        }
        return null;
    }
}
