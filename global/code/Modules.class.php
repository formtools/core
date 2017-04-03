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
                $info = ft_get_module_info_file_contents($folder);

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
                $lang_info = _ft_get_module_lang_file_contents($lang_file);

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

            ft_install_module($module_info["module_id"]);
        }
    }

}
