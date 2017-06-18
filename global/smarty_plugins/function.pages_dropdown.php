<?php

use FormTools\Menus;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.pages_dropdown
 * Type:     function
 * Name:     pages_dropdown
 * Purpose:  displays a dropdown of available Form Tools pages, including anything specified in the Pages module. Used
 *           for either a client or an administrator account.
 * -------------------------------------------------------------
 */
function smarty_function_pages_dropdown($params, &$smarty)
{
    if (!Templates::hasRequiredParams($smarty, $params, array("menu_type", "name_id"))) {
        return "";
    }

    $menu_type        = $params["menu_type"];
    $default_value    = (isset($params["default"])) ? $params["default"] : "";
    $onchange         = (isset($params["onchange"])) ? $params["onchange"] : "";
    $onkeyup          = (isset($params["onkeyup"])) ? $params["onkeyup"] : "";
    $is_building_menu = (isset($params["is_building_menu"]) && $params["is_building_menu"] === true) ? true : false;
    $class            = (isset($params["class"])) ? $params["class"] : "";

    if (isset($params["omit_pages"]) && !empty($params["omit_pages"])) {
        $omit_pages = explode(",", $params["omit_pages"]);
    } else {
        $omit_pages = array();
    }

    $attributes = array(
        "id"   => $params["name_id"],
        "class" => $class,
        "name" => $params["name_id"],
        "onchange" => $onchange,
        "onkeyup" => $onkeyup
    );

    $dropdown_str = "";
    if ($menu_type == "admin") {
        $dropdown_str = Menus::getAdminMenuPagesDropdown($default_value, $attributes, $is_building_menu, $omit_pages);
    }
    if ($menu_type == "client") {
        $dropdown_str = Menus::getClientMenuPagesDropdown($default_value, $attributes, $omit_pages);
    }

    return $dropdown_str;
}

