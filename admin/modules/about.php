<?php

require("../../global/session_start.php");
ft_check_permission("admin");
$request = array_merge($_POST, $_GET);
$module_info = ft_get_module($request["module_id"]);

// compile header information
$page_vars = array();
$page_vars["page"]        = "modules_about";
$page_vars["page_url"]    = ft_get_page_url("modules_about");
$page_vars["head_title"]  = "{$LANG["word_modules"]} - {$LANG["word_about"]}";
$page_vars["module_info"] = $module_info;

ft_display_page("admin/modules/about.tpl", $page_vars);
