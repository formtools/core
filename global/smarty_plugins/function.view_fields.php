<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.view_fields
 * Type:     function
 * -------------------------------------------------------------
 */
function smarty_function_view_fields($params, &$smarty)
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
  $blank_option  = (isset($params["blank_option"])) ? $params["blank_option"] : "";
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

  if (!empty($blank_option))
    $rows[] = "<option value=\"\">$blank_option</option>";

  foreach ($view_fields as $field_info)
  {
    $field_title = $field_info["field_title"];
    $field_id    = $field_info["field_id"];
    $selected = ($default_value == $field_id) ? "selected" : "";
    $rows[] = "<option value=\"{$field_id}\" $selected>{$field_title}</option>";
  }

  $dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}
