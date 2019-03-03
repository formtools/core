<?php

require_once("../../global/library.php");

use FormTools\Administrator;
use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Settings;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$post_values = array();

$success = true;
$message = "";
if (isset($_POST) && !empty($_POST['add_client'])) {
	list($success, $message, $new_account_id) = Administrator::addClient($request);

	// if added, redirect to the manage client page
	if ($success) {
	    General::redirect("edit.php?page=main&client_id=$new_account_id");
		exit;
	} else {
		$post_values = $_POST;
	}
}

$LANG = Core::$L;
$settings = Settings::get();
$conditional_validation = array();
if (!empty($settings["min_password_length"])) {
	$rule = General::evalSmartyString($LANG["validation_client_password_too_short"], array("number" => $settings["min_password_length"]));
	$conditional_validation[] = "rules.push(\"if:password!=,length>={$settings["min_password_length"]},password,$rule\");";
}

$required_password_chars = explode(",", $settings["required_password_chars"]);
if (in_array("uppercase", $required_password_chars)) {
    $conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[A-Z],{$LANG["validation_client_password_missing_uppercase"]}\")";
}
if (in_array("number", $required_password_chars)) {
    $conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[0-9],{$LANG["validation_client_password_missing_number"]}\")";
}
if (in_array("special_char", $required_password_chars)) {
	$error = General::evalSmartyString($LANG["validation_client_password_missing_special_char"], array("chars" => $g_password_special_chars));
	$password_special_chars = preg_quote($g_password_special_chars);
	$conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[$password_special_chars],$error\")";
}
$conditional_rules = implode("\n", $conditional_validation);
$core_password_special_chars = Core::getRequiredPasswordSpecialChars();


$head_js =<<<END
var rules = [];
rules.push("required,first_name,{$LANG['validation_no_client_first_name']}");
rules.push("required,last_name,{$LANG['validation_no_client_first_name']}");
rules.push("required,email,{$LANG['validation_no_client_email']}");
rules.push("valid_email,email,{$LANG['validation_invalid_email']}");
rules.push("required,username,{$LANG['validation_no_client_username']}");
rules.push("function,validate_username");
rules.push("required,password,{$LANG['validation_no_client_password']}");
rules.push("same_as,password,password_2,{$LANG['validation_passwords_different']}");
$conditional_rules

function validate_username() {
    var username = $("input[name=username]").val();
    if (username.match(/[^\.@a-zA-Z0-9_]/)) {
        return [[$("input[name=username]")[0], "{$LANG['validation_invalid_client_username']}"]];
    }
    return true;
}
$(function() { $("#first_name").focus(); });
END;

// compile the header information
$page_vars = array(
    "page" => "add_client",
    "g_success" => $success,
    "g_message" => $message,
    "page_url" => Pages::getPageUrl("add_client"),
    "head_title" => $LANG["phrase_add_client"],
    "required_password_chars" => $required_password_chars,
    "password_special_chars" => $core_password_special_chars,
    "has_extra_password_requirements" => (!empty($settings["required_password_chars"]) || !empty($settings["min_password_length"])),
    "has_min_password_length" => !empty($settings["min_password_length"]),
    "password_special_char" => General::evalSmartyString($LANG["phrase_password_special_char"], array("chars" => $core_password_special_chars)),
    "phrase_password_min" => General::evalSmartyString($LANG["phrase_password_min"], array("length" => $settings["min_password_length"])),
    "vals" => $post_values,
    "head_js" => $head_js
);

Themes::displayPage("admin/clients/add.tpl", $page_vars);
