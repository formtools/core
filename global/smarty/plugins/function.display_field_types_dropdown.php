<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_field_types_dropdown
 * Type:     function
 * Name:     display_field_types_dropdown
 * Purpose:  generates a dropdown of all available field types. The field types are stored in the core
 *           database (ft_field_types and ft_list_groups) but can be only be edited via the Custom
 *           Fields module.
 * -------------------------------------------------------------
 */
function smarty_function_display_field_types_dropdown($params, &$smarty)
{
	global $LANG;

  $default_value = (isset($params["default"])) ? $params["default"] : "";

	$attribute_whitelist = array("name", "id", "onchange", "onkeyup", "onfocus", "tabindex", "class");
  $attributes = array();
	foreach ($attribute_whitelist as $attribute_name)
	{
		if (isset($params[$attribute_name]) && !empty($params[$attribute_name]))
		  $attributes[] = "$attribute_name=\"{$params[$attribute_name]}\"";
	}
  $attribute_str = implode(" ", $attributes);

  $grouped_field_types = ft_get_grouped_field_types();
  $rows = array();
  foreach ($grouped_field_types as $grouped_field_type)
  {
  	$group_name = ft_eval_smarty_string($grouped_field_type["group"]["group_name"]);
  	$rows[] = "<optgroup label=\"" . htmlspecialchars($group_name) . "\">";
  	foreach ($grouped_field_type["field_types"] as $field_type_info)
  	{
  		$field_type_id   = $field_type_info["field_type_id"];
  		$field_type_name = htmlspecialchars(ft_eval_smarty_string($field_type_info["field_type_name"]));
  		$selected = ($default_value == $field_type_id) ? " selected" : "";
      $rows[] = "<option value=\"$field_type_id\"{$selected}>$field_type_name</option>";
  	}
  	$rows[] = "</optgroup>";
  }

	$dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}

