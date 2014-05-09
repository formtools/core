<?php

if (isset($request["update_main"]))
  list($g_success, $g_message) = ft_update_main_settings($_POST);

$page_vars = array();
$page_vars["page"] = "main";
$page_vars["page_url"] = ft_get_page_url("settings_main");
$page_vars["tabs"] = $tabs;
$page_vars["head_title"] = "{$LANG["word_settings"]} - {$LANG["word_main"]}";
$replacement_info = array("datefunctionlink" => '<a href="http://ca3.php.net/manual/en/function.date.php" target="_blank">date()</a>');
$page_vars["text_date_formatting_link"] = ft_eval_smarty_string($LANG["text_date_formatting_link"], $replacement_info);
$page_vars["head_js"] =<<<END
  var rules = [];
  rules.push("required,program_name,{$LANG["validation_no_program_name"]}");
  rules.push("required,num_clients_per_page,{$LANG["validation_no_num_clients_per_page"]}");
  rules.push("digits_only,num_clients_per_page,{$LANG["validation_invalid_num_clients_per_page"]}");
  rules.push("required,num_emails_per_page,{$LANG["validation_no_num_emails_per_page"]}");
  rules.push("digits_only,num_emails_per_page,{$LANG["validation_invalid_num_emails_per_page"]}");
  rules.push("required,num_forms_per_page,{$LANG["validation_no_num_forms_per_page"]}");
  rules.push("digits_only,num_forms_per_page,{$LANG["validation_invalid_num_forms_per_page"]}");
  rules.push("required,num_option_lists_per_page,{$LANG["validation_no_num_option_lists_per_page"]}");
  rules.push("digits_only,num_option_lists_per_page,{$LANG["validation_invalid_num_option_lists_per_page"]}");
  rules.push("required,num_menus_per_page,{$LANG["validation_no_num_menus_per_page"]}");
  rules.push("digits_only,num_menus_per_page,{$LANG["validation_invalid_num_menus_per_page"]}");
  rules.push("required,num_modules_per_page,{$LANG["validation_no_num_modules_per_page"]}");
  rules.push("digits_only,num_modules_per_page,{$LANG["validation_invalid_num_modules_per_page"]}");
END;

ft_display_page("admin/settings/index.tpl", $page_vars);