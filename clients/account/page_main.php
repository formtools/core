<?php

if (isset($request["update"]))
{
  $request["page"] = "main";
  list($g_success, $g_message) = ft_update_client($account_id, $request);
}
$client_info = ft_get_account_info($account_id);

$conditional_validation = array();
if (!empty($client_info["settings"]["min_password_length"]))
{
  $rule = ft_eval_smarty_string($LANG["validation_client_password_too_short"], array("number" => $client_info["settings"]["min_password_length"]));
  $conditional_validation[] = "rules.push(\"if:password!=,length>={$client_info["settings"]["min_password_length"]},password,$rule\");";
}

$required_password_chars = explode(",", $client_info["settings"]["required_password_chars"]);
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


// compile header information
$page_vars = array();
$page_vars["head_title"] = ft_eval_smarty_string($_SESSION["ft"]["account"]["settings"]["page_titles"], array("page" => $LANG["phrase_login_info"]));
$page_vars["page"]     = "main";
$page_vars["tabs"]     = $tabs;
$page_vars["client_info"] = $client_info;
$page_vars["page_url"] = ft_get_page_url("client_account");
$page_vars["required_password_chars"] = $required_password_chars;
$page_vars["password_special_chars"]  = $g_password_special_chars;
$page_vars["has_extra_password_requirements"] = (!empty($client_info["settings"]["required_password_chars"]) || !empty($client_info["settings"]["min_password_length"]));
$page_vars["has_min_password_length"] = !empty($client_info["settings"]["min_password_length"]);
$page_vars["password_special_char"] = ft_eval_smarty_string($LANG["phrase_password_special_char"], array("chars" => $g_password_special_chars));
$page_vars["phrase_password_min"]   = ft_eval_smarty_string($LANG["phrase_password_min"], array("length" => $client_info["settings"]["min_password_length"]));
$page_vars["head_js"] =<<< END
var rules = [];
rules.push("required,first_name,{$LANG["validation_no_first_name"]}");
rules.push("required,last_name,{$LANG["validation_no_last_name"]}");
rules.push("required,email,{$LANG["validation_no_email"]}");
rules.push("required,username,{$LANG["validation_no_username"]}");
rules.push("is_alpha,username,{$LANG['validation_invalid_username']}");
rules.push("if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed"]}");
rules.push("if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}");
$conditional_rules
END;

ft_display_page("clients/account/index.tpl", $page_vars);


