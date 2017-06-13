<?php

/**
 * Modules.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO;


class Modules
{
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

        $modules = self::getUninstalledModules();

        foreach ($modules as $module_folder => $row) {
            $module_info = $row["module_info"];
            $lang_info   = $row["lang_info"];

            // convert the date into a MySQL datetime
            list($year, $month, $day) = explode("-", $row["module_info"]["date"]);
            $timestamp = mktime(null, null, null, $month, $day, $year);
            $module_date = General::getCurrentDatetime($timestamp);

            $db->query("
                INSERT INTO {PREFIX}modules (is_installed, is_enabled, origin_language, module_name,
                  module_folder, version, author, author_email, author_link, description, module_date)
                VALUES (:is_installed, :is_enabled, :origin_language, :module_name, :folder, :module_version,
                  :author, :author_email, :author_link, :module_description, :module_date)
            ");
            $db->bindAll(array(
                "is_installed" => "no",
                "is_enabled" => "no",
                "origin_language"    => $module_info["origin_language"],
                "module_name"        => $lang_info["module_name"],
                "folder"             => $module_folder,
                "module_version"     => $module_info["version"],
                "author"             => $module_info["author"],
                "author_email"       => $module_info["author_email"],
                "author_link"        => $module_info["author_link"],
                "module_description" => $lang_info["module_description"],
                "module_date"        => $module_date
            ));
            $db->execute();
            $module_id = $db->getInsertId();

            // now add any navigation links for this module
            $order = 1;
            while (list($lang_file_key, $info) = each($row["module_info"]["nav"])) {
                $url        = $info[0];
                $is_submenu = ($info[1]) ? "yes" : "no";
                if (empty($lang_file_key) || empty($url)) {
                    continue;
                }

                // odd this. Why not just store the lang string in the DB? That way it'll be translated for each user...
                $display_text = isset($lang_info[$lang_file_key]) ? $lang_info[$lang_file_key] : $LANG[$lang_file_key];

                ModuleMenu::addMenuItem($module_id, $display_text, $url, $is_submenu, $order);
                $order++;
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

            if (is_dir("$modules_folder/$folder") && $folder != "." && $folder != "..") {
                $info = self::getModuleInfoFileContents($folder);

                if (empty($info)) {
                    continue;
                }

                // check the required info file fields
                $required_fields = array("author", "version", "date", "origin_language");
                $all_found = true;
                foreach ($required_fields as $field) {
                    if (empty($info[$field])) {
                        $all_found = false;
                    }
                }
                if (!$all_found) {
                    continue;
                }

                // now check the language file contains the two required fields: module_name and module_description
                $lang_file = "$modules_folder/$folder/lang/{$info["origin_language"]}.php";
                $lang_info = self::getModuleLangFileContents($lang_file);

                // check the required language file fields
                if ((!isset($lang_info["module_name"]) || empty($lang_info["module_name"])) ||
                    (!isset($lang_info["module_description"]) || empty($lang_info["module_description"]))) {
                    continue;
                }

                $modules[$folder] = array(
                    "module_info" => $info,
                    "lang_info" => $lang_info
                );
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

        $latest_module_info = self::getModuleInfoFileContents($module_folder);

        $actual_version = isset($latest_module_info["version"]) ? $latest_module_info["version"] : "";

        return ($current_db_version != $actual_version);
    }


    /**
     * Upgrades an individual module.
     */
    public static function upgradeModule($module_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $root_url = Core::getRootUrl();
        $root_dir = Core::getRootDir();

        $module_info = self::getModule($module_id);
        $module_folder = $module_info["module_folder"];
        $current_db_version = $module_info["version"];

        $info = self::getModuleInfoFileContents($module_folder);
        $new_version = $info["version"];

        if ($current_db_version == $new_version) {
            return array(false, "");
        }

        // if the module has its own upgrade function, call it. In Oct 2011, a BIG problem was identified
        // in the way modules were being updated. For backward compatibility, the new upgrade function
        // must be named [module folder]__update (not ...__upgrade). if the __update function is defined,
        // it will be called instead of the older __upgrade one.
        @include_once("$root_dir/modules/$module_folder/library.php");

        // NEW "update" function
        $update_function_name = "{$module_folder}__update";
        if (function_exists($update_function_name)) {
            list($success, $message) = $update_function_name($module_info, $info);
            if (!$success) {
                return array($success, $message);
            }
        } else {
            // OLD "upgrade" function
            $upgrade_function_name = "{$module_folder}__upgrade";
            if (function_exists($upgrade_function_name)) {
                $upgrade_function_name($current_db_version, $new_version);
            }
        }

        // now, update the main module record

        // we're assuming the module developer hasn't removed any of the required fields...

        // now check the language file contains the two required fields: module_name and module_description
        $lang_file = "$root_dir/modules/$module_folder/lang/{$info["origin_language"]}.php";
        $lang_info = _ft_get_module_lang_file_contents($lang_file);

        // check the required language file fields
        if ((!isset($lang_info["module_name"]) || empty($lang_info["module_name"])) ||
            (!isset($lang_info["module_description"]) || empty($lang_info["module_description"]))) {
            return;
        }

        $module_date = $info["date"];
        $nav                  = $info["nav"];

        $module_description   = $lang_info["module_description"];

        // convert the date into a MySQL datetime
        list($year, $month, $day) = explode("-", $module_date);
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
                   description = '$module_description',
                   module_date = '$module_datetime'
            WHERE  module_id = $module_id
        ");
        $db->bindAll(array(
            "origin_language" => $info["origin_language"],
            "module_name" => $lang_info["module_name"],
            "module_version" => $info["version"],
            "author" => $info["author"],
            "author_email" => $info["author_email"],
            "author_link" => $info["author_link"],
            "module_date" => $module_date,
        ));

        // remove and update the navigation links for this module
        ModuleMenu::resetModuleNav($module_id, $nav);

        // And we're done! inform the user that it's been upgraded
        $placeholders = array(
            "module"  => $lang_info["module_name"],
            "version" => $new_version,
            "link"    => "$root_url/modules/$module_folder"
        );

        $message = General::evalSmartyString($LANG["notify_module_updated"], $placeholders);

        return array(true, $message);
    }


    /**
     * Returns the contents of a module's language file for a particular language.
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
        $root_dir = Core::getRootDir();
        $root_url = Core::getRootUrl();

        $module_info = self::getModule($module_id);
        $module_folder = $module_info["module_folder"];

        $success = true;
        $message = General::evalSmartyString($LANG["notify_module_installed"], array("link" => "$root_url/modules/$module_folder"));

        $has_custom_install_script = false;

        if (is_file("$root_dir/modules/$module_folder/library.php")) {
		    @include_once("$root_dir/modules/$module_folder/library.php");
		    $install_function_name = "{$module_folder}__install";
            if (function_exists($install_function_name)) {
                $has_custom_install_script = true;

                // get the module language file contents and store the info in the $LANG global for
                // so it can be accessed by the installation script
                $LANG[$module_folder] = self::getModuleLangFile($module_folder, Core::$user->getLang());
                list($success, $custom_message) = $install_function_name($module_id);

                // if there was a custom message returned (error or notification), overwrite the default
                // message
                if (!empty($custom_message)) {
                    $message = $custom_message;
                }
            }
        }

        // if there wasn't a custom installation script, or there was and it was successfully run update the record in the
        // module table to mark it as both is_installed and is_enabled
        if (!$has_custom_install_script || ($has_custom_install_script && $success)) {
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
            } catch (PDOException $e) {
                return array(false, $e->getMessage());
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
            $fields = array("module_name", "module_folder", "description");

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

        // add up the where clauses
        $where_clauses = array();
        if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";
        if (!empty($status_clause))  $where_clauses[] = "($status_clause)";
        if (!empty($where_clauses)) {
            $where_clause = "WHERE " . join(" AND ", $where_clauses);
        } else {
            $where_clause = "";
        }

        // get form info
        $db->query("
            SELECT *
            FROM   {PREFIX}modules
            $where_clause
            $order_clause
       ");
        $db->execute();

        // now retrieve the basic info (id, first and last name) about each client assigned to this form
        $module_info = array();
        foreach ($db->fetchAll() as $row) {
            $module_info[] = $row;
        }

        return $module_info;
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
     * Uninstalls a module from the database.
     *
     * @param integer $module_id
     */
    public static function uninstallModule($module_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $delete_module_folder_on_uninstallation = Core::shouldDeleteFolderOnUninstallation();
        $root_dir = Core::getRootDir();

        $module_info = self::getModule($module_id);
        $module_folder = $module_info["module_folder"];

        if (empty($module_info)) {
            return false;
        }
        $success = true;

        $has_custom_uninstall_script = false;
        if (is_file("$root_dir/modules/$module_folder/library.php")) {
            @include_once("$root_dir/modules/$module_folder/library.php");
            $uninstall_function_name = "{$module_folder}__uninstall";
            if (function_exists($uninstall_function_name)) {
                $has_custom_uninstall_script = true;

                // get the module language file contents and store the info in the $LANG global for
                // so it can be accessed by the uninstallation script
                $LANG[$module_folder] = self::getModuleLangFile($module_folder, Core::$user->getLang());
                list($success, $custom_message) = $uninstall_function_name($module_id);

                // if there was a custom message returned (error or notification), overwrite the default
                // message
                if (!empty($custom_message)) {
                    $message = $custom_message;
                }
            }
        }

        // finally, if there wasn't a custom uninstallation script, or there WAS and it was successfully
        // run, remove the module record and any old database references
        if (!$has_custom_uninstall_script || ($has_custom_uninstall_script && $success)) {
            $db->query("
                DELETE FROM {PREFIX}modules
                WHERE module_id = $module_id
            ");
            $db->bind("module_id", $module_id);
            $db->execute();

            ModuleMenu::clearModuleNav($module_id);

            // if this module was used in any menus, update them
            $db->query("
                SELECT DISTINCT menu_id
                FROM   {PREFIX}menu_items
                WHERE  page_identifier = :page_identifier
            ");
            $db->bind("page_identifier", "module_$module_id");
            $db->execute();

            $affected_menu_ids = array();
            foreach ($db->fetchAll() as $row) {
                $affected_menu_ids[] = $row["menu_id"];
            }

            if (!empty($affected_menu_ids)) {
                $db->query("
                    DELETE FROM {PREFIX}menu_items
                    WHERE page_identifier = :page_identifier
                ");
                $db->bind("page_identifier", "module_$module_id");
                $db->execute();

                // now update the orders of all affected menus
                foreach ($affected_menu_ids as $menu_id) {
                    Menus::updateMenuOrder($menu_id);
                }

                // if rows were deleted, re-cache the admin menu and update the ordering of the admin account.
                // ASSUMPTION: only administrator accounts can have modules as items (will need to update at some
                // point soon, no doubt).
                Menus::cacheAccountMenu(Sessions::get("account.account_id"));
                Menus::updateMenuOrder(Sessions::get("account.menu_id"));
            }

            // delete any hooks registered by this module
            Hooks::unregisterModuleHooks($module_folder);
        }

        // now delete the entire module folder
        $deleted = false;
        if ($delete_module_folder_on_uninstallation) {
            $deleted = Files::deleteFolder("$root_dir/modules/$module_folder");
        }
        if ($deleted) {
            $message = $LANG["notify_module_uninstalled"];
        } else {
            $message = $LANG["notify_module_uninstalled_files_not_deleted"];
        }
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
     * This function should be called at the top of every module page - or at least every module page that wants to
     * retain the custom module nav. It does the following:
     *
     * 	- start sessions
     *  - checks permission
     *  - loads the module language file into the $LANG[module_folder] variable in the global namespace with
     *    the users chosen language (or if it doesn't exist, the module's default language). It also
     *    loads the language snippets into a $L global, for shorter use. So these are synonymous:
     *        $LANG.image_manager.phrase_hello_world
     *        $L.phrase_hello_world
     *
     * (the longer option is provided simply for consistency: that's how you access the module language variables in
     * regular Form Tools pages after using the ft_include_module() function).
     *
     * @param string $account_type who is allowed to see this module page: "admin", "client"
     */
    public static function initModulePage($required_account_type = "admin")
    {
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();
        $session_save_path = Core::getSessionSavePath();

        global $g_session_type, $g_check_ft_sessions;

        if ($g_session_type == "database") {
            $sess = new SessionManager();
        }

        if (!empty($session_save_path)) {
            session_save_path($session_save_path);
        }

        @session_start();
        header("Cache-control: private");
        header("Content-Type: text/html; charset=utf-8");

        Core::$user->checkAuth($required_account_type);

        if ($g_check_ft_sessions && isset($_SESSION["ft"]["account"])) {
            General::checkSessionsTimeout();
        }

        $module_folder = self::getCurrentModuleFolder();

        // if there's a library file defined, include it
        if (is_file("$root_dir/modules/$module_folder/library.php")) {
            include_once("$root_dir/modules/$module_folder/library.php");
        }

        // get the language file content
        $content = self::getModuleLangFile($module_folder, Core::$user->getLang());
        $LANG[$module_folder] = $content;
        $GLOBALS["L"] = $content;

        extract(Hooks::processHookCalls("end", compact("account_type", "module_folder"), array()), EXTR_OVERWRITE);
    }


    /**
     * Sets one or more module settings. This basically acts as a wrapper function for ft_set_settings,
     * which ensures that the module field is set appropriately.
     *
     * @param array $setting_name a hash of "setting_name" => "setting_value"
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
     * This function is used throughout Form Tools to include all server-side resources from a module.
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
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();

        foreach ($GLOBALS as $key => $val) {
            @eval("global \$$key;");
        }

        // code file
        if (is_file("$root_dir/modules/$module_folder/library.php")) {
            include_once("$root_dir/modules/$module_folder/library.php");
        }

        // Smarty resources
        if (is_dir("$root_dir/modules/$module_folder/smarty")) {
            $smarty->setPluginsDir("$root_dir/modules/$module_folder/smarty");
        }

        // load the language file into the $LANG var, under
        $content = self::getModuleLangFile($module_folder, Core::$user-getLang());
        $LANG[$module_folder] = $content;

        extract(Hooks::processHookCalls("end", compact("module_folder"), array()), EXTR_OVERWRITE);
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


    // --------------------------------------------------------------------------------------------


    /**
     * A simple helper function to read the module's info file (module.php).
     *
     * @param string $module_folder the module's folder name
     * @return array the module info file contents, or a blank array if there was any problem reading the
     *   file, or it didn't exist or had blank contents.
     */
    private static function getModuleInfoFileContents($module_folder)
    {
        $root_dir = Core::getRootDir();
        $file = "$root_dir/modules/$module_folder/module.php";

        if (!is_file($file)) {
            return array();
        }

        @include($file);
        $v = get_defined_vars();

        if (!isset($v["MODULE"])) {
            return array();
        }

        $values = $v["MODULE"];
        $info["author"] = isset($values["author"]) ? $values["author"] : "";
        $info["author_email"] = isset($values["author_email"]) ? $values["author_email"] : "";
        $info["author_link"] = isset($values["author_link"]) ? $values["author_link"] : "";
        $info["version"] = isset($values["version"]) ? $values["version"] : "";
        $info["date"] = isset($values["date"]) ? $values["date"] : "";
        $info["origin_language"] = isset($values["origin_language"]) ? $values["origin_language"] : "";
        $info["nav"] = isset($values["nav"]) ? $values["nav"] : array();

        return $info;
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
