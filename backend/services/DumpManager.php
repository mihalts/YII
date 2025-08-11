<?php

namespace backend\services;

use Yii;
use yii\web\UploadedFile;
use backend\repositories\DumpRepository;

class DumpManager
{
    private DumpRepository $repo;

    public function __construct(DumpRepository $repo = null)
    {
        $this->repo = $repo ?? new DumpRepository();
    }

    /** Повертає true/false; валідація: тільки .sql, унікальна назва */
    public function handleUpload(string $fieldName): bool
    {
        $file = UploadedFile::getInstanceByName($fieldName);
        if (!$file || strtolower($file->extension) !== 'sql') {
            return false;
        }

        $target = $this->repo->path($file->name);
        if (file_exists($target)) {
            $pi = pathinfo($file->name);
            $target = $this->repo->path($pi['filename'] . '_' . date('Ymd_His') . '.sql');
        }
        \yii\helpers\FileHelper::createDirectory(dirname($target));
        return $file->saveAs($target);
    }
}
