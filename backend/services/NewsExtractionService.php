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

    public function __construct(
        SchemaInspector $inspector = null,
        HtmlSanitizer $sanitizer = null
    ) {
        $this->inspector = $inspector ?? new SchemaInspector();
        $this->sanitizer = $sanitizer ?? new HtmlSanitizer();

        // ← підхоплюємо реквізити з params/env, якщо вони задані
        $p = Yii::$app->params['dbImport'] ?? [];
        $this->dbHost = $p['host'] ?? getenv('DB_HOST') ?? $this->dbHost;
        $this->dbPort = $p['port'] ?? getenv('DB_PORT') ?? $this->dbPort;
        $this->dbUser = $p['user'] ?? getenv('DB_USER') ?? $this->dbUser;
        $this->dbPass = $p['pass'] ?? getenv('DB_PASS') ?? $this->dbPass;
    }
    
    public function getSchema(string $dbName): array
    {
        $this->ensureImported($dbName);
        $pdo = $this->pdo($dbName);
        return $this->inspector->get($pdo);
    }

    public function getNews(string $dbName, array $map): array
    {
        // кеш (щоб не тягнути БД щоразу)
        $cacheKey = "news_{$dbName}_" . md5(json_encode($map));
        $cache = Yii::$app->cache;
        if (($cached = $cache->get($cacheKey)) !== false) {
            return $cached;
        }
    
        // гарантуємо, що дамп імпортований і є конект
        $this->ensureImported($dbName);
        $pdo = $this->pdo($dbName);
    
        // мапінг з дефолтами
        $table = $map['table']   ?? null;
        $title = $map['title']   ?? 'title';
        $text  = $map['content'] ?? 'content';
    
        // якщо таблицю не вказали — спробуємо знайти її автоматично
        if (!$table) {
            $table = $this->autoFindTable($pdo);
            if (!$table) {
                return [];
            }
        }
    
        // нормалізація імен
        $t  = str_replace('`', '', $table);
        $ti = str_replace('`', '', $title);
        $tx = str_replace('`', '', $text);
    
        // валідація: таблиця та колонки існують
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        if (!in_array($t, $tables, true)) {
            return [];
        }
        $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        if (!in_array($ti, $cols, true) || !in_array($tx, $cols, true)) {
            return [];
        }
    
        // витягуємо та санітизуємо
        $sql  = "SELECT `{$ti}` AS title, `{$tx}` AS content FROM `{$t}`";
        $stmt = $pdo->query($sql);
    
        $out = [];
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC)) !== false) {
            $out[] = [
                'title' => $this->sanitizer->clean($row['title'] ?? ''),
                'text'  => $this->sanitizer->clean($row['content'] ?? ''),
            ];
        }
    
        $cache->set($cacheKey, $out, $this->cacheTtl);
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
        // 1) Створюємо БД через PDO
        $serverPdo = new \PDO(
            "mysql:host={$this->dbHost};port={$this->dbPort}",
            $this->dbUser,
            $this->dbPass,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $serverPdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        // 2) Якщо вже є таблиці — імпорт не потрібен
        $dbPdo = new \PDO(
            "mysql:host={$this->dbHost};port={$this->dbPort};dbname={$dbName}",
            $this->dbUser,
            $this->dbPass,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
        $tables = $dbPdo->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
        if (!empty($tables)) {
            return;
        }

        // 3) SQL дамп у контейнері PHP
        $sqlPath = \Yii::getAlias("@storage/databases/{$dbName}.sql");
        if (!is_file($sqlPath)) {
            throw new \RuntimeException("SQL-файл не знайдено: {$sqlPath}");
        }

        // 4) Перевірка клієнта mysql
        $which = trim(shell_exec('which mysql') ?? '');
        if ($which === '') {
            throw new \RuntimeException("Клієнт `mysql` не знайдено в PHP-контейнері. Встанови: apt-get update && apt-get install -y default-mysql-client");
        }

        // 5) Імпорт через shell (ВАЖЛИВО: sh -c і 2>&1)
        $cmd = sprintf(
            'sh -c "mysql -h%s -P%s -u%s -p%s %s < %s 2>&1"',
            escapeshellarg($this->dbHost),
            escapeshellarg($this->dbPort),
            escapeshellarg($this->dbUser),
            escapeshellarg($this->dbPass),
            escapeshellarg($dbName),
            escapeshellarg($sqlPath)
        );

        $out = [];
        $code = 0;
        exec($cmd, $out, $code);

        if ($code !== 0) {
            throw new \RuntimeException(
                "Помилка імпорту SQL з {$sqlPath}\n" . implode("\n", $out)
            );
        }
    }
    
}
