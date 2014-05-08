<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.views_dropdown
 * Type:     function
 * Name:     views_dropdown
 * Purpose:  displays a list of Views for a form.
 * -------------------------------------------------------------
 */
function smarty_function_views_dropdown($params, &$smarty)
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

	$views = ft_get_view_list($params["form_id"]);

	$dd = "<select $attribute_str>
	         <option value=\"\">{$LANG["phrase_please_select"]}</option>";

  foreach ($views as $view_info)
  {
  	$selected = ($view_info["view_id"] == $default_value) ? "selected" : "";
  	$dd .= "<option value=\"{$view_info["view_id"]}\" {$selected}>{$view_info["view_name"]}</option>\n";
  }
  $dd .= "</select>";

	return $dd;
}

