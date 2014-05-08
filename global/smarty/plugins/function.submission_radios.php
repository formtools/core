<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.submission_radios
 * Type:     function
 * Name:     submission_radios
 * Purpose:  generates a set of radio buttons for a particular submission field, in vertical or horizontal
 *           format, with all the trimmings (labels, default value, etc). If is_editable parameter is set to
 *           "no", it just displays the selected radio value.
 * -------------------------------------------------------------
 */
function smarty_function_submission_radios($params, &$smarty)
{
  global $LANG;

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

	$field_info    = ft_get_form_field($field_id, true);
  $field_group_id = $field_info["field_group_id"];
  $options = $field_info["options"];

	if (empty($field_group_id))
	{
	  echo "<div class=\"medium_grey\">{$LANG["notify_no_assigned_field_option_group"]}</div>";
		return;
	}

  $group_info = ft_get_field_option_group($field_group_id);
  $orientation = $group_info["field_orientation"];
  $pagebreak   = ($orientation == "vertical") ? "<br />" : "";

	$count = 1;
	$selected_value = "";
	$dd_str = "";
	foreach ($options as $option)
	{
		// generate a unique ID for this option (used for the label)
		$id = "field{$field_id}_$count";

		$dd_str .= "<input type=\"radio\" name=\"$name\" value=\"{$option['option_value']}\" id=\"$id\"";
		if ($option['option_value'] == $selected)
		{
			$dd_str .= " checked";
			$selected_value = $option['option_name'];
		}
		$dd_str .= "><label for=\"$id\">{$option['option_name']}</label>$pagebreak\n";

		$count++;
	}

	if ($is_editable == "no")
	  echo $selected_value;
	else
	  echo $dd_str;
}
