<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.form_view_fields_dropdown
 * Type:     function
 * Name:     form_view_fields_dropdown
 * Purpose:  generates a dropdown of all fields in a View. Used on the main submission listing page
 *           in the search form for the administrator and client.
 *
 *           This is an old version that does some fancy stuff for the Submission Listing page. If you just
 *           want a dropdown of the View fields with no other clutter, use {view_fields}. This function should
 *           be renamed!
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
  if (empty($params["field_types"]))
  {
    $smarty->trigger_error("assign: missing 'field_types' parameter.");
    return;
  }

  $default_value = (isset($params["default"])) ? $params["default"] : "";
  $onchange      = (isset($params["onchange"])) ? $params["onchange"] : "";
  $style         = (isset($params["style"])) ? $params["style"] : "";
  $blank_option_value = (isset($params["blank_option_value"])) ? $params["blank_option_value"] : "";
  $blank_option_text  = (isset($params["blank_option_text"])) ? $params["blank_option_text"] : "";
  $view_id = $params["view_id"];
  $form_id = $params["form_id"];
  $field_types = $params["field_types"];

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

  // find out which field type IDs are date fields
  $date_field_type_ids = array();
  foreach ($field_types as $field_type_info)
  {
  	if ($field_type_info["is_date_field"] == "yes")
  	  $date_field_type_ids[] = $field_type_info["field_type_id"];
  }

  if (!empty($blank_option_value) && !empty($blank_option_text))
    $rows[] = "<option value=\"{$blank_option_value}\">$blank_option_text</option>";

  foreach ($view_fields as $field_info)
  {
    if ($field_info["is_searchable"] != "yes")
      continue;

    $col_name    = $field_info["col_name"];
    $field_title = $field_info["field_title"];

    $is_date_field = (in_array($field_info["field_type_id"], $date_field_type_ids)) ? true : false;
    $suffix = "";
    if ($is_date_field)
    {
      $suffix = "|date";
      $selected = ($default_value == "$col_name|date") ? "selected" : "";
    }
    else
    {
      $selected = ($default_value == $col_name) ? "selected" : "";
    }

    $rows[] = "<option value=\"{$col_name}{$suffix}\" $selected>{$field_title}</option>";
  }

  $dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}
