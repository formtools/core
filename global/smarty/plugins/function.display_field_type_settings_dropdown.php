<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_field_type_settings_dropdown
 * Type:     function
 * Name:     display_field_types_dropdown
 * Purpose:  generates a dropdown of all available settings for a field type.
 * -------------------------------------------------------------
 */
function smarty_function_display_field_type_settings_dropdown($params, &$smarty)
{
  global $LANG;

  $field_type_id = $params["field_type_id"];
  $default_value = (isset($params["default"])) ? $params["default"] : "";

  $attribute_whitelist = array("name", "id", "onchange", "onkeyup", "onfocus", "tabindex", "class");
  $attributes = array();
  foreach ($attribute_whitelist as $attribute_name)
  {
    if (isset($params[$attribute_name]) && !empty($params[$attribute_name]))
      $attributes[] = "$attribute_name=\"{$params[$attribute_name]}\"";
  }
  $attribute_str = implode(" ", $attributes);

  $field_type_settings = ft_get_field_type_settings($field_type_id);
  $rows = array();
  foreach ($field_type_settings as $setting_info)
  {
  	$field_setting_identifier = $setting_info["field_setting_identifier"];
    $field_setting_label = htmlspecialchars(ft_eval_smarty_string($setting_info["field_label"]));
    $selected = ($default_value == $field_setting_identifier) ? " selected" : "";
    $rows[] = "<option value=\"{$field_setting_identifier}\"{$selected}>$field_setting_label</option>";
  }

  $dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}

