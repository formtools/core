<?php

/**
 * This file defines all functions relating to Form Tools themes. Note: the Theme setting tab/page
 * is updated via ft_update_theme_settings, found in the settings.php file.
 *
 * @copyright Benjamin Keen 2014
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Themes
 */


// -------------------------------------------------------------------------------------------------

/**
 * Returns all info about a particular theme.
 *
 * @param integer $theme_id
 * @return array
 */
function ft_get_theme($theme_id)
{
	global $g_table_prefix;

	$query = mysql_query("SELECT * FROM {$g_table_prefix}themes WHERE theme_id = $theme_id");
	$theme_info = mysql_fetch_assoc($query);

	extract(ft_process_hook_calls("end", compact("theme_id", "theme_info"), array("theme_info")), EXTR_OVERWRITE);

	return $theme_info;
}


/**
 * The counterpart to ft_get_theme. Returns everything about a theme by the theme folder instead
 * of theme ID.
 *
 * @param string $theme_folder
 * @return array
 */
function ft_get_theme_by_theme_folder($theme_folder)
{
	global $g_table_prefix;

	$query = mysql_query("SELECT * FROM {$g_table_prefix}themes WHERE theme_folder = '$theme_folder'");
	$theme_info = mysql_fetch_assoc($query);

	extract(ft_process_hook_calls("end", compact("theme_folder", "theme_info"), array("theme_info")), EXTR_OVERWRITE);

	return $theme_info;
}


/**
 * Retrieves the list of themes currently in the database. The optional parameter limits the results to
 * only those that are enabled
 *
 * @return array $theme_info an ordered array of hashes, each hash being the theme info.
 */
function ft_get_themes($enabled_only = false)
{
	global $g_table_prefix;

	$enabled_only_clause = ($enabled_only) ? "WHERE is_enabled = 'yes'" : "";
	$query = mysql_query("SELECT * FROM {$g_table_prefix}themes $enabled_only_clause ORDER BY theme_name");

	$theme_info = array();
	while ($theme = mysql_fetch_assoc($query))
		$theme_info[] = $theme;

	extract(ft_process_hook_calls("end", compact("theme_info"), array("theme_info")), EXTR_OVERWRITE);

	return $theme_info;
}


/**
 * This function should be called in every $g_smarty->display() call. Instead of directly loading the
 * desired template, it provides a simple fallback mechanism in case the template doesn't exist. Namely,
 * theme developers only need
 *
 * @param string $theme
 * @param string $template
 */
function ft_get_smarty_template_with_fallback($theme, $template)
{
	global $g_default_theme, $g_root_dir, $LANG;

	$file = "";
	if (is_file("$g_root_dir/themes/$theme/$template"))
		$file = "$g_root_dir/themes/$theme/$template";
	else if (is_file("$g_root_dir/themes/$g_default_theme/$template"))
		$file = "$g_root_dir/themes/$g_default_theme/$template";
	else
	{
		echo "The \"<b>$template</b>\" template could not be located at the following locations:
         <b>$g_root_dir/themes/$theme/$template</b> and <b>$g_root_dir/themes/$g_default_theme/$template</b>.";
		exit;
	}

	return $file;
}


/**
 * Loads a theme opening page for a module. This should be used loaded for every page in a
 * module. It serves the same function as ft_display_page, except that it sets the appropriate root
 * folder for the module and loads the
 *
 * @param array $page_vars a hash of information to display / provide to the template.
 * @param string $theme
 */
