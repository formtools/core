<?php

if (isset($request["update_email_settings"]))
  list($g_success, $g_message) = ft_update_form_email_settings($form_id, $request);

$form_info = ft_get_form($form_id);
$columns = ft_get_form_column_names($form_id, "", true);

// remove the system fields


// compile the templates information
$page_vars = array();
$page_vars["page"]       = "email_settings";
$page_vars["page_url"]   = ft_get_page_url("edit_form_email_settings", array("form_id" => $form_id));
$page_vars["tabs"]       = $tabs;
$page_vars["form_id"]    = $form_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_email_settings"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["columns"]    = $columns;

ft_display_page("admin/forms/edit.tpl", $page_vars);