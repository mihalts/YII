<?php

namespace backend\services\Schema;

use PDO;

class SchemaInspector
{
    /** @return array [['table'=>'...', 'columns'=>['a','b',...]], ...] */
    public function get(PDO $pdo): array
    {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $out = [];
        foreach ($tables as $t) {
            $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN) ?: [];
            $out[] = ['table' => $t, 'columns' => $cols];
        }
        return $out;
    }
}
