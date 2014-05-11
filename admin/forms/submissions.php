<?php

require("../../global/session_start.php");
ft_check_permission("admin");
$request = array_merge($_POST, $_GET);

// if the form ID is specified in GET or POST, store it in sessions as curr_form_id
$form_id = ft_load_field("form_id", "curr_form_id");
if (empty($form_id) || !is_numeric($form_id))
{
  session_write_close();
  header("location: index.php");
  exit;
}

// check this is a valid form
if (!ft_check_form_exists($form_id))
{
  ft_handle_error($LANG["notify_form_does_not_exist"]);
  exit;
}

// next, get the View. If it's not defined, the user has just arrives at the page. We grab the first View in
// (ordered) list of Views for this form. If THAT doesn't exist, the user has deleted all Views (doh!), so
// there's nothing to show. In that case, just redirect them to the Views tab, where an error / warning message
// will appear in the page
$view_id       = ft_load_field("view_id", "form_{$form_id}_view_id");
$grouped_views = ft_get_grouped_views($form_id, array("omit_hidden_views" => true, "omit_empty_groups" => true));

if (empty($view_id) || !ft_check_view_exists($view_id, true))
{
  // here, we know that the first View group has at least one item. [hmm...]
  if (count($grouped_views[0]["views"]) == 0)
  {
    // no Views defined for this form! redirect to the Views page and display a message
    header("location: edit.php?page=views&form_id=$form_id&message=no_views");
    exit;
  }
  else
  {
    $view_id = $grouped_views[0]["views"][0]["view_id"];
  }
}

$_SESSION["ft"]["form_{$form_id}_view_id"] = $view_id;
$_SESSION["ft"]["last_link_page_{$form_id}"] = "submissions";

$form_info   = ft_get_form($form_id);
$form_fields = ft_get_form_fields($form_id, array("include_field_type_info" => true, "include_field_settings" => true));
$view_info   = ft_get_view($view_id);

if (isset($_GET["add_submission"]) && $view_info["may_add_submissions"] == "yes")
{
  $submission_id = ft_create_blank_submission($form_id, $view_id, true);
  header("location: edit_submission.php?form_id=$form_id&view_id=$view_id&submission_id=$submission_id&message=new_submission");
  exit;
}

// if the View just changed (i.e. it was just selected by the user), deselect any form rows
if (isset($request["view_id"]))
{
  $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
  $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();
  $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";
}

// Fix for bug #174
$has_search_info_for_other_form = (isset($_SESSION["ft"]["current_search"]) && $_SESSION["ft"]["current_search"]["form_id"] != $form_id);
$is_resetting_search            = (isset($_GET["reset"]) && $_GET["reset"] == "1");

if ($is_resetting_search || $has_search_info_for_other_form)
{
  unset($_SESSION["ft"]["search_field"]);
  unset($_SESSION["ft"]["search_keyword"]);
  unset($_SESSION["ft"]["search_date"]);
  unset($_SESSION["ft"]["current_search"]);

  // only empty the memory of selected submission ID info if the user just reset the search
  if ($is_resetting_search)
  {
    $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
    $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();
    $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";
  }
}
$search_fields = array(
  "search_field"   => ft_load_field("search_field", "search_field", ""),
  "search_date"    => ft_load_field("search_date", "search_date", ""),
  "search_keyword" => ft_load_field("search_keyword", "search_keyword", "")
);

if (isset($_GET["delete"]))
{
  // if delete actually a value, it's being fed a submission ID from the edit submission page
  // in order to delete it
  if (!empty($_GET["delete"]))
  {
    $ids = explode(",", $_GET["delete"]);
    foreach ($ids as $id)
    {
      list($g_success, $g_message) = ft_delete_submission($form_id, $view_id, $id, true);
    }
  }
  else
  {
    $delete_all = (isset($_SESSION["ft"]["form_{$form_id}_select_all_submissions"]) && $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] == 1) ? true : false;
    $submissions_to_delete = $_SESSION["ft"]["form_{$form_id}_selected_submissions"];
    $omit_list = array();
    if ($delete_all)
    {
      $submissions_to_delete = "all";
      $omit_list = $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"];
    }

    list($g_success, $g_message) = ft_delete_submissions($form_id, $view_id, $submissions_to_delete, $omit_list, $search_fields, true);
  }
}

