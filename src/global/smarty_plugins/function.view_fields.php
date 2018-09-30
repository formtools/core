<?php

use FormTools\ViewFields;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.view_fields
 * Type:     function
 * -------------------------------------------------------------
 */
function smarty_function_view_fields($params, &$smarty)
{
	if (!Templates::hasRequiredParams($smarty, $params, array("name_id", "view_id"))) {
		return "";
	}

	$default_value = (isset($params["default"])) ? $params["default"] : "";
	$onchange = (isset($params["onchange"])) ? $params["onchange"] : "";
	$style = (isset($params["style"])) ? $params["style"] : "";
	$blank_option = (isset($params["blank_option"])) ? $params["blank_option"] : "";
	$view_id = $params["view_id"];

	$attributes = array(
		"id" => $params["name_id"],
		"name" => $params["name_id"],
		"onchange" => $onchange,
		"style" => $style
	);

	$attribute_str = "";
	foreach ($attributes as $key => $value) {
		if (!empty($value)) {
			$attribute_str .= " $key=\"$value\"";
		}
	}

	$view_fields = ViewFields::getViewFields($view_id);
	$rows = array();

	if (!empty($blank_option)) {
		$rows[] = "<option value=\"\">$blank_option</option>";
	}

	foreach ($view_fields as $field_info) {
		$field_title = $field_info["field_title"];
		$field_id = $field_info["field_id"];
		$selected = ($default_value == $field_id) ? "selected" : "";
		$rows[] = "<option value=\"{$field_id}\" $selected>{$field_title}</option>";
	}

	$dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

	return $dd;
}
