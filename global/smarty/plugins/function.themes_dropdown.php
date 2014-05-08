<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.themes_dropdown
 * Type:     function
 * Name:     themes_dropdown
 * Purpose:  displays a list of available themes.
 * -------------------------------------------------------------
 */
function smarty_function_themes_dropdown($params, &$smarty)
{
	global $LANG;

	if (empty($params["name_id"]))
  {
	  $smarty->trigger_error("assign: missing 'name_id' parameter. This is used to give the select field a name and id value.");
    return;
  }
  $default_value   = (isset($params["default"])) ? $params["default"] : "";
  $onchange        = (isset($params["onchange"])) ? $params["onchange"] : "";

  $attributes = array(
    "id"   => $params["name_id"],
    "name" => $params["name_id"],
    "onchange" => $onchange
      );

	$attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
  	if (!empty($value))
  	  $attribute_str .= " $key=\"$value\"";
  }

	$themes = ft_get_themes();

	$dd = "<select $attribute_str>
	         <option value=\"\">{$LANG["phrase_please_select"]}</option>";

  foreach ($themes as $theme)
  {
  	$selected = ($theme["theme_folder"] == $default_value) ? "selected" : "";
  	$dd .= "<option value=\"{$theme["theme_folder"]}\" {$selected}>{$theme["theme_name"]}</option>";
  }
  $dd .= "</select>";

	return $dd;
}

?>