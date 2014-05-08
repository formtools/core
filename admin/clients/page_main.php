<?php

// update this client
if (isset($_POST["update_client"]))
  list($g_success, $g_message) = ft_admin_update_client($request, 1);

$client_info = ft_get_account_info($client_id);

// -------------------------------------------------------------------------------------------

// compile header information
$page_vars = array();
$page_vars["page"] = "main";
$page_vars["page_url"] = ft_get_page_url("edit_client_main", array("client_id" => $client_id));
$page_vars["tabs"] = $tabs;
$page_vars["head_title"]   = "{$LANG["phrase_edit_client"]} - {$LANG["word_main"]}";
$page_vars["client_info"]  = $client_info;
$page_vars["client_id"]    = $client_id;
$page_vars["head_js"] =<<< EOF
var rules = [];
rules.push("required,first_name,{$LANG['validation_no_client_first_name']}");
rules.push("required,last_name,{$LANG['validation_no_client_last_name']}");
rules.push("required,email,{$LANG['validation_no_client_email']}");
rules.push("valid_email,email,{$LANG['validation_invalid_email']}");
rules.push("required,username,{$LANG["validation_no_username"]}");
rules.push("is_alpha,username,{$LANG["validation_invalid_client_username"]}");
rules.push("if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed"]}");
rules.push("if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}");

Event.observe(document, 'dom:loaded', function() { $("add_client").focusFirstElement(); });
EOF;

ft_display_page("admin/clients/edit.tpl", $page_vars);