<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.field_option_list_dropdown
 * Type:     function
 * Name:     field_option_groups
 * Purpose:  generates a dropdown of all Option Lists in the database
 * -------------------------------------------------------------
 */
function smarty_function_option_list_dropdown($params, &$smarty)
{
  global $LANG;

  if (empty($params["name_id"]))
  {
    $smarty->trigger_error("assign: missing 'name_id' parameter. This is used to give the select field a name and id value.");
    return;
  }

  $default_value = (isset($params["default"])) ? $params["default"] : "";
  $onchange      = (isset($params["onchange"])) ? $params["onchange"] : "";
  $style         = (isset($params["style"])) ? $params["style"] : "";

  $attributes = array(
    "id"       => $params["name_id"],
    "name"     => $params["name_id"],
    "onchange" => $onchange,
    "style"    => $style
      );

  $attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
    if (!empty($value))
      $attribute_str .= " $key=\"$value\"";
  }

  $groups = ft_get_option_lists("all");

  $rows = array();
  $rows[] = "<option value=\"\">{$LANG["phrase_please_select"]}</option>";

  foreach ($groups["results"] as $group_info)
  {
    $list_id          = $group_info["list_id"];
    $option_list_name = $group_info["option_list_name"];
    $selected = ($default_value == $list_id) ? "selected" : "";
    $rows[] = "<option value=\"$list_id\" $selected>$option_list_name</option>\n";
  }

   $html = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $html;
}

