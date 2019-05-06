<?php

use FormTools\Core;
use FormTools\Forms;
use FormTools\Templates;


/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.forms_dropdown
 * Type:     function
 * Name:     form_dropdown
 * Purpose:  generates a dropdown of all forms in the database (ordered by form name).
 * -------------------------------------------------------------
 */
function smarty_function_forms_dropdown($params, &$smarty)
{
    $LANG = Core::$L;

    if (!Templates::hasRequiredParams($smarty, $params, array("name_id"))) {
        return "";
    }

    $name_id = $params["name_id"];
    $default_value = (isset($params["default"])) ? $params["default"] : ""; // may be array or single form ID

    $attribute_whitelist = array("onchange", "style", "class");
    $attributes = array(
        "name=\"$name_id\"",
        "id=\"$name_id\""
    );

    foreach ($attribute_whitelist as $attribute_name) {
        if (isset($params[$attribute_name]) && !empty($params[$attribute_name])) {
            $attributes[] = "$attribute_name=\"{$params[$attribute_name]}\"";
        }
    }
    $attribute_str = implode(" ", $attributes);

    $include_blank_option = (isset($params["include_blank_option"])) ? $params["include_blank_option"] : false;
    $blank_option_label   = (isset($params["blank_option_label"])) ? $params["blank_option_label"] : $LANG["phrase_please_select"];
    $blank_option_is_optgroup = (isset($params["blank_option_is_optgroup"])) ? $params["blank_option_is_optgroup"] : false;
    $hide_incomplete_forms = (isset($params["hide_incomplete_forms"])) ? $params["hide_incomplete_forms"] : true;
    $omit_forms    = (isset($params["omit_forms"])) ? $params["omit_forms"] : array(); // a list of forms to omit from the list

    // option to limit the forms to a particular status (online/offline). Pass empty string to return all
    $form_status = (isset($params["form_status"])) ? $params["form_status"] : "online";

    // if this option is set, it only shows those form in the array
    $only_show_forms = (isset($params["only_show_forms"])) ? $params["only_show_forms"] : array();

    // this option tells the function that if there's only a single form, display it as straight text
    // rather than in a dropdown. Only compatible with the non-multiple dropdown list
    $display_single_form_as_text = (isset($params["display_single_form_as_text"])) ? $params["display_single_form_as_text"] : false;

    $forms = Forms::searchForms(array(
        "is_admin" => true,
        "status" => $form_status
    ));

    $rows = array();

    foreach ($forms as $form_info) {
        if ($form_info["is_complete"] == "no" && $hide_incomplete_forms) {
            continue;
        }

        $form_id   = $form_info["form_id"];
        $form_name = $form_info["form_name"];

        if (in_array($form_id, $omit_forms)) {
            continue;
        }

        if (!empty($only_show_forms) && !in_array($form_id, $only_show_forms)) {
            continue;
        }

        $rows[] = array("form_id" => $form_id, "form_name" => $form_name);
    }

    if (count($rows) == 1 && $display_single_form_as_text) {
        $html = $rows[0]["form_name"];
    } else {
        $options = array();

        if ($include_blank_option) {
            if ($blank_option_is_optgroup) {
                $options[] = "<optgroup label=\"{$blank_option_label}\">";
            } else {
                $options[] = "<option value=\"\">{$blank_option_label}</option>";
            }
        }

        foreach ($rows as $row_info) {
            $selected = "";
            if (is_array($default_value) && in_array($row_info["form_id"], $default_value)) {
                $selected = "selected";
            } else if ($default_value == $row_info["form_id"]) {
                $selected = "selected";
            }
            $options[] = "<option value=\"{$row_info["form_id"]}\" $selected>{$row_info["form_name"]}</option>";
        }

        if ($include_blank_option && $blank_option_is_optgroup) {
            $options[] = "</optgroup>";
        }

        $html = "<select $attribute_str>" . join("\n", $options) . "</select>";
    }

    return $html;
}