function ft_display_module_page($template, $page_vars = array(), $theme = "", $swatch = "")
{
	global $g_root_dir, $g_root_url, $g_success, $g_message, $g_link, $g_smarty_debug, $g_language, $LANG,
		   $g_smarty, $L, $g_smarty_use_sub_dirs, $g_js_debug, $g_benchmark_start, $g_enable_benchmarking,
		   $g_hide_upgrade_link;

	$module_folder = _ft_get_current_module_folder();

	// $module_id = ft_get_module_id_from_module_folder($module_folder);
	$default_module_language = "en_us";

	if (empty($theme) && (isset($_SESSION["ft"]["account"]["theme"])))
	{
		$theme  = $_SESSION["ft"]["account"]["theme"];
		$swatch = isset($_SESSION["ft"]["account"]["swatch"]) ? $_SESSION["ft"]["account"]["swatch"] : "";
	}
	elseif (empty($theme))
	{
		$settings = ft_get_settings(array("default_theme", "default_client_swatch"));
		$theme  = $settings["default_theme"];
		$swatch = $settings["default_client_swatch"];
	}

	if (!isset($_SESSION["ft"]["account"]["is_logged_in"]))
		$_SESSION["ft"]["account"]["is_logged_in"] = false;
	if (!isset($_SESSION["ft"]["account"]["account_type"])) $_SESSION["ft"]["account"]["account_type"] = "";

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
	if (isset($_GET["message"]))
	{
		list($g_success, $g_message) = ft_display_custom_page_message($_GET["message"]);
	}
	$g_smarty->assign("g_success", $g_success);
	$g_smarty->assign("g_message", $g_message);


	$module_id = ft_get_module_id_from_module_folder($module_folder);
	$module_nav = ft_get_module_menu_items($module_id, $module_folder);
	$g_smarty->assign("module_nav", $module_nav);

	// if there's no module title, display the module name. TODO not compatible with languages...
	if (!isset($page_vars["head_title"]))
	{
		$module_id = ft_get_module_id_from_module_folder($module_folder);
		$module_info = ft_get_module($module_id);
		$page_vars["head_title"] = $module_info["module_name"];
	}

	// check the "required" vars are at least set so they don't produce warnings when smarty debug is enabled
	if (!isset($page_vars["head_css"]))    $page_vars["head_css"] = "";
	if (!isset($page_vars["head_js"]))     $page_vars["head_js"] = "";
	if (!isset($page_vars["page"]))        $page_vars["page"] = "";

	// if we need to include custom JS messages in the page, add it to the generated JS. Note: even if the js_messages
	// key is defined but still empty, the ft_generate_js_messages function is called, returning the "base" JS - like
	// the JS version of g_root_url. Only if it is not defined will that info not be included. This feature was hacked
	// in 2.1 to support js_messages from a single module file
	$js_messages = "";
	if (isset($page_vars["js_messages"]) || isset($page_vars["module_js_messages"]))
	{
		$core_js_messages   = isset($page_vars["js_messages"]) ? $page_vars["js_messages"] : "";
		$module_js_messages = isset($page_vars["module_js_messages"]) ? $page_vars["module_js_messages"] : "";
		$js_messages = ft_generate_js_messages($core_js_messages, $module_js_messages);
	}

	if (!empty($page_vars["head_js"]) || !empty($js_messages))
		$page_vars["head_js"] = "<script type=\"text/javascript\">\n//<![CDATA[\n{$page_vars["head_js"]}\n$js_messages\n//]]>\n</script>";

	if (!isset($page_vars["head_css"]))
		$page_vars["head_css"] = "";
	else if (!empty($page_vars["head_css"]))
		$page_vars["head_css"] = "<style type=\"text/css\">\n{$page_vars["head_css"]}\n</style>";

	// theme-specific vars
	$g_smarty->assign("images_url", "$g_root_url/themes/$theme/images");
	$g_smarty->assign("theme_url", "$g_root_url/themes/$theme");
	$g_smarty->assign("theme_dir", "$g_root_dir/themes/$theme");

	// if there's a Smarty folder, import any of its resources
	if (is_dir("$g_root_dir/modules/$module_folder/smarty"))
		$g_smarty->plugins_dir[] = "$g_root_dir/modules/$module_folder/smarty";

	// now add the custom variables for this template, as defined in $page_vars
	foreach ($page_vars as $key=>$value)
		$g_smarty->assign($key, $value);

	// if smarty debug is on, enable Smarty debugging
	if ($g_smarty_debug)
		$g_smarty->debugging = true;

	extract(ft_process_hook_calls("main", compact("g_smarty", "template", "page_vars"), array("g_smarty")), EXTR_OVERWRITE);

	$g_smarty->display("$g_root_dir/modules/$module_folder/$template");

	ft_db_disconnect($g_link);
}


/**
 * This function is provided for theme developers who find themselves in a position where a theme they
 * create is wonky, and prevents them seeing anything in the UI. It can only be called after having
 * logged in as an administrator (which will not be affected by a dud theme - even through they may
 * see nothing after logging in). To call it, they'll need to construct this URL:
 *
 *     http://www.yourdomain.com/formtools/admin/settings/index.php?page=themes&theme_override=deepblue
 *
 * @param string $theme the name of the theme folder to reset to (e.g. deepblue)
 */
function ft_reset_admin_theme($theme)
{
	global $g_table_prefix, $LANG;

	$admin_id = $_SESSION["ft"]["account"]["account_id"];

	mysql_query("
    UPDATE {$g_table_prefix}accounts
    SET    theme = '$theme'
    WHERE  account_id = $admin_id
      ");

	$_SESSION["ft"]["account"]["theme"] = $theme;

	return array(true, $LANG["notify_admin_theme_overridden"]);
}


/**
 * Helper function to return a human friendly version of the available theme swatches.
 *
 * @param string $str the serialized swatch string found in the theme tables "swatches" field.
 */
function ft_get_theme_swatch_list($str)
{
	$swatch_list = array();
	$pairs = explode("|", $str);
	foreach ($pairs as $pair)
	{
		list($swatch, $swatch_label) = explode(",", $pair);
		$swatch_list[] = ft_eval_smarty_string($swatch_label);
	}
	$swatch_list_str = implode(", ", $swatch_list);

	return $swatch_list_str;
}


// ------------------------------------------------------------------------------------------------


/**
 * A helper function to read the theme's info file (theme.php) values. The only REQUIRED
 * field if the theme name. If that isn't defined, the theme won't be added to the database.
 *
 * @param string $summary_file the full file path and filename
 */
function _ft_get_theme_info_file_contents($summary_file)
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
