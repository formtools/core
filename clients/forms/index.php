<?php

require("../../global/session_start.php");
ft_check_permission("client");

$request = array_merge($_POST, $_GET);
$account_id = $_SESSION["ft"]["account"]["account_id"];

// include the relevant modules
if (ft_check_module_enabled("image_manager"))
  ft_include_module("image_manager");
if (ft_check_module_enabled("export_manager"))
	ft_include_module("export_manager");


// if the form ID is specified in GET or POST, store it in sessions as curr_form_id
$form_id = ft_load_field("form_id", "curr_form_id");
if (empty($form_id))
{
  session_write_close();
  header("location: index.php");
  exit;
}

$view_id = ft_load_field("view", "form_{$form_id}_view_id");

// this returns all and ONLY the Views accessible by this client
$form_views = ft_get_form_views($form_id, $account_id);

// check the current client is permitted to view this information!
ft_check_client_may_view($account_id, $form_id, $view_id);


if (empty($view_id))
{
	if (count($form_views) == 0)
	{
		// no Views defined for this client
		ft_handle_error($LANG["notify_no_views_assigned_to_client_form"], "", "notify");
		exit;
	}
	else
	{
		$default_view_id = $form_views[0]["view_id"];
		$_SESSION["ft"]["form_{$form_id}_view_id"] = $default_view_id;
		$view_id = $default_view_id;
	}
}

$form_info = ft_get_form($form_id);
$view_info = ft_get_view($view_id);

ft_check_client_may_view($account_id, $form_id, $view_id);


// if the View just changed (i.e. it was just selected by the user), deselect any items in
// this form
if (isset($request["view"]))
{
  $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
  $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();
  $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";
}

if (isset($_GET["reset"]) && $_GET["reset"] == "1")
{
	unset($_SESSION["ft"]["search_field"]);
	unset($_SESSION["ft"]["search_keyword"]);
	unset($_SESSION["ft"]["search_date"]);
	unset($_SESSION["ft"]["current_search"]);

	$_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
  $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();
  $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";
}

$search_field   = ft_load_field("search_field", "search_field", "");
$search_date    = ft_load_field("search_date", "search_date", "");
$search_keyword = ft_load_field("search_keyword", "search_keyword", "");

$search_fields = array(
  "search_field"   => $search_field,
  "search_date"    => $search_date,
  "search_keyword" => $search_keyword
);

if (isset($_GET["delete"]))
{
  // if delete actually a value, it's being fed a submission ID from the edit submission page
  // in order to delete it
  if (!empty($_GET["delete"]))
  {
    list($g_success, $g_message) = ft_delete_submission($form_id, $view_id, $_GET["delete"], true);
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


$display_fields = ft_get_submission_field_info($view_info["fields"]);

// used to tell the search function which columns to return
$search_columns  = array();
foreach ($display_fields as $field_info)
  $search_columns[] = $field_info["col_name"];


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

// perform the almighty search query
$results_info = ft_search_submissions($form_id, $view_id, $results_per_page, $current_page, $order, $search_columns, $search_fields);

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
  header("location: index.php");
}

// this sets the total number of submissions that the admin can see in this form and View in the form_X_num_submissions
// and view_X_num_submissions keys. It's used to generate the list of searchable dates
_ft_cache_form_stats($form_id);
_ft_cache_view_stats($form_id, $view_id);

if (!isset($_SESSION["ft"]["form_{$form_id}_select_all_submissions"]))
  $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";

// get a list of all submission IDs in this page
$submission_ids = array();
for ($i=0; $i<count($search_rows); $i++)
	$submission_ids[] = $search_rows[$i]["submission_id"];

$submission_id_str = join(",", $submission_ids);


// set as STRING for used in JS below
$select_all_submissions_returned = ($_SESSION["ft"]["form_{$form_id}_select_all_submissions"] == "1") ? "true" : "false";

// figure out which submissions should be selected on page load
$preselected_subids = array();
$all_submissions_selected_omit_list_str = "";
if ($select_all_submissions_returned == "true")
{
	$all_submissions_selected_omit_list = isset($_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]) ?
	   $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] : array();

	$all_submissions_selected_omit_list_str = join(",", $all_submissions_selected_omit_list);
  $preselected_subids = array_diff($submission_ids, $all_submissions_selected_omit_list);
}
else
	$preselected_subids = isset($_SESSION["ft"]["form_{$form_id}_selected_submissions"]) ? $_SESSION["ft"]["form_{$form_id}_selected_submissions"] : array();