// figure out the current page
$current_page = ft_load_field("page", "view_{$view_id}_page", 1);
if (isset($_POST["search"]))
  $current_page = 1;

// make a map of field_id => col_name for use in determining the search cols. This contains
// all the fields in the View
$all_view_field_columns = array();
$searchable_columns  = array();

foreach ($view_info["fields"] as $field_info)
{
  $all_view_field_columns[$field_info["field_id"]] = $field_info["col_name"];
  if ($field_info["is_searchable"] == "yes")
    $searchable_columns[] = $field_info["col_name"];
}
$db_columns = array_values($all_view_field_columns); // used for the search query

// with 2.1.0, users can now assign fields to be columns on the Submission Listing page but not actually
// include them in the list of fields to appear in the View. This section tacks on those columns so
// that they're included in the Almighty Search Query
foreach ($view_info["columns"] as $column_info)
{
  $curr_field_id = $column_info["field_id"];
  $curr_col_name = "";
  foreach ($form_fields as $field_info)
  {
    if ($field_info["field_id"] == $curr_field_id)
    {
      $curr_col_name = $field_info["col_name"];
      break;
    }
  }

  if (!array_key_exists($curr_col_name, $db_columns) && !empty($curr_col_name))
  {
  	$db_columns[] = $curr_col_name;
  }
}

// display_fields contains ALL the information we need for the fields in the template, i.e. a composite
// of the view column, view field and form field information. For this page it only contains fields marked
// as columns. The submissions.tpl template also needs a bunch of other stuff, but for passing to the
// {display_custom_field} smarty function, actually very field keys are needed (see description in
// ft_generate_viewable_field)
$display_fields = array();
foreach ($view_info["columns"] as $col_info)
{
  $curr_field_id = $col_info["field_id"];
  $data_to_merge = $col_info;
  foreach ($view_info["fields"] as $view_field_info)
  {
    if ($view_field_info["field_id"] != $curr_field_id)
      continue;

    $data_to_merge = array_merge($view_field_info, $data_to_merge);
  }

  foreach ($form_fields as $form_field_info)
  {
    if ($form_field_info["field_id"] != $curr_field_id)
      continue;

    $data_to_merge = array_merge($form_field_info, $data_to_merge);
  }

  $display_fields[] = $data_to_merge;
}


// determine the sort order
if (isset($_GET["order"]))
{
  $_SESSION["ft"]["view_{$view_id}_sort_order"] = $_GET["order"];
  $order = $_GET["order"];
}
else
{
  if (isset($_SESSION["ft"]["view_{$view_id}_sort_order"]))
    $order = $_SESSION["ft"]["view_{$view_id}_sort_order"];
  else
    $order = "{$view_info['default_sort_field']}-{$view_info['default_sort_field_order']}";
}

$results_per_page = $view_info["num_submissions_per_page"];

// perform the almighty search query [urgh. Too many params...!]
$results_info = ft_search_submissions($form_id, $view_id, $results_per_page, $current_page, $order, $db_columns,
      $search_fields, array(), $searchable_columns);

$search_rows        = $results_info["search_rows"];
$search_num_results = $results_info["search_num_results"];
$view_num_results   = $results_info["view_num_results"];

// store the current search settings. This information is used on the item details page to provide
// "<< previous  next >>" links that only apply to the CURRENT search result set
$_SESSION["ft"]["new_search"] = "yes";
$_SESSION["ft"]["current_search"] = array(
    "form_id"          => $form_id,
    "results_per_page" => $results_per_page,
    "order"            => $order,
    "search_fields"    => $search_fields
      );

