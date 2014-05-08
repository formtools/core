<?php

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

  $name        = $params["name"];
  $field_id    = $params["field_id"];
  $selected    = (isset($params["selected"])) ? $params["selected"] : "";
	$is_editable = (isset($params["is_editable"])) ? $params["is_editable"] : "yes";

	$option_info = ft_get_field_options($field_id);

	$selected_value = "";
	$dd_str = "<select name=\"$name\">";
	foreach ($option_info as $option)
	{
		$dd_str .= "<option value=\"{$option['option_value']}\"";
		if ($option['option_value'] == $selected)
		{
			$dd_str .= " selected";
			$selected_value = $option['option_name'];
		}

		$dd_str .= ">{$option['option_name']}</option>\n";
	}
	$dd_str .= "</select>";

	if ($is_editable == "no")
	  echo $selected_value;
	else
	  echo $dd_str;
}

?>