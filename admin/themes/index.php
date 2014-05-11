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

  // if this theme uses swatches, generate a list
  if ($theme_info["uses_swatches"] == "yes")
  {
  	$theme_info["available_swatches"] = ft_get_theme_swatch_list($theme_info["swatches"]);
  }

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
$page_vars["admin_theme_swatch"]  = $_SESSION["ft"]["account"]["swatch"];
$page_vars["client_theme"] = $_SESSION["ft"]["settings"]["default_theme"];
$page_vars["client_theme_swatch"] = $_SESSION["ft"]["settings"]["default_client_swatch"];
$page_vars["head_js"] =<<< EOF
var rules = [];
rules.push("required,admin_theme,{$LANG["validation_no_admin_theme"]}");
rules.push("function,validate_admin_swatch");
rules.push("required,default_client_theme,{$LANG["validation_no_default_client_theme"]}");
rules.push("function,validate_client_swatch");

function validate_admin_swatch() {
  var admin_theme = $("#admin_theme").val();
  var swatch_id   = "#" + admin_theme + "_admin_theme_swatches";
  if ($(swatch_id).length > 0 && $(swatch_id).val() == "") {
    return [[$(swatch_id)[0], "{$LANG["validation_no_admin_theme_swatch"]}"]];
  }
  return true;
}
function validate_client_swatch() {
  var client_theme = $("#default_client_theme").val();
  var swatch_id   = "#" + client_theme + "_default_client_theme_swatches";
  if ($(swatch_id).length > 0 && $(swatch_id).val() == "") {
    return [[$(swatch_id)[0], "{$LANG["validation_no_client_theme_swatch"]}"]];
  }
  return true;
}

$(function() {
  $(".fancybox").fancybox();
});
EOF;

$page_vars["head_string"] =<<< EOF
<script src="$g_root_url/global/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" href="$g_root_url/global/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
EOF;

ft_display_page("admin/themes/index.tpl", $page_vars);
