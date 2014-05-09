<?php

$view_id = $request["view_id"];

if (isset($request["update_public_view_omit_list"]))
  list($g_success, $g_message) = ft_update_public_view_omit_list($request, $view_id);

$form_info = ft_get_form($form_id);
$view_omit_list = ft_get_public_view_omit_list($view_id);

// ------------------------------------------------------------------------------------------------

// override the form nav links so that it always links to the Views page
$page_vars["prev_tabset_link"] = (!empty($links["prev_form_id"])) ? "edit.php?page=views&form_id={$links["prev_form_id"]}" : "";
$page_vars["next_tabset_link"] = (!empty($links["next_form_id"])) ? "edit.php?page=views&form_id={$links["next_form_id"]}" : "";

$page_vars["page"]       = "public_view_omit_list";
$page_vars["page_url"]   = ft_get_page_url("edit_form_public_view_omit_list", array("form_id" => $form_id, "view_id" => $view_id));
$page_vars["view_id"]    = $view_id;
$page_vars["head_title"] = $LANG["phrase_public_view_omit_list"];
$page_vars["form_info"]  = $form_info;
$page_vars["view_omit_list"] = $view_omit_list;
$page_vars["head_js"] =<<< EOF
var page_ns = {};
page_ns.clear_omit_list = function() {
  ft.select_all('selected_client_ids[]');
  ft.move_options('selected_client_ids[]', 'available_client_ids[]');
}
EOF;

ft_display_page("admin/forms/edit.tpl", $page_vars);