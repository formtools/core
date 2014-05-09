<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.dropdown
 * Type:     function
 * Name:     dropdown
 * Purpose:  displays a dropdown containing any information.
 * -------------------------------------------------------------
 */
function smarty_function_dropdown($params, &$smarty)
{
  global $LANG;

  $options       = $params["options"];
  $default_value = (isset($params["default"])) ? $params["default"] : "";
  $blank_option_text  = (isset($params["blank_option_text"])) ? $params["blank_option_text"] : $LANG["phrase_please_select"];

  $attributes = array(
    "id"       => isset($params["id"]) ? $params["id"] : "",
    "name"     => isset($params["name"]) ? $params["name"] : "",
    "tabindex" => isset($params["tabindex"]) ? $params["tabindex"] : ""
      );

  $attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
    if (!empty($value))
      $attribute_str .= " $key=\"$value\"";
  }

  $rows = array();
  $rows[] = "<option value=\"\">$blank_option_text</option>";

  while (list($value, $text) = each($options))
    $rows[] = "<option value=\"$value\" " . (($default_value == $value) ? "selected" : "") . ">$text</option>";

  $dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}
