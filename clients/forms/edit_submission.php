<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\FieldTypes;
use FormTools\FieldValidation;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Settings;
use FormTools\Submissions;
use FormTools\Themes;
use FormTools\Views;
use FormTools\ViewFields;
use FormTools\ViewTabs;

Core::init();
Core::$user->checkAuth("client");
$root_url = Core::getRootUrl();

$account_id = Sessions::get("account.account_id");

// combine the GET and POST variables into a single variable for easy reference
$request = array_merge($_GET, $_POST);
$form_id = General::loadField("form_id", "curr_form_id");
$view_id = General::loadField("view_id", "form_{$form_id}_view_id");

$tab_number = General::loadField("tab", "view_{$view_id}_current_tab", 1);
$grouped_views = Views::getGroupedViews($form_id, array(
    "omit_hidden_views" => true,
    "omit_empty_groups" => true,
    "account_id" => $account_id
));

if (isset($_GET["copy_submission"]) && is_numeric($_GET["copy_submission"])) {
	$submission_id = $_GET["copy_submission"];
} else {
	$submission_id = isset($request["submission_id"]) ? $request["submission_id"] : "";
}

if (empty($submission_id)) {
	General::redirect("index.php");
}

// check the current client is permitted to view this information!
General::checkClientMayView($account_id, $form_id, $view_id);
if (!Submissions::checkViewContainsSubmission($form_id, $view_id, $submission_id)) {
    General::redirect("index.php");
}

$form_info = Forms::getForm($form_id);
$view_info = Views::getView($view_id);

$success = false;
$message = "";

if ($view_info["may_copy_submissions"] == "yes" && isset($_GET["copy_submission"]) && is_numeric($_GET["copy_submission"])) {
	list($success, $message, $new_submission_id) = Submissions::copySubmission($form_id, $_GET["copy_submission"]);
	if ($success) {
		$submission_id = $new_submission_id;
	}
}


// store this submission ID
Sessions::set("last_submission_id", $submission_id);

// get a list of all editable fields in the View. This is used both for security purposes
// for the update function and to determine whether the page contains any editable fields
$editable_field_ids = ViewFields::getEditableViewFields($view_id);

$failed_validation = false;
$core_update_success = true;
$changed_fields = array();
if (isset($_POST) && !empty($_POST)) {
	list($success, $message, $changed_fields, $failed_validation, $core_update_success) = Submissions::updateSubmissionWithConflictDetection($form_id, $submission_id, $view_id, $editable_field_ids, $request);
}

// this is crumby
$has_tabs = false;
foreach ($view_info["tabs"] as $tab_info) {
	if (!empty($tab_info["tab_label"])) {
		$has_tabs = true;
		break;
	}
}
if ($has_tabs) {
    $tab_number = General::loadField("tab", "view_{$view_id}_current_tab", 1);
} else {
    $tab_number = "";
}

$grouped_fields = ViewFields::getGroupedViewFields($view_id, $tab_number, $form_id, $submission_id);
if ($failed_validation && !$core_update_success) {
	$grouped_fields = FieldValidation::mergeFormSubmission($grouped_fields, $_POST);
}

$reconcile_changed_fields = array();
if (empty($changed_fields)) {
	Sessions::clear("conflicted_user_values");
	Submissions::trackCurrentEditSubmissionFields($grouped_fields, $submission_id, $view_id, $tab_number);
} else {
	$reconcile_changed_fields = Submissions::getChangedFieldsToReconcile($grouped_fields, $changed_fields);
}

$page_field_ids      = array();
$page_field_type_ids = array();
foreach ($grouped_fields as $group) {
	foreach ($group["fields"] as $field_info) {
		$page_field_ids[] = $field_info["field_id"];
		if (!in_array($field_info["field_type_id"], $page_field_type_ids)) {
            $page_field_type_ids[] = $field_info["field_type_id"];
        }
	}
}
$page_field_types = FieldTypes::get(true, $page_field_type_ids);


