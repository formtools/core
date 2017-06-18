<?php

use FormTools\Fields;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.submission_dropdown
 * Type:     function
 * Name:     submission_dropdown
 * Purpose:  generates a submission field dropdown with the appropriate default value. If the is_editable
 *           parameter is defined and set to "no", it just displays the selected value, not the dropdown.
 * -------------------------------------------------------------
 */
function smarty_function_submission_dropdown($params, &$smarty)
{
    if (!Templates::hasRequiredParams($smarty, $params, array("name", "field_id"))) {
        return "";
    }

    $name        = $params["name"];
    $field_id    = $params["field_id"];
    $selected    = (isset($params["selected"])) ? $params["selected"] : "";
    $is_editable = (isset($params["is_editable"])) ? $params["is_editable"] : "yes";

	$option_info = Fields::getFieldOptions($field_id);

	$selected_value = "";
	$dd_str = "<select name=\"$name\">";
	foreach ($option_info as $option) {
		$dd_str .= "<option value=\"{$option['option_value']}\"";
		if ($option['option_value'] == $selected) {
			$dd_str .= " selected";
			$selected_value = $option['option_name'];
		}

		$dd_str .= ">{$option['option_name']}</option>\n";
	}
	$dd_str .= "</select>";

    if ($is_editable == "no") {
        echo $selected_value;
    } else {
        echo $dd_str;
    }
}