$preselected_subids_str = join(",", $preselected_subids);

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars = array();
$page_vars["page"]    = "form";
$page_vars["page_url"] = ft_get_page_url("client_form_submissions", array("form_id" => $form_id));
$page_vars["head_title"]  = $LANG["word_submissions"];
$page_vars["form_info"]   = $form_info;
$page_vars["form_id"]     = $form_id;
$page_vars["view_id"]     = $view_id;
$page_vars["search_rows"] = $search_rows;
$page_vars["search_num_results"] = $search_num_results;
$page_vars["view_num_results"] = $view_num_results;
$page_vars["total_form_submissions"] = $_SESSION["ft"]["form_{$form_id}_num_submissions"];
$page_vars["form_views"]  = $form_views;
$page_vars["view_info"]   = $view_info;
$page_vars["preselected_subids"] = $preselected_subids;
$page_vars["results_per_page"]   = $results_per_page;
$page_vars["display_fields"]     = $display_fields;
$page_vars["order"]              = $order;
$page_vars["curr_search_fields"] = $_SESSION["ft"]["current_search"]["search_fields"];
$page_vars["pagination"]  = ft_get_page_nav($search_num_results, $results_per_page, $current_page, "");
$page_vars["js_messages"] = array("validation_select_rows_to_view", "validation_select_rows_to_download", "validation_select_submissions_to_delete",
        "confirm_delete_submission", "confirm_delete_submissions", "phrase_select_all_X_results",
  			"phrase_select_all_on_page", "phrase_all_X_results_selected", "phrase_row_selected", "phrase_rows_selected");
$page_vars["head_string"] = '<script type="text/javascript" src="../../global/scripts/manage_submissions.js"></script>';
$page_vars["head_js"] = "
var rules = [];
rules.push(\"if:search_field!=submission_date,required,search_keyword,{$LANG["validation_please_enter_search_keyword"]}\");
rules.push(\"if:search_field=submission_date,required,search_date,{$LANG["validation_please_enter_search_date_range"]}\");

if (typeof ms == \"undefined\")
  ms = {};

ms.page_submission_ids = [$submission_id_str]; // the submission IDs on the current page
ms.all_submissions_on_page_selected = null; // boolean; set on page load
ms.all_submissions_in_result_set_selected = $select_all_submissions_returned;
ms.selected_submission_ids = [$preselected_subids_str]; // regardless of page; only populated if all_submissions_in_result_set_selected == false
ms.all_submissions_selected_omit_list = [$all_submissions_selected_omit_list_str]; // if all submissions in result set selected, the unselected rows (for this page only!) are stored here
ms.search_num_results = $search_num_results; // the total number of View-search results, regardless of page
ms.form_id = $form_id;
ms.num_results_per_page = $results_per_page;

Event.observe(document, 'dom:loaded', function() {
  ms.init_page();
  $$('#submissions_table tr').invoke('observe', 'click',
    function(e)
		{
		  // TODO: fix this for Safari. Also, does it work with image manager? all browsers?
		  var el = Event.element(e);
		  if (el.nodeName != \"INPUT\" && el.nodeName != \"A\")
		  {
		    var tr = ft.get_ancestor_node(el, \"TR\"); // there's already a Prototype function for this
			  if (tr.id)
			  {
			    var result = tr.id.match(/^submission_row_(\d+)/);
			    var sub_id = result[1];
			    $('submission_cb_' + sub_id).checked = ($('submission_cb_' + sub_id).checked) ? false : true;
			    ms.select_row(sub_id, ms.num_results_per_page);
			  }
      }
		});

		// add an ellipsis to all custom fields to prevent widening the table too much
    $$('.ellipsis').each(ellipsis);
  });
";


ft_display_page("clients/forms/index.tpl", $page_vars);