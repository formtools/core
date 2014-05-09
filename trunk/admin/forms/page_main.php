<?php

if (isset($request["update_main"]))
  list($g_success, $g_message) = ft_update_form_main_tab($request, $form_id);


$form_info = ft_get_form($form_id);
$form_omit_list = ft_get_public_form_omit_list($form_id);
$num_clients_on_omit_list = count($form_omit_list);

$selected_client_ids = array();
foreach ($form_info["client_info"] as $client_info)
  $selected_client_ids[] = $client_info["account_id"];

$num_pages_in_multi_page_form = count($form_info["multi_page_form_urls"]) + 1;

// ------------------------------------------------------------------------------------------------

// compile the templates information
$page_vars = array();
$page_vars["page"]       = "main";
$page_vars["page_url"]   = ft_get_page_url("edit_form_main", array("form_id" => $form_id));
$page_vars["tabs"]       = $tabs;
$page_vars["form_id"]    = $form_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["word_main"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["selected_client_ids"] = $selected_client_ids;
$page_vars["num_clients_on_omit_list"] = $num_clients_on_omit_list;
$page_vars["js_messages"] = array("validation_no_url", "phrase_verify_url", "word_page", "validation_invalid_url",
  "word_verified", "validation_urls_not_verified");
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_forms.js\"></script>";
$page_vars["head_js"] =<<< EOF
var rules = [];
rules.push("required,form_name,{$LANG['validation_no_form_name']}");
rules.push("required,form_url,{$LANG['validation_no_form_url']}");
rules.push("function,mf_ns.check_urls_verified");
rules.push("required,access_type,{$LANG["validation_no_access_type"]}");

rsv.onCompleteHandler = function() { ft.select_all($('selected_client_ids[]')); return true; }

Event.observe(document, "dom:loaded", function() {
  mf_ns.num_multi_page_form_pages = $num_pages_in_multi_page_form;
  mf_ns.toggle_multi_page_form_fields($("is_multi_page_form").checked);
});

EOF;

ft_display_page("admin/forms/edit.tpl", $page_vars);