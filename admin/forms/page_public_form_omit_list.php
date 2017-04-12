<?php

use FormTools\Forms;
use FormTools\Themes;


if (isset($request["update_public_form_omit_list"])) {
    list($g_success, $g_message) = Forms::updatePublicFormOmitList($request, $form_id);
}

$form_info = Forms::getForm($form_id);
$form_omit_list = Forms::getPublicFormOmitList($form_id);


// a little hacky, but not too bad. Override the form nav links so that it always links to the main tab, not this
// (possibly non-relevant) omit list page
$page_vars["prev_tabset_link"] = (!empty($links["prev_form_id"])) ? "edit.php?page=main&form_id={$links["prev_form_id"]}" : "";
$page_vars["next_tabset_link"] = (!empty($links["next_form_id"])) ? "edit.php?page=main&form_id={$links["next_form_id"]}" : "";

$page_vars["page"]       = "public_form_omit_list";
$page_vars["page_url"]   = Pages::getPageUrl("edit_form_public_form_omit_list", array("form_id" => $form_id));
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_public_form_omit_list"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["form_omit_list"]  = $form_omit_list;
$page_vars["head_js"] =<<< EOF
var page_ns = {};
page_ns.clear_omit_list = function() 	{
  ft.select_all('selected_client_ids[]');
  ft.move_options('selected_client_ids[]', 'available_client_ids[]');
}
EOF;

Themes::displayPage("admin/forms/edit.tpl", $page_vars);
