<?php

namespace backend\services\Export;

use Yii;

class CsvExportStrategy implements ExportStrategyInterface
{
    public function output(array $result): void
    {
        $filename = 'export_' . date('Y-m-d_H-i-s') . '.csv';
        Yii::$app->response->headers->set('Content-Type', 'text/csv');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $fh = fopen('php://output', 'w');
        fputcsv($fh, ['Database', 'Title', 'Text']);
        foreach ($result as $db => $items) {
            foreach ($items as $row) {
                fputcsv($fh, [$db, $row['title'] ?? '', $row['text'] ?? '']);
            }
        }
        fclose($fh);
    }
}
