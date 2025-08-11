<?php

namespace backend\services\Export;

use Yii;

class TxtExportStrategy implements ExportStrategyInterface
{
    public function output(array $result): void
    {
        $filename = 'export_' . date('Y-m-d_H-i-s') . '.txt';
        Yii::$app->response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        foreach ($result as $db => $items) {
            fwrite($out, "Database: {$db}\n\n");
            foreach ($items as $row) {
                fwrite($out, "Title: " . ($row['title'] ?? '') . "\n");
                fwrite($out, "Text: " . ($row['text'] ?? '') . "\n");
                fwrite($out, str_repeat('-', 40) . "\n");
            }
            fwrite($out, "\n");
        }
        fclose($out);
    }
}
