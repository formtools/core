<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.forms_dropdown
 * Type:     function
 * Name:     form_dropdown
 * Purpose:  displays a dropdown containing any information. Note: it always includes a 
 *           default "Please Select" option by default.
 * -------------------------------------------------------------
 */
function smarty_function_dropdown($params, &$smarty)
{
	global $LANG;

	$options       = $params["options"];
  $default_value = (isset($params["default"])) ? $params["default"] : "";

  $attributes = array(
    "id"   => $params["id"], 
    "name" => $params["name"]
      );

	$attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
  	if (!empty($value))
  	  $attribute_str .= " $key=\"$value\"";
  }

  $rows = array();
  $rows[] = "<option value=\"\">{$LANG["phrase_please_select"]}</option>";
  
  while (list($value, $text) = each($options))
  	$rows[] = "<option value=\"$value\" " . (($default_value == $value) ? "selected" : "") . ">$text</option>";

	$dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}

?> 