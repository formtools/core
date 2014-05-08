<?php

if (isset($request["update"]))
{
  $request["page"] = "main";
  list($g_success, $g_message) = ft_update_client($account_id, $request);
}
$client_info = ft_get_account_info($account_id);

// compile header information
$page_vars = array();
$page_vars["head_title"] = ft_eval_smarty_string($_SESSION["ft"]["account"]["settings"]["page_titles"], array("page" => $LANG["phrase_login_info"]));
$page_vars["page"]     = "main";
$page_vars["tabs"]     = $tabs;
$page_vars["client_info"] = $client_info;
$page_vars["page_url"] = ft_get_page_url("client_account");
$page_vars["head_js"] =<<< EOF
var rules = [];
rules.push("required,first_name,{$LANG["validation_no_first_name"]}");
rules.push("required,last_name,{$LANG["validation_no_last_name"]}");
rules.push("required,email,{$LANG["validation_no_email"]}");
rules.push("required,username,{$LANG["validation_no_username"]}");
rules.push("is_alpha,username,{$LANG['validation_invalid_username']}");
rules.push("if:password!=,is_alpha,password,{$LANG['validation_invalid_password']}");
rules.push("if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed"]}");
rules.push("if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}");
EOF;

ft_display_page("clients/account/index.tpl", $page_vars);