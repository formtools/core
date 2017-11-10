<?php

require_once("global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Settings;
use FormTools\Themes;
use FormTools\Upgrade;

if (!Core::checkConfigFileExists()) {
    General::redirect("install/");
}
Core::init();

$upgrade_info = Upgrade::upgrade();

// only verify the core tables exist if there wasn't a problem upgrading
//if (!($g_upgrade_info["upgraded"] && !$g_upgrade_info["success"])) {
//    General::verifyCoreTablesExist();
//}

// if this user is already logged in, redirect them to their specified login page
if (Core::$user->isLoggedIn()) {
    Core::$user->redirectToLoginPage();
}

$settings = Settings::get();
$id = General::getLoginOverrideId();

$error = "";
if (isset($_POST["username"]) && !empty($_POST["username"])) {
    $error = Core::$user->login($_POST);
}

$username = General::stripChars((isset($_POST["username"]) && !empty($_POST["username"])) ? $_POST["username"] : "");
$LANG = Core::$L;

$replacements = array(
    "program_name"         => $settings["program_name"],
    "forgot_password_link" => "forget_password.php" . (!empty($id) ? "?id={$id}" : "")
);


// compile the variables for use in the templates
$page = array(
    "page" => "login",
    "page_url" => Pages::getPageUrl("login"),
    "settings" => Settings::get(),
    "head_title" => $LANG["phrase_admin_panel"],
    "error" => $error,
    "text_login" => General::evalSmartyString($LANG["text_login"], $replacements),
    "program_name" => $settings["program_name"],
    "login_heading" => sprintf("%s %s", $settings['program_name'], $LANG["word_administration"]),
    "username" => $username,
    "is_logged_in" => false,
    "head_string" => "<noscript><style type=\"text/css\">.login_outer_table { display: none; }</style></noscript>"
);

if ($upgrade_info["upgraded"]) {
    $replacements = array("version" => Core::getVersionString());
    $page["upgrade_notification"] = General::evalSmartyString($LANG["text_upgraded"], $replacements);
}

//if (!isset($g_upgrade_info["message"]) && isset($_GET["message"])) {
//    $success = false;
//
//    if (array_key_exists($_GET["message"], $LANG)) {
//        $message = $LANG[$_GET["message"]];
//
//    // this provides a simple mechanism for module developers to output their own messages on the index
//    // page (e.g. if they're forbidding a user from logging in & need to notify them)
//    } else {
//        $message = strip_tags($_GET["message"]);
//    }
//
//    $page["success"] = false;
//    $page["message"] = $message;
//}

Themes::displayPage("index.tpl", $page, Core::$user->getTheme(), Core::$user->getSwatch());