// check that the current page is stored in sessions is, in fact, a valid page. e.g. if the person
// was having 10 submissions listed per page, had 11 submissions, and was on page 2 before deleting
// the 11th, when they returned to this page, they'd have page 2 stored in sessions, although there
// is no longer a second page. So for this fringe case, we update the session and refresh the page to
// load the appropriate page
$total_pages = ceil($search_num_results / $results_per_page);
if (isset($_SESSION["ft"]["view_{$view_id}_page"]) && $_SESSION["ft"]["view_{$view_id}_page"] > $total_pages)
{
  $_SESSION["ft"]["view_{$view_id}_page"] = $total_pages;
  header("location: submissions.php");
}

// this sets the total number of submissions that the admin can see in this form and View in the form_X_num_submissions
// and view_X_num_submissions keys
_ft_cache_form_stats($form_id);
_ft_cache_view_stats($form_id, $view_id);

if (!isset($_SESSION["ft"]["form_{$form_id}_select_all_submissions"]))
  $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";

// get a list of all submission IDs in this page
$submission_ids = array();
for ($i=0; $i<count($search_rows); $i++)
  $submission_ids[] = $search_rows[$i]["submission_id"];

$submission_id_str = implode(",", $submission_ids);

// set as STRING for used in JS below
$select_all_submissions_returned = ($_SESSION["ft"]["form_{$form_id}_select_all_submissions"] == "1") ? "true" : "false";

// figure out which submissions should be selected on page load
$preselected_subids = array();
$all_submissions_selected_omit_list_str = "";
if ($select_all_submissions_returned == "true")
{
  $all_submissions_selected_omit_list = isset($_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]) ?
     $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] : array();

  $all_submissions_selected_omit_list_str = implode(",", $all_submissions_selected_omit_list);
  $preselected_subids = array_diff($submission_ids, $all_submissions_selected_omit_list);
}
else
  $preselected_subids = isset($_SESSION["ft"]["form_{$form_id}_selected_submissions"]) ? $_SESSION["ft"]["form_{$form_id}_selected_submissions"] : array();

$preselected_subids_str = implode(",", $preselected_subids);

// to pass to the smarty template
$field_types = ft_get_field_types(true);

$has_searchable_field = false;
foreach ($view_info["fields"] as $field_info)
{
  if ($field_info["is_searchable"] == "yes")
  {
  	$has_searchable_field = true;
  	break;
  }
}

$settings = ft_get_settings("", "core");

$date_picker_info = ft_get_default_date_field_search_value($settings["default_date_field_search_value"]);
$default_date_field_search_value = $date_picker_info["default_date_field_search_value"];
$date_field_search_js_format     = $date_picker_info["date_field_search_js_format"];

// get all the shared resources
$shared_resources_list = ft_get_settings("edit_submission_onload_resources");
$shared_resources_array = explode("|", $shared_resources_list);
$shared_resources = "";
foreach ($shared_resources_array as $resource)
{
  $shared_resources .= ft_eval_smarty_string($resource, array("g_root_url" => $g_root_url)) . "\n";
}

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars = array();
$page_vars["page"]    = "admin_forms";
$page_vars["page_url"] = ft_get_page_url("form_submissions", array("form_id" => $form_id));
$page_vars["head_title"]  = $LANG["word_submissions"];
$page_vars["form_info"]   = $form_info;
$page_vars["form_id"]     = $form_id;
$page_vars["view_id"]     = $view_id;
$page_vars["default_date_field_search_value"] = $default_date_field_search_value;
$page_vars["search_rows"] = $search_rows;
$page_vars["search_num_results"] = $search_num_results;
$page_vars["view_num_results"] = $view_num_results;
$page_vars["total_form_submissions"] = $_SESSION["ft"]["form_{$form_id}_num_submissions"];
$page_vars["grouped_views"] = $grouped_views;
$page_vars["view_info"]     = $view_info;
$page_vars["settings"]      = $settings;
$page_vars["preselected_subids"] = $preselected_subids;
$page_vars["results_per_page"]   = $results_per_page;
$page_vars["display_fields"]    = $display_fields;
$page_vars["page_submission_ids"] = $submission_id_str;
$page_vars["order"] = $order;
$page_vars["field_types"] = $field_types;
$page_vars["has_searchable_field"] = $has_searchable_field;
$page_vars["notify_view_missing_columns_admin_fix"] = ft_eval_smarty_string($LANG["notify_view_missing_columns_admin_fix"], array("LINK" => "edit.php?form_id={$form_id}&view_id={$view_id}&page=edit_view&edit_view_tab=2"));
$page_vars["curr_search_fields"] = $_SESSION["ft"]["current_search"]["search_fields"];
$page_vars["pagination"]  = ft_get_page_nav($search_num_results, $results_per_page, $current_page, "");
$page_vars["js_messages"] = array("validation_select_rows_to_view", "validation_select_rows_to_download",
        "validation_select_submissions_to_delete", "confirm_delete_submission", "confirm_delete_submissions",
        "phrase_select_all_X_results", "phrase_select_all_on_page", "phrase_all_X_results_selected",
        "phrase_row_selected", "phrase_rows_selected", "confirm_delete_submissions_on_other_pages",
        "confirm_delete_submissions_on_other_pages2", "word_yes", "word_no", "phrase_please_confirm",
        "validation_please_enter_search_keyword", "notify_invalid_search_dates");
