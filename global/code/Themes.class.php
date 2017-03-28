<?php

/**
 * Themes.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDOException;


class Themes {

    public static function getList($enabled_only = false) {
        $db = Core::$db;
        $enabled_only_clause = ($enabled_only) ? "WHERE is_enabled = 'yes'" : "";

        $db->query("
          SELECT * 
          FROM {PREFIX}themes
          $enabled_only_clause
          ORDER BY theme_name
        ");

        $db->execute();
        $theme_info = $db->fetch();
        print_r($theme_info);
        exit;

//        while ($theme = mysql_fetch_assoc($query))
//            $theme_info[] = $theme;
//
//        extract(ft_process_hook_calls("end", compact("theme_info"), array("theme_info")), EXTR_OVERWRITE);

        return $theme_info;
    }

    /**
     * Updates the list of themes in the database by examining the contents of the /themes folder.
     */
    public static function updateThemeList()
    {
        $db = Core::$db;
        $LANG = Core::$L;

        // get the existing themes
//        $current_themes = self::getList();

        // empty the themes table
        self::emptyThemesTable();

        $themes = self::getUninstalledThemes();

        $db->beginTransaction();
        foreach ($themes as $theme_info) {
            $db->query("
                INSERT INTO {PREFIX}themes (theme_folder, theme_name, uses_swatches, swatches, author, theme_link, 
                  description, is_enabled, theme_version)
                VALUES (:folder, :theme_name, :theme_uses_swatches, :swatches, :theme_author, :theme_link, 
                  :theme_description, :cache_folder_writable, :theme_version)
            ");
            $db->bindAll(array(
                ":folder" => $theme_info["theme_folder"],
                ":theme_name" => $theme_info["theme_name"],
                ":theme_uses_swatches" => $theme_info["theme_uses_swatches"],
                ":swatches" => $theme_info["swatches"],
                ":theme_author" => $theme_info["theme_author"],
                ":theme_link" => $theme_info["theme_link"],
                ":theme_description" => $theme_info["theme_description"],
                ":cache_folder_writable" => $theme_info["cache_folder_writable"],
                ":theme_version" => $theme_info["theme_version"]
            ));
            $db->execute();
        }

        try {
            $db->processTransaction();
        } catch (PDOException $e) {
            return array(false, $e->getMessage());
        }

        extract(ft_process_hook_calls("end", array(), array("success", "message")), EXTR_OVERWRITE);

        return array(true, $LANG["notify_theme_list_updated"]);
    }

    public static function emptyThemesTable() {
        Core::$db->query("TRUNCATE {PREFIX}themes");
        Core::$db->execute();
    }

    private static function getUninstalledThemes() {
        $root_dir = Core::getRootDir();
        $themes_folder = "$root_dir/themes";
        $dh = opendir($themes_folder);
#
        // if we couldn't open the themes folder, it probably doesn't exist
        if (!$dh) {
            return array(); // TODO
        }

        $theme_data = array();
        while (($folder = readdir($dh)) !== false) {
            if (!is_dir("$themes_folder/$folder") || $folder == "." || $folder == "..") {
                continue;
            }

            $summary_file = "$themes_folder/$folder/about/theme.php";
            $thumbnail = "$themes_folder/$folder/about/thumbnail.gif";

            if (!is_file($summary_file) || !is_file($thumbnail)) {
                continue;
            }

            $info = _ft_get_theme_info_file_contents($summary_file);

            // if the theme name is not defined, skip it
            if (empty($info["theme_name"])) {
                continue;
            }

            $theme_uses_swatches = $info["theme_uses_swatches"];
            $swatches = "";
            if ($theme_uses_swatches == "yes") {
                $swatch_info = array();
                while (list($key, $value) = each($info["theme_swatches"])) {
                    $swatch_info[] = "$key,$value";
                }
                $swatches = implode("|", $swatch_info);
            }

            // try to set the cache folder as writable
            if (!is_writable("$themes_folder/$folder/cache/")) {
                @chmod("$themes_folder/$folder/cache/", 0777);
            }
            $cache_folder_writable = (is_writable("$themes_folder/$folder/cache/")) ? "yes" : "no";


            $theme_data[] = array(
                "theme_folder" => $folder,
                "theme_name" => $info["theme_name"],
                "theme_author" => $info["theme_author"],
                "theme_link" => $info["theme_link"],
                "theme_description" => $info["theme_description"],
                "theme_version" => $info["theme_version"],
                "theme_uses_swatches" => $info["theme_uses_swatches"],
                "swatches" => $swatches,
                "cache_folder_writable" => $cache_folder_writable
            );
        }
        closedir($dh);

        return $theme_data;
    }
}
