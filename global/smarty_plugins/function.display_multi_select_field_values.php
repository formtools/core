<?php

use FormTools\Core;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_multi_select_field_values
 * Type:     function
 * Name:     form_dropdown
 * Purpose:  used to display the values of any form of multi-select field: dropdowns, multi-select dropdowns,
 *           checkboxes and radio buttons.
 * -------------------------------------------------------------
 */
function smarty_function_display_multi_select_field_values($params, &$smarty)
{
	$multi_val_delimiter = Core::getMultiFieldValDelimiter();

	// technically, options CAN be empty: this can happen if the admin just set the field type to a multi-value
	// type (radio, checkboxes, select, multi-select) but didn't assign a field option group
    if (!Templates::hasRequiredParams($smarty, $params, array("options"))) {
        return "";
    }

    $options  = (isset($params["options"])) ? $params["options"] : "";
    $var_name = (isset($params["var_name"])) ? $params["var_name"] : "";

    // contains the raw field VALUES (not display values), separated by $g_multi_val_delimiter
    $values = (isset($params["values"])) ? $params["values"] : "";

	$values = explode("$multi_val_delimiter", $values);
	$display_vals = array();
	foreach ($options as $option) {
	  if (in_array($option["option_value"], $values))
	    $display_vals[] = $option['option_name'];
	}

	$cell_value = join(", ", $display_vals);

    if (!empty($var_name)) {
        $smarty->assign($var_name, $cell_value);
    } else {
        return $cell_value;
    }
}
