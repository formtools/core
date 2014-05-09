<?php

require("../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);
$form_id = ft_load_field("form_id", "form_id", "");

if (isset($request["add_field"]))
{
	$request["page"] = "add_fields";
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
    case "images":
      $remember_page = "fields";
      break;
    case "add_fields":
      $remember_page = "database";
      break;
    case "edit_email":
      $remember_page = "emails";
      break;
  }

  $_SESSION["ft"]["form_{$form_id}_tab"] = $remember_page;
  $page = $request["page"];
}
else
  $page = ft_load_field("page", "form_{$form_id}_tab", "edit_form_main");


if (isset($request['edit_email_user_settings']))
{
  header("Location: edit.php?page=email_settings");
  exit;
}

$tabs = array(
  "main" => array(
      "tab_label" => $LANG["word_main"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=main",
      "pages" => array("main", "public_form_omit_list")
        ),
  "fields" => array(
      "tab_label" => $LANG["word_fields"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=fields",
      "pages" => array("field_options", "files", "images")
        ),
  "views" => array(
      "tab_label" => $LANG["word_views"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=views",
      "pages" => array("edit_view", "view_tabs", "public_view_omit_list")
        ),
  "emails" => array(
      "tab_label" => $LANG["word_emails"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=emails",
      "pages" => array("email_settings", "edit_email")
        ),
  "database" => array(
      "tab_label" => $LANG["word_database"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=database",
      "pages" => array("add_fields")
        )
    );

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
	case "field_options":
		require("page_field_options.php");
		break;
	case "files":
		require("page_files.php");
		break;
	case "images":
		require("$g_root_dir/modules/image_manager/page_images.php");
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
  case "database":
		require("page_database.php");
		break;
	case "add_fields":
		require("page_add_fields.php");
		break;

	default:
		require("page_main.php");
		break;
}

