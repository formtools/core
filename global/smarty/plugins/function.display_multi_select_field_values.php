<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.forms_dropdown
 * Type:     function
 * Name:     form_dropdown
 * Purpose:  generates a dropdown of all forms in the database (ordered by form name).
 * -------------------------------------------------------------
 */
function smarty_function_display_multi_select_field_values($params, &$smarty)
{
	global $g_multi_val_delimiter;

	// technically, options CAN be empty: this can happen if the admin just set the field type to a multi-value
	// type (radio, checkboxes, select, multi-select) but didn't assign a field option group
	if (!isset($params["options"]))
  {
	  $smarty->trigger_error("assign: missing 'options' parameter. This contains all available multi-select options.");
    return;
  }
  $options = (isset($params["options"])) ? $params["options"] : "";
  $var_name = (isset($params["var_name"])) ? $params["var_name"] : "";

  // contains the raw field VALUES (not display values), separated by $g_multi_val_delimiter
  $values   = (isset($params["values"])) ? $params["values"] : "";


	$values = explode("$g_multi_val_delimiter", $values);
	$display_vals = array();
	foreach ($options as $option)
	{
	  if (in_array($option["option_value"], $values))
	    $display_vals[] = $option['option_name'];
	}

	$cell_value = join(", ", $display_vals);

  if (!empty($var_name))
	  $smarty->assign($var_name, $cell_value);
	else
	  return $cell_value;
}

?>