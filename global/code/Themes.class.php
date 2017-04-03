<?php


/**
 * This file defines all methods relating to Form Tools themes. Note: the Theme setting tab/page
 * is updated via Settings::updateThemeSettings.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Themes
 */

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
        $theme_info = array();
        foreach ($db->fetchAll() as $theme) {
            $theme_info[] = $theme;
        }

        extract(Hooks::processHookCalls("end", compact("theme_info"), array("theme_info")), EXTR_OVERWRITE);

        return $theme_info;
    }

    /**
     * Returns all info about a particular theme.
     *
     * @param integer $theme_id
     * @return array
     */
    public static function getTheme($theme_id)
    {
        $db = Core::$db;

        $db->query("SELECT * FROM {PREFIX}themes WHERE theme_id = :theme_id");
        $db->execute();
        $theme_info = $db->fetch();

        extract(Hooks::processHookCalls("end", compact("theme_id", "theme_info"), array("theme_info")), EXTR_OVERWRITE);

        return $theme_info;
    }


    /**
     * The counterpart to getTheme. Returns everything about a theme by the theme folder instead of theme ID.
     *
     * @param string $theme_folder
     * @return array
     */
    public static function getThemeByThemeFolder($theme_folder)
    {
        $db = Core::$db;

        $db->query("SELECT * FROM {PREFIX}themes WHERE theme_folder = '$theme_folder'");
        $db->execute();
        $theme_info = $db->fetch();

        extract(Hooks::processHookCalls("end", compact("theme_folder", "theme_info"), array("theme_info")), EXTR_OVERWRITE);

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

        extract(Hooks::processHookCalls("end", array(), array("success", "message")), EXTR_OVERWRITE);

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

            $info = self::getThemeInfoFileContents($summary_file);

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
        // key is defined but still empty, the General::generateJsMessages function is called, returning the "base" JS - like
        // the JS version of g_root_url. Only if it is not defined will that info not be included.
        $js_messages = (isset($page_vars["js_messages"])) ? General::generateJsMessages($page_vars["js_messages"]) : "";

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

        extract(Hooks::processHookCalls("main", compact("g_smarty", "template", "page_vars"), array("g_smarty")), EXTR_OVERWRITE);

        // if the page or hook actually defined some CSS for inclusion in the page, wrap it in the appropriate style tag. This
        // was safely moved here in 2.2.0, because nothing used it (!)
        if (!empty($g_smarty->_tpl_vars["head_css"])) {
            $g_smarty->assign("head_css", "<style type=\"text/css\">\n{$g_smarty->_tpl_vars["head_css"]}\n</style>");
        }

        $g_smarty->display(self::getSmartyTemplateWithFallback($theme, $template));
    }


    /**
     * TODO. Combine as much of this with the prev method.
     *
     * Loads a theme opening page for a module. This should be used loaded for every page in a
     * module. It serves the same function as ft_display_page, except that it sets the appropriate root
     * folder for the module and loads the
     *
     * @param array $page_vars a hash of information to display / provide to the template.
     * @param string $theme
     */
    function displayModulePage($template, $page_vars = array(), $theme = "", $swatch = "")
    {
        global $g_root_dir, $g_root_url, $g_success, $g_message, $g_link, $g_smarty_debug, $g_language, $LANG,
               $g_smarty, $L, $g_smarty_use_sub_dirs, $g_js_debug, $g_enable_benchmarking,
               $g_hide_upgrade_link;

        $module_folder = _ft_get_current_module_folder();

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

        // common variables. These are sent to EVERY template
        $g_smarty->template_dir = "$g_root_dir/themes/$theme";
        $g_smarty->compile_dir  = "$g_root_dir/themes/$theme/cache/";
        $g_smarty->use_sub_dirs = $g_smarty_use_sub_dirs;
        $g_smarty->assign("LANG", $LANG);

        // this contains the custom language content of the module, in the language required. It's populated by
        // ft_init_module_page(), called on every module page
        $g_smarty->assign("L", $L);

        $g_smarty->assign("SESSION", $_SESSION["ft"]);
        $settings = isset($_SESSION["ft"]["settings"]) ? $_SESSION["ft"]["settings"] : array();
        $g_smarty->assign("settings", $settings);
        $g_smarty->assign("account", $_SESSION["ft"]["account"]);
        $g_smarty->assign("g_root_dir", $g_root_dir);
        $g_smarty->assign("g_root_url", $g_root_url);
        $g_smarty->assign("g_js_debug", ($g_js_debug) ? "true" : "false");
        $g_smarty->assign("g_hide_upgrade_link", $g_hide_upgrade_link);
        $g_smarty->assign("same_page", ft_get_clean_php_self());
        $g_smarty->assign("query_string", $_SERVER["QUERY_STRING"]); // TODO FIX
        $g_smarty->assign("dir", $LANG["special_text_direction"]);
        $g_smarty->assign("g_enable_benchmarking", $g_enable_benchmarking);
        $g_smarty->assign("swatch", $swatch);

        // if this page has been told to dislay a custom message, override g_success and g_message
        if (isset($_GET["message"])) {
            list($g_success, $g_message) = ft_display_custom_page_message($_GET["message"]);
        }
        $g_smarty->assign("g_success", $g_success);
        $g_smarty->assign("g_message", $g_message);


        $module_id = ft_get_module_id_from_module_folder($module_folder);
        $module_nav = ft_get_module_menu_items($module_id, $module_folder);
        $g_smarty->assign("module_nav", $module_nav);

        // if there's no module title, display the module name. TODO not compatible with languages...
        if (!isset($page_vars["head_title"])) {
            $module_id = ft_get_module_id_from_module_folder($module_folder);
            $module_info = ft_get_module($module_id);
            $page_vars["head_title"] = $module_info["module_name"];
        }

        // check the "required" vars are at least set so they don't produce warnings when smarty debug is enabled
        if (!isset($page_vars["head_css"]))    $page_vars["head_css"] = "";
        if (!isset($page_vars["head_js"]))     $page_vars["head_js"] = "";
        if (!isset($page_vars["page"]))        $page_vars["page"] = "";

        // if we need to include custom JS messages in the page, add it to the generated JS. Note: even if the js_messages
        // key is defined but still empty, the General::generateJsMessages function is called, returning the "base" JS - like
        // the JS version of g_root_url. Only if it is not defined will that info not be included. This feature was hacked
        // in 2.1 to support js_messages from a single module file
        $js_messages = "";
        if (isset($page_vars["js_messages"]) || isset($page_vars["module_js_messages"])) {
            $core_js_messages   = isset($page_vars["js_messages"]) ? $page_vars["js_messages"] : "";
            $module_js_messages = isset($page_vars["module_js_messages"]) ? $page_vars["module_js_messages"] : "";
            $js_messages = General::generateJsMessages($core_js_messages, $module_js_messages);
        }

        if (!empty($page_vars["head_js"]) || !empty($js_messages)) {
            $page_vars["head_js"] = "<script type=\"text/javascript\">\n//<![CDATA[\n{$page_vars["head_js"]}\n$js_messages\n//]]>\n</script>";
        }

        if (!isset($page_vars["head_css"])) {
            $page_vars["head_css"] = "";
        } else if (!empty($page_vars["head_css"])) {
            $page_vars["head_css"] = "<style type=\"text/css\">\n{$page_vars["head_css"]}\n</style>";
        }

        // theme-specific vars
        $g_smarty->assign("images_url", "$g_root_url/themes/$theme/images");
        $g_smarty->assign("theme_url", "$g_root_url/themes/$theme");
        $g_smarty->assign("theme_dir", "$g_root_dir/themes/$theme");

        // if there's a Smarty folder, import any of its resources
        if (is_dir("$g_root_dir/modules/$module_folder/smarty")) {
            $g_smarty->plugins_dir[] = "$g_root_dir/modules/$module_folder/smarty";
        }

        // now add the custom variables for this template, as defined in $page_vars
        foreach ($page_vars as $key=>$value) {
            $g_smarty->assign($key, $value);
        }

        // if smarty debug is on, enable Smarty debugging
        if ($g_smarty_debug) {
            $g_smarty->debugging = true;
        }

        extract(Hooks::processHookCalls("main", compact("g_smarty", "template", "page_vars"), array("g_smarty")), EXTR_OVERWRITE);

        $g_smarty->display("$g_root_dir/modules/$module_folder/$template");

        ft_db_disconnect($g_link);
    }


    /**
     * This function should be called in every $g_smarty->display() call. Instead of directly loading the
     * desired template, it provides a simple fallback mechanism in case the template doesn't exist. Namely,
     * theme developers only need
     *
     * @param string $theme
     * @param string $template
     */
    public static function getSmartyTemplateWithFallback($theme, $template)
    {
        $default_theme = Core::getDefaultTheme();
        $root_dir = Core::getRootDir();

        if (is_file("$root_dir/themes/$theme/$template")) {
            $file = "$root_dir/themes/$theme/$template";
        } else if (is_file("$root_dir/themes/$default_theme/$template")) {
            $file = "$root_dir/themes/$default_theme/$template";
        } else {
            echo "The \"<b>$template</b>\" template could not be located at the following locations:
                  <b>$root_dir/themes/$theme/$template</b> and <b>$root_dir/themes/$default_theme/$template</b>.";
            exit;
        }

        return $file;
    }



    /**
     * TODO move to administrator?
     *
     * This function is provided for theme developers who find themselves in a position where a theme they
     * create is wonky, and prevents them seeing anything in the UI. It can only be called after having
     * logged in as an administrator (which will not be affected by a dud theme - even through they may
     * see nothing after logging in). To call it, they'll need to construct this URL:
     *
     *     http://www.yourdomain.com/formtools/admin/settings/index.php?page=themes&theme_override=deepblue
     *
     * @param string $theme the name of the theme folder to reset to (e.g. deepblue)
     */
    public static function resetAdminTheme($theme)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        // yikes!
        $admin_id = $_SESSION["ft"]["account"]["account_id"];

        $db->query("
            UPDATE {PREFIX}accounts
            SET    theme = :theme
            WHERE  account_id = :admin_id
        ");
        $db->bindAll(array(
            ":theme" => $theme,
            ":admin_id" => $admin_id
        ));
        $db->execute();

        // double yikes!
        $_SESSION["ft"]["account"]["theme"] = $theme;

        return array(true, $LANG["notify_admin_theme_overridden"]);
    }


    /**
     * Returns a human-friendly string version of the available theme swatches.
     *
     * @param string $str the serialized swatch string found in the theme tables "swatches" field.
     */
    public static function getThemeSwatchList($str)
    {
        $swatch_list = array();
        $pairs = explode("|", $str);
        foreach ($pairs as $pair)  {
            list($swatch, $swatch_label) = explode(",", $pair);
            $swatch_list[] = ft_eval_smarty_string($swatch_label);
        }
        $swatch_list_str = implode(", ", $swatch_list);

        return $swatch_list_str;
    }


    // --------------------------------------------------------------------------------------------


    /**
     * Helper function to read the theme's info file (theme.php) values. The only REQUIRED
     * field if the theme name. If that isn't defined, the theme won't be added to the database.
     *
     * @param string $summary_file the full file path and filename
     */
    private static function getThemeInfoFileContents($summary_file)
    {
	    @include($summary_file);
	    $vars = get_defined_vars();

        $info["theme_name"] = isset($vars["theme_name"]) ? $vars["theme_name"] : "";
        $info["theme_author"] = isset($vars["theme_author"]) ? $vars["theme_author"] : "";
        $info["theme_author_email"] = isset($vars["theme_author_email"]) ? $vars["theme_author_email"] : "";
        $info["theme_link"] = isset($vars["theme_link"]) ? $vars["theme_link"] : "";
        $info["theme_description"] = isset($vars["theme_description"]) ? $vars["theme_description"] : "";
        $info["theme_version"] = isset($vars["theme_version"]) ? $vars["theme_version"] : "";
        $info["theme_uses_swatches"] = isset($vars["theme_uses_swatches"]) ? $vars["theme_uses_swatches"] : "no";
        $info["theme_swatches"] = isset($vars["theme_swatches"]) ? $vars["theme_swatches"] : array();

        return $info;
    }

}
