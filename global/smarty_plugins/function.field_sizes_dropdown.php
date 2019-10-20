<?php

use FormTools\Core;
use FormTools\FieldSizes;


/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.field_sizes_dropdown
 * Type:     function
 * Name:     field_sizes_dropdown
 * Purpose:  generates a dropdown of the available field sizes.
 * -------------------------------------------------------------
 */
function smarty_function_field_sizes_dropdown($params, &$smarty)
{
    $LANG = Core::$L;
    $field_sizes = FieldSizes::get();

    $default_values = (isset($params["default"])) ? $params["default"] : array();
    $field_type_id  = (isset($params["field_type_id"])) ? $params["field_type_id"] : "";

    if (is_string($default_values)) {
        $default_values = array($default_values);
    }

    $attribute_whitelist = array("name", "id", "onchange", "class", "multiple", "size");
    $attributes = array();
	foreach ($attribute_whitelist as $attribute_name) {
        if (isset($params[$attribute_name]) && !empty($params[$attribute_name])) {
            $attributes[] = "$attribute_name=\"{$params[$attribute_name]}\"";
        }
	}
    $attribute_str = implode(" ", $attributes);

    // if a field type ID is specified, limit the options in the field sizes dropdown to whatever's permitted
    // for that field type. If there's only one defined, just output the name
    $available_field_type_sizes = array();
    $limit_size_list = false;
    if (!empty($field_type_id)) {
        $limit_size_list = true;
        $available_field_type_sizes = FieldSizes::getFieldTypeSizes($field_type_id);
    }

    $html = "";
    if ($limit_size_list) {
        if (count($available_field_type_sizes) == 1) {
            echo $LANG[$field_sizes[$available_field_type_sizes[0]]["lang_key"]];
            echo "<input type=\"hidden\" name=\"{$params["name"]}\" value=\"{$available_field_type_sizes[0]}\" />";
        } else {
            $html = "<select $attribute_str>";
			foreach ($field_sizes as $key => $info) {
				$lang_key = $info["lang_key"];
				if (in_array($key, $available_field_type_sizes)) {
                    $html .= "<option value=\"$key\"" . (in_array($key,
                    $default_values) ? " selected" : "") . ">{$LANG[$lang_key]}</option>";
                }
			}
			$html .= "</select>";
			reset($field_sizes);
        }
    } else {
        $html = "<select $attribute_str>";
		foreach ($field_sizes as $key => $info) {
            $lang_key = $info["lang_key"];
            $html .= "<option value=\"$key\"" . (in_array($key, $default_values) ? " selected" : "") . ">{$LANG[$lang_key]}</option>";
        }
        $html .= "</select>";
        reset($field_sizes);
    }

	return $html;
}


