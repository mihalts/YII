<?php

namespace backend\services\Export;

class ExportService
{
    public function asCsv(array $result): void
    {
        (new CsvExportStrategy())->output($result);
    }

    public function asTxt(array $result): void
    {
        (new TxtExportStrategy())->output($result);
    }

    public function asXml(array $result, bool $merge = false): void
    {
        (new XmlExportStrategy($merge))->output($result);
    }
}
