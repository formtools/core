<?php

require("../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);
$page    = ft_load_field("page", "settings_page", "main");

// store the current selected tab in memory - except for pages which require additional
// query string info. For those, use the "parent" page
if (isset($request["page"]) && !empty($request["page"]))
{
  $remember_page = $request["page"];
  switch ($remember_page)
  {
    case "edit_admin_menu":
    case "edit_client_menu":
      $remember_page = "menus";
      break;
  }

  $_SESSION["ft"]["settings_tab"] = $remember_page;
  $page = $request["page"];
}
else
  $page = ft_load_field("page", "settings_tab", "main");


$tabs = array(
  "main" => array(
      "tab_label" => $LANG["word_main"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=main"
        ),
  "accounts" => array(
      "tab_label" => $LANG["word_accounts"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=accounts"
        ),
  "files" => array(
      "tab_label" => $LANG["word_files"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=files"
        ),
  "wysiwyg" => array(
      "tab_label" => $LANG["word_wysiwyg"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=wysiwyg"
        ),
  "menus" => array(
      "tab_label" => $LANG["word_menus"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=menus",
      "pages" => array("edit_admin_menu", "edit_client_menu")
        )
    );


switch ($page)
{
  case "main":
    require("page_main.php");
    break;
  case "accounts":
    require("page_accounts.php");
    break;
  case "files":
    require("page_files.php");
    break;
  case "wysiwyg":
    require("page_wysiwyg.php");
    break;
  case "menus":
    require("page_menus.php");
    break;
  case "edit_client_menu":
    require("page_edit_client_menu.php");
    break;
  case "edit_admin_menu":
    require("page_edit_admin_menu.php");
    break;

  default:
    require("page_main.php");
    break;
}