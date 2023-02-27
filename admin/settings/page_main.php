<?php

use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Settings;
use FormTools\Themes;

$LANG = Core::$L;

General::updateApiVersion();

$success = true;
$message = "";
if (isset($request["update_main"])) {
    list($success, $message) = Settings::updateMainSettings($_POST);
}

$text_date_formatting_link = General::evalSmartyString($LANG["text_date_formatting_link"], array(
    "datefunctionlink" => '<a href="https://www.php.net/manual/en/function.date.php" target="_blank">date()</a>'
));

$head_js =<<<END
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

$page_vars = array(
    "page" => "main",
    "g_success" => $success,
    "g_message" => $message,
    "page_url" => Pages::getPageUrl("settings_main"),
    "tabs" => $tabs,
    "cache_folder" => Core::getCacheFolder(),
    "head_title" => "{$LANG["word_settings"]} - {$LANG["word_main"]}",
    "text_date_formatting_link" => $text_date_formatting_link,
    "head_js" => $head_js
);

Themes::displayPage("admin/settings/index.tpl", $page_vars);
