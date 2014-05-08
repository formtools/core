<?php

if (isset($request["update_main"]))
  list($g_success, $g_message) = ft_update_main_settings($_POST);

$beta_version = $_SESSION["ft"]["settings"]["beta_version"];
$replacement_info = array("version" => $beta_version);

// compile the header information
$page_vars = array();
$page_vars["page"] = "main";
$page_vars["page_url"] = ft_get_page_url("settings_main");
$page_vars["tabs"] = $tabs;
$page_vars["head_title"] = "{$LANG["word_settings"]} - {$LANG["word_main"]}";
$page_vars["ft_version"] = ft_get_version_as_number();
$page_vars["is_beta"]    = $_SESSION["ft"]["settings"]["is_beta"];
$page_vars["beta_version"] = $beta_version;
$page_vars["beta_version_str"] = ft_eval_smarty_string($LANG["phrase_beta_version"], $replacement_info);
$page_vars["upgrade_info"] = $_SESSION["ft"]["upgrade_info"];

$replacement_info = array("datefunctionlink" => '<a href="http://ca3.php.net/manual/en/function.date.php" target="_blank">date()</a>');
$page_vars["text_date_formatting_link"] = ft_eval_smarty_string($LANG["text_date_formatting_link"], $replacement_info);

$page_vars["head_js"] = "
	var rules = [];
	rules.push(\"required,program_name,{$LANG["validation_no_program_name"]}\");
	rules.push(\"required,num_clients_per_page,{$LANG["validation_no_num_clients_per_page"]}\");
	rules.push(\"digits_only,num_clients_per_page,{$LANG["validation_invalid_num_clients_per_page"]}\");
	rules.push(\"required,num_emails_per_page,{$LANG["validation_no_num_emails_per_page"]}\");
	rules.push(\"digits_only,num_emails_per_page,{$LANG["validation_invalid_num_emails_per_page"]}\");
	rules.push(\"required,num_forms_per_page,{$LANG["validation_no_num_forms_per_page"]}\");
	rules.push(\"digits_only,num_forms_per_page,{$LANG["validation_invalid_num_forms_per_page"]}\");
	rules.push(\"required,num_field_option_groups_per_page,{$LANG["validation_no_num_field_option_groups_per_page"]}\");
	rules.push(\"digits_only,num_field_option_groups_per_page,{$LANG["validation_invalid_num_field_option_groups_per_page"]}\");
	rules.push(\"required,num_menus_per_page,{$LANG["validation_no_num_menus_per_page"]}\");
	rules.push(\"digits_only,num_menus_per_page,{$LANG["validation_invalid_num_menus_per_page"]}\");
	rules.push(\"required,num_views_per_page,{$LANG["validation_no_num_views_per_page"]}\");
	rules.push(\"digits_only,num_views_per_page,{$LANG["validation_invalid_num_views_per_page"]}\");
	";

ft_display_page("admin/settings/index.tpl", $page_vars);