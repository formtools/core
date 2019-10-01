<?php

namespace FormTools;

use ReflectionClass;


/**
 * Our base class for all Modules. All Form Tools modules need to extend this abstract class to be recognized by the
 * script.
 * @author Ben Keen <ben.keen@gmail.com>
 * @package Core
 * @abstract
 */
abstract class Module {

    // REQUIRED
    protected $moduleName;
    protected $author;
    protected $authorEmail;
    protected $authorLink;
    protected $version;
    protected $date;

    // OPTIONAL
    protected $originLanguage = "en_us";
    protected $nav = array();

    /**
     * An array of JS files included for this module. Files defined here will automatically be included on all module pages.
     * @var array
     */
    protected $jsFiles = array();

    /**
     * An array of JS files included for this module. Files defined here will automatically be included on all module pages.
     * @var array
     */
    protected $cssFiles = array();

    /**
     * Contains all strings for the current language. This is populated automatically on instantiation and
     * contains the strings for the currently selected language.
     * @var array
     */
    protected $L = array();

    // internal
    private $moduleFolder;
    private $currentLang;
    private $currentLangFound;


    /**
     * The default constructor. Automatically populates the $L member var with whatever language is currently being
     * used. If a Module defines its own constructor, it should always call the parent constructor as well to ensure
     * $L is populated. ( parent::__construct(); )
     */
    public function __construct($lang) {
        $this->currentLang = $lang;

        // a little magic to find the current instantiated class's folder
        $currClass = new ReflectionClass(get_class($this));
        $currClassFolder = dirname($currClass->getFileName());

        $currentLangFile = realpath($currClassFolder . "/../lang/{$lang}.php");
        $defaultLangFile = realpath($currClassFolder . "/../lang/{$this->originLanguage}.php");

        $this->moduleFolder = basename(realpath($currClassFolder . "/../"));

        // TODO this can't be a require_once for the install script instantiates the module multiple times
        if (file_exists($currentLangFile)) {
            require($currentLangFile);
            $this->currentLangFound = true;
        } else if (file_exists($defaultLangFile)) {
            require($defaultLangFile);
            $this->currentLangFound = true;
        }

        if (isset($L)) {
            $this->L = $L;
        }
    }

    /**
     * This is called once during the initial installation of the script, or when the installation is reset (which is
     * effectively a fresh install). It is called AFTER the Core tables are installed, and you can rely
     * on Core::$db having been initialized and the database connection having been set up.
     *
     * @return array [0] success / error
     * 				 [1] the error message, if there was a problem
     */
    public function install($module_id) {
        return array(true, "");
    }

    public function uninstall($module_id) {
        return array(true, "");
    }

    public function upgrade($module_id, $old_module_version) {
        return array(true, "");
    }


    // non-overridable getters

    public final function displayPage($template, $page_vars = array(), $theme = "", $swatch = "") {

        // add in the JS and CSS files
        if (!isset($page_vars["js_files"])) {
            $page_vars["js_files"] = array();
        } else {
            if (!is_array($page_vars["js_files"])) {
                echo "Developer error: if defining a js_files page_vars property, it should be set to an array.";
                $page_vars["js_files"] = array();
            }
        }

        // add in the JS and CSS files
        if (!isset($page_vars["css_files"])) {
            $page_vars["css_files"] = array();
        } else {
            if (!is_array($page_vars["css_files"])) {
                echo "Developer error: if defining a css_files page_vars property, it should be set to an array.";
                $page_vars["css_files"] = array();
            }
        }

        $page_vars["js_files"] = array_merge($page_vars["js_files"], self::getJSFiles());
        $page_vars["css_files"] = array_merge($page_vars["css_files"], self::getCSSFiles());

        Themes::displayModulePage($this->getModuleFolder(), $template, $page_vars, $theme, $swatch);
    }

    // note that this method and the following actually returns the values from the CURRENT LANG FILE, not the private
    // var above. See thoughts: https://github.com/formtools/core/issues/82
    public final function getModuleName() {
        return $this->L["module_name"];
    }

    public final function getModuleDesc() {
        return $this->L["module_description"];
    }

    public final function getAuthor() {
        return $this->author;
    }

    public final function getAuthorEmail() {
        return $this->authorEmail;
    }

    public final function getAuthorLink() {
        return $this->authorLink;
    }

    public final function getDate() {
        return $this->date;
    }

    public final function getVersion() {
        return $this->version;
    }

    public final function getOriginLang() {
        return $this->originLanguage;
    }

    public final function getModuleNav() {
        $module_folder = Core::getRootUrl() . "/modules/" . $this->getModuleFolder();

        $nav = array();
        foreach ($this->nav as $lang_key => $nav_item) {
            $nav[$lang_key] = array("$module_folder/{$nav_item[0]}", $nav_item[1]);
        }

        return $nav;
    }

    public final function getModuleFolder() {
        return $this->moduleFolder;
    }

    /**
     * Returns a list of all javascript files for this module. The file paths may contain the following placeholders
     * which will be replaced:
     *    {FTROOT} - the Form Tools root URL
     *    {FTVERSION} - the current Form Tools version
     *    {MODULEVERSION} - the module version
     *    {MODULEROOT} - the module root URL
     * @return array
     */
    public final function getJSFiles()
    {
        $files = array();
        $root_url = Core::getRootUrl();
        $ft_version = Core::getVersionString();
        $module_root = "$root_url/modules/" . $this->getModuleFolder();

        foreach ($this->jsFiles as $file) {
            $files[] = str_replace(
                array("{FTROOT}", "{FTVERSION}", "{MODULEVERSION}", "{MODULEROOT}"),
                array($root_url, $ft_version, $this->getVersion(), $module_root),
                $file
            );
        }
        return $files;
    }

    /**
     * Returns a list of all javascript files for this module.
     * @return array
     */
    public final function getCSSFiles()
    {
        $files = array();
        $root_url = Core::getRootUrl();
        $ft_version = Core::getVersionString();
        $module_root = "$root_url/modules/" . $this->getModuleFolder();

        foreach ($this->cssFiles as $file) {
            $files[] = str_replace(
                array("{FTROOT}", "{FTVERSION}", "{MODULEVERSION}", "{MODULEROOT}"),
                array($root_url, $ft_version, $this->getVersion(), $module_root),
                $file
            );
        }
        return $files;
    }

    /**
     * Returns all or specific settings for the module.
     * @param mixed $settings array of settings or single setting string, or nothing (returns all)
     * @return array
     */
    public final function getSettings ($settings = "")
    {
        return Settings::get($settings, $this->moduleFolder);
    }

    /**
     * Returns all or specific settings for the module.
     * @param mixed $settings array of settings or single setting string, or nothing (returns all)
     * @return array
     */
    public final function setSettings ($settings = "")
    {
        return Settings::set($settings, $this->moduleFolder);
    }

    /**
     * Returns the language strings
     * @return array
     */
    public final function getLangStrings() {
        return $this->L;
    }

    /**
     * Returns the language strings
     * @return bool
     */
    public final function isCurrentLangFound() {
        return $this->currentLangFound;
    }

    /**
     * Helper method to clear the current module's hooks.
     */
    public final function clearHooks() {
        Hooks::unregisterModuleHooks($this->moduleFolder);
    }
}
