<?php

use FormTools\Core;
use FormTools\Themes;

Core::init();

//require("../../global/session_start.php");
Core::$user->checkAuth("admin");


$request = array_merge($_POST, $_GET);
$module_info = ft_get_module($request["module_id"]);

// compile header information
$page_vars = array();
$page_vars["page"]        = "modules_about";
$page_vars["page_url"]    = ft_get_page_url("modules_about");
$page_vars["head_title"]  = "{$LANG["word_modules"]} - {$LANG["word_about"]}";
$page_vars["module_info"] = $module_info;

Themes::displayPage("admin/modules/about.tpl", $page_vars);
