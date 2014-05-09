<?php

require("../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);

// provides means to manually override admin theme in case of disaster
if (isset($request["theme_override"]))
  list($g_success, $g_message) = ft_reset_admin_theme($request["theme_override"]);

if (isset($request["update"]))
  list($g_success, $g_message) = ft_update_theme_settings($_POST);

if (isset($_POST["refresh_theme_list"]))
	list($g_success, $g_message) = ft_update_theme_list();

if (isset($_GET["mass_assign"]))
  list($g_success, $g_message) = ft_update_client_themes($_GET["accounts"], $_GET["theme_id"]);

$themes = ft_get_themes();

// check permissions on all the themes
$updated_themes = array();
foreach ($themes as $theme_info)
{
	$cache_folder = "$g_root_dir/themes/{$theme_info["theme_folder"]}/cache";
	$theme_info["cache_folder_writable"] = is_writable($cache_folder);
	$updated_themes[] = $theme_info;
}

// compile the header information
$page_vars = array();
$page_vars["page"] = "themes";
$page_vars["page_url"] = ft_get_page_url("settings_themes");
$page_vars["head_title"] = "{$LANG["word_settings"]} - {$LANG["word_themes"]}";
$page_vars["nav_page"] = "program_settings";
$page_vars["themes"] = $updated_themes;
$page_vars["js_messages"] = "";
$page_vars["admin_theme"]  = $_SESSION["ft"]["account"]["theme"];
$page_vars["client_theme"] = $_SESSION["ft"]["settings"]["default_theme"];
$page_vars["head_js"] = "

var rules = [];
rules.push(\"required,admin_theme_id,{$LANG["validation_no_admin_theme"]}\");
rules.push(\"required,default_client_theme_id,{$LANG["validation_no_default_client_theme"]}\");";
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/lightbox.js\"></script>
<link rel=\"stylesheet\" href=\"$g_root_url/global/css/lightbox.css\" type=\"text/css\" media=\"screen\" />";

ft_display_page("admin/themes/index.tpl", $page_vars);