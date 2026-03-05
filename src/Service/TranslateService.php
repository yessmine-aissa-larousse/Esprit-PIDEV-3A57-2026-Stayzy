<?php

namespace App\Service;

use Stichoza\GoogleTranslate\GoogleTranslate;

class TranslateService
{
    public function translate(string $text, string $targetLang): string
    {
        $tr = new GoogleTranslate($targetLang);
        return $tr->translate($text);
    }
}