<?php

namespace backend\services\Export;

use Yii;
use yii\helpers\FileHelper;

class XmlExportStrategy implements ExportStrategyInterface
{
    private bool $merge;

    public function __construct(bool $merge = false)
    {
        $this->merge = $merge;
    }

    public function output(array $result): void
    {
        $dir = Yii::getAlias('@webroot/exports');
        FileHelper::createDirectory($dir);
        $zipName = $dir . '/news_' . date('Ymd_His') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create archive: ' . $zipName);
        }

        if ($this->merge) {
            $xml = $this->buildXml($result);
            $zip->addFromString('merged.xml', $xml);
        } else {
            foreach ($result as $db => $items) {
                $xml = $this->buildXml([$db => $items]);
                $zip->addFromString($db . '.xml', $xml);
            }
        }
        $zip->close();

        Yii::$app->response->sendFile($zipName)->send();
    }

    private function buildXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><news></news>');
        foreach ($data as $db => $items) {
            foreach ($items as $row) {
                $item = $xml->addChild('item');
                $item->addChild('title', htmlspecialchars($row['title'] ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8'));
                $item->addChild('text',  htmlspecialchars($row['text'] ?? '',  ENT_XML1 | ENT_COMPAT, 'UTF-8'));
                $item->addChild('source', htmlspecialchars($db, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
            }
        }
        return $xml->asXML();
    }
}
