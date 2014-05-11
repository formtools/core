<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.form_fields_dropdown
 * Type:     function
 * Name:     form_view_fields_dropdown
 * Purpose:  generates a dropdown of all fields in a form.
 * -------------------------------------------------------------
 */
function smarty_function_form_fields_dropdown($params, &$smarty)
{
	global $LANG;

	if (empty($params["name_id"]))
  {
	  $smarty->trigger_error("assign: missing 'name_id' parameter. This is used to give the select field a name and id value.");
    return;
  }
	if (empty($params["form_id"]))
  {
	  $smarty->trigger_error("assign: missing 'form_id' parameter.");
    return;
  }

  $default_value = (isset($params["default"])) ? $params["default"] : "";
  $onchange      = (isset($params["onchange"])) ? $params["onchange"] : "";
  $style         = (isset($params["style"])) ? $params["style"] : "";
	$display_column_names = (isset($params["display_column_names"])) ? $params["display_column_names"] : false;
  $blank_option_value = (isset($params["blank_option_value"])) ? $params["blank_option_value"] : "";
  $blank_option_text  = (isset($params["blank_option_text"])) ? $params["blank_option_text"] : "";
  $tabindex  = (isset($params["tabindex"])) ? $params["tabindex"] : "";

  $attributes = array(
    "id"   => $params["name_id"],
    "name" => $params["name_id"],
    "onchange" => $onchange,
    "style" => $style,
    "tabindex" => $tabindex
      );

	$attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
  	if (!empty($value))
  	  $attribute_str .= " $key=\"$value\"";
  }


  $column_info = ft_get_form_column_names($params["form_id"]);
  $rows = array();


  if (!empty($blank_option_value) && !empty($blank_option_text))
    $rows[] = "<option value=\"{$blank_option_value}\">$blank_option_text</option>";

  while (list($col_name, $display_text) = each($column_info))
  {
	  if ($display_column_names)
		  $display_text = $col_name;

  	$rows[] = "<option value=\"{$col_name}\" " . (($default_value == $col_name) ? "selected" : "") . ">{$display_text}</option>";
  }

	$dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}

