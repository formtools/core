<?php

use FormTools\Accounts;
use FormTools\Administrator;
use FormTools\Core;
use FormTools\General;
use FormTools\Themes;
use FormTools\Pages;

$LANG = Core::$L;
$req_password_special_chars = Core::getRequiredPasswordSpecialChars();

// update this client
$success = true;
$message = "";
if (isset($_POST["update_client"])) {
    list($success, $message) = Administrator::adminUpdateClient($request, 1);
}

$client_info = Accounts::getAccountInfo($client_id);
$conditional_validation = array();
if (!empty($client_info["settings"]["min_password_length"])) {
	$rule = General::evalSmartyString($LANG["validation_client_password_too_short"], array("number" => $client_info["settings"]["min_password_length"]));
	$conditional_validation[] = "rules.push(\"if:password!=,length>={$client_info["settings"]["min_password_length"]},password,$rule\");";
}

$required_password_chars = explode(",", $client_info["settings"]["required_password_chars"]);
if (in_array("uppercase", $required_password_chars)) {
    $conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[A-Z],{$LANG["validation_client_password_missing_uppercase"]}\")";
}
if (in_array("number", $required_password_chars)) {
    $conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[0-9],{$LANG["validation_client_password_missing_number"]}\")";
}
if (in_array("special_char", $required_password_chars)) {
	$error = General::evalSmartyString($LANG["validation_client_password_missing_special_char"], array("chars" => $req_password_special_chars));
	$password_special_chars = preg_quote($req_password_special_chars);
	$conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[$password_special_chars],$error\")";
}
$conditional_rules = implode("\n", $conditional_validation);


// define info for template
$page_vars["page"] = "main";
$page_vars["g_success"] = $success;
$page_vars["g_message"] = $message;
$page_vars["page_url"] = Pages::getPageUrl("edit_client_main", array("client_id" => $client_id));
$page_vars["head_title"]   = "{$LANG["phrase_edit_client"]} - {$LANG["word_main"]}";
$page_vars["client_info"]  = $client_info;
$page_vars["client_id"]    = $client_id;
$page_vars["required_password_chars"] = $required_password_chars;
$page_vars["password_special_chars"]  = $req_password_special_chars;
$page_vars["has_extra_password_requirements"] = (!empty($client_info["settings"]["required_password_chars"]) || !empty($client_info["settings"]["min_password_length"]));
$page_vars["has_min_password_length"] = !empty($client_info["settings"]["min_password_length"]);
$page_vars["password_special_char"] = General::evalSmartyString($LANG["phrase_password_special_char"], array("chars" => $req_password_special_chars));
$page_vars["phrase_password_min"]   = General::evalSmartyString($LANG["phrase_password_min"], array("length" => $client_info["settings"]["min_password_length"]));

$page_vars["head_js"] =<<<END
var rules = [];
rules.push("required,first_name,{$LANG['validation_no_client_first_name']}");
rules.push("required,last_name,{$LANG['validation_no_client_last_name']}");
rules.push("required,email,{$LANG['validation_no_client_email']}");
rules.push("valid_email,email,{$LANG['validation_invalid_email']}");
rules.push("required,username,{$LANG['validation_no_client_username']}");
rules.push("function,validate_username");
rules.push("if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed2"]}");
rules.push("if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}");
$conditional_rules

function validate_username() {
  var username = $("input[name=username]").val();
  if (username.match(/[^\.@a-zA-Z0-9_]/)) {
    return [[$("input[name=username]")[0], "{$LANG['validation_invalid_client_username']}"]];
  }
  return true;
}

$(function() { $("#add_client :input:visible:enabled:first").focus(); });
END;

Themes::displayPage("admin/clients/edit.tpl", $page_vars);
