<?php

require_once("../../global/library.php");

use FormTools\Clients;
use FormTools\Core;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Settings;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("client");

// provides a way to manually override admin theme in case of disaster
$success = true;
$message = "";
if (isset($request["theme_override"])) {
	list($success, $message) = Themes::resetAdminTheme($request["theme_override"]);
}
if (isset($request["update"])) {
	list($success, $message) = Settings::updateThemeSettings($_POST);
}
if (isset($_POST["refresh_theme_list"])) {
	list($success, $message) = Themes::updateThemeList();
}
if (isset($_GET["mass_assign"])) {
	list($success, $message) = Clients::updateClientThemes($_GET["accounts"], $_GET["theme_id"]);
}

$themes = Themes::getList();
$root_url = Core::getRootUrl();
$root_dir = Core::getRootDir();
$LANG = Core::$L;

// check permissions on all the themes
$updated_themes = array();
foreach ($themes as $theme_info) {

	// if this theme uses swatches, generate a list
	if ($theme_info["uses_swatches"] == "yes") {
		$theme_info["available_swatches"] = Themes::getThemeSwatchList($theme_info["swatches"]);
	}

	$updated_themes[] = $theme_info;
}

$head_js = <<< EOF
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

$head_string = <<< END
<script src="$root_url/global/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" href="$root_url/global/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
END;

$page = array(
	"page" => "themes",
	"g_success" => $success,
	"g_message" => $message,
	"page_url" => Pages::getPageUrl("settings_themes"),
	"head_title" => "{$LANG["word_settings"]} - {$LANG["word_themes"]}",
	"nav_page" => "program_settings",
	"themes" => $updated_themes,
	"js_messages" => "",
	"admin_theme" => Sessions::get("account.theme"),
	"admin_theme_swatch" => Sessions::get("account.swatch"),
	"client_theme" => Sessions::get("settings.default_theme"),
	"client_theme_swatch" => Sessions::get("settings.default_client_swatch"),
	"head_js" => $head_js,
	"head_string" => $head_string
);

Themes::displayPage("admin/themes/index.tpl", $page);
