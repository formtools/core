<?php

require("../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);

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
}

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars = array();
$page_vars["page"] = "add_client";
$page_vars["page_url"] = ft_get_page_url("add_client");
$page_vars["head_title"] = $LANG["phrase_add_client"];
$page_vars["head_js"] = "var rules = [];
rules.push(\"required,first_name,{$LANG['validation_no_client_first_name']}\");
rules.push(\"required,last_name,{$LANG['validation_no_client_first_name']}\");
rules.push(\"required,email,{$LANG['validation_no_client_email']}\");
rules.push(\"valid_email,email,{$LANG['validation_invalid_email']}\");
rules.push(\"required,username,{$LANG['validation_no_client_username']}\");
rules.push(\"is_alpha,username,{$LANG['validation_invalid_client_username']}\");
rules.push(\"required,password,{$LANG['validation_no_client_password']}\");
rules.push(\"is_alpha,password,{$LANG['validation_invalid_client_password']}\");
rules.push(\"same_as,password,password_2,{$LANG['validation_passwords_different']}\");

Event.observe(document, 'dom:loaded', function() { $(\"first_name\").focus(); });
  ";

ft_display_page("admin/clients/add.tpl", $page_vars);