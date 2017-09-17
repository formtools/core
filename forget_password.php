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
$LANG = Core::$L;

$settings = Settings::get();
$theme = $settings['default_theme'];

$admin_info = Administrator::getAdminInfo();
$admin_email = $admin_info["email"];

// if a user id is included in the query string, use it to determine the appearance of the
// interface (including logo)
//$id = General::loadField("id", "id", "");
//
//if (!empty($id)) {
//    $info = Accounts::getAccountInfo($id);
//
//    if (!empty($info)) {
//        $theme  = $info['theme'];
//        $language = $info["ui_language"];
//        include_once("global/lang/{$language}.php");
//    }
//}

// if trying to send password
$g_success = true;
$g_message = "";
if (isset($_POST) && !empty($_POST)) {
    list($g_success, $g_message) = Accounts::sendPassword($_POST);
}

$username = (isset($_POST["username"]) && !empty($_POST["username"])) ? $_POST["username"] : "";
$username = General::stripChars($username);

$replacements = array("site_admin_email" => "<a href=\"mailto:$admin_email\">$admin_email</a>");

$head_js =<<<END
var rules = [];
rules.push("required,username,{$LANG['validation_no_username']}");
$(function() { document.forget_password.username.focus(); });
END;

$page_vars = array(
    "text_forgot_password" => General::evalSmartyString($LANG["text_forgot_password"], $replacements),
    "g_success" => $g_success,
    "g_message" => $g_message,
    "head_title" => $settings["program_name"],
    "page" => "forgot_password",
    "page_url" => Pages::getPageUrl("forgot_password"),
    "settings" => $settings,
    "username" => $username,
    "head_js" => $head_js,
    "query_params" => ""
);

Themes::displayPage("forget_password.tpl", $page_vars, $theme);