$page_vars["head_string"] =<<< END
<link rel="stylesheet" href="../../global/css/ui.daterangepicker.css" type="text/css" />
<script src="../../global/scripts/manage_submissions.js"></script>
<script src="../../global/scripts/daterangepicker.jquery.js"></script>
<script src="$g_root_url/global/scripts/field_types.php"></script>
<link rel="stylesheet" href="$g_root_url/global/css/field_types.php" type="text/css" />
$shared_resources
END;

$page_vars["head_js"] =<<< END
var rules = [];
rules.push("function,ms.check_search_keyword");
rules.push("function,ms.check_valid_date");
if (typeof ms == "undefined") {
  ms = {};
}

ms.page_submission_ids = [$submission_id_str]; // the submission IDs on the current page
ms.all_submissions_on_page_selected = null; // boolean; set on page load
ms.all_submissions_in_result_set_selected = $select_all_submissions_returned;
ms.selected_submission_ids = [$preselected_subids_str]; // regardless of page; only populated if all_submissions_in_result_set_selected == false
ms.all_submissions_selected_omit_list = [$all_submissions_selected_omit_list_str]; // if all submissions in result set selected, the unselected rows (for this page only!) are stored here
ms.search_num_results = $search_num_results; // the total number of View-search results, regardless of page
ms.form_id = $form_id;
ms.view_id = $view_id;
ms.num_results_per_page = $results_per_page;

$(function() {
  ms.init_submissions_page();
  if ($("#search_field").length) {
    ms.change_search_field($("#search_field").val());
    $("#search_field").bind("keyup change", function() {
      ms.change_search_field(this.value);
    });
  }
  if ($("#search_date").length) {
    $("#search_date").daterangepicker({
      dateFormat: "$date_field_search_js_format",
      doneButtonText: "{$LANG["word_done"]}",
      presetRanges: [
        {text: '{$LANG["word_today"]}', dateStart: 'today', dateEnd: 'today' },
        {text: '{$LANG["phrase_last_7_days"]}', dateStart: 'today-7days', dateEnd: 'today' },
        {text: '{$LANG["phrase_month_to_date"]}', dateStart: function(){ return Date.parse('today').moveToFirstDayOfMonth();  }, dateEnd: 'today' },
        {text: '{$LANG["phrase_year_to_date"]}', dateStart: function(){ var x= Date.parse('today'); x.setMonth(0); x.setDate(1); return x; }, dateEnd: 'today' },
        {text: '{$LANG["phrase_the_previous_month"]}', dateStart: function(){ return Date.parse('1 month ago').moveToFirstDayOfMonth();  }, dateEnd: function(){ return Date.parse('1 month ago').moveToLastDayOfMonth();  } }
      ],
      datepickerOptions: {
        changeYear: true,
        changeMonth: true
      }
    });
  }
});
END;


ft_display_page("admin/forms/submissions.tpl", $page_vars);
