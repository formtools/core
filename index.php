<?php

require_once("global/library.php");

use FormTools\Accounts;
use FormTools\Core;
use FormTools\General;
use FormTools\Installation;
use FormTools\Pages;
use FormTools\Settings;
use FormTools\Themes;


Installation::checkInstalled();
Core::init();

//$g_upgrade_info = ft_upgrade_form_tools();

// only verify the core tables exist if there wasn't a problem upgrading
//if (!($g_upgrade_info["upgraded"] && !$g_upgrade_info["success"])) {
//    ft_verify_core_tables_exist();
//}

// if this user is already logged in, redirect them to their specified login page
if (Core::$user->isLoggedIn()) {
    Core::$user->redirectToLoginPage();
}

// default settings
$settings = Settings::get();
$theme  = $settings["default_theme"];
$swatch = $settings["default_client_swatch"];


// if an account id is included in the query string, use it to determine the appearance of the
// interface, including logo and footer and even language
//$id = General::loadField("id", "id", "");

//if (!empty($id)) {
//  $info = Accounts::getAccountInfo($id);
//  if (isset($info["account_id"])) {
//    // just in case, boot up the appropriate language file (this overrides any language file already loaded)
//    $theme  = $info["theme"];
//    $language = $info["ui_language"];
//    if (!empty($language) && is_file("global/lang/{$language}.php"))
//      include_once("global/lang/{$language}.php");
//  }
//}


$error = "";
if (isset($_POST["username"]) && !empty($_POST["username"]))
  $error = ft_login($_POST);

$username = (isset($_POST["username"]) && !empty($_POST["username"])) ? $_POST["username"] : "";
$username = General::stripChars($username);


// -------------------------------------------------------------------------------------------

$LANG = Core::$L;


$replacements = array(
    "program_name"         => $settings["program_name"],
    "forgot_password_link" => "forget_password.php"
);

// compile the variables for use in the templates
$page = array(
    "page" => "login",
    "page_url" => Pages::getPageUrl("login"),
    "head_title" => $LANG["phrase_admin_panel"],
    "error" => $error,
    "text_login" => General::evalSmartyString($LANG["text_login"], $replacements, $theme),
    "program_name" => $settings["program_name"],
    "login_heading" => sprintf("%s %s", $settings['program_name'], $LANG["word_administration"]),
    "username" => $username,
    "is_logged_in" => false,
    "head_js" => "$(function() { document.login.username.focus(); });",
    "head_string" => "<noscript><style type=\"text/css\">.login_outer_table { display: none; }</style></noscript>"
);


//if ($g_upgrade_info["upgraded"])
//{
//  if ($g_upgrade_info["success"])
//  {
//    $new_version = $settings["program_version"];
//    if ($settings["release_type"] == "alpha")
//      $new_version = "{$settings['program_version']}-alpha-{$settings['release_date']}";
//    else if ($settings["release_type"] == "beta")
//      $new_version = "{$settings['program_version']}-beta-{$settings['release_date']}";
//
//    $replacements = array("version" => $new_version);
//    $page_vars["upgrade_notification"] = General::evalSmartyString($LANG["text_upgraded"], $replacements, $g_theme);
//  }
//  else
//  {
//  	$g_success = false;
//  	$g_message = $g_upgrade_info["message"];
//  }
//}

if (!isset($g_upgrade_info["message"]) && isset($_GET["message"])) {
    $g_success = false;

    if (array_key_exists($_GET["message"], $LANG)) {
        $g_message = $LANG[$_GET["message"]];
    }

    // this provides a simple mechanism for module developers to output their own messages on the index
    // page (e.g. if they're forbidding a user from logging in & need to notify them)
    else {
        $g_message = strip_tags($_GET["message"]);
    }
}

Themes::displayPage("index.tpl", $page, $theme, $swatch);
