<?php

if (isset($request["update_public_form_omit_list"]))
  list($g_success, $g_message) = ft_update_public_form_omit_list($request, $form_id);

$form_info = ft_get_form($form_id);
$form_omit_list = ft_get_public_form_omit_list($form_id);

// ------------------------------------------------------------------------------------------------

// compile the templates information
$page_vars = array();
$page_vars["page"]       = "public_form_omit_list";
$page_vars["page_url"]   = ft_get_page_url("edit_form_public_form_omit_list", array("form_id" => $form_id));
$page_vars["tabs"]       = $tabs;
$page_vars["form_id"]    = $form_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_public_form_omit_list"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["form_omit_list"]  = $form_omit_list;
$page_vars["head_js"] = "
var page_ns = {};
page_ns.clear_omit_list = function()
{
  ft.select_all($('selected_client_ids[]'));
  ft.move_options($('selected_client_ids[]'), $('available_client_ids[]'));
}";

ft_display_page("admin/forms/edit.tpl", $page_vars);