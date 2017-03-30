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


    /**
     * Loads a theme opening page - as stored in the "theme" session key. This is loaded for every
     * page in the Form Tools UI.
     *
     * Note: if the page isn't found in the current theme, it defaults to the
     * "default" theme. This is important (and handy!): it means that only the default theme
     * needs to contain every file. Other themes can just define whatever pages they want to override
     * and omit the others.
     *
     * @param string $template the location of the template file, relative to the theme folder
     * @param array $page_vars a hash of information to provide to the template
     * @param string $g_theme an optional parameter, letting you override the default theme
     * @param string $g_theme an optional parameter, letting you override the default swatch
     */
    public static function displayPage($template, $page_vars, $theme = "", $swatch = "")
    {
        global $g_root_dir, $g_root_url, $g_success, $g_message, $g_smarty_debug, $g_debug, $LANG,
               $g_smarty, $g_smarty_use_sub_dirs, $g_js_debug, $g_benchmark_start, $g_enable_benchmarking,
               $g_upgrade_info, $g_hide_upgrade_link;

        if (empty($theme) && (isset($_SESSION["ft"]["account"]["theme"]))) {
            $theme  = $_SESSION["ft"]["account"]["theme"];
            $swatch = isset($_SESSION["ft"]["account"]["swatch"]) ? $_SESSION["ft"]["account"]["swatch"] : "";
        } elseif (empty($theme)) {
            $settings = Settings::get(array("default_theme", "default_client_swatch"));
            $theme  = $settings["default_theme"];
            $swatch = $settings["default_client_swatch"];
        }

        if (!isset($_SESSION["ft"]["account"]["is_logged_in"])) {
            $_SESSION["ft"]["account"]["is_logged_in"] = false;
        }
        if (!isset($_SESSION["ft"]["account"]["account_type"])) {
            $_SESSION["ft"]["account"]["account_type"] = "";
        }

        // common variables. These are sent to EVERY templates
        $g_smarty->template_dir = "$g_root_dir/themes/$theme";
        $g_smarty->compile_dir  = "$g_root_dir/themes/$theme/cache";

        // check the compile directory has the write permissions
        if (!is_writable($g_smarty->compile_dir)) {
            General::displaySeriousError("Either the theme cache folder doesn't have write-permissions, or your \$g_root_dir value is invalid. Please update the <b>{$g_smarty->compile_dir}</b> to have full read-write permissions (777 on unix).", "");
            exit;
        }

        $g_smarty->use_sub_dirs = $g_smarty_use_sub_dirs;
        $g_smarty->assign("LANG", $LANG);
        $g_smarty->assign("SESSION", $_SESSION["ft"]);
        $settings = isset($_SESSION["ft"]["settings"]) ? $_SESSION["ft"]["settings"] : array();
        $g_smarty->assign("settings", $settings);
        $g_smarty->assign("account", $_SESSION["ft"]["account"]);
        $g_smarty->assign("g_root_dir", $g_root_dir);
        $g_smarty->assign("g_root_url", $g_root_url);
        $g_smarty->assign("g_debug", $g_debug);
        $g_smarty->assign("g_js_debug", ($g_js_debug) ? "true" : "false");
        $g_smarty->assign("g_hide_upgrade_link", $g_hide_upgrade_link);
        $g_smarty->assign("same_page", ft_get_clean_php_self());
        $g_smarty->assign("query_string", $_SERVER["QUERY_STRING"]);
        $g_smarty->assign("dir", $LANG["special_text_direction"]);
        $g_smarty->assign("g_enable_benchmarking", $g_enable_benchmarking);
        $g_smarty->assign("swatch", $swatch);

        // if this page has been told to dislay a custom message, override g_success and g_message
        if ((!isset($g_upgrade_info["message"]) || empty($g_upgrade_info["message"])) && isset($_GET["message"])) {
            list($g_success, $g_message) = ft_display_custom_page_message($_GET["message"]);
        }
        $g_smarty->assign("g_success", $g_success);
        $g_smarty->assign("g_message", $g_message);

        if (isset($page_vars["page_url"])) {
            $parent_page_url = ft_get_parent_page_url($page_vars["page_url"]);
            $g_smarty->assign("nav_parent_page_url", $parent_page_url);
        }

        // check the "required" vars are at least set so they don't produce warnings when smarty debug is enabled
        if (!isset($page_vars["head_string"])) $page_vars["head_string"] = "";
        if (!isset($page_vars["head_title"]))  $page_vars["head_title"] = "";
        if (!isset($page_vars["head_js"]))     $page_vars["head_js"] = "";
        if (!isset($page_vars["page"]))        $page_vars["page"] = "";

        // if we need to include custom JS messages in the page, add it to the generated JS. Note: even if the js_messages
        // key is defined but still empty, the ft_generate_js_messages function is called, returning the "base" JS - like
        // the JS version of g_root_url. Only if it is not defined will that info not be included.
        $js_messages = (isset($page_vars["js_messages"])) ? ft_generate_js_messages($page_vars["js_messages"]) : "";

        if (!empty($page_vars["head_js"]) || !empty($js_messages)) {
            $page_vars["head_js"] = "<script>\n//<![CDATA[\n{$page_vars["head_js"]}\n$js_messages\n//]]>\n</script>";
        }

        if (!isset($page_vars["head_css"])) {
            $page_vars["head_css"] = "";
        }

        $g_smarty->assign("modules_dir", "$g_root_url/modules");

        // theme-specific vars
        $g_smarty->assign("images_url", "$g_root_url/themes/$theme/images");
        $g_smarty->assign("theme_url", "$g_root_url/themes/$theme");
        $g_smarty->assign("theme_dir", "$g_root_dir/themes/$theme");

        // now add the custom variables for this template, as defined in $page_vars
        foreach ($page_vars as $key=>$value)
            $g_smarty->assign($key, $value);

        // if smarty debug is on, enable Smarty debugging
        if ($g_smarty_debug)
            $g_smarty->debugging = true;

        extract(ft_process_hook_calls("main", compact("g_smarty", "template", "page_vars"), array("g_smarty")), EXTR_OVERWRITE);

        // if the page or hook actually defined some CSS for inclusion in the page, wrap it in the appropriate style tag. This
        // was safely moved here in 2.2.0, because nothing used it (!)
        if (!empty($g_smarty->_tpl_vars["head_css"])) {
            $g_smarty->assign("head_css", "<style type=\"text/css\">\n{$g_smarty->_tpl_vars["head_css"]}\n</style>");
        }

        $g_smarty->display(ft_get_smarty_template_with_fallback($theme, $template));
    }
}
