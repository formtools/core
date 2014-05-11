<?php

require("../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);
$form_id = ft_load_field("form_id", "form_id", "");

if (!ft_check_form_exists($form_id))
{
  header("location: index.php");
  exit;
}

// store the current selected tab in memory - except for pages which require additional
// query string info. For those, use the parent page
if (isset($request["page"]) && !empty($request["page"]))
{
  $remember_page = $request["page"];
  switch ($remember_page)
  {
    case "field_options":
    case "files":
      $remember_page = "fields";
      break;
    case "edit_email":
      $remember_page = "emails";
      break;
  }

  $_SESSION["ft"]["form_{$form_id}_tab"] = $remember_page;
  $page = $request["page"];
}
else
{
  $page = ft_load_field("page", "form_{$form_id}_tab", "edit_form_main");
}

if (isset($request['edit_email_user_settings']))
{
  header("Location: edit.php?page=email_settings");
  exit;
}

$view_submissions_link = "submissions.php?form_id={$form_id}";
if (isset($_SESSION["ft"]["last_link_page_{$form_id}"]) && isset($_SESSION["ft"]["last_submission_id_{$form_id}"]) &&
  $_SESSION["ft"]["last_link_page_{$form_id}"] == "edit")
{
  $view_submissions_link = "edit_submission.php?form_id={$form_id}&submission_id={$_SESSION["ft"]["last_submission_id_{$form_id}"]}";
}

$same_page = ft_get_clean_php_self();
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

$tabs = ft_module_override_data("admin_edit_form_tabs", $tabs);

$order     = ft_load_field("order", "form_sort_order", "form_name-ASC");
$keyword   = ft_load_field("keyword", "form_search_keyword", "");
$status    = ft_load_field("status", "form_search_status", "");
$client_id = ft_load_field("client_id", "form_search_client_id", "");
$search_criteria = array(
  "order"     => $order,
  "keyword"   => $keyword,
  "status"    => $status,
  "client_id" => $client_id,

  // this is a bit weird, but it's used so that ft_get_form_prev_next_links() doesn't return incomplete forms:
  // they're not fully set up, so the Edit pages wouldn't work for them yet
  "is_admin"  => false
    );

$links = ft_get_form_prev_next_links($form_id, $search_criteria);
$prev_tabset_link = (!empty($links["prev_form_id"])) ? "edit.php?page=$page&form_id={$links["prev_form_id"]}" : "";
$next_tabset_link = (!empty($links["next_form_id"])) ? "edit.php?page=$page&form_id={$links["next_form_id"]}" : "";

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
switch ($page)
{
  case "main":
    require("page_main.php");
    break;
  case "public_form_omit_list":
    require("page_public_form_omit_list.php");
    break;
  case "fields":
    require("page_fields.php");
    break;
  case "files":
    require("page_files.php");
    break;
  case "views":
    require("page_views.php");
    break;
  case "edit_view":
    require("page_edit_view.php");
    break;
  case "public_view_omit_list":
    require("page_public_view_omit_list.php");
    break;
  case "emails":
    require("page_emails.php");
    break;
  case "email_settings":
    require("page_email_settings.php");
    break;
  case "edit_email":
    require("page_edit_email.php");
    break;

  default:
  	$vals = ft_module_override_data("admin_edit_form_page_name_include", array("page_name" => "page_main.php"));
    require($vals["page_name"]);
    break;
}

