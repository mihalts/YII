<?php
namespace backend\services\Export;

use yii\web\Response;

interface ExportStrategyInterface
{
    /**
     * @param array $result Масив виду:
     *   ['dbName' => [ ['title'=>..., 'content'=>...], ... ], ...]
     * @param string|null $filename Ім'я файлу (опціонально)
     */
    public function output(array $result, ?string $filename = null): Response;
}
