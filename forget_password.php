<?php

require_once("global/library.php");

use FormTools\Accounts;
use FormTools\Administrator;
use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Settings;
use FormTools\Themes;

Core::init();

$settings = Settings::get();

$admin_info = Administrator::getAdminInfo();
$admin_email = $admin_info["email"];

$id = General::getLoginOverrideId();

$LANG = Core::$L;

// if trying to send password
$success = true;
$message = "";
if (isset($_POST) && !empty($_POST)) {
    list ($success, $message) = Accounts::sendPassword($_POST);
}

$username = General::stripChars((isset($_POST["username"]) && !empty($_POST["username"])) ? $_POST["username"] : "");
$replacements = array("site_admin_email" => "<a href=\"mailto:$admin_email\">$admin_email</a>");

$head_js =<<<END
var rules = [];
rules.push("required,username,{$LANG['validation_no_username']}");
$(function() { document.forget_password.username.focus(); });
END;

$page_vars = array(
    "text_forgot_password" => General::evalSmartyString($LANG["text_forgot_password"], $replacements),
    "g_success" => $success,
    "g_message" => $message,
    "head_title" => $settings["program_name"],
    "page" => "forgot_password",
    "page_url" => Pages::getPageUrl("forgot_password"),
    "settings" => $settings,
    "username" => $username,
    "head_js" => $head_js
);

Themes::displayPage("forget_password.tpl", $page_vars, Core::$user->getTheme(), Core::$user->getSwatch());
