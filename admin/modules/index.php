<?php

require("../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);

if (isset($request["install"]))
	list($g_success, $g_message) = ft_install_module($request["install"]);

if (isset($request["enable_modules"]))
	list($g_success, $g_message) = ft_update_enabled_modules($request);

if (isset($request["refresh_module_list"]))
	list($g_success, $g_message) = ft_update_module_list();

if (isset($request["uninstall"]))
	list($g_success, $g_message) = ft_uninstall_module($request["uninstall"]);

if (isset($request["upgrade"]))
	list($g_success, $g_message) = ft_upgrade_module($request["upgrade"]);

if (isset($_GET["reset"]))
{
	$_SESSION["ft"]["module_sort_order"] = "";
	$_SESSION["ft"]["module_search_keyword"] = "";
	$_SESSION["ft"]["module_search_status"] = "";
}
$order       = ft_load_field("order", "module_sort_order", "");
$keyword     = ft_load_field("keyword", "module_search_keyword", "");
$status      = ft_load_field("status", "module_search_status", "");

$search_criteria = array(
  "order"       => $order,
  "keyword"     => $keyword,
  "status"      => $status,
    );
$num_modules = ft_get_module_count();
$modules     = ft_search_modules($search_criteria);

// find out if any of the modules have been upgraded
$updated_modules = array();
foreach ($modules as $module_info)
{
  $module_id = $module_info["module_id"];
	$curr_module = $module_info;
	$curr_module["needs_upgrading"] = ft_module_needs_upgrading($module_id);
  $updated_modules[] = $curr_module;
}


// ------------------------------------------------------------------------------------------

// compile header information
$page_vars = array();
$page_vars["page"]        = "modules";
$page_vars["page_url"]    = ft_get_page_url("modules");
$page_vars["head_title"]  = $LANG["word_modules"];
$page_vars["modules"]     = $updated_modules;
$page_vars["num_modules"] = $num_modules;
$page_vars["order"]       = $order;
$page_vars["search_criteria"] = $search_criteria;
$page_vars["pagination"]  = ft_get_dhtml_page_nav(count($modules), $_SESSION["ft"]["settings"]["num_modules_per_page"], 1);

$page_vars["head_js"] = "var page = {};
page.uninstall_module = function(module_id)
{
  return confirm(\"{$LANG["confirm_uninstall_module"]}\");
}";

ft_display_page("admin/modules/index.tpl", $page_vars);