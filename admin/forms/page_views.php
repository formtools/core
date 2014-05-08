<?php

$form_info = ft_get_form($form_id);

if (isset($request["add_view"]))
{
	$duplicate_view_id = "";
	if (isset($request["create_view_from_view_id"]) && !empty($request["create_view_from_view_id"]))
	  $duplicate_view_id = $request["create_view_from_view_id"];

	$view_id = ft_create_new_view($form_id, $duplicate_view_id);

	// always set the default Edit View tab to the first one
	$_SESSION["ft"]["edit_view_tab"] = 1;

	session_write_close();
  header("Location: edit.php?page=edit_view&form_id=$form_id&view_id=$view_id");
  exit;
}

$view_id   = ft_load_field("view_id", "form_{$form_id}_view_id", $form_info["default_view_id"]);
$view_page = ft_load_field("view_page", "form_{$form_id}_view_page", 1);

if (isset($request["update_view"]))
{
	list($g_success, $g_message) = ft_update_view_filters($view_id, $request);
	list($g_success, $g_message) = ft_update_view($view_id, $request);
}

if (isset($request["update_view_order"]))
  list($g_success, $g_message) = ft_update_view_order($form_id, $request);

if (isset($request["delete_view"]))
{
  list($g_success, $g_message) = ft_delete_view($request["delete_view"]);

  if (isset($_SESSION["ft"]["form_{$form_id}_num_views"]))
  {
    $num_remaining_views = $_SESSION["ft"]["form_{$form_id}_num_views"] - 1;
    $max_num_pages = ceil($num_remaining_views / $_SESSION["ft"]["settings"]["num_views_per_page"]);

    if ($view_page > $max_num_pages)
    {
    	$_SESSION["ft"]["form_{$form_id}_view_page"] = $max_num_pages;
    	$view_page = $max_num_pages;
    }
  }
}

$form_view_info  = ft_get_views($form_id, $view_page);
$form_views      = $form_view_info["results"];
$num_form_views  = $form_view_info["num_results"];

// store the number of Views in sessions. This is used after deleting a View to figure out
$_SESSION["ft"]["form_{$form_id}_num_views"] = $num_form_views;


// a little irksome, but we also need to retrieve ALL views for the Create View From Existing View dropdown
$all_form_views = ft_get_form_views($form_id);

// ------------------------------------------------------------------------------------------------

// compile the templates information
$page_vars["page"]       = "views";
$page_vars["page_url"]   = ft_get_page_url("edit_form_views", array("form_id" => $form_id));
$page_vars["tabs"]       = $tabs;
$page_vars["form_id"]    = $form_id;
$page_vars["form_views"] = $form_views;
$page_vars["all_form_views"] = $all_form_views;
$page_vars["num_form_views"] = $num_form_views;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["word_views"]}";
$page_vars["form_info"]  = $form_info;


// build values to pass along in nav query string
$pass_along_str = "page=views&form_id=$form_id";
$page_vars["pagination"] = ft_get_page_nav($num_form_views, $_SESSION["ft"]["settings"]["num_views_per_page"], $view_page, $pass_along_str, "view_page");

$page_vars["head_js"]    = "
  var page_ns = {};
	page_ns.delete_view = function(view_id)
	{
	  var answer = confirm(\"{$LANG['confirm_delete_view']}\");

	  if (answer)
	    window.location = 'edit.php?page=views&form_id={$form_id}&delete_view=' + view_id;

	  return false;
	}
		";

ft_display_page("admin/forms/edit.tpl", $page_vars);
