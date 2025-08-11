<?php

namespace backend\dto;

final class NewsItem
{
    public string $title;
    public string $text;

    public function __construct(string $title, string $text)
    {
        $this->title = $title;
        $this->text  = $text;
    }

    public function toArray(): array
    {
        return ['title' => $this->title, 'text' => $this->text];
    }
}
