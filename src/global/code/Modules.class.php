<?php

/**
 * Modules.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, Exception;


class Modules
{
    // rather than instantiate each module again and again as needed, this tracks the instances created. Use
    // Modules::getModuleInstance()
    private static $moduleInstances = array();

    /**
     * Retrieves the list of all modules currently in the database.
     *
     * @return array $module_info an ordered array of hashes, each hash being the module info
     */
    public static function getList()
    {
        $db = Core::$db;
        $db->query("SELECT * FROM {PREFIX}modules ORDER BY module_name");
        $db->execute();

        $modules_info = array();
        foreach ($db->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $modules_info[] = $row;
        }

        extract(Hooks::processHookCalls("start", compact("modules_info"), array("modules_info")), EXTR_OVERWRITE);

        return $modules_info;
    }


    /**
     * Updates the list of modules in the database by examining the contents of the /modules folder.
     */
    public static function updateModuleList()
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();

        $modules = self::getUninstalledModules();

        foreach ($modules as $module_folder => $module) {

            // Abstract classes in PHP don't have the option to force properties being defined, so check for them here
            if ($module->getModuleName() == "" ||
                $module->getAuthor() == "" ||
                $module->getAuthorEmail() == "" ||
                $module->getAuthorLink() == "" ||
                $module->getVersion() == "" ||
                $module->getDate() == "") {
                continue;
            }

            list($year, $month, $day) = explode("-", $module->getDate());
            $timestamp = mktime(null, null, null, $month, $day, $year);
            $module_date = General::getCurrentDatetime($timestamp);

            $db->query("
                INSERT INTO {PREFIX}modules (is_installed, is_enabled, origin_language, module_name,
                  module_folder, version, author, author_email, author_link, module_date)
                VALUES (:is_installed, :is_enabled, :origin_language, :module_name, :folder, :module_version,
                  :author, :author_email, :author_link, :module_date)
            ");
            $db->bindAll(array(
                "is_installed" => "no",
                "is_enabled" => "no",
                "origin_language"    => $module->getOriginLang(),
                "module_name"        => $module->getModuleName(),
                "folder"             => $module_folder,
                "module_version"     => $module->getVersion(),
                "author"             => $module->getAuthor(),
                "author_email"       => $module->getAuthorEmail(),
                "author_link"        => $module->getAuthorLink(),
                "module_date"        => $module_date
            ));
            $db->execute();
        }

        // also, parse the existing modules and see if any folders have been removed altogether
        $modules = self::getList();
        foreach ($modules as $module_info) {
            if ($module_info["is_installed"] === "yes") {
                continue;
            }
            $module_folder = $module_info["module_folder"];

            // here the module is NOT installed and the folder doesn't even exist. So remove it from the DB.
            if (!file_exists("$root_dir/modules/$module_folder")) {
                $db->query("
                    DELETE FROM {PREFIX}modules
                    WHERE module_id = :module_id
                ");
                $db->bind("module_id", $module_info["module_id"]);
                $db->execute();
            }
        }

        return array(true, $LANG["notify_module_list_updated"]);
    }


    /**
     * Examines the content of the installation's modules folder and extracts information about all
     * valid uploaded modules.
     * @return array
     */
    private static function getUninstalledModules()
    {
        $root_dir = Core::getRootDir();

        $modules_folder = "$root_dir/modules";
        $dh = opendir($modules_folder);

        // if we couldn't open the modules folder, it doesn't exist or something went wrong
        if (!$dh) {
            return array(false, "");
        }

        // get the list of currently installed modules
        $current_modules = self::getList();
        $current_module_folders = array();
        foreach ($current_modules as $module_info) {
            $current_module_folders[] = $module_info["module_folder"];
        }

        $modules = array();
        while (($folder = readdir($dh)) !== false) {

            // if this module is already in the database, ignore it
            if (in_array($folder, $current_module_folders)) {
                continue;
            }

            if (!is_dir("$modules_folder/$folder") || $folder == "." || $folder == "..") {
                continue;
            }

            if (self::isValidModule($folder)) {
                $modules[$folder] = self::getModuleInstance($folder);
            }

        }
        closedir($dh);

        return $modules;
    }


    public static function installModules()
    {
        // this will run the installation scripts for any module in the /modules folder. Note: the special "Core Field Types"
        // module has a dummy installation function that gets called here. That ensures the module is marked as "enabled", etc.
        // even though we actually installed it elsewhere.

        $modules = self::getList();

        foreach ($modules as $module_info) {
            if ($module_info["is_installed"] == "yes") {
                continue;
            }
            self::installModule($module_info["module_id"]);
        }
    }


    /**
     * This is called on the main Modules listing page for each module. It checks to
     * see if the module has a new upgrade available. If it has, it displays an "Upgrade
     * Module" link to allow the administrator to call the upgrade script (self::upgradeModule)
     *
     * @param integer $module_id
     */
    public static function moduleNeedsUpgrading($module_id)
    {
        $module_info = self::getModule($module_id);
        $module_folder = $module_info["module_folder"];
        $current_db_version = $module_info["version"];

        $module = self::getModuleInstance($module_folder);

        return $current_db_version != $module->getVersion();
    }


    /**
     * Upgrades an individual module.
     */
    public static function upgradeModule($module_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $root_url = Core::getRootUrl();

        $module_info = self::getModule($module_id);
        $module_folder = $module_info["module_folder"];
        $old_module_version = $module_info["version"];

        $module = self::getModuleInstance($module_folder);

        if ($old_module_version == $module->getVersion()) {
            return array(false, "");
        }

        // run whatever upgrade method has been defined
        $module->upgrade($module_id, $old_module_version);

        // convert the date into a MySQL datetime
        list($year, $month, $day) = explode("-", $module->getDate());
        $timestamp = mktime(null, null, null, $month, $day, $year);
        $module_datetime = General::getCurrentDatetime($timestamp);

        $db->query("
            UPDATE {PREFIX}modules
            SET    origin_language = :origin_language,
                   module_name = :module_name,
                   version = :module_version,
                   author = :author,
                   author_email = :author_email,
                   author_link = :author_link,
                   module_date = :module_datetime
            WHERE  module_id = :module_id
        ");
        $db->bindAll(array(
            "origin_language" => $module->getOriginLang(),
            "module_name" => $module->getModuleName(),
            "module_version" => $module->getVersion(),
            "author" => $module->getAuthor(),
            "author_email" => $module->getAuthorEmail(),
            "author_link" => $module->getAuthorLink(),
            "module_datetime" => $module_datetime,
            "module_id" => $module_id
        ));
        $db->execute();

        // remove and update the navigation links for this module
        ModuleMenu::resetModuleNav($module_id, $module->getModuleNav(), $module->getLangStrings());

        // And we're done! inform the user that it's been upgraded
        $placeholders = array(
            "module"  => $module->getModuleName(),
            "version" => $module->getVersion(),
            "link"    => "$root_url/modules/$module_folder"
        );

        $message = General::evalSmartyString($LANG["notify_module_updated"], $placeholders);

        return array(true, $message);
    }


    /**
     * Returns the contents of a module's language file for a particular language.
     *
     * TODO deprecated. This should be available via the module class itself.
     *
     * @param string $module_folder
     * @param string $language "en_us", "fr" etc.
     * @return array
     */
    public static function getModuleLangFile($module_folder, $language)
    {
        $root_dir = Core::getRootDir();
        $lang_file_path = "$root_dir/modules/$module_folder/lang";
        $desired_lang_file = "$lang_file_path/{$language}.php";

        // if the desired language file exists, use it. Otherwise use the default language file
        $content = array();
        if (!empty($desired_lang) && is_file($desired_lang_file)) {
            $content = self::getModuleLangFileContents($desired_lang_file);
        } else {
            $module_id = self::getModuleIdFromModuleFolder($module_folder);
            $module_info = self::getModule($module_id);
            $origin_lang = $module_info["origin_language"];

            $origin_lang_file = "$lang_file_path/{$origin_lang}.php";
            if (!empty($origin_lang) && is_file($origin_lang_file)) {
                $content = self::getModuleLangFileContents($origin_lang_file);
            }
        }

        return $content;
    }


    /**
     * Added in 2.1.6, to allow for simple "inline" hook overriding from within the PHP pages.
     *
     * @param string $location
     * @param mixed $data
     */
    public static function moduleOverrideData($location, $data)
    {
        extract(Hooks::processHookCalls("start", compact("location", "data"), array("data")), EXTR_OVERWRITE);
        return $data;
    }


    /**
     * Called automatically on installation, or when the administrator clicks on the "Install" link for a module
     * This function runs the module's installation script (if it exists) and returns the appropriate success
     * or error message.
     *
     * @param integer $module_id
     * @return array [0] T/F, [1] error / success message.
     */
    public static function installModule($module_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $root_url = Core::getRootUrl();

        $module_info = self::getModule($module_id);
        $module_folder = $module_info["module_folder"];

        $module = self::getModuleInstance($module_folder);
        list ($success, $message) = $module->install($module_id);

        if ($success) {

            // now add any navigation links for this module
            ModuleMenu::addMenuItems($module_id, $module);

            // get the module language file contents and store the info in the $LANG global for
            // so it can be accessed by the installation script (TODO needed?)
            $LANG[$module_folder] = $module->getLangStrings();

            // if there is no custom installation message, use the default
            if (empty($message)) {
                $message = General::evalSmartyString($LANG["notify_module_installed"], array(
                    "link" => "$root_url/modules/$module_folder"
                ));
            }

            $db->query("
                UPDATE {PREFIX}modules
                SET    is_installed = :is_installed,
                       is_enabled = :is_enabled
                WHERE  module_id = :module_id
            ");
            $db->bindAll(array(
                "is_installed" => "yes",
                "is_enabled" => "yes",
                "module_id" => $module_id
            ));

            try {
                $db->execute();
            } catch (Exception $e) {
                return array(false, $e->getMessage());
            }
        } else {
            if (!empty($message)) {
                $message = $LANG["text_error_installing"] . " <b>$message</b>";
            } else {
                $message = $LANG["text_error_installing"];
            }
        }

        return array($success, $message);
    }


    /**
     * Retrieves all information about a particular module.
     *
     * @return array
     */
    public static function getModule($module_id)
    {
        $db = Core::$db;
        $db->query("SELECT * FROM {PREFIX}modules WHERE module_id = :module_id");
        $db->bind("module_id", $module_id);
        $db->execute();
        $result = $db->fetch();

        extract(Hooks::processHookCalls("end", compact("module_id", "result"), array("result")), EXTR_OVERWRITE);

        return $result;
    }


    /**
     * Since it's often more convenient to identify modules by its unique folder name, this function is
     * provided to find the module ID. If not found, returns the empty string.
     *
     * @param string $module_folder
     */
    public static function getModuleIdFromModuleFolder($module_folder)
    {
        $db = Core::$db;
        $db->query("
            SELECT module_id
            FROM   {PREFIX}modules
            WHERE  module_folder = :module_folder
        ");
        $db->bind("module_folder", $module_folder);
        $db->execute();
        $info = $db->fetch();

        return (isset($info["module_id"])) ? $info["module_id"] : "";
    }


    /**
     * Returns the total number of modules in the database (regardless of whether they're enabled or not).
     * @return integer the number of modules
     */
    public static function getModuleCount()
    {
        $db = Core::$db;
        $db->query("SELECT count(*) as c FROM {PREFIX}modules");
        $db->execute();
        $info = $db->fetch();
        return $info["c"];
    }


    /**
     * This is used on the administrator Modules page. It allows for a simple search/sort mechanism.
     * @param array $search_criteria
     */
    public static function searchModules($search_criteria)
    {
        $db = Core::$db;

        if (!isset($search_criteria["order"])) {
            $search_criteria["order"] = "module_name-DESC";
        }

        extract(Hooks::processHookCalls("start", compact("search_criteria"), array("search_criteria")), EXTR_OVERWRITE);

        // verbose, but at least it prevents any invalid sorting. We always return modules that aren't installed first
        // so they show up on the first page of results. The calling page then sorts the ones that require upgrading next
        $order_clause = "is_installed DESC";
        switch ($search_criteria["order"]) {
            case "module_name-DESC":
                $order_clause .= ", module_name DESC";
                break;
            case "module_name-ASC":
                $order_clause .= ", module_name ASC";
                break;
            case "is_enabled-DESC":
                $order_clause .= ", is_enabled DESC";
                break;
            case "is_enabled-ASC":
                $order_clause .= ", is_enabled ASC";
                break;
            default:
                $order_clause .= ", module_name";
                break;
        }
        $order_clause = "ORDER BY $order_clause";

        $keyword_clause = "";
        if (!empty($search_criteria["keyword"])) {
            $string = $search_criteria["keyword"];
            $fields = array("module_name", "module_folder");

            $clauses = array();
            foreach ($fields as $field) {
                $clauses[] = "$field LIKE '%$string%'";
            }
            $keyword_clause = join(" OR ", $clauses);
        }

        // status ("enabled"/"disabled") clause
        $status_clause = "";
        if (count($search_criteria["status"]) < 2) {
            if (in_array("enabled", $search_criteria["status"])) {
                $status_clause = "is_enabled = 'yes'";
            } else {
                $status_clause = "is_enabled = 'no'";
            }
        }

        $where_clauses = array();
        if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";
        if (!empty($status_clause))  $where_clauses[] = "($status_clause)";
        if (!empty($where_clauses)) {
            $where_clause = "WHERE " . join(" AND ", $where_clauses);
        } else {
            $where_clause = "";
        }

        $db->query("
            SELECT *
            FROM   {PREFIX}modules
            $where_clause
            $order_clause
       ");
        $db->execute();

        return $db->fetchAll();
    }


    /**
     * Finds out if a module is enabled or not. If it's not even installed, just returns false.
     *
     * @param string $module_folder
     * @return boolean
     */
    public static function checkModuleEnabled($module_folder)
    {
        $db = Core::$db;
        $db->query("
            SELECT is_enabled
            FROM   {PREFIX}modules
            WHERE  module_folder = :module_folder
        ");
        $db->bind("module_folder", $module_folder);
        $db->execute();
        $result = $db->fetch();

        return (!empty($result) && $result["is_enabled"] == "yes");
    }

    /**
     * Finds out if a module is available. By "available", we mean: has the files uploaded to the modules
     * folder and has a corresponding record in the modules table. It may not be installed/enabled.
     *
     * @param string $module_folder
     * @return boolean
     */
    public static function checkModuleAvailable($module_folder)
    {
        $db = Core::$db;

        $db->query("
            SELECT count(*) as c
            FROM   {PREFIX}modules
            WHERE  module_folder = :module_folder
        ");
        $db->bind("module_folder", $module_folder);
        $db->execute();
        $result = $db->fetch();

        return $result["c"] == 1;
    }

    /**
     * Convenience method.
     * @param $module_folder
     * @return bool
     */
    public static function checkModuleUsable($module_folder)
    {
        return self::checkModuleAvailable($module_folder) && self::checkModuleEnabled($module_folder);
    }


    /**
     * Uninstalls a module from the database.
     *
     * @param integer $module_id
     */
    public static function uninstallModule($module_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $module_info = self::getModule($module_id);
        $module_folder = $module_info["module_folder"];

        if (empty($module_info)) {
            return array(false, "");
        }

        $success = true;

        // attempt to uninstall it
        if (self::isValidModule($module_folder)) {
            $module = self::getModuleInstance($module_folder);
            list ($success, $message) = $module->uninstall($module_id);

            if (!$success) {
                return array(false, $message);
            }
        }

        $db->query("
            UPDATE {PREFIX}modules
            SET    is_installed = 'no',
                   is_enabled = 'no' 
            WHERE  module_id = :module_id
        ");
        $db->bind("module_id", $module_id);
        $db->execute();

        ModuleMenu::clearModuleNav($module_id);

        // if this module was used in any menus, update them
        Menus::removeModuleFromMenus($module_id);

        // delete any hooks registered by this module
        Hooks::unregisterModuleHooks($module_folder);

        // delete any module settings
        Modules::deleteModuleSettings($module_info["module_folder"]);

        // now delete the entire module folder
        $message = $LANG["notify_module_uninstalled"];

        extract(Hooks::processHookCalls("end", compact("module_id", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Since it's often more convenient to identify modules by its unique folder name, this function is
     * provided to find the module ID. If not found, returns the empty string.
     *
     * @param string $module_folder
     */
    public static function getModuleFolderFromModuleId($module_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT module_folder
            FROM   {PREFIX}modules
            WHERE  module_id = :module_id
        ");
        $db->bind("module_id", $module_id);
        $db->execute();
        $info = $db->fetch();

        return (isset($info["module_folder"])) ? $info["module_folder"] : "";
    }


    /**
     * Retrieves one or more settings for a module. This can be used in two ways:
     * 1. It can be called within any module page WITHOUT the second parameter; it then figures out
     *    what module you're currently on, and returns those setting(s) for that module.
     * 2. It's called from outside a module folder (or within a different module, trying to access the
     *    settings of another module). In that case, it needs to specify the second $module_folder param.
     *
     * To return all settings for the current module, just call the function with no parameters.
     *
     * @param mixed $settings a single setting name or an array of setting names
     * @param string $module_folder
     * @return mixed a single setting string value or an array of setting values
     */
    public static function getModuleSettings($settings = "", $module_folder = "")
    {
        if (empty($module_folder)) {
            $module_folder = self::getCurrentModuleFolder();
        }
        return Settings::get($settings, $module_folder);
    }


    /**
     * Expects to be called from within the modules folder; namely, within a particular module. This
     * function returns the name of the current module. Assumption: no module contains a /modules folder.
     *
     * @return string
     */
    public static function getCurrentModuleFolder()
    {
        $script_name = $_SERVER["SCRIPT_NAME"];

        $module_folder = "";
        if (preg_match("/\/modules\/([^\/]*)/", $script_name, $matches)) {
            $module_folder = $matches[1];
        }

        return $module_folder;
    }


    /**
     * Handles initialization of a module page.
     *  - Core::init()'s
     *  - sets auth (param required for auth)
     *  - instantiates the module's Module class and returns it
     * return Module
     */
    public static function initModulePage($auth = "")
    {
        Core::init();
        $root_dir = Core::getRootDir();
        $module_folder = self::getCurrentModuleFolder();

        if (!is_file("$root_dir/modules/$module_folder/library.php")) {
            Errors::handleError("Error with $module_folder module. Missing library.php file.");
            exit;
        }

        require_once("$root_dir/modules/$module_folder/library.php");

        if (!empty($auth)) {
            Core::$user->checkAuth($auth);
        }
        return self::getModuleInstance($module_folder);
    }

    // should only be called after isValidModule() has been called first
    public static function getModuleInstance($module_folder) {
        if (array_key_exists($module_folder, self::$moduleInstances)) {
            return self::$moduleInstances[$module_folder];
        } else {
            self::$moduleInstances[$module_folder] = self::instantiateModule($module_folder);
            return self::$moduleInstances[$module_folder];
        }
    }

    // should only be called after isValidModule() has been called first
    public static function instantiateModule($module_folder)
    {
        $root_dir = Core::getRootDir();

        require_once("$root_dir/modules/$module_folder/library.php");

        $namespace = self::getModuleNamespace($module_folder);
        $module_class = "FormTools\\Modules\\$namespace\\Module";

        $module = false;

        // return a newly minted instance of the module
        try {
            if (class_exists($module_class)) {
                $module = new $module_class(Core::$user->getLang());
            }
        } catch (Exception $e) {

        }

        return $module;
    }

    public static function isValidModule($module_folder)
    {
        $root_dir = Core::getRootDir();

        // verify the library file exists
        if (!is_file("$root_dir/modules/$module_folder/library.php")) {
            return false;
        }

        require_once("$root_dir/modules/$module_folder/library.php");

        // verify the class exists
        $namespace = self::getModuleNamespace($module_folder);
        $module_class = "FormTools\\Modules\\$namespace\\Module";

        if (!class_exists($module_class)) {
            return false;
        }

        return true;
    }


    public static function getModuleNamespace($module_folder)
    {
        $no_underscores = str_replace("_", " ", $module_folder);
        $upper_case = ucwords($no_underscores);
        return str_replace(" ", "", $upper_case);
    }

    /**
     * Sets one or more module settings. This basically acts as a wrapper function for Settings::set(),
     * which ensures that the module field is set appropriately.
     *
     * @param array hash of "setting_name" => "setting_value"
     */
    public static function setModuleSettings($settings)
    {
        Settings::set($settings, self::getCurrentModuleFolder());
    }


    /**
     * Updates the list of enabled & disabled modules.
     *
     * There seems to be a bug with the way this function is called or something. Occasionally all modules
     * are no longer enabled...
     *
     * @param array $request
     */
    public static function updateEnabledModules($request)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $module_ids_in_page = $request["module_ids_in_page"]; // a comma-delimited string
        $enabled_module_ids = isset($request["is_enabled"]) ? $request["is_enabled"] : array();

        if (!empty($module_ids_in_page)) {
            $db->query("
                UPDATE {PREFIX}modules
                SET    is_enabled = 'no'
                WHERE  module_id IN ($module_ids_in_page)
            ");
            $db->execute();
        }

        foreach ($enabled_module_ids as $module_id) {
            $db->query("
                UPDATE {PREFIX}modules
                SET    is_enabled = 'yes'
                WHERE  module_id = :module_id
            ");
            $db->bind("module_id", $module_id);
            $db->execute();
        }

        return array(true, $LANG["notify_enabled_module_list_updated"]);
    }


    /**
     * This function is used by the Form Tools Core to include all server-side resources from a module.
     * All content in the global namespace is made available to the included files. It relies on the
     * modules having a certain file-folder structure, and imports the following information:
     *
     *   - module PHP code, found in /module_root/library.php
     *   - Smarty plugins, modifiers etc., found in /module_root/smarty/
     *   - the appropriate language file, found in /module_root/lang/
     *
     * Custom module language files are added to the $LANG global for use in any of the templates, using the the
     * module folder as the "namespace". For example, to access the phrase "Hello World!" in a page, you'd use:
     *
     *   $LANG.image_manager.phrase_hello_world
     *
     * Or, just FYI, if you're within the module itself, you can use the shortcut:
     *
     *   $L.phrase_hello_world
     *
     * @param mixed $modules either a string
     */
    public static function includeModule($module_folder)
    {
        $smarty = Core::$smarty;
        $root_dir = Core::getRootDir();

        $module = self::getModuleInstance($module_folder);

        // include the smarty resources
        if (is_dir("$root_dir/modules/$module_folder/smarty_plugins")) {
            $smarty->addPluginsDir("$root_dir/modules/$module_folder/smarty_plugins");
        }

        extract(Hooks::processHookCalls("end", compact("module_folder"), array()), EXTR_OVERWRITE);

        return $module;
    }


    /**
     * This if the module counterpart function of the General::loadField function. It works exactly the same
     * way, except that it namespaces the variables in a $_SESSION["ft"][$module_folder] key.
     *
     * It assumes that a variable name can be found in GET, POST or SESSIONS (or all three). What this
     * function does is return the value stored in the most important variable (GET first, POST second,
     * SESSIONS third), and update sessions at the same time. This is extremely helpful in situations
     * where you don't want to keep having to submit the same information from page to page.
     * The fourth parameter is included as a way to set a default value.
     *
     * @param string $module_folder
     * @param string $field_name the field name
     * @param string $session_name the session key for this field name
     * @param string $default_value the default value for the field
     * @return string the field value
     */
    public static function loadModuleField($module_folder, $field_name, $session_name, $default_value = "")
    {
        $field = $default_value;

        if (!isset($_SESSION["ft"][$module_folder]) || !is_array($_SESSION["ft"][$module_folder])) {
            $_SESSION["ft"][$module_folder] = array();
        }
        if (isset($_GET[$field_name])) {
            $field = $_GET[$field_name];
            $_SESSION["ft"][$module_folder][$session_name] = $field;
        } else if (isset($_POST[$field_name])) {
            $field = $_POST[$field_name];
            $_SESSION["ft"][$module_folder][$session_name] = $field;
        } else if (isset($_SESSION["ft"][$module_folder][$session_name])) {
            $field = $_SESSION["ft"][$module_folder][$session_name];
        }

        return $field;
    }


    public static function deleteModuleSettings($module_folder) {
        $db = Core::$db;

        $db->query("DELETE FROM {PREFIX}settings WHERE module = :module_folder");
        $db->bind("module_folder", $module_folder);
        $db->execute();
    }


    /**
     * Loads the contents of a module language file.
     *
     * @param string $summary_file the full file path and filename
     */
    private static function getModuleLangFileContents($lang_file)
    {
        @include($lang_file);
        $vars = get_defined_vars();
        $lang_array = isset($vars["L"]) ? $vars["L"] : array();
        return $lang_array;
    }

}
