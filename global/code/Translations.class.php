<?php

namespace FormTools;


/**
 * This will replace the old languages.php file. It contains any methods relating to the translations.
 */
class Translations
{
    // returns the list of available translations
    public static function getList() {
        $json = file_get_contents(__DIR__ . "/../lang/manifest.json");
        $translations = json_decode($json);
        return $translations->languages;
    }

}
