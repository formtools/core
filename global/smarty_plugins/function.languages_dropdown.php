<?php

use FormTools\Core;
use FormTools\Settings;
use FormTools\Templates;


/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.languages_dropdown
 * Type:     function
 * Name:     themes_dropdown
 * Purpose:  displays a list of available languages.
 * -------------------------------------------------------------
 */
function smarty_function_languages_dropdown($params, &$smarty)
{
	$LANG = Core::$L;

    if (!Templates::hasRequiredParams($smarty, $params, array("name_id"))) {
        return "";
    }

    $default_value   = (isset($params["default"])) ? $params["default"] : "";
    $onchange        = (isset($params["onchange"])) ? $params["onchange"] : "";

    $attributes = array(
        "id"   => $params["name_id"],
        "name" => $params["name_id"],
        "onchange" => $onchange
    );

    $attribute_str = "";
	foreach ($attributes as $key => $value) {
        if (!empty($value)) {
            $attribute_str .= " $key=\"$value\"";
        }
    }

    $available_language_info = Settings::get("available_languages");
    $available_language_arr = explode("|", $available_language_info);

	$html = "<select $attribute_str>
	           <option value=\"\">{$LANG["phrase_please_select"]}</option>";
	foreach ($available_language_arr as $lang_info) {
		list($lang_file, $lang_display) = explode(",", $lang_info);
		$selected = ($default_value == $lang_file) ? "selected" : "";
        $html .= "<option value=\"$lang_file\" {$selected}>$lang_display</option>\n";
	}
    $html .= "</select>";

    return $html;
}
