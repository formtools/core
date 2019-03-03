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
        include(realpath(__DIR__ . "/../lang/{$lang_file}"));

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

    /**
     * Refreshes the list of available language files found in the /global/lang folder. This
     * function parses the folder and stores the language info in the "available_languages"
     * settings in the settings table.
     *
     * @return array [0]: true/false (success / failure)
     *               [1]: message string
     */
    public function refreshLanguageList()
    {
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();

        $language_folder_dir = "$root_dir/global/lang";

        $available_language_info = array();
        if ($handle = opendir($language_folder_dir)) {
            while (false !== ($filename = readdir($handle))) {
                if ($filename != '.' && $filename != '..' && $filename != "index.php" &&
                    Files::getFilenameExtension($filename, true) == "php") {
                    list($lang_file, $lang_display) = $this->getLanguageFileInfo("$language_folder_dir/$filename");
                    $available_language_info[$lang_file] = $lang_display;
                }
            }
            closedir($handle);
        }

        $available_lang_str = Translations::getLangListAsString($available_language_info);

        Settings::set(array("available_languages" => $available_lang_str), "core");
        Sessions::set("settings.available_languages", $available_lang_str);

        return array(true, $LANG["notify_lang_list_updated"]);
    }


    // expects an hash of the form [[ar] => "Arabic", ...]
    public static function getLangListAsString ($list) {

        // sort the languages alphabetically
        ksort($list);

        // now piece everything together in a single delimited string
        $available_languages = array();
        foreach ($list as $key => $val) {
            $available_languages[] = "$key,$val";
        }

        return join("|", $available_languages);
    }


    /**
     * Helper function which examines a particular language file and returns the language
     * filename (en_us, fr_ca, etc) and the display name ("English (US), French (CA), etc).
     *
     * @param string $file the full path of the language file
     * @return array [0] the language file name<br />
     *               [1] the language display name
     */
    private function getLanguageFileInfo($file)
    {
        @include($file);

        $defined_vars = get_defined_vars();
        $language_display = $defined_vars["LANG"]["special_language_locale"];

        // now return the filename component, minus the .php
        $pathinfo = pathinfo($file);
        $lang_file = preg_replace("/\.php$/", "", $pathinfo["basename"]);

        return array($lang_file, $language_display);
    }



}
