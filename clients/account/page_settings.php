<?php

if (isset($request["update_account_settings"]))
{
  $account_id = $_SESSION["ft"]["account"]["account_id"];
  $request["page"] = "settings";
  list($g_success, $g_message) = ft_update_client($account_id, $request);
}

$client_info = ft_get_account_info($account_id);

// compile header information
$page_vars = array();
$page_vars["head_title"]  = ft_eval_smarty_string($_SESSION["ft"]["account"]["settings"]["page_titles"], array("page" => $LANG["phrase_account_settings"]));
$page_vars["page"]        = "settings";
$page_vars["tabs"]        = $tabs;
$page_vars["client_info"] = $client_info;
$page_vars["page_url"]    = ft_get_page_url("client_account_settings");

$js = array("var rules = []");
if ($client_info["settings"]["may_edit_page_titles"] == "yes")
  $js[] = "rules.push(\"required,page_titles,{$LANG["validation_no_titles"]}\")";
if ($client_info["settings"]["may_edit_theme"] == "yes")
{
  $js[] = "rules.push(\"required,theme,{$LANG["validation_no_theme"]}\")";
  $js[] = "rules.push(\"function,validate_swatch\")";
}
if ($client_info["settings"]["may_edit_logout_url"] == "yes")
  $js[] = "rules.push(\"required,logout_url,{$LANG["validation_no_logout_url"]}\")";
if ($client_info["settings"]["may_edit_language"] == "yes")
  $js[] = "rules.push(\"required,ui_language,{$LANG["validation_no_ui_language"]}\")";
if ($client_info["settings"]["may_edit_timezone_offset"] == "yes")
  $js[] = "rules.push(\"required,timezone_offset,{$LANG["validation_no_timezone_offset"]}\")";
if ($client_info["settings"]["may_edit_sessions_timeout"] == "yes")
{
  $js[] = "rules.push(\"required,sessions_timeout,{$LANG["validation_no_sessions_timeout"]}\")";
  $js[] = "rules.push(\"digits_only,sessions_timeout,{$LANG["validation_invalid_sessions_timeout"]}\")";
}
if ($client_info["settings"]["may_edit_date_format"] == "yes")
  $js[] = "rules.push(\"required,date_format,{$LANG["validation_no_date_format"]}\")";

$js[] =<<< END
function validate_swatch() {
  var theme     = $("#theme").val();
  var swatch_id = "#" + theme + "_theme_swatches";
  if ($(swatch_id).length > 0 && $(swatch_id).val() == "") {
    return [[$(swatch_id)[0], "{$LANG["validation_no_theme_swatch"]}"]];
  }
  return true;
}
END;

$page_vars["head_js"] = implode(";\n", $js);


ft_display_page("clients/account/index.tpl", $page_vars);