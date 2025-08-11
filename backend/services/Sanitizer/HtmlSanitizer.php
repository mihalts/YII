<?php

namespace backend\services\Sanitizer;

use yii\helpers\HtmlPurifier;

class HtmlSanitizer
{
    public function clean(string $html): string
    {
        $html = preg_replace('#<a[^>]*>.*?</a>#is', '', $html);
        $html = preg_replace('#<img[^>]*>#is', '', $html);

        return HtmlPurifier::process($html, [
            'HTML.Allowed' => 'p,b,strong,i,em,ul,ol,li,br'
        ]);
    }
}
