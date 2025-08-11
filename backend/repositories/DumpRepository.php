<?php

namespace backend\repositories;

use Yii;

class DumpRepository
{
    private string $dir;

    public function __construct(string $dir = null)
    {
        $this->dir = $dir ?: Yii::getAlias('@storage/databases');
        \yii\helpers\FileHelper::createDirectory($this->dir);
    }

    /** @return string[] імена файлів .sql */
    public function list(): array
    {
        $files = glob($this->dir . '/*.sql') ?: [];
        return array_map('basename', $files);
    }

    public function path(string $file): string
    {
        return $this->dir . '/' . basename($file);
    }

    public function exists(string $file): bool
    {
        return is_file($this->path($file));
    }

    public function delete(string $file): bool
    {
        $path = $this->path($file);
        return $this->exists($file) ? @unlink($path) : false;
    }
}
