<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\Themes;
use FormTools\Modules;
use FormTools\Pages;

Core::init();
Core::$user->checkAuth("admin");
$LANG = Core::$L;

$request = array_merge($_POST, $_GET);
$module_info = Modules::getModule($request["module_id"]);

// compile header information
$page_vars = array();
$page_vars["page"]        = "modules_about";
$page_vars["page_url"]    = Pages::getPageUrl("modules_about");
$page_vars["head_title"]  = "{$LANG["word_modules"]} - {$LANG["word_about"]}";
$page_vars["module_info"] = $module_info;

Themes::displayPage("admin/modules/about.tpl", $page_vars);
