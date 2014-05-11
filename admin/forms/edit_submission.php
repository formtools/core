<?php

require("../../global/session_start.php");
ft_check_permission("admin");
require(dirname(__FILE__) . "/edit_submission__code.php");

$request = array_merge($_GET, $_POST);
$form_id = ft_load_field("form_id", "curr_form_id");
$view_id = _ft_code_get_view($request, $form_id);

$submission_id = isset($request["submission_id"]) ? $request["submission_id"] : "";
if (empty($submission_id))
{
  header("location: submissions.php");
  exit;
}
$_SESSION["ft"]["last_submission_id"] = $submission_id;
$_SESSION["ft"]["last_submission_id_{$form_id}"] = $submission_id;
$_SESSION["ft"]["last_link_page_{$form_id}"] = "edit";

// get a list of all editable fields in the View. This is used for security purposes for the update function and to
// determine whether the page contains any editable fields
$editable_field_ids = _ft_get_editable_view_fields($view_id);

// update the submission
$failed_validation = false;
if (isset($_POST) && !empty($_POST))
{
  $_SESSION["ft"]["new_search"] = "yes";
  $request["view_id"] = $view_id;
  $request["editable_field_ids"] = $editable_field_ids;
  list($g_success, $g_message) = ft_update_submission($form_id, $submission_id, $request);

  // if there was any problem udpating this submission, make a special note of it: we'll use that info to merge the current POST request
  // info with the original field values to ensure the page contains the latest data (i.e. for cases where they fail server-side validation)
  if (!$g_success)
  {
  	$failed_validation = true;
  }
}

$form_info = ft_get_form($form_id);
$view_info = ft_get_view($view_id);

// this is crumby
$has_tabs = false;
foreach ($view_info["tabs"] as $tab_info)
{
  if (!empty($tab_info["tab_label"]))
  {
    $has_tabs = true;
    break;
  }
}
if ($has_tabs)
  $tab_number = ft_load_field("tab", "view_{$view_id}_current_tab", 1);
else
  $tab_number = "";

$grouped_fields = ft_get_grouped_view_fields($view_id, $tab_number, $form_id, $submission_id);
if ($failed_validation)
{
	$grouped_fields = ft_merge_form_submission($grouped_fields, $_POST);
}

$page_field_ids      = array();
$page_field_type_ids = array();
$page_has_required_fields = false;
foreach ($grouped_fields as $group)
{
  foreach ($group["fields"] as $field_info)
  {
    $page_field_ids[] = $field_info["field_id"];
    if (!in_array($field_info["field_type_id"], $page_field_type_ids))
      $page_field_type_ids[] = $field_info["field_type_id"];

    if ($field_info["is_required"])
      $page_has_required_fields = true;
  }
}
$page_field_types = ft_get_field_types(true, $page_field_type_ids);


// construct the tab list
$view_tabs = ft_get_view_tabs($view_id, true);
$tabs      = array();
$same_page = ft_get_clean_php_self();
while (list($key, $value) = each($view_tabs))
{
  $tabs[$key] = array(
    "tab_label" => $value["tab_label"],
    "tab_link"  => "{$same_page}?tab=$key&form_id=$form_id&submission_id={$submission_id}"
    );
}

// get a list of editable fields on this tab
$editable_tab_fields = array_intersect($page_field_ids, $editable_field_ids);

// if we're just coming here from the search results page, get a fresh list of every submission ID in this
// search result set. This is used to build the internal "<< previous   next >>" nav on this details page.
// They need to exactly correspond to the ordering of the search results or they don't make sense
$search = isset($_SESSION["ft"]["current_search"]) ? $_SESSION["ft"]["current_search"] : array();
if (isset($_SESSION["ft"]["new_search"]) && $_SESSION["ft"]["new_search"] == "yes")
{
  $searchable_columns = ft_get_view_searchable_fields("", $view_info["fields"]);

  // extract the original search settings and get the list of IDs
  $submission_ids = ft_get_search_submission_ids($form_id, $view_id, $search["results_per_page"], $search["order"],
    $search["search_fields"], $searchable_columns);
  $_SESSION["ft"]["form_{$form_id}_view_{$view_id}_submissions"] = $submission_ids;
  $_SESSION["ft"]["new_search"] = "no";
}

list($prev_link_html, $search_results_link_html, $next_link_html) = _ft_code_get_link_html($form_id, $view_id, $submission_id, $search["results_per_page"]);

// construct the page label
$submission_placeholders = ft_get_submission_placeholders($form_id, $submission_id);
$edit_submission_page_label = ft_eval_smarty_string($form_info["edit_submission_page_label"], $submission_placeholders);

$validation_js = ft_generate_submission_js_validation($grouped_fields);


// get all the shared resources
$settings = ft_get_settings("", "core");
$shared_resources_list = $settings["edit_submission_onload_resources"];
$shared_resources_array = explode("|", $shared_resources_list);
$shared_resources = "";
foreach ($shared_resources_array as $resource)
{
  $shared_resources .= ft_eval_smarty_string($resource, array("g_root_url" => $g_root_url)) . "\n";
}

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars = array();
$page_vars["page"]   = "admin_edit_submission";
$page_vars["page_url"] = ft_get_page_url("admin_edit_submission");
$page_vars["head_title"] = $edit_submission_page_label;
$page_vars["form_info"] = $form_info;
$page_vars["form_id"] = $form_id;
$page_vars["view_id"] = $view_id;
$page_vars["submission_id"] = $submission_id;
$page_vars["tabs"] = $tabs;
$page_vars["settings"] = $settings;
$page_vars["tab_number"] = $tab_number;
$page_vars["grouped_fields"] = $grouped_fields;
$page_vars["field_types"] = $page_field_types;
$page_vars["previous_link_html"] = $prev_link_html;
$page_vars["page_has_required_fields"] = $page_has_required_fields;
$page_vars["search_results_link_html"] = $search_results_link_html;
$page_vars["next_link_html"] = $next_link_html;
$page_vars["tab_has_editable_fields"] = count($editable_tab_fields) > 0;
$page_vars["view_info"] = $view_info;
$page_vars["edit_submission_page_label"] = $edit_submission_page_label;
$page_vars["page_field_ids"] = $page_field_ids;
$page_vars["page_field_ids_str"] = implode(",", $page_field_ids);
$page_vars["js_messages"] = array("confirm_delete_submission", "notify_no_email_template_selected", "confirm_delete_submission_file",
  "phrase_please_confirm", "word_no", "word_yes", "word_close", "phrase_validation_error");
$page_vars["head_string"] =<<< EOF
  <script src="$g_root_url/global/scripts/manage_submissions.js"></script>
  <script src="$g_root_url/global/scripts/field_types.php"></script>
  <link rel="stylesheet" href="$g_root_url/global/css/field_types.php" type="text/css" />
$shared_resources
EOF;
$page_vars["head_js"] =<<< END
$validation_js
END;


ft_display_page("admin/forms/edit_submission.tpl", $page_vars);
