<?php

namespace FormTools;


/**
 * This will replace the old languages.php file. It contains any methods relating to the translations.
 */
class Translations
{
    private $list;
    private $L;

    function __construct($lang) {
        $json = file_get_contents(__DIR__ . "/../lang/manifest.json");
        $translations = json_decode($json);

        // store the full list of translations
        $this->list = $translations->languages;

        // now load the appropriate one. This may be better with an autoloader & converting the lang files to classes.
        $lang_file = $lang . ".php";
        include_once(realpath(__DIR__ . "/../lang/{$lang_file}"));

        if (isset($LANG)) {
            $this->L = $LANG;
        }
    }

    // returns the list of available translations
    public function getList() {
        return $this->list;
    }

    public function getStrings() {
        return $this->L;
    }

}
