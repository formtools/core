<?php

use FormTools\Core;
use FormTools\Menus;
use FormTools\Templates;

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
    if (!Templates::hasRequiredParams($smarty, $params, array("name_id"))) {
        return "";
    }

    $LANG = Core::$L;
    $default = (isset($params["default"])) ? $params["default"] : "";
    $type    = (isset($params["type"])) ? $params["type"] : ""; // admin, client or ""

    $attributes = array(
        "id"   => $params["name_id"],
        "name" => $params["name_id"],
    );

	$attribute_str = "";
	foreach ($attributes as $key => $value) {
        if (!empty($value)) {
            $attribute_str .= " $key=\"$value\"";
        }
    }

    $menus = Menus::getMenuList();
    $rows = array("<option value=\"\">{$LANG["phrase_please_select"]}</option>");
    foreach ($menus as $menu_info) {
        $menu_id   = $menu_info["menu_id"];
        $menu      = $menu_info["menu"];
        $menu_type = $menu_info["menu_type"];

        if (!empty($type) && $menu_type != $type) {
            continue;
        }

        $rows[] = "<option value=\"$menu_id\" " . (($default == $menu_id) ? "selected" : "") . ">$menu</option>";
    }

	$dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

    return $dd;
}
