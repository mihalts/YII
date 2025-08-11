<?php

namespace backend\components;

use Yii;
use yii\base\Component;
use yii\helpers\HtmlPurifier;
use PDO;

class ParserComponent extends Component
{
    public $dbUser = 'root';
    public $dbPass = 'root';
    public $dbHost = 'db';      // docker-compose service name
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

            $pdo = new PDO(
                "mysql:host={$this->dbHost};port={$this->dbPort};dbname={$dbName}",
                $this->dbUser,
                $this->dbPass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec("SET NAMES utf8mb4");

            $table = $this->findNewsTable($pdo);
            if (!$table) {
                Yii::error("Не знайдено таблицю з полями title/content у БД [$dbName]");
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
            Yii::error("Помилка читання БД [$dbName]: " . $e->getMessage());
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

    /**
     * 1) Створюємо БД через PDO
     * 2) Імпортуємо дамп через `mysql` CLI (exec)
     */
    protected function ensureDatabaseImported(string $dbName): void
    {
        // 1) Перевіряємо/створюємо БД через PDO
        $serverPdo = new PDO(
            "mysql:host={$this->dbHost};port={$this->dbPort}",
            $this->dbUser,
            $this->dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $serverPdo->exec(
            "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        // Якщо в БД вже є хоча б 1 таблиця — імпорт не потрібен
        $checkPdo = new PDO(
            "mysql:host={$this->dbHost};port={$this->dbPort};dbname={$dbName}",
            $this->dbUser,
            $this->dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $tables = $checkPdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($tables)) {
            return;
        }

        // 2) Імпорт дампу через mysql CLI
        $sqlPath = Yii::getAlias("@storage/databases/{$dbName}.sql");
        if (!is_file($sqlPath)) {
            throw new \RuntimeException("SQL-файл не знайдено: {$sqlPath}");
        }

        // опційно: перевірка наявності клієнта mysql
        $which = trim(shell_exec('which mysql') ?? '');
        if ($which === '') {
            throw new \RuntimeException("Клієнт `mysql` не знайдено у контейнері PHP. Встанови його (apt-get install default-mysql-client).");
        }

        $cmd = sprintf(
            'mysql -h%s -P%s -u%s -p%s %s < %s',
            escapeshellarg($this->dbHost),
            escapeshellarg($this->dbPort),
            escapeshellarg($this->dbUser),
            escapeshellarg($this->dbPass),
            escapeshellarg($dbName),
            escapeshellarg($sqlPath)
        );

        exec($cmd, $out, $code);
        if ($code !== 0) {
            throw new \RuntimeException("Помилка імпорту SQL у БД `{$dbName}` з файлу {$sqlPath}");
        }
    }

    protected function findNewsTable(PDO $pdo): ?string
    {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (!$tables) return null;

        foreach ($tables as $table) {
            $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('title', $cols, true) && in_array('content', $cols, true)) {
                return $table;
            }
        }
        return null;
    }
}
