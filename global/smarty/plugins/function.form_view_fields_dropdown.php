<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.form_view_fields_dropdown
 * Type:     function
 * Name:     form_view_fields_dropdown
 * Purpose:  generates a dropdown of all fields in a View. Used on the main submission listing page
 *           in the search form for the administrator and client. As of 2.0.0 Beta 20090510, this
 *           only displays those fields marked as searchable.
 * -------------------------------------------------------------
 */
function smarty_function_form_view_fields_dropdown($params, &$smarty)
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
	if (empty($params["view_id"]))
  {
	  $smarty->trigger_error("assign: missing 'view_id' parameter.");
    return;
  }

  $default_value = (isset($params["default"])) ? $params["default"] : "";
  $onchange      = (isset($params["onchange"])) ? $params["onchange"] : "";
  $style         = (isset($params["style"])) ? $params["style"] : "";
  $blank_option_value = (isset($params["blank_option_value"])) ? $params["blank_option_value"] : "";
  $blank_option_text  = (isset($params["blank_option_text"])) ? $params["blank_option_text"] : "";
  $view_id = $params["view_id"];
  $form_id = $params["form_id"];

  $attributes = array(
    "id"   => $params["name_id"],
    "name" => $params["name_id"],
    "onchange" => $onchange,
    "style" => $style
      );

	$attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
  	if (!empty($value))
  	  $attribute_str .= " $key=\"$value\"";
  }

  $view_fields = ft_get_view_fields($view_id);
  $rows = array();

  if (!empty($blank_option_value) && !empty($blank_option_text))
    $rows[] = "<option value=\"{$blank_option_value}\">$blank_option_text</option>";

  foreach ($view_fields as $field_info)
  {
    if ($field_info["is_searchable"] != "yes")
      continue;

    $col_name    = $field_info["col_name"];
    $field_title = $field_info["field_title"];
    $rows[] = "<option value=\"{$col_name}\" " . (($default_value == $col_name) ? "selected" : "") . ">{$field_title}</option>";
  }

	$dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}
