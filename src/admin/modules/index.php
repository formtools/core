<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Modules;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$success = true;
$message = "";
if (isset($request["install"]) && is_numeric($request["install"])) {
    list($success, $message) = Modules::installModule($request["install"]);
}
if (isset($request["enable_modules"])) {
    list($success, $message) = Modules::updateEnabledModules($request);
}
if (isset($request["refresh_module_list"])) {
    list($success, $message) = Modules::updateModuleList();
}
if (isset($request["uninstall"])) {
    list($success, $message) = Modules::uninstallModule($request["uninstall"]);
}
if (isset($request["upgrade"])) {
    list($success, $message) = Modules::upgradeModule($request["upgrade"]);
}
if (isset($_GET["reset"])) {
    Sessions::set("module_sort_order", "");
    Sessions::set("module_search_keyword", "");
    Sessions::set("module_search_status", array("enabled", "disabled"));
}

$order   = General::loadField("order", "module_sort_order", "");
$keyword = General::loadField("keyword", "module_search_keyword", "");
$status  = General::loadField("status", "module_search_status", array("enabled", "disabled"));

$search_criteria = array(
	"order"   => $order,
	"keyword" => $keyword,
	"status"  => $status
);
$num_modules = Modules::getModuleCount();
$modules     = Modules::searchModules($search_criteria);

// Hacky patch. Longer term plan, see: https://github.com/formtools/core/issues/82
$localized_modules = array();
foreach ($modules as $module_info) {
	$module_folder = $module_info["module_folder"];
	if (!Modules::isValidModule($module_folder)) {
		continue;
	}
	$module = Modules::getModuleInstance($module_folder);
	$module_info["module_name"] = $module->getModuleName();
	$module_info["description"] = $module->getModuleDesc();
	$localized_modules[] = $module_info;
}
$modules = $localized_modules;

$module_ids = array();
foreach ($modules as $module_info) {
	$module_ids[] = $module_info["module_id"];
}
$module_ids_in_page = implode(",", $module_ids);

// find out if any of the modules have been upgraded
$updated_modules = array();
foreach ($modules as $module_info) {
	$module_id = $module_info["module_id"];
    $module_folder = $module_info["module_folder"];
	$curr_module = $module_info;

	// wedge in a check to confirm the module is valid. The most likely scenario is that the user upgraded the Core
    // to FT3, but failed to update the modules. In that case the modules won't be valid
	$is_valid = Modules::isValidModule($module_folder);
    $curr_module["is_valid"] = $is_valid;
	if ($is_valid) {
        $curr_module["needs_upgrading"] = Modules::moduleNeedsUpgrading($module_id);
    } else {
        $curr_module["needs_upgrading"] = false;
    }

	$updated_modules[] = $curr_module;
}

// now re-sort the list based on in_installed = no, needs_upgrading = yes, the rest
$sorted_modules = array();
$installed_modules = array();
foreach ($updated_modules as $module_info) {
	// we can rely on these guys being returned first
	if ($module_info["is_installed"] == "no") {
        $sorted_modules[] = $module_info;
    } else if ($module_info["needs_upgrading"]) {
        $sorted_modules[] = $module_info;
    } else {
        $installed_modules[] = $module_info;
    }
}

$modules = array_merge($sorted_modules, $installed_modules);
$LANG = Core::$L;

$page_vars = array(
    "page"        => "modules",
    "g_success"   => $success,
    "g_message"   => $message,
    "page_url"    => Pages::getPageUrl("modules"),
    "head_title"  => $LANG["word_modules"],
    "modules"     => $modules,
    "num_modules" => $num_modules,
    "order"       => $order,
    "search_criteria" => $search_criteria,
    "module_ids_in_page" => $module_ids_in_page,
    "pagination" => General::getJsPageNav(count($modules), Sessions::get("settings.num_modules_per_page"), 1),
    "js_messages" => array("validation_modules_search_no_status", "phrase_please_enter_license_key", "word_yes", "word_no",
	"phrase_please_confirm", "confirm_uninstall_module", "word_close", "word_verify", "notify_invalid_license_key",
	"notify_license_key_no_longer_valid", "notify_unknown_error"),
    "head_string" => "<script src=\"../../global/scripts/manage_modules.js\"></script>"
);

Themes::displayPage("admin/modules/index.tpl", $page_vars);
