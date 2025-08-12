<?php
namespace backend\services\Export;

use Yii;
use yii\web\Response;

class TxtExportStrategy implements ExportStrategyInterface
{
    public function output(array $result, ?string $filename = null): Response
    {
        $filename = $filename ?: 'export_' . date('Y-m-d_H-i-s') . '.txt';

        $lines = [];
        foreach ($result as $db => $items) {
            foreach ($items as $row) {
                $title = $row['title']   ?? '';
                $text  = $row['content'] ?? ($row['text'] ?? '');
                $lines[] = "[$db] " . $title;
                $lines[] = $text;
                $lines[] = str_repeat('-', 80);
            }
        }
        $txt = implode(PHP_EOL, $lines) . PHP_EOL;

        if (ob_get_length()) { @ob_end_clean(); }

        return Yii::$app->response->sendContentAsFile(
            $txt,
            $filename,
            ['mimeType' => 'text/plain; charset=UTF-8', 'inline' => false]
        );
    }
}
