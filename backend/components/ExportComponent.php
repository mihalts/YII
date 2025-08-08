<?php

namespace backend\components;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class ExportService extends Component
{
    public function exportToCsv(array $result)
    {
        $filename = 'export_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Database', 'Title', 'Text']);

        foreach ($result as $dbName => $newsList) {
            foreach ($newsList as $news) {
                fputcsv($output, [
                    $dbName,
                    $news['title'] ?? '',
                    $news['text'] ?? '',
                ]);
            }
        }

        fclose($output);
        Yii::$app->end();
    }

    public function exportToTxt(array $result)
    {
        $filename = 'export_' . date('Y-m-d_H-i-s') . '.txt';

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        foreach ($result as $dbName => $newsList) {
            echo "Database: $dbName\n\n";
            foreach ($newsList as $news) {
                echo "Title: " . ($news['title'] ?? '') . "\n";
                echo "Text: " . ($news['text'] ?? '') . "\n";
                echo str_repeat('-', 40) . "\n";
            }
        }

        Yii::$app->end();
    }

    public function exportToXml(array $result, bool $merge = false)
    {
        $dir = Yii::getAlias('@webroot/exports');
        FileHelper::createDirectory($dir);

        $zipName = $dir . '/news_' . time() . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipName, \ZipArchive::CREATE);

        if ($merge) {
            $xml = $this->generateXml($result);
            $zip->addFromString('merged.xml', $xml);
        } else {
            foreach ($result as $dbName => $newsList) {
                $xml = $this->generateXml([$dbName => $newsList]);
                $zip->addFromString("$dbName.xml", $xml);
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
                $item->addChild('title', htmlspecialchars($news['title'] ?? ''));
                $item->addChild('text', htmlspecialchars($news['text'] ?? ''));
                $item->addChild('source', $dbName);
            }
        }

        return $xml->asXML();
    }
}
