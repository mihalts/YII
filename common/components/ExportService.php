<?php

namespace common\components;

use Yii;

class ExportService
{
    /**
     * Очищає HTML від зображень та посилань, зберігаючи базове форматування.
     */
    protected function cleanContent($content): string
    {
        // Видаляємо <img> та <a>
        $content = preg_replace('/<img[^>]*>/i', '', $content);
        $content = preg_replace('/<a[^>]*>(.*?)<\/a>/i', '$1', $content);
        return strip_tags($content, '<p><br><b><strong><i><ul><ol><li>');
    }

    /**
     * Експортує новини з масиву в CSV.
     */
    public function exportToCsv(array $data, string $filePath): bool
    {
        $fp = fopen($filePath, 'w');
        if ($fp === false) {
            return false;
        }

        fputcsv($fp, ['title', 'content']);

        foreach ($data as $row) {
            $cleaned = [
                $row['title'],
                $this->cleanContent($row['content']),
            ];
            fputcsv($fp, $cleaned);
        }

        fclose($fp);
        return true;
    }

    /**
     * Експортує новини з масиву в TXT.
     */
    public function exportToTxt(array $data, string $filePath): bool
    {
        $lines = [];

        foreach ($data as $row) {
            $lines[] = "Title: " . $row['title'];
            $lines[] = "Content:\n" . $this->cleanContent($row['content']);
            $lines[] = str_repeat('-', 40);
        }

        return file_put_contents($filePath, implode("\n\n", $lines)) !== false;
    }
}
