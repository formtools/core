<?php

if (isset($request["update_fields"]))
{
  ft_reorder_form_fields($request, $form_id);
  list($g_success, $g_message) = ft_update_form_fields_tab($form_id, $request);
}

$form_info   = ft_get_form($form_id);
$form_fields = ft_get_form_fields($form_id);

// compile the template fields
$page_vars = array();
$page_vars["page"]        = "fields";
$page_vars["page_url"]    = ft_get_page_url("edit_form_fields", array("form_id" => $form_id));
$page_vars["tabs"]        = $tabs;
$page_vars["form_id"]     = $form_id;
$page_vars["head_title"]  = "{$LANG["phrase_edit_form"]} - {$LANG["word_fields"]}";
$page_vars["form_info"]   = $form_info;
$page_vars["form_fields"] = $form_fields;
$page_vars["image_manager_module_enabled"] = ft_check_module_enabled("image_manager");

$replacement_info = array("views_tab_link" => "{$_SERVER['PHP_SELF']}?page=views&form_id=$form_id");
$page_vars["text_fields_tab_summary"] = ft_eval_smarty_string($LANG["text_fields_tab_summary"], $replacement_info);

ft_display_page("admin/forms/edit.tpl", $page_vars);