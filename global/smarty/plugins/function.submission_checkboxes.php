<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.submission_checkboxes
 * Type:     function
 * Name:     submission_radios
 * Purpose:  generates one or more checkboxes for a particular submission field, aligned vertically
 *           or horizontally with the appropriate values and labels.
 * -------------------------------------------------------------
 */
function smarty_function_submission_checkboxes($params, &$smarty)
{
	global $g_multi_val_delimiter;

	if (empty($params["name"]))
  {
	  $smarty->trigger_error("assign: missing 'name' parameter. This is used to give the select field a name value.");
    return;
  }
  if (empty($params["field_id"]))
  {
	  $smarty->trigger_error("assign: missing 'field_id' parameter. This is used to give the select field a field_id value.");
    return;
  }
  
  $name          = $params["name"];
  $field_id      = $params["field_id"];
  $selected_vals = (isset($params["selected"])) ? explode("$g_multi_val_delimiter", $params["selected"]) : array();
  $orientation   = (isset($params["orientation"])) ? $params["orientation"] : "";
	$option_info   = ft_get_field_options($field_id);
	$pagebreak     = ($orientation == "vertical") ? "<br />" : "";
  $is_editable = (isset($params["is_editable"])) ? $params["is_editable"] : "yes";

	$count = 1;
	$selected_values = array();
	$dd_str = "";
	foreach ($option_info as $option)
	{
		// generate a unique ID for this option (used for the label)
		$id = "field{$field_id}_$count";

		$dd_str .= "<input type=\"checkbox\" name=\"{$name}[]\" value=\"{$option['option_value']}\" id=\"$id\"";
		if (in_array($option['option_value'], $selected_vals))
		{
			$selected_values[] = $option['option_name'];
			$dd_str .= " checked";
		}
		$dd_str .= "><label for=\"$id\">{$option['option_name']}</label>$pagebreak\n";
		
		$count++;
	}

	if ($is_editable == "no")
	  echo join($g_multi_val_delimiter, $selected_values);
	else
	  echo $dd_str;
}

?> 