<?php

if (isset($_POST['update_client']))
  list($g_success, $g_message) = ft_admin_update_client($request, 2);

// if required, update the list of available languages
if (isset($_GET["refresh_lang_list"]))
  list($g_success, $g_message) = ft_refresh_language_list();

$client_info = ft_get_account_info($client_id);
$forms       = ft_search_forms($client_id);

$replacement_info = array("datefunctionlink" => '<a href="http://ca3.php.net/manual/en/function.date.php" target="_blank">date()</a>');

// -------------------------------------------------------------------------------------------

// compile header information
$page_vars = array();
$page_vars["page"] = "settings";
$page_vars["page_url"] = ft_get_page_url("edit_client_settings", array("client_id" => $client_id));
$page_vars["tabs"] = $tabs;
$page_vars["head_title"]   = "{$LANG["phrase_edit_client"]} - {$LANG["word_settings"]}";
$page_vars["client_info"] = $client_info;
$page_vars["forms"]       = $forms;
$page_vars["client_id"]   = $client_id;
$page_vars["text_date_formatting_link"] = ft_eval_smarty_string($LANG["text_date_formatting_link"], $replacement_info);

$page_vars["head_js"] = "
var rules = [];
rules.push(\"required,page_titles,{$LANG["validation_no_titles"]}\");
rules.push(\"required,menu_id,{$LANG["validation_no_menu"]}\");
rules.push(\"required,theme,{$LANG["validation_no_theme"]}\");
rules.push(\"required,login_page,{$LANG["validation_no_client_login_page"]}\");
rules.push(\"required,logout_url,{$LANG["validation_no_logout_url"]}\");
rules.push(\"required,ui_language,{$LANG["validation_no_ui_language"]}\");
rules.push(\"required,sessions_timeout,{$LANG["validation_no_sessions_timeout"]}\");
rules.push(\"digits_only,sessions_timeout,{$LANG["validation_invalid_sessions_timeout"]}\");
rules.push(\"required,date_format,{$LANG["validation_no_date_format"]}\");

Event.observe(document, 'dom:loaded', function() { $(\"page_titles\").focus(); });";

ft_display_page("admin/clients/edit.tpl", $page_vars);