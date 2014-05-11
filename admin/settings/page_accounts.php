<?php

if (isset($request["update_accounts"]))
  list($g_success, $g_message) = ft_update_account_settings($request);

// if required, update the list of available languages
if (isset($_GET["refresh_lang_list"]))
  list($g_success, $g_message) = ft_refresh_language_list();

$replacement_info = array("datefunctionlink" => '<a href="http://ca3.php.net/manual/en/function.date.php" target="_blank">date()</a>');

// ------------------------------------------------------------------------------------------------

// compile the theme vars
$page_vars = array();
$page_vars["page"] = "accounts";
$page_vars["page_url"] = ft_get_page_url("settings_accounts");
$page_vars["tabs"] = $tabs;
$page_vars["head_title"] = "{$LANG["word_settings"]} - {$LANG["word_accounts"]}";
$page_vars["text_date_formatting_link"] = ft_eval_smarty_string($LANG["text_date_formatting_link"], $replacement_info);
$page_vars["phrase_one_special_char"] = ft_eval_smarty_string($LANG["phrase_one_special_char"], array("chars" => $g_password_special_chars));
$page_vars["head_js"] =<<< END
var rules = [];
rules.push("required,default_page_titles,{$LANG["validation_no_page_titles"]}");
rules.push("required,default_client_menu_id,{$LANG["validation_no_menu_id"]}");
rules.push("required,default_theme,{$LANG["validation_no_theme"]}");
rules.push("function,validate_swatch");
rules.push("required,default_login_page,{$LANG["validation_no_login_page"]}");
rules.push("required,default_logout_url,{$LANG["validation_no_logout_url"]}");
rules.push("required,default_language,{$LANG["validation_no_default_language"]}");
rules.push("required,default_sessions_timeout,{$LANG["validation_no_default_sessions_timeout"]}");
rules.push("digits_only,default_sessions_timeout,{$LANG["validation_invalid_default_sessions_timeout"]}");
rules.push("required,default_date_format,{$LANG["validation_no_date_format"]}");

function validate_swatch() {
  var theme     = $("#default_theme").val();
  var swatch_id = "#" + theme + "_default_theme_swatches";
  if ($(swatch_id).length > 0 && $(swatch_id).val() == "") {
    return [[$(swatch_id)[0], "{$LANG["validation_no_theme_swatch"]}"]];
  }
  return true;
}

END;

ft_display_page("admin/settings/index.tpl", $page_vars);