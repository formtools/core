<?php

session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");

$g_default_language       = "en_us.php";
$g_ft_installation_folder = dirname(__FILE__);

// for future: this information should be extracted from sql.php
$g_ft_tables = array(
  "accounts",
  "account_settings",
  "client_forms",
  "client_views",
  "email_templates",
  "email_template_edit_submission_views",
  "email_template_recipients",
  "field_options",
  "field_settings",
  "field_types",
  "field_type_settings",
  "field_type_setting_options",
  "forms",
  "form_email_fields",
  "form_fields",
  "hooks",
  "list_groups",
  "menus",
  "menu_items",
  "modules",
  "module_export_groups",
  "module_export_group_clients",
  "module_export_types",
  "module_menu_items",
  "module_pages",
  "multi_page_form_urls",
  "new_view_submission_defaults",
  "option_lists",
  "public_form_omit_list",
  "public_view_omit_list",
  "sessions",
  "settings",
  "themes",
  "views",
  "view_columns",
  "view_fields",
  "view_filters",
  "view_tabs"
);

// all session information for the installation script is stored in the $_SESSION["ft_install"] key
if (!isset($_SESSION["ft_install"]))
  $_SESSION["ft_install"] = array();

// include the language file
if (!isset($_SESSION["ft_install"]["lang_file"]))
  $_SESSION["ft_install"]["lang_file"] = $g_default_language;

$g_defer_init_page = true;
require_once("../global/library.php");
require_once("files/code.php");
require_once("files/sql.php");

$lang_file = ft_load_field("lang_file", "lang_file", $g_default_language, "ft_install");
include("../global/lang/{$_SESSION["ft_install"]["lang_file"]}");
