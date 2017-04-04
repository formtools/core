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
                ":origin_language"    => $module_info["origin_language"],
                ":module_name"        => $lang_info["module_name"],
                ":folder"             => $module_folder,
                ":module_version"     => $module_info["version"],
                ":author"             => $module_info["author"],
                ":author_email"       => $module_info["author_email"],
                ":author_link"        => $module_info["author_link"],
                ":module_description" => $lang_info["module_description"],
                ":module_date"        => $module_date
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

                $db->query("
                    INSERT INTO {PREFIX}module_menu_items (module_id, display_text, url, is_submenu, list_order)
                    VALUES (:module_id, :display_text, :url, :is_submenu, :nav_order)
                ");
                $db->bindAll(array(
                    ":module_id" => $module_id,
                    ":display_text" => $display_text,
                    ":url" => $url,
                    ":is_submenu" => $is_submenu,
                    ":nav_order" => $order
                ));
                $db->execute();
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
    public static function ft_module_needs_upgrading($module_id)
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
        global $LANG, $g_root_url, $g_root_dir, $g_table_prefix;

        $module_info = self::getModule($module_id);
        $module_folder = $module_info["module_folder"];
        $module_name = $module_info["module_name"];
        $old_module_version_date = $module_info["module_date"];
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
        @include_once("$g_root_dir/modules/$module_folder/library.php");

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
        $lang_file = "$g_root_dir/modules/$module_folder/lang/{$info["origin_language"]}.php";
        $lang_info = _ft_get_module_lang_file_contents($lang_file);

        // check the required language file fields
        if ((!isset($lang_info["module_name"]) || empty($lang_info["module_name"])) ||
            (!isset($lang_info["module_description"]) || empty($lang_info["module_description"]))) {
            return;
        }

        $author               = $info["author"];
        $author_email         = $info["author_email"];
        $author_link          = $info["author_link"];
        $module_version       = $info["version"];
        $module_date          = $info["date"];
        $origin_language      = $info["origin_language"];
        $nav                  = $info["nav"];

        $module_name          = $lang_info["module_name"];
        $module_description   = $lang_info["module_description"];

        // convert the date into a MySQL datetime
        list($year, $month, $day) = explode("-", $module_date);
        $timestamp = mktime(null, null, null, $month, $day, $year);
        $module_datetime = General::getCurrentDatetime($timestamp);

        @mysql_query("
    UPDATE {$g_table_prefix}modules
    SET    origin_language = '$origin_language',
           module_name = '$module_name',
           version = '$module_version',
           author = '$author',
           author_email = '$author_email',
           author_link = '$author_link',
           description = '$module_description',
           module_date = '$module_datetime'
    WHERE  module_id = $module_id
      ") or die(mysql_error());

        // remove and update the navigation links for this module
        @mysql_query("DELETE FROM {$g_table_prefix}module_menu_items WHERE module_id = $module_id");
        $order = 1;
        while (list($lang_file_key, $info) = each($nav)) {
            $url        = $info[0];
            $is_submenu = ($info[1]) ? "yes" : "no";
            if (empty($lang_file_key) || empty($url))
                continue;

            $display_text = isset($lang_info[$lang_file_key]) ? $lang_info[$lang_file_key] : $LANG[$lang_file_key];

            mysql_query("
      INSERT INTO {$g_table_prefix}module_menu_items (module_id, display_text, url, is_submenu, list_order)
      VALUES ($module_id, '$display_text', '$url', '$is_submenu', $order)
        ") or die(mysql_error());

            $order++;
        }

        // And we're done! inform the user that it's been upgraded
        $placeholders = array(
        "module"  => $module_name,
        "version" => $new_version,
        "link"    => "$g_root_url/modules/$module_folder"
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
