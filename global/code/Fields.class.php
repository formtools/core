<?php

/**
 * Fields.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Fields {

    /**
     * Retrieves all custom settings for an individual form field from the field_settings table.
     *
     * @param integer $field_id the unique field ID
     * @return array an array of hashes
     */
    public static function getFormFieldSettings($field_id, $evaluate_dynamic_fields = false)
    {
        $db = Core::$db;

        if ($evaluate_dynamic_fields) {
            $db->query("
                SELECT *
                FROM   {PREFIX}field_settings fs, {PREFIX}field_type_settings fts
                WHERE  fs.setting_id = fts.setting_id AND
                       field_id = :field_id
            ");
        } else {
            $db->query("
                SELECT *
                FROM   {PREFIX}field_settings
                WHERE  field_id = :field_id
            ");
        }
        $db->bind("field_id", $field_id);
        $db->execute();

        $settings = array();
        foreach ($db->fetchAll() as $row) {
            if ($evaluate_dynamic_fields && $row["default_value_type"] == "dynamic") {
                $settings[$row["setting_id"]] = "";
                $parts = explode(",", $row["setting_value"]);

                // TODO this needs updating
                if (count($parts) == 2) {
                    $settings[$row["setting_id"]] = Settings::get($parts[0], $parts[1]);
                }
            } else {
                $settings[$row["setting_id"]] = $row["setting_value"];
            }
        }

        extract(Hooks::processHookCalls("end", compact("field_id", "settings"), array("settings")), EXTR_OVERWRITE);

        return $settings;
    }


    /**
     * Returns all files associated with a particular form field or fields. Different field types may store the
     * files differently, so EVERY file upload module needs to add a hook to this function to return the
     * appropriate information.
     *
     * The module functions should return an array of hashes with the following structure:
     *    array(
     *      "submission_id" =>
     *      "field_id"      =>
     *      "field_type_id" =>
     *      "folder_path"   =>
     *      "folder_url"    =>
     *      "filename"      =>
     *    ),
     *    ...
     *
     * @param integer $form_id the unique form ID
     * @param array $field_ids an array of field IDs
     */
    public static function getUploadedFiles($form_id, $field_ids)
    {
        $uploaded_files = array();
        extract(Hooks::processHookCalls("start", compact("form_id", "field_ids"), array("uploaded_files")), EXTR_OVERWRITE);
        return $uploaded_files;
    }


    /**
     * This can be called at any junction for any form. It re-orders the form field list_orders based
     * on the current order. Basically, it's used whenever a form field is deleted to ensure that there
     * are no gaps in the list_order.
     *
     * @param integer $form_id
     */
    public static function autoUpdateFormFieldOrder($form_id)
    {
        $db = Core::$db;

        // we rely on this function returning the field by list_order
        $form_fields = self::getFormFields($form_id);

        $count = 1;
        foreach ($form_fields as $field_info) {
            $db->query("
                UPDATE {PREFIX}form_fields
                SET    list_order = :count
                WHERE  form_id = :form_id AND
                       field_id = :field_id
            ");
            $db->bindAll(array(
                "count" => $count,
                "form_id" => $form_id,
                "field_id" => $field_info["field_id"]
            ));
            $db->execute();
            $count++;
        }
    }


    /**
     * Retrieves all field information about a form, ordered by list_order. The 2nd and 3rd optional
     * parameters let you return a subset of the fields for a particular page. This function is purely
     * concerned with the raw fields themselves: not how they are arbitrarily grouped in a View. To
     * retrieve the grouped fields list for a View, use Views::getViewFields().
     *
     * @param integer $form_id the unique form ID
     * @param array $custom_settings optional settings
     * @return array an array of hash information
     */
    public static function getFormFields($form_id, $custom_params = array())
    {
        $db = Core::$db;

        $params = array_merge(array(
            "page" => 1,
            "num_fields_per_page" => "all",
            "include_field_type_info" => false,
            "include_field_settings" => false,
            "evaluate_dynamic_settings" => false,
            "field_ids" => "all"
        ), $custom_params);

        $limit_clause = General::getQueryPageLimitClause($params["page"], $params["num_fields_per_page"]);

        if ($params["include_field_type_info"]) {
            $db->query("
                SELECT ff.*, ft.field_type_name, ft.is_file_field, ft.is_date_field
                FROM   {PREFIX}form_fields ff, {PREFIX}field_types ft
                WHERE  ff.form_id = :form_id AND
                       ff.field_type_id = ft.field_type_id
                ORDER BY ff.list_order
                $limit_clause
            ");
        } else {
            $field_id_clause = "";
            if ($params["field_ids"] != "all") {
                $field_id_clause = "AND field_id IN (" . implode(",", $params["field_ids"]) . ")";
            }
            $db->query("
                SELECT *
                FROM   {PREFIX}form_fields
                WHERE  form_id = :form_id
                  $field_id_clause
                ORDER BY list_order
                  $limit_clause
            ");
        }
        $db->bind("form_id", $form_id);
        $db->execute();

        $infohash = array();
        foreach ($db->fetchAll() as $row) {
            if ($params["include_field_settings"]) {
                $row["settings"] = Fields::getFormFieldSettings($row["field_id"], $params["evaluate_dynamic_settings"]);
            }
            $infohash[] = $row;
        }

        extract(Hooks::processHookCalls("end", compact("form_id", "infohash"), array("infohash")), EXTR_OVERWRITE);

        return $infohash;
    }


}
