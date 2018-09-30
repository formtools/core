<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\Themes;
use FormTools\Modules;
use FormTools\Pages;

Core::init();
Core::$user->checkAuth("admin");
$LANG = Core::$L;

$module_info = Modules::getModule($request["module_id"]);

// Hacky patch. Longer term plan, see: https://github.com/formtools/core/issues/82
if (Core::$user->getLang() !== "en_us") {
    $module = Modules::getModuleInstance($module_info["module_folder"]);
    $module_info["module_name"] = $module->getModuleName();
    $module_info["description"] = $module->getModuleDesc();
}


// compile header information
$page_vars = array(
    "page"        => "modules_about",
    "page_url"    => Pages::getPageUrl("modules_about"),
    "head_title"  => "{$LANG["word_modules"]} - {$LANG["word_about"]}",
    "module_info" => $module_info
);

Themes::displayPage("admin/modules/about.tpl", $page_vars);
