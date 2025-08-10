<?php

namespace backend\components;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class ExportService extends Component
{
    public function exportToCsv(array $result): void
    {
        $filename = 'export_' . date('Y-m-d_H-i-s') . '.csv';
        Yii::$app->response->headers->set('Content-Type', 'text/csv');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $fh = fopen('php://output', 'w');
        fputcsv($fh, ['Database', 'Title', 'Text']);
        foreach ($result as $dbName => $newsList) {
            foreach ($newsList as $news) {
                fputcsv($fh, [$dbName, $news['title'] ?? '', $news['text'] ?? '']);
            }
        }
        fclose($fh);
        Yii::$app->end();
    }

    public function exportToTxt(array $result): void
    {
        $filename = 'export_' . date('Y-m-d_H-i-s') . '.txt';
        Yii::$app->response->headers->set('Content-Type', 'text/plain; charset=UTF-8');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        foreach ($result as $dbName => $newsList) {
            fwrite($out, "Database: {$dbName}\n\n");
            foreach ($newsList as $news) {
                fwrite($out, "Title: " . ($news['title'] ?? '') . "\n");
                fwrite($out, "Text: " . ($news['text'] ?? '') . "\n");
                fwrite($out, str_repeat('-', 40) . "\n");
            }
            fwrite($out, "\n");
        }
        fclose($out);
        Yii::$app->end();
    }

    public function exportToXml(array $result, bool $merge = false): void
    {
        $dir = Yii::getAlias('@webroot/exports');
        FileHelper::createDirectory($dir);
        $zipName = $dir . '/news_' . date('Ymd_His') . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create archive: ' . $zipName);
        }

        if ($merge) {
            $xml = $this->generateXml($result);
            $zip->addFromString('merged.xml', $xml);
        } else {
            foreach ($result as $dbName => $newsList) {
                $xml = $this->generateXml([$dbName => $newsList]);
                $zip->addFromString($dbName . '.xml', $xml);
            }
        }

        $zip->close();

        Yii::$app->response->sendFile($zipName)->send();
        Yii::$app->end();
    }

    private function generateXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><news></news>');
        foreach ($data as $dbName => $newsList) {
            foreach ($newsList as $news) {
                $item = $xml->addChild('item');
                $item->addChild('title', htmlspecialchars($news['title'] ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8'));
                $item->addChild('text',  htmlspecialchars($news['text'] ?? '',  ENT_XML1 | ENT_COMPAT, 'UTF-8'));
                $item->addChild('source', htmlspecialchars($dbName ?? '',        ENT_XML1 | ENT_COMPAT, 'UTF-8'));
            }
        }
        return $xml->asXML();
    }
}
