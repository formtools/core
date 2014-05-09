<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);
$form_id = (isset($request["form_id"])) ? $request["form_id"] : "";

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_values = array();
$page_vars["page"]     = "add_form1";
$page_vars["form_id"]  = $form_id;
$page_vars["page_url"] = ft_get_page_url("add_form1");
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_1"]}";

ft_display_page("admin/forms/add/step1.tpl", $page_vars);