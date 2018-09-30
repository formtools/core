<?php

use FormTools\Accounts;
use FormTools\Administrator;
use FormTools\Core;
use FormTools\Forms;
use FormTools\Pages;
use FormTools\Themes;
use FormTools\Views;


// update this client
$success = true;
$message = "";
if (isset($_POST["update_client"])) {
	list($success, $message) = Administrator::adminUpdateClient($request, 3);
}

$client_info = Accounts::getAccountInfo($client_id);
$forms = Forms::searchForms(array(
	"is_admin" => true,
	"status" => ""
));

$forms_js_rows = array();
$forms_js_rows[] = "var page_ns = {}";
$forms_js_rows[] = "page_ns.forms = []";
$form_views_js_info = array("page_ns.form_views = []");

// convert ALL form and View info into Javascript, for use in the page
foreach ($forms as $form_info) {
	// ignore those forms that aren't set up
	if ($form_info["is_complete"] == "no") {
		continue;
	}

	$form_id = $form_info["form_id"];
	$form_name = htmlspecialchars($form_info["form_name"]);
	$forms_js_rows[] = "page_ns.forms.push([$form_id, \"$form_name\"])";

	$form_views = Views::getViews($form_id);

	$v = array();
	foreach ($form_views["results"] as $form_view) {
		$view_id = $form_view["view_id"];
		$view_name = htmlspecialchars($form_view["view_name"]);
		$v[] = "[$view_id, \"$view_name\"]";
	}
	$views = implode(",", $v);

	$form_views_js_info[] = "page_ns.form_views.push([$form_id,[$views]])";
}

$forms_js = implode(";\n", $forms_js_rows);
$form_views_js = implode(";\n", $form_views_js_info);

// loop through each form and add all the Views
$all_form_views = array();
foreach ($forms as $form_info) {
	$form_id = $form_info["form_id"];
	$all_form_views[$form_id] = Views::getFormViews($form_id);
}

$client_forms = Forms::searchForms(array(
	"account_id" => $client_id,
	"is_admin" => true,
	"status" => ""
));


$updated_client_forms = array();
foreach ($client_forms as $form_info) {
	$form_id = $form_info["form_id"];
	$form_info["views"] = Views::getFormViews($form_id, $client_id);
	$updated_client_forms[] = $form_info;
}

$LANG = Core::$L;
$root_url = Core::getRootUrl();

// compile header information
$page_vars["page"] = "forms";
$page_vars["g_success"] = $success;
$page_vars["g_message"] = $message;
$page_vars["page_url"] = Pages::getPageUrl("edit_client_forms", array("client_id" => $client_id));
$page_vars["head_title"] = "{$LANG["phrase_edit_client"]} - {$LANG["word_forms"]}";
$page_vars["client_info"] = $client_info;
$page_vars["forms"] = $forms;
$page_vars["client_forms"] = $updated_client_forms;
$page_vars["all_form_views"] = $all_form_views;
$page_vars["client_id"] = $client_id;
$page_vars["js_messages"] = array(
	"word_delete", "phrase_please_select", "phrase_please_select_form", "word_add_uc_rightarrow",
	"word_remove_uc_leftarrow", "phrase_form_already_selected"
);
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$root_url/global/scripts/manage_client_forms.js\"></script>";
$page_vars["head_js"] = <<< END
$forms_js
$form_views_js
END;

Themes::displayPage("admin/clients/edit.tpl", $page_vars);
