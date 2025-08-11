<?php

namespace backend\services\Export;

interface ExportStrategyInterface
{
    /** @param array $result [dbName => [ ['title'=>..,'text'=>..], ...], ...] */
    public function output(array $result): void;
}
