<?php

/**
 * FieldValidation.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, Exception;


class FieldValidation {

    /**
     * Returns all defined validation rules for a field.
     *
     * @param integer $field_id
     */
    public static function get($field_id)
    {
        $db = Core::$db;
        $db->query("
            SELECT *
            FROM   {PREFIX}field_validation fv, {PREFIX}field_type_validation_rules ftvr
            WHERE  fv.field_id = :field_id AND
                ftvr.rule_id = fv.rule_id
        ");
        $db->bind("field_id", $field_id);
        $db->execute();

        return $db->fetchAll();
    }

    /**
     * Deletes any validation defined for a particular field. This is called on the main Edit Form -> Fields tab, after a
     * field has it's field type changed.
     *
     * @param integer $field_id
     */
    public static function deleteValidation($field_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}field_validation WHERE field_id = :field_id");
        $db->bind("field_id", $field_id);
        $db->execute();
    }

    public static function getPHPValidationRules($field_ids)
    {
        $db = Core::$db;
        if (empty($field_ids)) {
            return array();
        }

        $field_ids = implode(",", $field_ids);
        try {
            $db->query("
                SELECT *
                FROM   {PREFIX}field_validation fv, {PREFIX}field_type_validation_rules ftvr
                WHERE  fv.field_id IN ($field_ids) AND
                       fv.rule_id = ftvr.rule_id AND
                       ftvr.custom_function_required != 'yes'
                ORDER BY fv.field_id, ftvr.list_order
            ");
            $db->execute();
        } catch (Exception $e) {
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
            exit;
        }

        $rules = array();
        foreach ($db->fetchAll() as $row) {
            $rules[] = $row;
        }

        return $rules;
    }


    /**
     * Called after a form submission is made, but it fails server-side validation. This merges the original content
     * with whatever is in the POST request.
     *
     * @param array $grouped_fields
     * @param array $request
     */
    public static function mergeFormSubmission($grouped_fields, $request)
    {
        $multi_val_delimiter = Core::getMultiFieldValDelimiter();

        $updated_grouped_fields = array();
        foreach ($grouped_fields as $group_info) {
            $group  = $group_info["group"];
            $fields = $group_info["fields"];

            $updated_fields = array();
            foreach ($fields as $field_info) {
                if (array_key_exists($field_info["field_name"], $request)) {
                    // TODO! This won't work for phone_number fields, other fields
                    $value = (is_array($request[$field_info["field_name"]])) ? implode($multi_val_delimiter, $request[$field_info["field_name"]]) : $request[$field_info["field_name"]];
                    $field_info["submission_value"] = $value;
                }
                $updated_fields[] = $field_info;
            }

            $updated_grouped_fields[] = array(
                "group"  => $group,
                "fields" => $updated_fields
            );
        }

        return $updated_grouped_fields;
    }


    /**
     * This is the main server-side validation function, called whenever updating a submission. The current version (Core 2.1.9)
     * only performs a subset of the total validation rules; namely, those non-custom ones that
     *
     * @param array $editable_field_ids - this contains ALL editable field IDs in the form
     * @param array $request
     * @return array an array of errors, or an empty array if no errors
     */
    public static function validateSubmission($editable_field_ids, $request)
    {
        if (empty($editable_field_ids)) {
            return array();
        }

        // get the validation rules for the current page. The use of $request["field_ids"] is a fix for bug #339; this should be handled
        // a lot better. The calling page (edit_submission.php amongst other) should be figuring out what fields are editable on that particular
        // page and passing THAT info as $editable_field_ids
        $editable_field_ids_on_tab = explode(",", $request["field_ids"]);

        // return all validation rules for items on tab, including those marked as editable == "no"
        $rules = FieldValidation::getPHPValidationRules($editable_field_ids_on_tab);

        // gets all form fields in this View
        $form_fields = ViewFields::getViewFields($request["view_id"]);

        // reorganize $form_fields to be a hash of field_id => array(form_name => "", field_field => "")
        $field_info = array();
        foreach ($form_fields as $curr_field_info) {
            $field_info[$curr_field_info["field_id"]] = array(
                "field_name"  => $curr_field_info["field_name"],
                "field_title" => $curr_field_info["field_title"],
                "is_editable" => $curr_field_info["is_editable"]
            );
        }

        // construct the RSV-friendly validation
        $validation = array();
        foreach ($rules as $rule_info) {
            $rule          = $rule_info["rsv_rule"];
            $field_id      = $rule_info["field_id"];
            $field_name    = $field_info[$field_id]["field_name"];
            $field_title   = $field_info[$field_id]["field_title"];
            $error_message = $rule_info["error_message"];

            // if this field is marked as non-editable, ignore it. We don't need to validate it
            if ($field_info[$field_id]["is_editable"] == "no") {
                continue;
            }

            $placeholders = array(
                "field"      => $field_title,
                "field_name" => $field_name
            );
            $error_message = General::evalSmartyString($error_message, $placeholders);

            $validation[] = "$rule,$field_name,$error_message";
        }

        $errors = array();
        if (!empty($validation)) {
            $errors = validate_fields($request, $validation);
        }

        return $errors;
    }

    public static function getFieldTypeValidationRules() {
        Core::$db->query("
            SELECT *
            FROM   {PREFIX}field_type_validation_rules
            ORDER BY field_type_id, list_order
        ");
        Core::$db->execute();
        return Core::$db->fetchAll();
    }

    /**
     * Added in 2.1.4 to return all field type validation information.
     *
     * @param $options
     */
    public static function generateFieldTypeValidationJs($options = array())
    {
        // for use in dev work
        $minimize = false;

        $delimiter = "\n";
        if ($minimize) {
            $delimiter = "";
        }

        $namespace = isset($options["page_ns"]) ? $options["page_ns"] : "page_ns";

        $validation_rules = self::getFieldTypeValidationRules();
        $grouped_rules = array();
        foreach ($validation_rules as $row) {
            $field_type_id = $row["field_type_id"];
            if (!array_key_exists($field_type_id, $grouped_rules)) {
                $grouped_rules[$field_type_id] = array();
            }

            $grouped_rules[$field_type_id][] = array(
                "rule_id"    => $row["rule_id"],
                "rule_label" => General::evalSmartyString($row["rule_label"]),
                "default_error_message" => General::evalSmartyString($row["default_error_message"])
            );
        }

        $curr_js = array("{$namespace}.field_validation = {};");
		foreach ($grouped_rules as $field_type_id => $rules) {
            $curr_js[] = "{$namespace}.field_validation[\"field_type_{$field_type_id}\"] = [";
            $curr_rules = array();
            foreach ($rules as $rule_info) {
                $error = html_entity_decode($rule_info["default_error_message"]);
                $curr_rules[] = "{ rule_id: {$rule_info["rule_id"]}, label: \"{$rule_info["rule_label"]}\", error: \"$error\" }";
            }
            $curr_js[] = implode(",$delimiter", $curr_rules);
            $curr_js[] = "];";
        }

        $rows[] = implode("$delimiter", $curr_js);

        return implode("$delimiter", $rows);
    }


    /**
     * Used on the Edit Submission pages, Form Builder - and anywhere that's actually displaying the fields for editing. This
     * generates the JS used for the RSV validation, according to whatever rules the user has specified.
     *
     * @param array $grouped_fields
     */
    public static function generateSubmissionJsValidation($grouped_fields, $settings = array())
    {
        // overridable settings
        $custom_error_handler     = isset($settings["custom_error_handler"]) ? $settings["custom_error_handler"] : "ms.submit_form";
        $form_element_id          = isset($settings["form_element_id"]) ? $settings["form_element_id"] : "edit_submission_form";
        $omit_non_editable_fields = isset($settings["omit_non_editable_fields"]) ? $settings["omit_non_editable_fields"] : true;

        $js_lines = array();
        $custom_func_errors = array();
        foreach ($grouped_fields as $group_info) {
            foreach ($group_info["fields"] as $field_info) {
                if (empty($field_info["validation"])) {
                    continue;
                }
                if ($omit_non_editable_fields && $field_info["is_editable"] == "no") {
                    continue;
                }

                $field_name  = $field_info["field_name"];
                $field_title = $field_info["field_title"];
                $field_id    = $field_info["field_id"];

                foreach ($field_info["validation"] as $rule_info) {
                    $rsv_rule = $rule_info["rsv_rule"];
                    $placeholders = array(
                        "field"      => $field_title,
                        "field_name" => $field_name
                    );
                    $message = General::evalSmartyString($rule_info["error_message"], $placeholders);

                    if ($rsv_rule == "function") {
                        $custom_function = $rule_info["custom_function"];
                        $new_rule = "rules.push(\"function,$custom_function\")";
                        if (!in_array($new_rule, $js_lines)) {
                            $js_lines[] = $new_rule;
                        }
                        $custom_func_errors[] = "{field:\"$field_name\",field_id:$field_id,func:\"$custom_function\",err:\"$message\"}";
                    } else {
                        $rsv_field_name = General::evalSmartyString($rule_info["rsv_field_name"], $placeholders);
                        $js_lines[] = "rules.push(\"$rsv_rule,$rsv_field_name,$message\")";
                    }
                }
            }
        }

        // kind of a hack, but passable. The RSV custom function string doesn't pass the error string in the rule; so
        // we just store it in a JS object in the page for use by the functions
        if (!empty($custom_func_errors)) {
            $js_lines[] = "var rsv_custom_func_errors = []";
            foreach ($custom_func_errors as $js_obj) {
                $js_lines[] = "rsv_custom_func_errors.push($js_obj)";
            }
        }

        $js = "";
        if (!empty($js_lines)) {
            $rules = implode(";\n", $js_lines);
            $custom_error_handler_str = (!empty($custom_error_handler)) ? "rsv.customErrorHandler = $custom_error_handler;" : "";
            $js =<<< END
$(function() {
  $("#{$form_element_id}").bind("submit", function() { return rsv.validate(this, rules); });
  $custom_error_handler_str
});
var rules = [];
$rules
END;
        }

        return $js;
    }





}
