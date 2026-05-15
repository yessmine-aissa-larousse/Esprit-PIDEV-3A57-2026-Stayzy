<?php

namespace App\Service;

use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslateService
{
    public function translate(?string $text, string $targetLang): string
    {
        if (!$text) return '';
        $tr = new GoogleTranslate($targetLang);
        return $tr->translate($text) ?? $text;
    }
}