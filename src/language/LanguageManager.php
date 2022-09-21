<?php

declare(strict_types=1);

namespace outiserver\worldbackup\language;

use pocketmine\lang\Language;
use pocketmine\utils\SingletonTrait;

class LanguageManager
{
    use SingletonTrait;

    private array $languages;

    public function __construct(string $path)
    {
        self::setInstance($this);

        $langList = Language::getLanguageList($path);
        foreach ($langList as $lang) {
            $this->languages[$lang] = new Language($lang, $path, $lang);
        }
    }

    public function getLanguage(string $lang): Language
    {
        return $this->languages[$lang] ?? $this->languages["ja_JP"];
    }
}