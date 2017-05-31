<?php

/**
 * Fields.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDOException;


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


    public static function clearFormFields($form_id) {
        $db = Core::$db;

        $in_clause = "";
        $in_params = array();
        $form_fields = self::getFormFields($form_id);
        for ($i=0; $i<count($form_fields); $i++) {
            $field_id = $form_fields[$i]["field_id"];
            $key = ":id{$i}";
            $in_clause .= "$key,";
            $in_params[$key] = $field_id;
        }
        $in_clause = rtrim($in_clause, ",");

        $db->beginTransaction();
        try {
            $db->query("DELETE FROM {PREFIX}form_fields WHERE form_id = :form_id");
            $db->bind("form_id", $form_id);
            $db->execute();

            // now delete any associated settings
            $db->query("DELETE FROM {PREFIX}field_settings WHERE field_id IN ($in_clause)");
            $db->bindAll($in_params);
            $db->execute();

            $db->processTransaction();

        } catch (PDOException $e) {
            $db->rollbackTransaction();
        }
    }


    /**
     * Every form configuration contains a Submission ID, placed first.
     */
    public static function addSubmissionIdSystemField($form_id, $textbox_field_type_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $db->query("
            INSERT INTO {PREFIX}form_fields (form_id, field_name, field_test_value, field_type_id, is_system_field,
              data_type, field_title, col_name, list_order, is_new_sort_group)
            VALUES (:form_id, 'core__submission_id', '', :field_type_id, 'yes', 'number', :id, 'submission_id', '1', 'yes')
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "field_type_id" => $textbox_field_type_id,
            "id" => $LANG["word_id"]
        ));

        $db->execute();
    }


    /**
     * Used during the add form process. This populates the form_fields table with the custom fields in the user's form.
     * @param $form_id
     * @param $form_data
     * @return array
     */
    public static function addFormFields($form_id, $form_data, $order)
    {
        $db = Core::$db;
        $multi_val_delimiter = Core::getMultiFieldValDelimiter();

        while (list($key, $value) = each($form_data)) {

            // if the value is an array, it's either a checkbox field or a multi-select field. Separate the values
            // with the delimiter
            if (is_array($value)) {
                $value = join("$multi_val_delimiter", $value);
            }

            $db->query("
                INSERT INTO {PREFIX}form_fields (form_id, field_name, field_type_id, is_system_field,
                  field_test_value, data_type, list_order, is_new_sort_group)
                VALUES (:form_id, :field_key, 1, 'no', :field_value, 'string', :field_order, 'yes')
            ");
            $db->bindAll(array(
                "form_id" => $form_id,
                "field_key" => $key,
                "field_value" => $value,
                "field_order" => $order
            ));
            $db->execute();
            $order++;
        }

        return $order;
    }


    public static function addFormFileFields($form_id, $file_data, $order)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        // now see if any files were uploaded, too
        while (list($key) = each($file_data)) {
            $db->query("
                INSERT INTO {PREFIX}form_fields (form_id, field_name, field_type_id, is_system_field,
                  field_test_value, data_type, list_order)
                VALUES ($form_id, :field_key, 8, 'no', :field_label, 'string', :field_order)
            ");
            $db->bindAll(array(
                "field_key" => $key,
                "field_label" => $LANG["word_file_b_uc"],
                "field_order" => $order
            ));
            $db->execute();
            $order++;
        }

        return $order;
    }


    /**
     * Add the Submission Date, Last Modified Date and IP Address system fields. For the date fields, we also add in
     * a custom formatting to display the full datetime. This is because the default date formatting is date only -
     * I think that's probably going to be more useful as a default than a datetime - hence the extra work here.
     */
    public static function addSystemFields($form_id, $textbox_field_type_id, $order)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $date_field_type_id = FieldTypes::getFieldTypeIdByIdentifier("date");
        $date_field_type_datetime_setting_id = FieldTypes::getFieldTypeSettingIdByIdentifier($date_field_type_id, "display_format");
        $date_field_type_timezone_setting_id = FieldTypes::getFieldTypeSettingIdByIdentifier($date_field_type_id, "apply_timezone_offset");

        $insert_field_query = "
            INSERT INTO {PREFIX}field_settings (field_id, setting_id, setting_value)
            VALUES (:field_id, :setting_id, :setting_value)
        ";

        // submission date field
        $db->query("
            INSERT INTO {PREFIX}form_fields (form_id, field_name, field_test_value, field_type_id, is_system_field,
              field_title, data_type, col_name, list_order)
            VALUES (:form_id, 'core__submission_date', '', :field_type_id, 'yes', :field_title,
              'date', 'submission_date', :field_order)
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "field_type_id" => $date_field_type_id,
            "field_title" => $LANG["word_date"],
            "field_order" => $order
        ));
        $db->execute();
        $submission_date_field_id = $db->getInsertId();

        $db->query($insert_field_query);
        $db->bindAll(array(
            "field_id" => $submission_date_field_id,
            "setting_id" => $date_field_type_datetime_setting_id,
            "setting_value" => FieldTypes::$defaultDatetimeFormat
        ));
        $db->execute();

        $db->query($insert_field_query);
        $db->bindAll(array(
            "field_id" => $submission_date_field_id,
            "setting_id" => $date_field_type_timezone_setting_id,
            "setting_value" => "yes"
        ));
        $db->execute();

        // last modified date
        $db->query("
            INSERT INTO {PREFIX}form_fields (form_id, field_name, field_test_value, field_type_id, is_system_field,
              field_title, data_type, col_name, list_order)
            VALUES (:form_id, 'core__last_modified', '', :field_type_id, 'yes', :field_title, 'date',
              'last_modified_date', :field_order)
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "field_type_id" => $date_field_type_id,
            "field_title" => $LANG["phrase_last_modified"],
            "field_order" => $order + 1
        ));
        $db->execute();
        $last_modified_date_field_id = $db->getInsertId();

        $db->query($insert_field_query);
        $db->bindAll(array(
            "field_id" => $last_modified_date_field_id,
            "setting_id" => $date_field_type_datetime_setting_id,
            "setting_value" => FieldTypes::$defaultDatetimeFormat
        ));
        $db->execute();

        $db->query($insert_field_query);
        $db->bindAll(array(
            "field_id" => $last_modified_date_field_id,
            "setting_id" => $date_field_type_timezone_setting_id,
            "setting_value" => "yes"
        ));
        $db->execute();

        // ip address
        $db->query("
            INSERT INTO {PREFIX}form_fields (form_id, field_name, field_test_value, field_type_id, is_system_field,
              field_title, data_type, col_name, list_order)
            VALUES (:form_id, 'core__ip_address', '', :field_type_id, 'yes', :field_title, 'number', 'ip_address',
              :field_order)
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "field_type_id" => $textbox_field_type_id,
            "field_title" => $LANG["phrase_ip_address"],
            "field_order" => $order + 2
        ));
        $db->execute();
    }


    /**
     * Called on the Add External Form Step 4 page. It reorders the form fields and their groupings.
     *
     * @param integer $form_id
     * @param integer $infohash the POST data from the form
     * @param boolean $set_default_form_field_names if true, this tell the function to rename the columns
     */
    public static function updateFormFields($form_id, $infohash, $set_default_form_field_names = false)
    {
        $db = Core::$db;

        $sortable_id = $infohash["sortable_id"];
        $sortable_rows       = explode(",", $infohash["{$sortable_id}_sortable__rows"]);
        $sortable_new_groups = explode(",", $infohash["{$sortable_id}_sortable__new_groups"]);

        extract(Hooks::processHookCalls("start", compact("infohash", "form_id"), array("infohash")), EXTR_OVERWRITE);

        // get a list of the system fields so we don't overwrite anything special
        $existing_form_field_info = Fields::getFormFields($form_id);
        $system_field_ids = array();
        foreach ($existing_form_field_info as $form_field) {
            if ($form_field["is_system_field"] == "yes") {
                $system_field_ids[] = $form_field["field_id"];
            }
        }

        $order = 1;
        $custom_col_num = 1;
        foreach ($sortable_rows as $field_id) {
            $set_clauses = array("list_order = $order");
            if ($set_default_form_field_names && !in_array($field_id, $system_field_ids)) {
                $set_clauses[] = "col_name = 'col_$custom_col_num'";
                $custom_col_num++;
            }

            if (isset($infohash["field_{$field_id}_display_name"])) {
                $set_clauses[] = "field_title = '" . $infohash["field_{$field_id}_display_name"] . "'";
            }

            if (isset($infohash["field_{$field_id}_size"])) {
                $set_clauses[] = "field_size = '" . $infohash["field_{$field_id}_size"] . "'";
            }

            $is_new_sort_group = (in_array($field_id, $sortable_new_groups)) ? "yes" : "no";
            $set_clauses[] = "is_new_sort_group = '$is_new_sort_group'";

            $set_clauses_str = implode(",\n", $set_clauses);

            $db->query("
                UPDATE {PREFIX}form_fields
                SET    $set_clauses_str
                WHERE  field_id = :field_id AND
                       form_id = :form_id
            ");
            $db->bindAll(array(
                "field_id" => $field_id,
                "form_id" => $form_id
            ));
            $db->execute();
            $order++;
        }
    }


    /**
     * Deletes unwanted form fields. Called by administrator when creating an external form and when editing a form.
     *
     * Note: field types that require additional functionality when deleting a field type (e.g. file fields which
     * need to delete uploaded files), they need to define the appropriate hook. Generally this means the
     * "delete_fields" hook in the ft_update_form_fields_tab() function.
     */
    public static function deleteFormFields($form_id, $field_ids)
    {
        $LANG = Core::$L;

        // default return values
        $success = true;

        // find out if the form exists and is complete
        $form_info = Forms::getForm($form_id);
        $form_table_exists = ($form_info["is_complete"] == "yes") ? true : false;

        // stores the Views IDs of any View that is affected by deleting one of the form field, regardless of the field or form
        $affected_views = array();
        $removed_field_ids = array();

        $deleted_field_info = array();
        foreach ($field_ids as $field_id) {
            $field_id = trim($field_id);
            if (empty($field_id)) {
                continue;
            }

            // ignore brand new fields - nothing to delete!
            if (preg_match("/^NEW/", $field_id)) {
                continue;
            }

            $old_field_info = self::getFormField($field_id);
            $deleted_field_info[] = $old_field_info;

            @mysql_query("DELETE FROM {PREFIX}form_fields WHERE field_id = $field_id");
            if (!$form_table_exists) {
                continue;
            }

            mysql_query("DELETE FROM {PREFIX}new_view_submission_defaults WHERE field_id = $field_id");

            // see if this field had been flagged as an email field (either as the email field, first or last name).
            // if it's the email field, delete the whole row. If it's either the first or last name, just empty the value
            $query = mysql_query("SELECT form_email_id FROM {PREFIX}form_email_fields WHERE email_field_id = $field_id");
            while ($row = mysql_fetch_assoc($query)) {
                ft_unset_field_as_email_field($row["email_form_id"]);
            }
            mysql_query("UPDATE {PREFIX}form_email_fields SET first_name_field_id = '' WHERE first_name_field_id = $field_id");
            mysql_query("UPDATE {PREFIX}form_email_fields SET last_name_field_id = '' WHERE last_name_field_id = $field_id");

            // get a list of any Views that referenced this form field
            $view_query = mysql_query("SELECT view_id FROM {PREFIX}view_fields WHERE field_id = $field_id");
            while ($row = mysql_fetch_assoc($view_query)) {
                $affected_views[] = $row["view_id"];
                ft_delete_view_field($row["view_id"], $field_id);
            }

            $drop_column = $old_field_info["col_name"];
            mysql_query("ALTER TABLE {PREFIX}form_$form_id DROP $drop_column");

            // if any Views had this field as the default sort order, reset them to having the submission_date
            // field as the default sort order
            mysql_query("
              UPDATE {PREFIX}views
              SET     default_sort_field = 'submission_date',
                      default_sort_field_order = 'desc'
              WHERE   default_sort_field = '$drop_column' AND
                      form_id = $form_id
            ");

            $removed_field_ids[] = $field_id;
        }

        // update the list_order of this form's fields
        if ($form_table_exists) {
            Fields::autoUpdateFormFieldOrder($form_id);
        }

        // update the order of any Views that referenced this field
        foreach ($affected_views as $view_id) {
            ft_auto_update_view_field_order($view_id);
        }

        // determine the return message
        if (count($removed_field_ids) > 1) {
            $message = $LANG["notify_form_fields_removed"];
        } else {
            $message = $LANG["notify_form_field_removed"];
        }

        extract(Hooks::processHookCalls("end", compact("deleted_field_info", "form_id", "field_ids", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Retrieves all information about a specific form template field.
     *
     * @param integer $field_id the unique field ID
     * @return array A hash of information about this field.
     */
    public static function getFormField($field_id, $custom_params = array())
    {
        $db = Core::$db;

        $params = array(
            "include_field_type_info"   => (isset($custom_params["include_field_type_info"])) ? $custom_params["include_field_type_info"] : false,
            "include_field_settings"    => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false,
            "evaluate_dynamic_settings" => (isset($custom_params["evaluate_dynamic_settings"])) ? $custom_params["evaluate_dynamic_settings"] : false
        );

        if ($params["include_field_type_info"]) {
            $db->query("
                SELECT *
                FROM   {PREFIX}form_fields ff, {PREFIX}field_types ft
                WHERE  ff.field_id = :field_id AND
                       ff.field_type_id = ft.field_type_id
            ");
        } else {
            $db->query("
                SELECT *
                FROM   {PREFIX}form_fields
                WHERE  field_id = :field_id
            ");
        }
        $db->bind("field_id", $field_id);
        $db->execute();

        $info = $db->fetch();

        if ($params["include_field_settings"]) {
            $info["settings"] = Fields::getFormFieldSettings($field_id, $params["evaluate_dynamic_settings"]);
        }

        extract(Hooks::processHookCalls("end", compact("field_id", "info"), array("info")), EXTR_OVERWRITE);

        return $info;
    }

}
