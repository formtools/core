<?php

use FormTools\Core;
use FormTools\Fields;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.submission_dropdown_multiple
 * Type:     function
 * Name:     submission_dropdown
 * Purpose:  generates a submission field dropdown with the appropriate default value. If the is_editable
 *           parameter is defined and set to "no", it just displays the selected value, not the dropdown.
 * -------------------------------------------------------------
 */
function smarty_function_submission_dropdown_multiple($params, &$smarty)
{
    $multi_val_delimiter = Core::getMultiFieldValDelimiter();

    if (!Templates::hasRequiredParams($smarty, $params, array("name", "field_id"))) {
        return;
    }

    $name        = $params["name"];
    $field_id    = $params["field_id"];
    $is_editable = (isset($params["is_editable"])) ? $params["is_editable"] : "yes";
    $selected_vals = (isset($params["selected"])) ? explode("$multi_val_delimiter", $params["selected"]) : array();
	

	$option_info = Fields::getFieldOptions($field_id);

	$dd_str = "<select name=\"{$name}[]\" multiple size=\"4\">";
	foreach ($option_info as $option) {
		$dd_str .= "<option value='{$option['option_value']}'";
		if (in_array($option['option_value'], $selected_vals)) {
            $dd_str .= " selected";
        }
		$dd_str .= ">{$option['option_name']}</option>\n";
	}
	$dd_str .= "</select>";

	if ($is_editable == "no") {
        echo $params["selected"];
    } else {
        echo $dd_str;
    }
}
