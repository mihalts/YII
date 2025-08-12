<?php
namespace backend\services\Export;

use yii\web\Response;

class ExportService
{
    public function __construct(
        private CsvExportStrategy $csv,
        private TxtExportStrategy $txt,
        private XmlExportStrategy $xml
    ) {}

    public function asCsv(array $data, ?string $filename = null): Response
    {
        return $this->csv->output($data, $filename);
    }

    public function asTxt(array $data, ?string $filename = null): Response
    {
        return $this->txt->output($data, $filename);
    }

    /**
     * Якщо $merge=true — зливаємо всі елементи в один набір, щоб у файлі був єдиний список.
     */
    public function asXml(array $data, bool $merge = false, ?string $filename = null): Response
    {
        if ($merge) {
            $merged = [];
            foreach ($data as $db => $items) {
                foreach ($items as $row) {
                    $merged[] = $row + ['_db' => $db];
                }
            }
            // Загорнемо в один ключ, щоб стратегія пройшлась одним циклом
            $data = ['merged' => array_map(
                fn($r) => ['title' => $r['title'] ?? '', 'content' => $r['content'] ?? ($r['text'] ?? '')],
                $merged
            )];
        }

        return $this->xml->output($data, $filename);
    }
}
