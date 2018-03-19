<?php

/**
 * Fields.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, Exception;


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
     * retrieve the grouped fields list for a View, use ViewsFields::getViewFields().
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
            Fields::deleteAllFormFields($form_id);

            $db->query("DELETE FROM {PREFIX}field_settings WHERE field_id IN ($in_clause)");
            $db->bindAll($in_params);
            $db->execute();
            $db->processTransaction();

        } catch (Exception $e) {
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

        // TODO: "8" is very magical
        while (list($key) = each($file_data)) {
            $db->query("
                INSERT INTO {PREFIX}form_fields (form_id, field_name, field_type_id, is_system_field,
                  field_test_value, data_type, list_order)
                VALUES (:form_id, :field_key, 8, 'no', :field_label, 'string', :field_order)
            ");
            $db->bindAll(array(
                "form_id" => $form_id,
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

        FieldSettings::addSetting($submission_date_field_id, $date_field_type_datetime_setting_id, FieldTypes::$defaultDatetimeFormat);
        FieldSettings::addSetting($submission_date_field_id, $date_field_type_timezone_setting_id, "yes");

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

        FieldSettings::addSetting($last_modified_date_field_id, $date_field_type_datetime_setting_id, FieldTypes::$defaultDatetimeFormat);
        FieldSettings::addSetting($last_modified_date_field_id, $date_field_type_timezone_setting_id, "yes");

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
     * "delete_fields" hook in the updateFormFieldsTab() function.
     */
    public static function deleteFormFields($form_id, $field_ids)
    {
        $LANG = Core::$L;
        $removed_fields = array();

        foreach ($field_ids as $field_id) {
            $field_id = trim($field_id);
            if (empty($field_id)) {
                continue;
            }

            // ignore brand new fields - nothing to delete!
            if (preg_match("/^NEW/", $field_id)) {
                continue;
            }

            $field_map = Fields::getFieldColByFieldId($form_id, $field_id);

            self::deleteFormField($form_id, $field_id);

            $removed_fields[$field_id] = $field_map[$field_id];
        }

        // determine the return message
        if (count($removed_fields) > 1) {
            $message = $LANG["notify_form_fields_removed"];
        } else {
            $message = $LANG["notify_form_field_removed"];
        }

        extract(Hooks::processHookCalls("end", compact("removed_fields", "form_id", "field_ids", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

        return array(true, $message);
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


    /**
     * Adds new form field(s) to the database.
     *
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function addFormFieldsAdvanced($form_id, $fields)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $field_sizes = FieldSizes::get();

        $success = true;
        $message = "";

        foreach ($fields as $field_info) {
            $field_name    = $field_info["form_field_name"];
            $field_size    = $field_info["field_size"];
            $field_type_id = $field_info["field_type_id"];
            $display_name  = $field_info["display_name"];
            $include_on_redirect = $field_info["include_on_redirect"];
            $list_order = $field_info["list_order"];
            $col_name   = $field_info["col_name"];
            $is_new_sort_group = $field_info["is_new_sort_group"];

            // in order for the field to be added, it needs to have the label, name, size and column name. Otherwise
            // they're ignored
            if (empty($display_name) || empty($field_name) || empty($field_size) || empty($col_name)) {
                continue;
            }

            // TODO use transaction

            // add the new field to form_fields
            $db->query("
                INSERT INTO {PREFIX}form_fields (form_id, field_name, field_size, field_type_id,
                  data_type, field_title, col_name, list_order, is_new_sort_group, include_on_redirect)
                VALUES (:form_id, :field_name, :field_size, :field_type_id, 'string', :display_name, :col_name,
                  :list_order, :is_new_sort_group, :include_on_redirect)
            ");
            $db->bindAll(array(
                "form_id" => $form_id,
                "field_name" => $field_name,
                "field_size" => $field_size,
                "field_type_id" => $field_type_id,
                "display_name" => $display_name,
                "col_name" => $col_name,
                "list_order" => $list_order,
                "is_new_sort_group" => $is_new_sort_group,
                "include_on_redirect" => $include_on_redirect
            ));
            $db->execute();
            $new_field_id = $db->getInsertId();

            $new_field_size = $field_sizes[$field_size]["sql"];
            list($is_success) = General::addTableColumn("{PREFIX}form_{$form_id}", $col_name, $new_field_size);

            // if the alter table didn't work, return with an error message and remove the entry we just added to the form_fields table
            if (!$is_success) {
                if (!empty($new_field_id) && is_numeric($new_field_id)) {
                    $db->query("
                        DELETE FROM {PREFIX}form_fields
                        WHERE field_id = :field_id
                        LIMIT 1
                    ");
                    $db->bind("field_id", $new_field_id);
                    $db->execute();
                }
                $success = false;
                $replacement_info = array("fieldname" => $field_name);
                $message = General::evalSmartyString($LANG["notify_form_field_not_added"], $replacement_info);
                return array($success, $message);
            }
        }

        extract(Hooks::processHookCalls("end", compact("infohash", "form_id"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Helper function to return a field's database column name, based on its form field name.
     *
     * @param integer $form_id
     * @param string $field_name_or_names this can be a single field name or an array of field names
     * @return string the database column name, empty string if not found or an array of database column
     *     names if the $field_name_or_names was an array of field names
     */
    public static function getFieldColByFieldName($form_id, $field_name_or_names)
    {
        $db = Core::$db;

        if (is_array($field_name_or_names)) {
            $return_info = array();
            foreach ($field_name_or_names as $field_name) {
                $db->query("
                    SELECT col_name
                    FROM   {PREFIX}form_fields
                    WHERE  form_id = :form_id AND
                           field_name = :field_name
                ");
                $db->bindAll(array(
                    "form_id" => $form_id,
                    "field_name" => $field_name
                ));
                $db->execute();
                $result = $db->fetch();
                $return_info[] = (isset($result["col_name"])) ? $result["col_name"] : "";
            }
        } else {
            $db->query("
                SELECT col_name
                FROM   {PREFIX}form_fields
                WHERE  form_id = :form_id AND
                       field_name = :field_name
            ");
            $db->bindAll(array(
                "form_id" => $form_id,
                "field_name" => $field_name_or_names
            ));
            $db->execute();
            $result = $db->fetch();
            $return_info = (isset($result["col_name"])) ? $result["col_name"] : "";
        }

        return $return_info;
    }


    /**
     * Another getter function. This one finds out the column name for a field or fields,
     * based on their field IDs.
     *
     * TODO Bah! This should return a single bloody col_name string when passed a single field_id. Refactor! +2
     *
     * @param integer $form_id
     * @param mixed $field_id_or_ids integer or array of integers (field IDs)
     * @return array a hash of field_ids to col_names (only one key-value paid if single field ID passed)
     */
    public static function getFieldColByFieldId($form_id, $field_id_or_ids)
    {
        $db = Core::$db;
        if (is_array($field_id_or_ids)) {
            $field_id_str = implode(",", $field_id_or_ids);
        } else {
            $field_id_str = $field_id_or_ids;
        }

        $db->query("
            SELECT field_id, col_name
            FROM   {PREFIX}form_fields
            WHERE  form_id = :form_id AND
                   field_id IN ($field_id_str)
        ");
        $db->bind("form_id", $form_id);
        $db->execute();

        $return_info = array();
        foreach ($db->fetchAll() as $row) {
            $return_info[$row["field_id"]] = $row["col_name"];
        }

        return $return_info;
    }

    /**
     * Returns the field title by the field ID.
     *
     * @param integer $field_id
     * @return string the field title
     */
    public static function getFieldTitleByFieldId($field_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT field_title
            FROM   {PREFIX}form_fields
            WHERE  field_id = :field_id
        ");
        $db->bind("field_id", $field_id);
        $db->execute();

        $result = $db->fetch();
        $return_info = (isset($result["field_title"])) ? $result["field_title"] : "";

        return $return_info;
    }


    /**
     * Returns the field type ID by the field ID.
     *
     * @param integer $field_id
     * @return integer the field ID
     */
    public static function getFieldTypeIdByFieldId($field_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT field_type_id
            FROM   {PREFIX}form_fields
            WHERE  field_id = :field_id
        ");
        $db->bind("field_id", $field_id);
        $db->execute();
        $result = $db->fetch();
        $field_type_id = (isset($result["field_type_id"])) ? $result["field_type_id"] : "";

        return $field_type_id;
    }


    /**
     * Returns the field title by the field database column string.
     *
     * @param integer $form_id
     * @param string $col_name
     * @return string
     */
    public static function getFieldTitleByFieldCol($form_id, $col_name)
    {
        $db = Core::$db;

        $db->query("
            SELECT field_title
            FROM   {PREFIX}form_fields
            WHERE  form_id = :form_id AND
                   col_name = :col_name
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "col_name" => $col_name
        ));
        $db->execute();
        $result = $db->fetch();

        return (isset($result["field_title"])) ? $result["field_title"] : "";
    }


    /**
     * Returns all the field options for a particular multi-select field.
     *
     * @param integer $form_id the unique field ID.
     * @return array an array of field hashes
     */
    public static function getFieldOptions($field_id)
    {
        $db = Core::$db;

        // get the field option group ID
        $db->query("
            SELECT field_group_id
            FROM   {PREFIX}form_fields
            WHERE  field_id = :field_id
        ");
        $db->bind("field_id", $field_id);
        $db->execute();

        $result = $db->fetch();
        $group_id = $result["field_group_id"];

        if (!$group_id) {
            return array();
        }

        $db->query("
            SELECT *
            FROM   {PREFIX}field_options
            WHERE  field_group_id = :group_id
        ");
        $db->bind("group_id", $group_id);
        $db->execute();

        $options = $db->fetchAll();

        extract(Hooks::processHookCalls("end", compact("field_id", "options"), array("options")), EXTR_OVERWRITE);

        return $options;
    }


    /**
     * A getter function to retrieve everything about a form field from the database column name. This
     * is just a wrapper for Fields::getFormField().
     *
     * @param integer $form_id
     * @param string $col_name
     * @param array
     */
    public static function getFormFieldByColname($form_id, $col_name, $params = array())
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}form_fields
            WHERE  form_id = :form_id AND
                   col_name = :col_name
            LIMIT 1
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "col_name" => $col_name
        ));
        $db->execute();

        $info = $db->fetch();
        if (empty($info)) {
            return array();
        }

        $field_id = $info["field_id"];
        return Fields::getFormField($field_id, $params);
    }


    /**
     * Returns the field ID.
     *
     * @param string $field_name
     * @param integer $form_id
     * @return integer $field_id
     */
    public static function getFormFieldIdByFieldName($field_name, $form_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT field_id
            FROM   {PREFIX}form_fields
            WHERE  form_id = :form_id AND
                   field_name = :field_name
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "field_name" => $field_name
        ));
        $db->execute();
        $result = $db->fetch();

        return (isset($result["field_id"])) ? $result["field_id"] : "";
    }


    /**
     * Returns either a string (the field name), if a single field ID is passed, or a hash of field_id => field_names
     * if an array is passed.
     *
     * @param mixed $field_id_or_ids
     * @return mixed
     */
    public static function getFormFieldNameByFieldId($field_id_or_ids)
    {
        $db = Core::$db;

        $field_ids = array();
        if (is_array($field_id_or_ids)) {
            $field_ids = $field_id_or_ids;
        } else {
            $field_ids[] = $field_id_or_ids;
        }

        $field_id_str = implode(",", $field_ids);

        $db->query("
            SELECT field_id, field_name
            FROM   {PREFIX}form_fields
            WHERE  field_id IN ($field_id_str)
        ");
        $db->execute();

        if (is_array($field_id_or_ids)) {
            $result = $db->fetch();
            $return_info = $result["field_name"];
        } else {
            $return_info = array();
            foreach ($db->fetchAll() as $row) {
                $return_info[$row["field_id"]] = $row["field_name"];
            }
        }

        return $return_info;
    }


    /**
     * Returns the total number of form fields in a form.
     *
     * @param integer $form_id
     */
    public static function getNumFormFields($form_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT count(*)
            FROM   {PREFIX}form_fields
            WHERE  form_id = :form_id
        ");
        $db->bind("form_id", $form_id);
        $db->execute();

        return $db->fetch(PDO::FETCH_COLUMN);
    }


    /**
     * A getter function to retrieve everything about a form field from the database column name. This is used in
     * the ft_search_submissions function.
     *
     * @param integer $form_id
     * @param string $col_name
     * @return array
     */
    public static function getFieldOrderInfoByColname($form_id, $col_name)
    {
        $db = Core::$db;

        $db->query("
            SELECT ff.data_type, ft.is_date_field
            FROM   {PREFIX}form_fields ff, {PREFIX}field_types ft
            WHERE  ff.form_id = :form_id AND
                   ff.col_name = :col_name AND
                   ff.field_type_id = ft.field_type_id
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "col_name" => $col_name
        ));
        $db->execute();

        return $db->fetch();
    }


    /**
     * This function was totally rewritten in 2.1.0 for the new field settings structure. The ft_form_fields table
     * stores all the main settings for form fields which are shared across all fields, regardless of their
     * type. But some field types have "extended" settings, i.e. settings that only relate to that field type;
     * e.g. file upload fields allow for custom file upload URL & folders. Extended settings can now be created
     * by the administrator for any form field type through the Custom Fields module.
     *
     * Inheritance
     * -----------
     * When editing a field, the user has the option of checking the "Use Default" option for each field. If that's
     * checked, it will always inherit the setting value from the "default_value" setting value, defined in the
     * Custom Fields field type setting. Database-wise, if that value is checked, nothing is stored in the database:
     * this keeps the DB size as trim as possible.
     *
     * This function always returns all extended settings for a field, even those that use the default. The format
     * is:
     *
     *   array(
     *     array(
     *       "setting_id"    => X,
     *       "setting_value" => "...",
     *       "uses_default"  => true/false
     *     ),
     *     ...
     *   );
     *
     * @param integer $field_id
     * @param string $setting_id (optional)
     * @param boolean $convert_dynamic_values defaults to false just in case...
     * @return array an array of hashes
     */
    public static function getExtendedFieldSettings($field_id, $setting_id = "", $convert_dynamic_values = false)
    {
        // get whatever custom settings are defined for this field. These are settings that the user has overridden
        // in the Edit Field dialog
        $custom_settings = Fields::getFormFieldSettings($field_id);

        // now get a list of all settings defined for this field type
        $field_type_id = FieldTypes::getFieldTypeId($field_id);
        $field_type_settings = FieldTypes::getFieldTypeSettings($field_type_id);

        $settings = array();
        foreach ($field_type_settings as $curr_setting) {
            $curr_setting_id = $curr_setting["setting_id"];
            if (!empty($setting_id) && $setting_id != $curr_setting_id) {
                continue;
            }

            $uses_default  = true;
            $setting_value_type = $curr_setting["default_value_type"];
            $setting_value      = $curr_setting["default_value"];

            // if the user's specified a custom value for this field, return that value
            if (array_key_exists($curr_setting_id, $custom_settings)) {
                $uses_default  = false;
                $setting_value = $custom_settings[$curr_setting_id];
            } else if ($convert_dynamic_values && $setting_value_type == "dynamic") {
                $parts = explode(",", $setting_value);
                if (count($parts) == 2) {
                    $setting_value = Settings::get($parts[0], $parts[1]);
                }
            }

            $settings[] = array(
                "setting_id"    => $curr_setting_id,
                "setting_value" => $setting_value,
                "uses_default"  => $uses_default
            );
        }

        extract(Hooks::processHookCalls("end", compact("field_id", "setting_name"), array("settings")), EXTR_OVERWRITE);

        return $settings;
    }


    /**
     * Fields::getExtendedFieldSettings() doesn't quite do what I need, so I added this second function. It's
     * similar to FieldTypes::getFormFieldFieldTypeSettings(), except for a single field. All it does is return all
     * settings for a form field TAKING INTO ACCOUNT what's been overridden.
     *
     * Note: it returns the information as a hash of identifier => value pairs. This is fine, because no two field
     * settings for a single field type may have the same identifier.
     *
     * @param $field_id
     * @return array a hash of [identifier] = values
     */
    public static function getFieldSettings($field_id)
    {
        $db = Core::$db;

        if (empty($field_id) || !is_numeric($field_id)) {
            return array();
        }

        // get the overridden settings
        $db->query("
            SELECT fts.field_type_id, fs.field_id, fts.field_setting_identifier, fs.setting_value
            FROM   {PREFIX}field_type_settings fts, {PREFIX}field_settings fs
            WHERE  fts.setting_id = fs.setting_id AND
                   fs.field_id = :field_id
            ORDER BY fs.field_id
        ");
        $db->bind("field_id", $field_id);
        $db->execute();

        $overridden_settings = array();
        foreach ($db->fetchAll() as $row) {
            $overridden_settings[$row["field_setting_identifier"]] = $row["setting_value"];
        }

        $field_type_id = self::getFieldTypeIdByFieldId($field_id);
        $default_field_type_settings = FieldTypes::getFieldTypeSettings($field_type_id);

        // now overlay the two and return all field settings for all fields
        $complete_settings = array();
        foreach ($default_field_type_settings as $setting_info) {
            $identifier         = $setting_info["field_setting_identifier"];
            $default_value_type = $setting_info["default_value_type"];
            if ($default_value_type == "static") {
                $value = $setting_info["default_value"];
            } else {
                $parts = explode(",", $setting_info["default_value"]);

                // dynamic setting values should ALWAYS be of the form "setting_name,module_folder/'core'". If they're
                // not, just ignore it
                if (count($parts) != 2) {
                    $value = "";
                } else {
                    $value = Settings::get($parts[0], $parts[1]);
                }
            }

            // if the field has been overwritten use that instead!
            if (isset($overridden_settings[$identifier])) {
                $value = $overridden_settings[$identifier];
            }
            $complete_settings[$identifier] = $value;
        }

        return $complete_settings;
    }


    /**
     * Adds/updates all options for a given field. This is called when the user edits fields from the dialog
     * window on the Fields tab. It updates all information about a field: including the custom settings.
     *
     * TODO: holy crap. Refactor.
     *
     * @param integer $form_id The unique form ID
     * @param integer $field_id The unique field ID
     * @param integer $info a hash containing tab1 and/or tab2 indexes, containing all the latest values for
     *                the field
     * @param array [0] success/fail (boolean), [1] empty string for success, or error message
     */
    public static function updateField($form_id, $field_id, $tab_info)
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $FIELD_SIZES = FieldSizes::get();

        $existing_form_field_info = Fields::getFormField($field_id);

        // TAB 1: this tab contains the standard settings shared by all fields, regardless of type: display text,
        // form field name, field type, pass on, field size, data type and database col name
        $db_col_name_changes = array();
        if (is_array($tab_info["tab1"])) {
            $info = $tab_info["tab1"];
            $display_name = General::extractArrayVal($info, "edit_field__display_text");

            // bit weird. this field is a checkbox, so if it's not checked it won't be in the request and
            // _ft_extract_array_val returns an empty string
            $include_on_redirect = General::extractArrayVal($info, "edit_field__pass_on");
            $include_on_redirect = (empty($include_on_redirect)) ? "no" : "yes";

            if ($existing_form_field_info["is_system_field"] == "yes") {
                $db->query("
                    UPDATE {PREFIX}form_fields
                    SET    field_title = :field_title,
                           include_on_redirect = :include_on_redirect
                    WHERE  field_id = :field_id
                ");
                $db->bindAll(array(
                    "field_title" => $display_name,
                    "include_on_redirect" => $include_on_redirect,
                    "field_id" => $field_id
                ));

                try {
                    $db->execute();
                } catch (Exception $e) {
                    return array(false, $LANG["phrase_query_problem"] . $e->getMessage());
                }

            } else {
                $field_name    = General::extractArrayVal($info, "edit_field__field_name");
                $field_type_id = General::extractArrayVal($info, "edit_field__field_type");
                $field_size    = General::extractArrayVal($info, "edit_field__field_size");
                $data_type     = General::extractArrayVal($info, "edit_field__data_type");
                $col_name      = General::extractArrayVal($info, "edit_field__db_column");

                $db->query("
                    UPDATE {PREFIX}form_fields
                    SET    field_name = :field_name,
                           field_type_id = :field_type_id,
                           field_size = :field_size,
                           field_title = :field_title,
                           data_type = :data_type,
                           include_on_redirect = :include_on_redirect,
                           col_name = :col_name
                    WHERE  field_id = :field_id
                ");
                $db->bindAll(array(
                    "field_name" => $field_name,
                    "field_type_id" => $field_type_id,
                    "field_size" => $field_size,
                    "field_title" => $display_name,
                    "data_type" => $data_type,
                    "include_on_redirect" => $include_on_redirect,
                    "col_name" => $col_name,
                    "field_id" => $field_id
                ));

                try {
                    $db->execute();
                } catch (Exception $e) {
                    return array(false, $LANG["phrase_query_problem"] . $e->getMessage());
                }

                // if the column name or field size just changed, we need to "physically" update the form's database table
                // If this fails, we rollback both the field TYPE and the field size.
                // BUG The *one* potential issue here is if the user just deleted a field type, then updated a field which - for
                // whatever reason - fails. But this is very much a fringe case
                $old_field_size    = $existing_form_field_info["field_size"];
                $old_col_name      = $existing_form_field_info["col_name"];
                $old_field_type_id = $existing_form_field_info["field_type_id"];

                if ($old_field_size != $field_size || $old_col_name != $col_name) {
                    $new_field_size_sql = $FIELD_SIZES[$field_size]["sql"];
                    $table_name = "{PREFIX}form_{$form_id}";

                    list($is_success, $err_message) = General::alterTableColumn($table_name, $old_col_name, $col_name, $new_field_size_sql);
                    if ($is_success) {
                        if ($old_col_name != $col_name) {
                            $db_col_name_changes[] = $field_id;
                        }
                    } else {
                        $db->query("
                            UPDATE {PREFIX}form_fields
                            SET    field_type_id = :field_type_id,
                                   field_size    = :field_size,
                                   col_name      = :col_name
                            WHERE  field_id = :field_id
                        ");
                        $db->bindAll(array(
                            "field_type_id" => $old_field_type_id,
                            "field_size" => $old_field_size,
                            "col_name" => $old_col_name,
                            "field_id" => $field_id
                        ));

                        try {
                            $db->execute();
                        } catch (Exception $e) {
                            return array(false, $LANG["phrase_query_problem"] . $e->getMessage());
                        }

                        return array(false, $LANG["phrase_query_problem"] . $err_message);
                    }
                }

                // if the field type just changed, the field-specific settings are orphaned. Drop them. In this instance, the
                // client-side code ensures that the contents of the second tab are always passed so the code below will add
                // any default values that are needed
                if ($old_field_type_id != $field_type_id) {
                    FieldSettings::deleteSettings($field_id);
                }
            }
        }

        // if any of the database column names just changed we need to update any View filters that relied on them
        if (!empty($db_col_name_changes)) {
            foreach ($db_col_name_changes as $field_id) {
                ViewFilters::updateFieldFilters($field_id);
            }
        }

        // TAB 2: update the custom field settings for this field type. tab2 can be any of these values:
        //  1. a string "null": indicating that the user didn't change anything on the tab)
        //  2. the empty string: indicating that things DID change, but nothing is being passed on. This can happen
        //                      when the user checked the "Use Default Value" for all fields on the tab & the tab
        //                      doesn't contain an option list or form field
        //  3. an array of values
        if (isset($tab_info["tab2"]) && $tab_info["tab2"] != "null") {
            $info = is_array($tab_info["tab2"]) ? $tab_info["tab2"] : array();

            // since the second tab is being updated, we can rely on all the latest & greatest values being passed
            // in the request, so clean out all old values
            FieldSettings::deleteSettings($field_id);

            // convert the $info (which is an array of hashes) into a friendlier hash. This makes detecting for Option
            // List fields much easier
            $setting_hash = array();
            for ($i=0; $i<count($info); $i++) {
                $setting_hash[$info[$i]["name"]] = $info[$i]["value"];
            }

            $new_settings = array();
            foreach ($setting_hash as $setting_name => $setting_value) {

                // ignore the additional field ID and field order rows that are custom to Option List / Form Field types. They'll
                // be handled below
                if (preg_match("/edit_field__setting_(\d)+_field_id/", $setting_name) || preg_match("/edit_field__setting_(\d)+_field_order/", $setting_name)) {
                    continue;
                }

                $setting_id = preg_replace("/edit_field__setting_/", "", $setting_name);

                // if this field is being mapped to a form field, we serialize the form ID, field ID and order into a single var and
                // give it a "form_field:" prefix, so we know exactly what the data contains & we can select the appropriate form ID
                // and not Option List ID on re-editing. This keeps everything pretty simple, rather than spreading the data amongst
                // multiple fields
                // TODO the setting value starting with "ft" is special?! There can be arbitrary fields with text in them...
                if (preg_match("/^ft/", $setting_value)) {
                    $setting_value = preg_replace("/^ft/", "", $setting_value);
                    $setting_value = "form_field:$setting_value|" . $setting_hash["edit_field__setting_{$setting_id}_field_id"] . "|"
                        . $setting_hash["edit_field__setting_{$setting_id}_field_order"];
                }

                $new_settings[] = array(
                    "field_id" => $field_id,
                    "setting_id" => $setting_id,
                    "setting_value" => $setting_value
                );
            }

            if (!empty($new_settings)) {
                try {
                    $cols = array("field_id", "setting_id", "setting_value");
                    $db->insertQueryMultiple("field_settings", $cols, $new_settings);
                } catch (Exception $e) {
                    return array(false, $LANG["phrase_query_problem"] . ", " . $e->getMessage());
                }
            }
        }

        if (isset($tab_info["tab3"]) && $tab_info["tab3"] != "null") {
            $validation = is_array($tab_info["tab3"]) ? $tab_info["tab3"] : array();

            $db->query("DELETE FROM {PREFIX}field_validation WHERE field_id = :field_id");
            $db->bind("field_id", $field_id);
            $db->execute();

            foreach ($validation as $rule_info) {
                // ignore the checkboxes - we don't need 'em
                if (!preg_match("/^edit_field__v_(.*)_message$/", $rule_info["name"], $matches)) {
                    continue;
                }

                $rule_id = $matches[1];
                $error_message = $rule_info["value"];

                $db->query("
                    INSERT INTO {PREFIX}field_validation (rule_id, field_id, error_message)
                    VALUES (:rule_id, :field_id, :error_message)
                ");
                $db->bindAll(array(
                    "rule_id" => $rule_id,
                    "field_id" => $field_id,
                    "error_message" => $error_message
                ));
                $db->execute();
            }
        }

        $success = true;
        $message = $LANG["notify_form_field_options_updated"];

        extract(Hooks::processHookCalls("end", compact("field_id"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * This is called when the user updates the field type on the Edit Field Options page. It deletes all old
     * now-irrelevant settings, but retains values that will not change based on field type.
     *
     * @param integer $form_id
     * @param integer $field_id
     * @param string $new_field_type
     */
    public static function changeFieldType($form_id, $field_id, $new_field_type)
    {
        $db = Core::$db;

        $field_info = Fields::getFormField($field_id);

        // if the field just changes from one multi-select field to another (radio, checkboxes, select or multi-select)
        // don't delete the field_option group: it's probable that they just wanted to switch the appearance.
        $old_field_type = $field_info["field_type"];
        $multi_select_types = array("select", "multi-select", "radio-buttons", "checkboxes");

        $clauses = array("field_type = '$new_field_type'");
        if (!in_array($old_field_type, $multi_select_types) || !in_array($new_field_type, $multi_select_types)) {
            $clauses[] = "field_group_id = NULL";
        }
        if ($new_field_type == "file") {
            $clauses[] = "field_size = 'medium'";
        }

        $clauses_str = implode(",", $clauses);

        FieldSettings::deleteSettings($field_id);

        $db->query("
            UPDATE {PREFIX}form_fields
            SET    $clauses_str
            WHERE  field_id = :field_id
        ");
        $db->bind("field_id", $field_id);
        $db->execute();

        // if the user just changed to a file type, ALWAYS set the database field size to "medium"
        if ($old_field_type != $new_field_type && $new_field_type == "file") {
            General::alterTableColumn("{PREFIX}form_{$form_id}", $field_info["col_name"], $field_info["col_name"], "VARCHAR(255)");
        }
    }


    public static function updateFormField($field)
    {
        $db = Core::$db;

        if ($field["is_system_field"] == "yes") {
            $db->query("
                UPDATE {PREFIX}form_fields
                SET    field_title = :field_title,
                       include_on_redirect = :include_on_redirect,
                       list_order = :list_order,
                       is_new_sort_group = :is_new_sort_group
                WHERE  field_id = :field_id
            ");
            $db->bindAll(array(
                "field_title" => $field["display_name"],
                "include_on_redirect" => $field["include_on_redirect"],
                "list_order" => $field["list_order"],
                "is_new_sort_group" => $field["is_new_sort_group"],
                "field_id" => $field["field_id"]
            ));
        } else {
            $db->query("
                UPDATE {PREFIX}form_fields
                SET    field_name = :field_name,
                       field_title = :field_title,
                       field_size = :field_size,
                       col_name = :col_name,
                       data_type = :data_type,
                       field_type_id  = :field_type_id,
                       include_on_redirect = :include_on_redirect,
                       list_order = :list_order,
                       is_new_sort_group = :is_new_sort_group
                WHERE  field_id = :field_id
            ");
            $db->bindAll(array(
                "field_name" => $field["form_field_name"],
                "field_title" => $field["display_name"],
                "field_size" => $field["field_size"],
                "data_type" => $field["data_type"],
                "col_name" => $field["col_name"],
                "field_type_id" => $field["field_type_id"],
                "include_on_redirect" => $field["include_on_redirect"],
                "list_order" => $field["list_order"],
                "is_new_sort_group" => $field["is_new_sort_group"],
                "field_id" => $field["field_id"]
            ));
        }

        try {
            $db->execute();
        } catch (Exception $e) {
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
            exit;
        }
    }


    /**
     * Deletes a single field from a form configuration, and any references to it in any other tables. This is
     * executed as a single transaction to ensure data integrity.
     */
    public static function deleteFormField($form_id, $field_id)
    {
        $db = Core::$db;

        // find out if the form exists and is complete
        $form_info = Forms::getForm($form_id);
        $form_table_exists = $form_info["is_complete"] == "yes";

        // get the form field info before we delete it
        $old_field_info = self::getFormField($field_id);
        $drop_column = $old_field_info["col_name"];

        try {
            $db->beginTransaction();

            $db->query("DELETE FROM {PREFIX}form_fields WHERE field_id = :field_id");
            $db->bind("field_id", $field_id);
            $db->execute();

            $db->query("DELETE FROM {PREFIX}field_validation WHERE field_id = :field_id");
            $db->bind("field_id", $field_id);
            $db->execute();

            if ($form_table_exists) {
                $db->query("DELETE FROM {PREFIX}new_view_submission_defaults WHERE field_id = :field_id");
                $db->bind("field_id", $field_id);
                $db->execute();

                // see if this field had been flagged as an email field (either as the email field, first or last name).
                // if it's the email field, delete the whole row. If it's either the first or last name, just empty the value
                $db->query("SELECT form_email_id FROM {PREFIX}form_email_fields WHERE email_field_id = :field_id");
                $db->bind("field_id", $field_id);
                $db->execute();

                foreach ($db->fetchAll() as $row) {
                    Emails::unsetFieldAsEmailField($row["email_form_id"]);
                }

                $db->query("UPDATE {PREFIX}form_email_fields SET first_name_field_id = '' WHERE first_name_field_id = :field_id");
                $db->bind("field_id", $field_id);
                $db->execute();

                $db->query("UPDATE {PREFIX}form_email_fields SET last_name_field_id = '' WHERE last_name_field_id = :field_id");
                $db->bind("field_id", $field_id);
                $db->execute();

                // get a list of any Views that referenced this form field
                $db->query("SELECT view_id FROM {PREFIX}view_fields WHERE field_id = :field_id");
                $db->bind("field_id", $field_id);
                $db->execute();

                $view_ids = $db->fetchAll(PDO::FETCH_COLUMN);
                foreach ($view_ids as $view_id) {
                    ViewFields::deleteViewField($view_id, $field_id);
                }

                // now actually remove the column from the table
                $db->query("ALTER TABLE {PREFIX}form_$form_id DROP $drop_column");
                $db->execute();

                // if any Views had this field as the default sort order, reset them to having the submission_date
                // field as the default sort order
                $db->query("
                    UPDATE {PREFIX}views
                    SET     default_sort_field = 'submission_date',
                            default_sort_field_order = 'desc'
                    WHERE   default_sort_field = :default_sort_field AND
                            form_id = :form_id
                ");

                $db->bindAll(array(
                    "default_sort_field" => $drop_column,
                    "form_id" => $form_id
                ));
                $db->execute();
            }

            $db->processTransaction();

        } catch (Exception $e) {
            $db->rollbackTransaction();
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
        }
    }

    public static function deleteAllFormFields($form_id)
    {
        $db = Core::$db;

        $db->query("DELETE FROM {PREFIX}form_fields WHERE form_id = :form_id");
        $db->bind("form_id", $form_id);
        $db->execute();
    }
}

