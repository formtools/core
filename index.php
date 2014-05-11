<?php

require_once("global/session_start.php");
ft_verify_form_tools_installed();
$g_upgrade_info = ft_upgrade_form_tools();

// only verify the core tables exist if there wasn't a problem upgrading
if (!($g_upgrade_info["upgraded"] && !$g_upgrade_info["success"]))
{
  ft_verify_core_tables_exist();
}

// if this user is already logged in, redirect them to their specified login page
if (isset($_SESSION["ft"]["account"]) && isset($_SESSION["ft"]["account"]["is_logged_in"]) &&
  isset($_SESSION["ft"]["account"]["login_page"]) && $_SESSION["ft"]["account"]["is_logged_in"] == 1)
{
  $login_page = $_SESSION["ft"]["account"]["login_page"];
  $page = ft_construct_page_url($login_page);
  header("location: {$g_root_url}$page");
  exit;
}

// default settings
$settings = ft_get_settings();
$g_theme  = $settings["default_theme"];
$g_swatch = $settings["default_client_swatch"];

// if an account id is included in the query string, use it to determine the appearance of the
// interface, including logo and footer and even language
$id = ft_load_field("id", "id", "");

if (!empty($id))
{
  $info = ft_get_account_info($id);
  if (isset($info["account_id"]))
  {
    // just in case, boot up the appropriate language file (this overrides any language file already loaded)
    $g_theme  = $info["theme"];
    $language = $info["ui_language"];
    if (!empty($language) && is_file("global/lang/{$language}.php"))
      include_once("global/lang/{$language}.php");
  }
}

$error = "";
if (isset($_POST["username"]) && !empty($_POST["username"]))
  $error = ft_login($_POST);

$username = (isset($_POST["username"]) && !empty($_POST["username"])) ? $_POST["username"] : "";
$username = ft_strip_chars($username);

// -------------------------------------------------------------------------------------------

// compile the variables for use in the templates
$page_vars = array();
$page_vars["page"] = "login";
$page_vars["page_url"] = ft_get_page_url("login");
$page_vars["head_title"] = $LANG["phrase_admin_panel"];
$page_vars["error"] = $error;

if ($g_upgrade_info["upgraded"])
{
  if ($g_upgrade_info["success"])
  {
    $new_version = $settings["program_version"];
    if ($settings["release_type"] == "alpha")
      $new_version = "{$settings['program_version']}-alpha-{$settings['release_date']}";
    else if ($settings["release_type"] == "beta")
      $new_version = "{$settings['program_version']}-beta-{$settings['release_date']}";

    $replacements = array("version" => $new_version);
    $page_vars["upgrade_notification"] = ft_eval_smarty_string($LANG["text_upgraded"], $replacements, $g_theme);
  }
  else
  {
  	$g_success = false;
  	$g_message = $g_upgrade_info["message"];
  }
}
$replacements = array(
  "program_name"         => $settings["program_name"],
  "forgot_password_link" => "forget_password.php"
    );

$page_vars["text_login"] = ft_eval_smarty_string($LANG["text_login"], $replacements, $g_theme);
$page_vars["program_name"]  = $settings["program_name"];
$page_vars["login_heading"] = sprintf("%s %s", $settings['program_name'], $LANG["word_administration"]);
$page_vars["username"]      = $username;
$page_vars["is_logged_in"]  = false;
$page_vars["head_js"]  = "$(function() { document.login.username.focus(); });";
$page_vars["head_string"] = "<noscript><style type=\"text/css\">.login_outer_table { display: none; }</style></noscript>";

if (!isset($g_upgrade_info["message"]) && isset($_GET["message"]))
{
  $g_success = false;

  if (array_key_exists($_GET["message"], $LANG))
    $g_message = $LANG[$_GET["message"]];

  // this provides a simple mechanism for module developers to output their own messages on the index
  // page (e.g. if they're forbidding a user from logging in & need to notify them)
  else
    $g_message = strip_tags($_GET["message"]);
}

ft_display_page("index.tpl", $page_vars, $g_theme, $g_swatch);
