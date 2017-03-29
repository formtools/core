<?php

/**
 * FieldValidation.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


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
        $db->query(":field_id", $field_id);
        $db->execute();

        $rules = array();
        foreach ($db->fetchAll() as $row) {
            $rules[] = $row;
        }

        return $rules;
    }

    /**
     * Deletes any validation defined for a particular field. This is called on the main Edit Form -> Fields tab, after a
     * field has it's field type changed.
     *
     * @param integer $field_id
     */
    public static function delete($field_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}field_validation WHERE field_id = :field_id");
        $db->bind(":field_id", $field_id);
        $db->execute();
    }

    public static function getPHPValidationRules($field_ids)
    {
        $db = Core::$db;
        if (empty($field_ids)) {
            return array();
        }

        $db->query("
            SELECT *
            FROM   {PREFIX}field_validation fv, {PREFIX}field_type_validation_rules ftvr
            WHERE  fv.field_id IN (:field_id_str) AND
                   fv.rule_id = ftvr.rule_id AND
                   ftvr.custom_function_required != 'yes'
            ORDER BY fv.field_id, ftvr.list_order
        ");
        $db->bind(":field_ids", implode(",", $field_ids));
        $db->execute();

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
        global $g_multi_val_delimiter;

        $updated_grouped_fields = array();
        foreach ($grouped_fields as $group_info)
        {
            $group  = $group_info["group"];
            $fields = $group_info["fields"];

            $updated_fields = array();
            foreach ($fields as $field_info)
            {
                if (array_key_exists($field_info["field_name"], $request))
                {
                    // TODO! This won't work for phone_number fields, other fields
                    $value = (is_array($request[$field_info["field_name"]])) ? implode($g_multi_val_delimiter, $request[$field_info["field_name"]]) : $request[$field_info["field_name"]];
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




}
