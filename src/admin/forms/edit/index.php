<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\Forms;
use FormTools\General;
use FormTools\Modules;
use FormTools\Sessions;

Core::init();
Core::$user->checkAuth("admin");

$form_id = General::loadField("form_id", "form_id", "");

if (!Forms::checkFormExists($form_id)) {
    General::redirect("index.php");
    exit;
}

// store the current selected tab in memory - except for pages which require additional
// query string info. For those, use the parent page
if (isset($request["page"]) && !empty($request["page"])) {
	$remember_page = $request["page"];
	switch ($remember_page) {
		case "field_options":
		case "files":
			$remember_page = "fields";
			break;
		case "edit_email":
			$remember_page = "emails";
			break;
	}
	Sessions::set("form_{$form_id}_tab", $remember_page);
	$page = $request["page"];
} else {
	$page = General::loadField("page", "form_{$form_id}_tab", "edit_form_main");
}

if (isset($request['edit_email_user_settings'])) {
    General::redirect("?page=email_settings");
    exit;
}

$view_submissions_link = "../submissions.php?form_id={$form_id}";
if (Sessions::exists("last_link_page_{$form_id}") && Sessions::exists("last_submission_id_{$form_id}") &&
	Sessions::get("last_link_page_{$form_id}") == "edit") {
    $last_submission = Sessions::get("last_submission_id_{$form_id}");
	$view_submissions_link = "../edit_submission.php?form_id={$form_id}&submission_id=$last_submission";
}

$LANG = Core::$L;

$same_page = General::getCleanPhpSelf();
$tabs = array(
	"main" => array(
		"tab_label" => $LANG["word_main"],
		"tab_link" => "{$same_page}?form_id=$form_id&page=main",
		"pages" => array("main", "public_form_omit_list")
	),
	"fields" => array(
		"tab_label" => $LANG["word_fields"],
		"tab_link" => "{$same_page}?form_id=$form_id&page=fields",
		"pages" => array("fields")
	),
	"views" => array(
		"tab_label" => $LANG["word_views"],
		"tab_link" => "{$same_page}?form_id=$form_id&page=views",
		"pages" => array("edit_view", "view_tabs", "public_view_omit_list")
	),
	"emails" => array(
		"tab_label" => $LANG["word_emails"],
		"tab_link" => "{$same_page}?form_id=$form_id&page=emails",
		"pages" => array("email_settings", "edit_email")
	)
);

$tabs = Modules::moduleOverrideData("admin_edit_form_tabs", $tabs);

$order     = General::loadField("order", "form_sort_order", "form_name-ASC");
$keyword   = General::loadField("keyword", "form_search_keyword", "");
$status    = General::loadField("status", "form_search_status", "");
$client_id = General::loadField("client_id", "form_search_client_id", "");
$search_criteria = array(
	"order"      => $order,
	"keyword"    => $keyword,
	"status"     => $status,
	"account_id" => $client_id,
	"is_admin"   => false
);

$links = Forms::getFormPrevNextLinks($form_id, $search_criteria);
$prev_tabset_link = (!empty($links["prev_form_id"])) ? "?page=$page&form_id={$links["prev_form_id"]}" : "";
$next_tabset_link = (!empty($links["next_form_id"])) ? "?page=$page&form_id={$links["next_form_id"]}" : "";

// start compiling the page vars here, so we don't have to duplicate the shared stuff for each included code file below
$page_vars = array();
$page_vars["tabs"]    = $tabs;
$page_vars["form_id"] = $form_id;
$page_vars["view_submissions_link"] = $view_submissions_link;
$page_vars["show_tabset_nav_links"] = true;
$page_vars["prev_tabset_link"] = $prev_tabset_link;
$page_vars["next_tabset_link"] = $next_tabset_link;
$page_vars["prev_tabset_link_label"] = $LANG["phrase_prev_form"];
$page_vars["next_tabset_link_label"] = $LANG["phrase_next_form"];


// load the appropriate code page
$page_map = array(
    "main" => "page_main.php",
    "public_form_omit_list" => "page_public_form_omit_list.php",
    "fields" => "page_fields.php",
    "views" => "page_views.php",
    "edit_view" => "page_edit_view.php",
    "public_view_omit_list" => "page_public_view_omit_list.php",
    "emails" => "page_emails.php",
    "email_settings" => "page_email_settings.php",
    "edit_email" => "page_edit_email.php"
);

if (isset($page_map[$page])) {
    require_once($page_map[$page]);
} else {
    $vals = Modules::moduleOverrideData("admin_edit_form_page_name_include", array("page_name" => "page_main.php"));
    require_once($vals["page_name"]);
}
