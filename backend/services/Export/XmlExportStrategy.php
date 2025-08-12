<?php
namespace backend\services\Export;

use Yii;
use yii\web\Response;

class XmlExportStrategy implements ExportStrategyInterface
{
    public function __construct(private bool $pretty = true) {}

    public function output(array $result, ?string $filename = null): Response
    {
        $filename = $filename ?: 'export_' . date('Y-m-d_H-i-s') . '.xml';

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = $this->pretty;

        $root = $dom->createElement('items');
        $dom->appendChild($root);

        foreach ($result as $db => $items) {
            foreach ($items as $row) {
                $item = $dom->createElement('item');

                $item->appendChild($dom->createElement('database', $db));
                $item->appendChild($dom->createElement('title',   (string)($row['title'] ?? '')));
                $item->appendChild($dom->createElement('text',    (string)($row['content'] ?? ($row['text'] ?? ''))));

                $root->appendChild($item);
            }
        }

        $xml = $dom->saveXML();

        if (ob_get_length()) { @ob_end_clean(); }

        return Yii::$app->response->sendContentAsFile(
            $xml,
            $filename,
            ['mimeType' => 'application/xml; charset=UTF-8', 'inline' => false]
        );
    }
}