// construct the tab list
$view_tabs = ViewTabs::getViewTabs($view_id, true);
$same_page = General::getCleanPhpSelf();
$tabs      = array();
foreach ($view_tabs as $key => $value) {
	$tabs[$key] = array(
		"tab_label" => $value["tab_label"],
		"tab_link"  => "{$same_page}?tab=$key&form_id=$form_id&submission_id=$submission_id"
	);
}

// get a list of editable fields on this tab
$editable_tab_fields = array_intersect($page_field_ids, $editable_field_ids);

$search = Sessions::exists("current_search") ? Sessions::get("current_search") : array();

// if we're just coming here from the search results page, get a fresh list of every submission ID in this
// search result set. This is used to build the internal "<< previous   next >>" nav on this details page
if (Sessions::exists("new_search") && Sessions::get("new_search") == "yes") {
	// extract the original search settings and get the list of IDs
	$searchable_columns = ViewFields::getViewSearchableFields("", $view_info["fields"]);
	$submission_ids = Submissions::getSearchSubmissionIds($form_id, $view_id, $search["order"], $search["search_fields"], $searchable_columns);

	// this is awful.
	Sessions::set("form_{$form_id}_view_{$view_id}_submissions", $submission_ids);
	Sessions::set("new_search", "no");
}

list($prev_link_html, $search_results_link_html, $next_link_html) = Submissions::getPrevNextLinks($form_id, $view_id, $submission_id);

// construct the page label
$submission_placeholders = General::getSubmissionPlaceholders($form_id, $submission_id, "edit_submission");
$edit_submission_page_label = General::evalSmartyString($form_info["edit_submission_page_label"], $submission_placeholders);

// get all the shared resources
$settings = Settings::get("", "core");
$shared_resources_list = $settings["edit_submission_onload_resources"];
$shared_resources_array = explode("|", $shared_resources_list);
$shared_resources = "";
foreach ($shared_resources_array as $resource) {
	$shared_resources .= General::evalSmartyString($resource, array("g_root_url" => $root_url)) . "\n";
}

// ------------------------------------------------------------------------------------------------

$head_string =<<< EOF
  <script src="$root_url/global/scripts/manage_submissions.js?v=20110809"></script>
  <script src="$root_url/global/scripts/field_types.php"></script>
  <link rel="stylesheet" href="$root_url/global/css/field_types.php" type="text/css" />
$shared_resources
EOF;

$page_vars = array(
    "page"   => "client_edit_submission",
    "page_url" => Pages::getPageUrl("client_edit_submission"),
    "g_success" => $success,
    "g_message" => $message,
    "tabs" => $tabs,
    "form_info"   => $form_info,
    "grouped_views" => $grouped_views,
    "tab_number" => $tab_number,
    "settings" => $settings,
    "page_field_ids" => $page_field_ids,
    "grouped_fields" => $grouped_fields,
	"changed_fields" => $reconcile_changed_fields,
    "field_types" => $page_field_types,
    "head_title" => $edit_submission_page_label,
    "submission_id" => $submission_id,
    "previous_link_html" => $prev_link_html,
    "search_results_link_html" => $search_results_link_html,
    "next_link_html" => $next_link_html,
    "tab_has_editable_fields" => count($editable_tab_fields) > 0,
    "view_info" => $view_info,
    "form_id" => $form_id,
    "view_id" => $view_id,
    "edit_submission_page_label" => $edit_submission_page_label,
    "page_field_ids_str" => implode(",", $page_field_ids),
    "js_messages" => array(
        "confirm_delete_submission", "notify_no_email_template_selected", "confirm_delete_submission_file",
        "phrase_please_confirm", "word_no", "word_yes", "word_close", "phrase_validation_error"
    ),
    "head_string" => $head_string,
    "head_js" => FieldValidation::generateSubmissionJsValidation($grouped_fields)
);

Themes::displayPage("clients/forms/edit_submission.tpl", $page_vars);
