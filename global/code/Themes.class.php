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
        //$LANG;

        $root_dir = Core::getRootDir();
        $themes_folder = "$root_dir/themes";

        // loop through all themes in this folder and if the theme contains the requisite files, add it to the database
        $dh = opendir($themes_folder);

        // if we couldn't open the themes folder, it probably doesn't exist
        if (!$dh) {
            return array(false, "");
        }

        // get the existing themes
        $current_themes = self::getList();

        // empty the themes table
        self::emptyThemesTable();

        while (($folder = readdir($dh)) !== false) {
            if (is_dir("$themes_folder/$folder") && $folder != "." && $folder != "..")
            {
                $summary_file = "$themes_folder/$folder/about/theme.php";
                $thumbnail    = "$themes_folder/$folder/about/thumbnail.gif";

                if (!is_file($summary_file) || !is_file($thumbnail))
                    continue;

                $info = _ft_get_theme_info_file_contents($summary_file);
                $info = ft_sanitize($info);

                // if the theme name is not defined, skip it
                if (empty($info["theme_name"]))
                    continue;

                $theme_name          = $info["theme_name"];
                $theme_author        = $info["theme_author"];
                $theme_author_email  = $info["theme_author_email"];
                $theme_link          = $info["theme_link"];
                $theme_description   = $info["theme_description"];
                $theme_version       = $info["theme_version"];
                $theme_uses_swatches = $info["theme_uses_swatches"];

                $swatches = "";
                if ($theme_uses_swatches == "yes")
                {
                    $swatch_info = array();
                    while (list($key, $value) = each($info["theme_swatches"]))
                    {
                        $swatch_info[] = "$key,$value";
                    }
                    $swatches = ft_sanitize(implode("|", $swatch_info));
                }

                // try and set the cache folder as writable
                if (!is_writable("$themes_folder/$folder/cache/")) {
                    @chmod("$themes_folder/$folder/cache/", 0777);
                }

                $cache_folder_writable = (is_writable("$themes_folder/$folder/cache/")) ? "yes" : "no";

                mysql_query("
        INSERT INTO {$g_table_prefix}themes (theme_folder, theme_name, uses_swatches, swatches,
          author, theme_link, description, is_enabled, theme_version)
        VALUES ('$folder', '$theme_name', '$theme_uses_swatches', '$swatches', '$theme_author',
          '$theme_link', '$theme_description', '$cache_folder_writable', '$theme_version')
      ");
            }
        }
        closedir($dh);

        $success = true;
        $message = $LANG["notify_theme_list_updated"];
        extract(ft_process_hook_calls("end", array(), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }

    public static function emptyThemesTable() {
        Core::$db->query("TRUNCATE {PREFIX}themes");
        Core::$db->execute();
    }
}
