<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");
$LANG = Core::$L;

$theme_id = isset($request["theme_id"]) ? $request["theme_id"] : "";

if (empty($theme_id)) {
    General::redirect("index.php");
}
$theme_info = Themes::getTheme($theme_id);

// if this theme uses swatches, generate a list of all available swatches
if ($theme_info["uses_swatches"] == "yes") {
	$theme_info["available_swatches"] = Themes::getThemeSwatchList($theme_info["swatches"]);
}

// compile header information
$page_vars = array();
$page_vars["page"]       = "themes_about";
$page_vars["page_url"]   = Pages::getPageUrl("themes_about");
$page_vars["head_title"] = "{$LANG["word_themes"]} - {$LANG["word_about"]}";
$page_vars["theme_info"] = $theme_info;

Themes::displayPage("admin/themes/about.tpl", $page_vars);
