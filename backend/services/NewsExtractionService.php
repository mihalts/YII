<?php

namespace backend\services;

use PDO;
use Yii;
use backend\services\Schema\SchemaInspector;
use backend\services\Sanitizer\HtmlSanitizer;

class NewsExtractionService
{
    public string $dbUser = 'root';
    public string $dbPass = 'root';
    public string $dbHost = 'db';
    public string $dbPort = '3306';
    private int $cacheTtl = 300;

    private SchemaInspector $inspector;
    private HtmlSanitizer $sanitizer;

    public function __construct(SchemaInspector $inspector = null, HtmlSanitizer $sanitizer = null)
    {
        $this->inspector = $inspector ?? new SchemaInspector();
        $this->sanitizer = $sanitizer ?? new HtmlSanitizer();
    }

    public function getSchema(string $dbName): array
    {
        $this->ensureImported($dbName);
        $pdo = $this->pdo($dbName);
        return $this->inspector->get($pdo);
    }

    public function getNews(string $dbName, array $map): array
    {
        $cacheKey = "news_{$dbName}_" . md5(json_encode($map));
        $cache = Yii::$app->cache;
        if (($cached = $cache->get($cacheKey)) !== false) {
            return $cached;
        }

        $this->ensureImported($dbName);
        $pdo = $this->pdo($dbName);

        $table = $map['table']   ?? null;
        $title = $map['title']   ?? 'title';
        $text  = $map['content'] ?? 'content';

        if (!$table) {
            $table = $this->autoFindTable($pdo);
            if (!$table) {
                return [];
            }
        }

        $qTable = str_replace('`', '', $table);
        $qTitle = str_replace('`', '', $title);
        $qText  = str_replace('`', '', $text);

        $sql = "SELECT `{$qTitle}` AS title, `{$qText}` AS content FROM `{$qTable}`";
        $stmt = $pdo->query($sql);

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out.append if needed
        }
        return $out;
    }

    private function autoFindTable(PDO $pdo): ?string
    {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        foreach ($tables as $t) {
            $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN) ?: [];
            if (in_array('title', $cols, true) && in_array('content', $cols, true)) {
                return $t;
            }
        }
        return null;
    }

    private function pdo(string $dbName): PDO
    {
        $pdo = new PDO("mysql:host={$this->dbHost};port={$this->dbPort};dbname={$dbName}", $this->dbUser, $this->dbPass);
        $pdo->exec("SET NAMES utf8mb4");
        return $pdo;
    }

    private function ensureImported(string $dbName): void
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
            'mysql -h%s -P%s -u%s -p%s -e "CREATE DATABASE IF NOT EXISTS `%%s`;" && mysql -h%s -P%s -u%s -p%s `%%s` < %s',
            escapeshellarg($this->dbHost), escapeshellarg($this->dbPort),
            escapeshellarg($this->dbUser), escapeshellarg($this->dbPass),
            escapeshellarg($this->dbHost), escapeshellarg($this->dbPort),
            escapeshellarg($this->dbUser), escapeshellarg($this->dbPass),
            escapeshellarg($sqlPath)
        );
        $cmd = sprintf($cmd, $dbName, $dbName);

        exec($cmd, $out, $code);
        if ($code !== 0) {
            throw new \RuntimeException("Помилка імпорту SQL: {$sqlPath}");
        }
    }
}
