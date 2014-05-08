<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.menus_dropdown
 * Type:     function
 * Name:     menus_dropdown
 * Purpose:  generates a dropdown of all menus in the database, ordered by menu name.
 * -------------------------------------------------------------
 */
function smarty_function_menus_dropdown($params, &$smarty)
{
	global $LANG;

	if (empty($params["name_id"]))
  {
	  $smarty->trigger_error("assign: missing 'name_id' parameter. This is used to give the select field a name and id value.");
    return;
  }
  $default = (isset($params["default"])) ? $params["default"] : "";
  $type    = (isset($params["type"])) ? $params["type"] : ""; // admin, client or ""

  $attributes = array(
    "id"   => $params["name_id"],
    "name" => $params["name_id"],
      );

	$attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
  	if (!empty($value))
  	  $attribute_str .= " $key=\"$value\"";
  }

  $menus = ft_get_menu_list();
  $rows = array("<option value=\"\">{$LANG["phrase_please_select"]}</option>");
  foreach ($menus as $menu_info)
  {
  	$menu_id   = $menu_info["menu_id"];
  	$menu      = $menu_info["menu"];
  	$menu_type = $menu_info["menu_type"];

  	if (!empty($type) && $menu_type != $type)
  	  continue;

  	$rows[] = "<option value=\"$menu_id\" " . (($default == $menu_id) ? "selected" : "") . ">$menu</option>";
  }

	$dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}

?>