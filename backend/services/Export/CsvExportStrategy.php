<?php
namespace backend\services\Export;

use Yii;
use yii\web\Response;

class CsvExportStrategy implements ExportStrategyInterface
{
    public function output(array $result, ?string $filename = null): Response
    {
        $filename = $filename ?: 'export_' . date('Y-m-d_H-i-s') . '.csv';

        // Збираємо CSV у пам'яті
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, ['Database', 'Title', 'Text']);
        foreach ($result as $db => $items) {
            foreach ($items as $row) {
                $title = $row['title']   ?? '';
                $text  = $row['content'] ?? ($row['text'] ?? '');
                fputcsv($fp, [$db, $title, $text]);
            }
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        if (ob_get_length()) { @ob_end_clean(); }

        return Yii::$app->response->sendContentAsFile(
            $csv,
            $filename,
            ['mimeType' => 'text/csv; charset=UTF-8', 'inline' => false]
        );
    }
}
