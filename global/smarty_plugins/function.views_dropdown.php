<?php

use FormTools\Core;
use FormTools\Views;
use FormTools\Submissions;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.views_dropdown
 * Type:     function
 * Name:     views_dropdown
 * Purpose:  displays a list of Views for a form. As of 2.1.0, this function groups the
 *           Views into optgroups to reflect whatever grouping the administrator has entered.
 * -------------------------------------------------------------
 */
function smarty_function_views_dropdown($params, &$smarty)
{
	$LANG = Core::$L;

	if (!Templates::hasRequiredParams($smarty, $params, array("form_id"))) {
		return "";
	}

	$form_id = $params["form_id"];
	$name_id = (isset($params["name_id"])) ? $params["name_id"] : "";
	$show_empty_label = (isset($params["show_empty_label"])) ? $params["show_empty_label"] : false;
	$empty_label = (isset($params["empty_label"])) ? $params["empty_label"] : $LANG["phrase_please_select"];
	$selected = (isset($params["selected"])) ? $params["selected"] : "";
	$onchange = (isset($params["onchange"])) ? $params["onchange"] : "";
	$submission_id = (isset($params["submission_id"])) ? $params["submission_id"] : "";
	$omit_hidden_views = (isset($params["omit_hidden_views"])) ? $params["omit_hidden_views"] : false;
	$create_view_dropdown = (isset($params["create_view_dropdown"])) ? $params["create_view_dropdown"] : false;
	$class = (isset($params["class"])) ? $params["class"] : "";
	$open_html = (isset($params["open_html"])) ? $params["open_html"] : "";
	$close_html = (isset($params["close_html"])) ? $params["close_html"] : "";
	$hide_single_view = (isset($params["hide_single_view"])) ? $params["hide_single_view"] : false;

	// if the calling page has the view information already calculated, it can pass it to this function to
	// reduce the amount of work it needs to do. Otherwise, it will just do a separate request for the data
	$grouped_views = (isset($params["grouped_views"])) ? $params["grouped_views"] : Views::getGroupedViews($form_id, array("omit_hidden_views" => $omit_hidden_views));

	$attributes = array(
		"id" => $name_id,
		"name" => $name_id,
		"onchange" => $onchange,
		"class" => $class
	);

	$attribute_str = "";
	foreach ($attributes as $key => $value) {
		if (!empty($value)) {
			$attribute_str .= " $key=\"$value\"";
		}
	}

	$num_views = 0;
	$class_str = (empty($class)) ? "" : " class=\"$class\"";
	$dd = "<select $attribute_str{$class_str}>";

	if ($show_empty_label) {
		$dd .= "<option value=\"\">{$empty_label}</option>";
	}

	if ($create_view_dropdown) {
		$dd .= "<option value=\"blank_view_all_fields\">{$LANG["phrase_new_view_all_fields"]}</option>";
		$dd .= "<option value=\"blank_view_no_fields\">{$LANG["phrase_new_blank_view"]}</option>";
	}

	foreach ($grouped_views as $curr_group) {
		$group_name = $curr_group["group"]["group_name"];

		$view_options = "";
		foreach ($curr_group["views"] as $view_info) {
			$curr_view_id = $view_info["view_id"];
			$view_name = $view_info["view_name"];
			$is_selected = ($curr_view_id == $selected) ? "selected" : "";

			if (empty($submission_id)) {
				$view_options .= "<option value=\"$curr_view_id\" {$is_selected}>$view_name</option>\n";
				$num_views++;
			} else {
				if (Submissions::checkViewContainsSubmission($form_id, $curr_view_id, $submission_id)) {
					$view_options .= "<option value=\"$curr_view_id\" {$is_selected}>$view_name</option>";
					$num_views++;
				}
			}
		}

		if (!empty($view_options)) {
			if (!empty($group_name)) {
				$dd .= "<optgroup label=\"$group_name\">";
			}

			$dd .= $view_options;

			if (!empty($group_name)) {
				$dd .= "</optgroup>";
			}
		}
	}

	$dd .= "</select>";

	if ($num_views <= 1 && $hide_single_view) {
		// do nothing!
		$dd = "";
	} else {
		$dd = $open_html . $dd . $close_html;
	}

	return $dd;
}

