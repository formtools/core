<?php

use FormTools\Accounts;
use FormTools\Clients;
use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Themes;
use FormTools\Sessions;

$LANG = Core::$L;

$account_id = Sessions::get("account.account_id");
$g_success = true;
$g_message = "";
if (isset($request["update_account_settings"])) {
	$request["page"] = "settings";
	list($g_success, $g_message) = Clients::updateClient($account_id, $request);
}

$client_info = Accounts::getAccountInfo($account_id);

$text_date_formatting_link = General::evalSmartyString($LANG["text_date_formatting_link"], array(
    "datefunctionlink" => '<a href="http://ca3.php.net/manual/en/function.date.php" target="_blank">date()</a>'
));

$js = array("var rules = []");
if ($client_info["settings"]["may_edit_page_titles"] == "yes")
    $js[] = "rules.push(\"required,page_titles,{$LANG["validation_no_titles"]}\")";
if ($client_info["settings"]["may_edit_theme"] == "yes") {
    $js[] = "rules.push(\"required,theme,{$LANG["validation_no_theme"]}\")";
    $js[] = "rules.push(\"function,validate_swatch\")";
}
if ($client_info["settings"]["may_edit_logout_url"] == "yes")
    $js[] = "rules.push(\"required,logout_url,{$LANG["validation_no_logout_url"]}\")";
if ($client_info["settings"]["may_edit_language"] == "yes")
    $js[] = "rules.push(\"required,ui_language,{$LANG["validation_no_ui_language"]}\")";
if ($client_info["settings"]["may_edit_timezone_offset"] == "yes")
    $js[] = "rules.push(\"required,timezone_offset,{$LANG["validation_no_timezone_offset"]}\")";
if ($client_info["settings"]["may_edit_sessions_timeout"] == "yes") {
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

$page_vars = array(
    "g_success"   => $g_success,
    "g_message"   => $g_message,
    "head_title"  => General::evalSmartyString(Sessions::get("account.settings.page_titles"), array("page" => $LANG["phrase_account_settings"])),
    "page"        => "settings",
    "text_date_formatting_link" => $text_date_formatting_link,
    "tabs"        => $tabs,
    "client_info" => $client_info,
    "page_url"    => Pages::getPageUrl("client_account_settings"),
    "head_js"     => implode(";\n", $js)
);

Themes::displayPage("clients/account/index.tpl", $page_vars);
