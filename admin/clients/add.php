<?php

require("../../global/session_start.php");
ft_check_permission("admin");
$request = array_merge($_POST, $_GET);

$post_values = array();
if (isset($_POST) && !empty($_POST['add_client']))
{
  list($g_success, $g_message, $new_account_id) = ft_add_client($request);

  // if added, redirect to the manage client page
  if ($g_success)
  {
    session_write_close();
    header("Location: edit.php?page=main&client_id=$new_account_id");
    exit;
  }
  else
  {
    $post_values = $_POST;
  }
}

$settings = ft_get_settings();
$conditional_validation = array();
if (!empty($settings["min_password_length"]))
{
  $rule = ft_eval_smarty_string($LANG["validation_client_password_too_short"], array("number" => $settings["min_password_length"]));
  $conditional_validation[] = "rules.push(\"if:password!=,length>={$settings["min_password_length"]},password,$rule\");";
}

$required_password_chars = explode(",", $settings["required_password_chars"]);
if (in_array("uppercase", $required_password_chars))
  $conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[A-Z],{$LANG["validation_client_password_missing_uppercase"]}\")";
if (in_array("number", $required_password_chars))
  $conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[0-9],{$LANG["validation_client_password_missing_number"]}\")";
if (in_array("special_char", $required_password_chars))
{
  $error = ft_eval_smarty_string($LANG["validation_client_password_missing_special_char"], array("chars" => $g_password_special_chars));
  $password_special_chars = preg_quote($g_password_special_chars);
  $conditional_validation[] = "rules.push(\"if:password!=,reg_exp,password,[$password_special_chars],$error\")";
}
$conditional_rules = implode("\n", $conditional_validation);

// compile the header information
$page_vars = array();
$page_vars["page"] = "add_client";
$page_vars["page_url"] = ft_get_page_url("add_client");
$page_vars["head_title"] = $LANG["phrase_add_client"];
$page_vars["required_password_chars"] = $required_password_chars;
$page_vars["password_special_chars"] = $g_password_special_chars;
$page_vars["has_extra_password_requirements"] = (!empty($settings["required_password_chars"]) || !empty($settings["min_password_length"]));
$page_vars["has_min_password_length"] = !empty($settings["min_password_length"]);
$page_vars["password_special_char"] = ft_eval_smarty_string($LANG["phrase_password_special_char"], array("chars" => $g_password_special_chars));
$page_vars["phrase_password_min"] = ft_eval_smarty_string($LANG["phrase_password_min"], array("length" => $settings["min_password_length"]));
$page_vars["vals"] = $post_values;

$page_vars["head_js"] =<<<END
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

ft_display_page("admin/clients/add.tpl", $page_vars);