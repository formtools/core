<?php

/**
 * Code related to managing fields within Views.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage ViewFields
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, Exception;


class ViewFields
{
    /**
     * Returns the View field values from the view_fields table, as well as a few values
     * from the corresponding form_fields table.
     *
     * @param integer $view_id the unique View ID
     * @param integer $field_id the unique field ID
     * @return array a hash containing the various view field values
     */
    public static function getViewField($view_id, $field_id, $custom_params = array())
    {
        $db = Core::$db;

        $params = array(
            "include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
        );

        $db->query("
            SELECT vf.*, ft.field_title, ft.col_name, ft.field_type_id, ft.field_name
            FROM   {PREFIX}view_fields vf, {PREFIX}form_fields ft
            WHERE  view_id = :view_id AND
                   vf.field_id = ft.field_id AND
                   vf.field_id = :field_id
        ");
        $db->bindAll(array(
            "view_id" => $view_id,
            "field_id" => $field_id
        ));
        $db->execute();

        $result = $db->fetch();
        if ($params["include_field_settings"]) {
            $result["field_settings"] = Fields::getFormFieldSettings($field_id);
        }

        return $result;
    }


    /**
     * Returns all fields in a View.
     *
     * @param integer $view_id the unique View ID
     * @return array $info an array of hashes containing the various view field values.
     */
    public static function getViewFields($view_id, $custom_params = array())
    {
        $db = Core::$db;

        $params = array(
            "include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
        );

        $db->query("
            SELECT vf.field_id
            FROM   {PREFIX}list_groups lg, {PREFIX}view_fields vf
            WHERE  lg.group_type = :group_type AND
                   lg.group_id = vf.group_id
            ORDER BY lg.list_order ASC, vf.list_order ASC
        ");
        $db->bind("group_type", "view_fields_$view_id");
        $db->execute();

        $fields_info = array();
        foreach ($db->fetchAll() as $field_info) {
            $field_id = $field_info["field_id"];
            $fields_info[] = ViewFields::getViewField($view_id, $field_id, $params);
        }

        return $fields_info;
    }


    /**
     * Deletes an specific View field. Called when a field is deleted.
     *
     * @param integer $view_id
     * @param integer $field_id
     */
    public static function deleteViewField($view_id, $field_id)
    {
        $db = Core::$db;

        $db->query("DELETE FROM {PREFIX}view_columns WHERE view_id = :view_id AND field_id = :field_id");
        $db->bindAll(array(
            "view_id" => $view_id,
            "field_id" => $field_id
        ));
        $db->execute();

        $db->query("DELETE FROM {PREFIX}view_filters WHERE view_id = :view_id AND field_id = :field_id");
        $db->bindAll(array(
            "view_id" => $view_id,
            "field_id" => $field_id
        ));
        $db->execute();

        $db->query("DELETE FROM {PREFIX}view_fields WHERE view_id = :view_id AND field_id = :field_id");
        $db->bindAll(array(
            "view_id" => $view_id,
            "field_id" => $field_id
        ));
        $db->execute();

        // now update the view field order to ensure there are no gaps
        ViewFields::autoUpdateViewFieldOrder($view_id);
    }

    /**
     * This function is called any time a form field is deleted, or unassigned to the View. It ensures
     * there are no gaps in the view_order
     *
     * @param integer $view_id
     */
    public static function autoUpdateViewFieldOrder($view_id)
    {
        $db = Core::$db;

        // we rely on this function returning the field by list_order
        $view_fields = ViewFields::getViewFields($view_id);

        $count = 1;
        foreach ($view_fields as $field_info) {
            $field_id = $field_info["field_id"];

            $db->query("
                UPDATE {PREFIX}view_fields
                SET    list_order = :list_order
                WHERE  view_id = :view_id AND
                       field_id = :field_id
            ");
            $db->bindAll(array(
                "list_order" => $count,
                "view_id" => $view_id,
                "field_id" => $field_id
            ));
            $db->execute();

            $count++;
        }
    }


    /**
     * Called by the Views::updateView function; updates the field settings of the View. This covers things like
     * which fields are included in the view, which appear as a column, which are editable and so on.
     *
     * @param integer $view_id
     * @param array $info
     */
    public static function updateViewFields($view_id, $info)
    {
        $db = Core::$db;

        $sortable_id  = $info["view_fields_sortable_id"];
        $grouped_info = explode("~", $info["{$sortable_id}_sortable__rows"]);
        $new_groups   = explode(",", $info["{$sortable_id}_sortable__new_groups"]);

        // empty the old View fields; we're about to update them
        ViewFields::deleteViewFields($view_id);

        // if there are any deleted groups, delete 'em! (N.B. we're not interested in deleted groups
        // that were just created in the page
        if (isset($info["deleted_groups"]) && !empty($info["deleted_groups"])) {
            $deleted_group_ids = explode(",", $info["deleted_groups"]);
            foreach ($deleted_group_ids as $group_id) {
                if (preg_match("/^NEW/", $group_id)) {
                    continue;
                }
                ListGroups::deleteListGroup($group_id);
            }
        }

        $new_group_order = 1;
        foreach ($grouped_info as $curr_grouped_info) {
            if (empty($curr_grouped_info)) {
                continue;
            }

            list($curr_group_id, $ordered_field_ids_str) = explode("|", $curr_grouped_info);
            $ordered_field_ids = explode(",", $ordered_field_ids_str);

            $group_name = $info["group_name_{$curr_group_id}"];
            $group_tab  = (isset($info["group_tab_{$curr_group_id}"]) && !empty($info["group_tab_{$curr_group_id}"])) ?
            $info["group_tab_{$curr_group_id}"] : "";

            if (preg_match("/^NEW/", $curr_group_id)) {
                $db->query("
                    INSERT INTO {PREFIX}list_groups (group_type, group_name, custom_data, list_order)
                    VALUES (:group_type, :group_name, :custom_data, :list_order)
                ");
                $db->bindAll(array(
                    "group_type" => "view_fields_{$view_id}",
                    "group_name" => $group_name,
                    "custom_data" => $group_tab,
                    "list_order" => $new_group_order
                ));
                $db->execute();
                $curr_group_id = $db->getInsertId();
            } else {
                $db->query("
                    UPDATE {PREFIX}list_groups
                    SET    group_name  = :group_name,
                           custom_data = :custom_data,
                           list_order  = :list_order
                    WHERE  group_id = :group_id
                ");
                $db->bindAll(array(
                    "group_name" => $group_name,
                    "custom_data" => $group_tab,
                    "list_order" => $new_group_order,
                    "group_id" => $curr_group_id
                ));
                $db->execute();
            }
            $new_group_order++;

            // if the user unchecked the "Allow fields to be edited" checkbox, nothing is passed for this field
            $editable_fields   = (isset($info["editable_fields"])) ? $info["editable_fields"] : array();
            $searchable_fields = (isset($info["searchable_fields"])) ? $info["searchable_fields"] : array();

            $field_order = 1;
            foreach ($ordered_field_ids as $field_id) {
                if (empty($field_id) || !is_numeric($field_id)) {
                    continue;
                }
                $is_editable   = (in_array($field_id, $editable_fields)) ? "yes" : "no";
                $is_searchable = (in_array($field_id, $searchable_fields)) ? "yes" : "no";
                $is_new_sort_group = (in_array($field_id, $new_groups)) ? "yes" : "no";

                $db->query("
                    INSERT INTO {PREFIX}view_fields (view_id, field_id, group_id, is_editable,
                        is_searchable, list_order, is_new_sort_group)
                    VALUES (:view_id, :field_id, :group_id, :is_editable, :is_searchable,
                        :list_order, :is_new_sort_group)
                ");
                $db->bindAll(array(
                    "view_id" => $view_id,
                    "field_id" => $field_id,
                    "group_id" => $curr_group_id,
                    "is_editable" => $is_editable,
                    "is_searchable" => $is_searchable,
                    "list_order" => $field_order,
                    "is_new_sort_group" => $is_new_sort_group
                ));
                $db->execute();

                $field_order++;
            }
        }
    }


    /**
     * This makes a copy of all field groups for a View and returns a hash of old group IDs to new group IDs.
     * It's used in the create View functionality when the user wants to base the new View on an existing
     * one.
     *
     * @param integer $source_view_id
     * @param integer $target_view_id
     * @return array
     */
    public static function duplicateViewFieldGroups($source_view_id, $target_view_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}list_groups
            WHERE group_type = :group_type
            ORDER BY list_order
        ");
        $db->bind("group_type", "view_fields_{$source_view_id}");
        $db->execute();

        $map = array();
        foreach ($db->fetchAll() as $row) {
            $group_id = $row["group_id"];

            try {
                $db->query("
                    INSERT INTO {PREFIX}list_groups (group_type, group_name, custom_data, list_order)
                    VALUES (:group_type, :group_name, :custom_data, :list_order)
                ");
                $db->bindAll(array(
                    "group_type" => "view_fields_{$target_view_id}",
                    "group_name" => $row["group_name"],
                    "custom_data" => $row["custom_data"],
                    "list_order" => $row["list_order"]
                ));
                $db->execute();
            } catch (Exception $e) {
                Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
                exit;
            }

            $map[$group_id] = $db->getInsertId();
        }

        return $map;
    }

    /**
     * Return all fields in a View. If this is being on the edit submission page, the second optional
     * parameter is used to limit the results to ONLY those groups on the appropriate tab.
     *
     * @param integer $view_id
     * @param integer $tab_number
     * @param integer $form_id       - this is optional. If this and the next $submission_id param is defined,
     *                                 details about the actual form submission is returned as well
     * @param integer $submission_id
     * @return array
     */
    public static function getGroupedViewFields($view_id, $tab_number = "", $form_id = "", $submission_id = "")
    {
        $db = Core::$db;

        if (!empty($submission_id)) {
            $submission_info = Submissions::getSubmissionInfo($form_id, $submission_id);
        }

        $tab_clause = (!empty($tab_number)) ? "AND custom_data = $tab_number" : "";

        $db->query("
            SELECT *
            FROM  {PREFIX}list_groups
            WHERE  group_type = :group_type
                   $tab_clause
            ORDER BY list_order
        ");
        $db->bind("group_type", "view_fields_{$view_id}");
        $db->execute();

        $grouped_info = array();
        foreach ($db->fetchAll() as $group_info) {
            $group_id = $group_info["group_id"];

            $db->query("
                SELECT *, vf.list_order as list_order, vf.is_new_sort_group as view_field_is_new_sort_group
                FROM   {PREFIX}view_fields vf, {PREFIX}form_fields ff
                WHERE  group_id = :group_id AND
                       vf.field_id = ff.field_id
                ORDER BY vf.list_order
            ");
            $db->bind("group_id", $group_id);
            $db->execute();

            $fields_info = array();
            $field_ids   = array();
            foreach ($db->fetchAll() as $row) {
                $field_ids[]   = $row["field_id"];
                $fields_info[] = $row;
            }

            // for efficiency reasons, we just do a single query to find all validation rules for the all relevant fields
            $rules_by_field_id = array();
            if (!empty($field_ids)) {
                $field_ids_str = implode(",", $field_ids);
                $db->query("
                    SELECT *
                    FROM   {PREFIX}field_validation fv, {PREFIX}field_type_validation_rules ftvr
                    WHERE  fv.field_id IN ($field_ids_str) AND
                           fv.rule_id = ftvr.rule_id
                ");
                $db->execute();

                foreach ($db->fetchAll() as $rule_info) {
                    $field_id = $rule_info["field_id"];
                    if (!array_key_exists($field_id, $rules_by_field_id)) {
                        $rules_by_field_id[$field_id]["is_required"] = false;
                        $rules_by_field_id[$field_id]["rules"] = array();
                    }

                    $rules_by_field_id[$field_id]["rules"][] = $rule_info;
                    if ($rule_info["rsv_rule"] == "required" || ($rule_info["rsv_rule"] == "function" && $rule_info["custom_function_required"] == "yes")) {
                        $rules_by_field_id[$field_id]["is_required"] = true;
                    }
                }
            }

            // now merge the original field info with the new validation rules. "required" is a special validation rule: that's
            // used to determine whether or not an asterix should appear next to the field. As such, we pass along a
            // custom "is_required" key
            $updated_field_info = array();
            foreach ($fields_info as $field_info) {
                $curr_field_id = $field_info["field_id"];
                $field_info["validation"] = array_key_exists($curr_field_id, $rules_by_field_id) ? $rules_by_field_id[$curr_field_id]["rules"] : array();
                $field_info["is_required"] = array_key_exists($curr_field_id, $rules_by_field_id) ? $rules_by_field_id[$curr_field_id]["is_required"] : false;
                $updated_field_info[] = $field_info;
            }
            $fields_info = $updated_field_info;

            // now, if the submission ID is set it returns an additional submission_value key
            if (!empty($field_ids)) {
                // do a single query to get a list of ALL settings for any of the field IDs we're dealing with
                $field_id_str = implode(",", $field_ids);
                $db->query("
                    SELECT *
                    FROM   {PREFIX}field_settings
                    WHERE  field_id IN ($field_id_str)
                ");
                $db->execute();

                $field_settings = array();
                foreach ($db->fetchAll() as $row) {
                    $field_id = $row["field_id"];
                    if (!array_key_exists($field_id, $field_settings)) {
                        $field_settings[$field_id] = array();
                    }
                    $field_settings[$field_id][] = array($row["setting_id"] => $row["setting_value"]);
                }

                // now append the submission info to the field info that we already have stored
                $updated_fields_info = array();
                foreach ($fields_info as $curr_field_info) {
                    $curr_col_name = $curr_field_info["col_name"];
                    $curr_field_id = $curr_field_info["field_id"];
                    $curr_field_info["field_settings"] = (array_key_exists($curr_field_id, $field_settings)) ? $field_settings[$curr_field_id] : array();

                    if (!empty($submission_id)) {
                        $curr_field_info["submission_value"] = $submission_info[$curr_col_name];
                    }
                    $updated_fields_info[] = $curr_field_info;
                }
                $fields_info = $updated_fields_info;
            }

            $grouped_info[] = array(
                "group"  => $group_info,
                "fields" => $fields_info
            );
        }

        return $grouped_info;
    }


    /**
     * This returns the database column names of all searchable fields in this View. To reduce the number of
     * DB queries, this function allows you to pass in all field info to just extract the information from that.
     *
     * @param integer $view_id optional, but if not supplied, the second $fields parameter is require
     * @param array $fields optional, but if not supplied, the first $view_id param is required. This should
     *   be the $view_info["fields"] key, returned from $view_info = Views::getView($view_id), which contains all
     *   View field info
     *
     * @return array an array of searchable database column names
     */
    public static function getViewSearchableFields($view_id = "", $fields = array())
    {
        if (!empty($view_id) && is_numeric($view_id)) {
            $view_info = Views::getView($view_id);
            $fields = $view_info["fields"];
        }
        $searchable_columns = array();
        foreach ($fields as $field_info) {
            if ($field_info["is_searchable"] == "yes") {
                $searchable_columns[] = $field_info["col_name"];
            }
        }
        return $searchable_columns;
    }


    /**
     * Helper function to return all editable field IDs in a View. This is used for security purposes
     * to ensure that anyone editing a form submission can't hack it and send along fake values for
     * fields that don't appear in the form.
     *
     * @param integer $view_id
     * @return array a list of field IDs
     */
    public static function getEditableViewFields($view_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT field_id
            FROM   {PREFIX}view_fields
            WHERE  is_editable = 'yes' AND
                   view_id = :view_id
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        return $db->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Deletes all fields in a view. Note that View fields are *independent* of View columns and filters: a user
     * is permitted to add columns or filters for fields that aren't shown in the View.
     * @param $view_id
     */
    public static function deleteViewFields($view_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}view_fields WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();
    }
}
