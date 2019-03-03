<?php

use FormTools\Core;
use FormTools\Forms;
use FormTools\Pages;
use FormTools\Themes;
use FormTools\Views;


$view_id = $request["view_id"];

$success = true;
$message = "";
if (isset($request["update_public_view_omit_list"])) {
    list($success, $message) = Views::updatePublicViewOmitList($request, $view_id);
}

$form_info = Forms::getForm($form_id);
$view_omit_list = Views::getPublicViewOmitList($view_id);

$LANG = Core::$L;

// override the form nav links so that it always links to the Views page
$page_vars["prev_tabset_link"] = (!empty($links["prev_form_id"])) ? "?page=views&form_id={$links["prev_form_id"]}" : "";
$page_vars["next_tabset_link"] = (!empty($links["next_form_id"])) ? "?page=views&form_id={$links["next_form_id"]}" : "";

$page_vars["g_success"]  = $success;
$page_vars["g_message"]  = $message;
$page_vars["page"]       = "public_view_omit_list";
$page_vars["page_url"]   = Pages::getPageUrl("edit_form_public_view_omit_list", array("form_id" => $form_id, "view_id" => $view_id));
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

Themes::displayPage("admin/forms/edit/index.tpl", $page_vars);
