<?php

/**
 * Views.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Views {

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
            $fields_info[] = Views::getViewField($view_id, $field_id, $params);
        }

        return $fields_info;
    }

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
            WHERE  view_id = $view_id AND
                   vf.field_id = ft.field_id AND
                   vf.field_id = $field_id
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
     * This function is called after creating a new form (Forms::finalizeForm), and creates a default
     * View - one containing all fields and assigned to all clients that are assigned to the form.
     *
     * Notes: I'm not terribly happy about the relationship between the list_groups table and whatever
     * they're grouping - here, Views. The issue is that to make the entries in the list_groups table
     * have additional meaning, I customize the group_type value to something like "form_X_view_group"
     * where "X" is the form name. ...
     *
     * @param integer $form_id
     */
    public static function addDefaultView($form_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        // 1. create the new View
        $form_info = Forms::getForm($form_id);
        $num_submissions_per_page = isset($_SESSION["ft"]["settings"]["num_submissions_per_page"]) ? $_SESSION["ft"]["settings"]["num_submissions_per_page"] : 10;

        $db->query("
            INSERT INTO {PREFIX}views (form_id, view_name, view_order, num_submissions_per_page,
              default_sort_field, default_sort_field_order)
            VALUES (:form_id, :view_name, '1', :view_order, 'submission_date', 'desc')
        ");
        $db->bindAll(array(
            "form_id" => $form_id,
            "view_name" => $LANG["phrase_all_submissions"],
            "view_order" => $num_submissions_per_page
        ));
        $db->execute();

        $view_id = $db->getInsertId();

        // 2. create the View group and update the view record we just created (blurgh!)
        $db->query("
            INSERT INTO {PREFIX}list_groups (group_type, group_name, list_order)
            VALUES (:group_type, :group_name, 1)
        ");
        $db->bindAll(array(
            "group_type" => "form_{$form_id}_view_group",
            "group_name" => $LANG["word_views"]
        ));
        $db->execute();
        $group_id = $db->getInsertId();

        $db->query("UPDATE {PREFIX}views SET group_id = :group_id WHERE view_id = :view_id");
        $db->bindAll(array(
            "group_id" => $group_id,
            "view_id" => $view_id
        ));
        $db->execute();

        // 3. add the default tabs [N.B. this table should eventually be dropped altogether and data moved to list_groups]
        $view_tab_inserts = array(
            "($view_id, 1, '{$LANG["phrase_default_tab_label"]}')",
            "($view_id, 2, '')",
            "($view_id, 3, '')",
            "($view_id, 4, '')",
            "($view_id, 5, '')",
            "($view_id, 6, '')"
        );
        $view_tab_insert_str = implode(",\n", $view_tab_inserts);
        $db->query("INSERT INTO {PREFIX}view_tabs VALUES $view_tab_insert_str");
        $db->execute();

        // now populate the new View fields and the View columns
        self::populateNewViewFields($form_id, $view_id);

        // assign the View to all clients attached to this form
        $client_info = $form_info["client_info"];
        foreach ($client_info as $user) {
            $account_id = $user["account_id"];
            $db->query("
                INSERT INTO {PREFIX}client_views (account_id, view_id)
                VALUES (:account_id, :view_id)
            ");
            $db->bindAll(array(
                "account_id" => $account_id,
                "view_id" => $view_id
            ));
            $db->execute();
        }

        return array(true, $LANG["notify_new_default_view_created"]);
    }


    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Helper function that's called when creating new Views. It populates the View fields and View column
     * with ALL form fields and 5 columns (Submission ID, Submission Date + 3 others).
     *
     * @param integer $form_id
     * @param integer $view_id
     */
    private static function populateNewViewFields($form_id, $view_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $db->query("
            INSERT INTO {PREFIX}list_groups (group_type, group_name, custom_data, list_order)
            VALUES (:group_type, :group_name, 1, 1)
        ");
        $db->bindAll(array(
            "group_type" => "view_fields_$view_id",
            "group_name" => $LANG["phrase_default_tab_label"]
        ));
        $db->execute();

        $view_fields_group_id = $db->getInsertId();

        $count = 1;
        $num_custom_fields_added = 0;
        $form_fields = Fields::getFormFields($form_id);

        $form_field_view_inserts = array();
        $view_column_inserts     = array();
        $view_column_order = 1;
        foreach ($form_fields as $field) {
            $field_id = $field["field_id"];

            // make the submission ID, submission date and the 1st 3 columns visible by default
            $is_column   = "no";
            if ($field["col_name"] == "submission_id" || $field["col_name"] == "submission_date") {
                $is_column   = "yes";
            } else {
                if ($num_custom_fields_added < 3) {
                    $is_column   = "yes";
                    $num_custom_fields_added++;
                }
            }

            // by default, make every field editable except the system fields
            $is_editable = ($field["is_system_field"] == "yes") ? "no" : "yes";
            $is_new_sort_group = $field["is_new_sort_group"];

            $form_field_view_inserts[] = "($view_id, $field_id, $view_fields_group_id, '$is_editable', $count, '$is_new_sort_group')";
            $count++;

            // if this is a column field, add the view_columns record
            if ($is_column == "yes") {
                $auto_size = "yes";
                $custom_width = "";
                if ($field["col_name"] == "submission_id") {
                    $auto_size    = "no";
                    $custom_width = 50;
                } else if ($field["col_name"] == "submission_date") {
                    $auto_size    = "no";
                    $custom_width = 160;
                }
                $view_column_inserts[] = "($view_id, $field_id, $view_column_order, 'yes', '$auto_size', '$custom_width', 'truncate')";
                $view_column_order++;
            }
        }

        // should NEVER be empty, but check anyway
        if (!empty($form_field_view_inserts)) {
            $form_field_view_insert_str = implode(",\n", $form_field_view_inserts);
            $db->query("
                INSERT INTO {PREFIX}view_fields (view_id, field_id, group_id, is_editable, list_order, is_new_sort_group)
                VALUES $form_field_view_insert_str
            ");
            $db->execute();
        }
        if (!empty($view_column_inserts)) {
            $view_columns_insert_str = implode(",\n", $view_column_inserts);
            $db->query("
                INSERT INTO {PREFIX}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
                VALUES $view_columns_insert_str
            ");
            $db->execute();
        }
    }
}
