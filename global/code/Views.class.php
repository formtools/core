<?php

/**
 * Views.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Views
{

    /**
     * Retrieves a list of all views for a form. As of 2.0.5 this function now always returns ALL Views,
     * instead of the option of a single page.
     *
     * @param integer $form_id the unique form ID
     * @return array a hash of view information
     */
    public static function getViews($form_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT view_id
            FROM   {PREFIX}views
            WHERE  form_id = :form_id
            ORDER BY view_order
        ");
        $db->bind("form_id", $form_id);
        $db->execute();

        $view_info = array();
        foreach ($db->fetchAll() as $row) {
            $view_id = $row["view_id"];
            $view_info[] = Views::getView($view_id);
        }

        $return_hash = array(
            "results" => $view_info,
            "num_results" => count($view_info)
        );

        extract(Hooks::processHookCalls("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

        return $return_hash;
    }


    /**
     * A simple, fast, no-frills function to return an array of all View IDs for a form. If you need it ordered,
     * include the second parameter. The second param makes it slower, so only use when needed.
     *
     * @param integer $form_id
     * @param boolean $order_results whether or not the results should be ordered. If so, it orders by view group,
     *    then view_order.
     * @return array
     */
    public static function getViewIds($form_id, $order_results = false)
    {
        $db = Core::$db;

        if ($order_results) {
            $db->query("
                SELECT view_id
                FROM   {PREFIX}views v, {PREFIX}list_groups lg
                WHERE  v.group_id = lg.group_id AND
                       form_id = :form_id
                ORDER BY lg.list_order, v.view_order
            ");
        } else {
            $db->query("SELECT view_id FROM {PREFIX}views WHERE form_id = :form_id");
        }
        $db->bind("form_id", $form_id);
        $db->execute();

        $view_ids = $db->fetchAll();

        extract(Hooks::processHookCalls("end", compact("view_ids"), array("view_ids")), EXTR_OVERWRITE);

        return $view_ids;
    }


    /**
     * Creates a new form View. If the $view_id parameter is set, it makes a copy of that View.
     * Otherwise, it creates a new blank view has *all* fields associated with it by default, a single tab
     * that is not enabled by default, no filters, and no clients assigned to it.
     *
     * @param integer $form_id the unique form ID
     * @param integer $group_id the view group ID that we're adding this View to
     * @param integer $create_from_view_id (optional) either the ID of the View from which to base this new View on,
     *                or "blank_view_no_fields" or "blank_view_all_fields"
     * @return integer the new view ID
     */
    public static function createNewView($form_id, $group_id, $view_name = "", $create_from_view_id = "")
    {
        $db = Core::$db;
        $LANG = Core::$L;

        // figure out the next View order number
        $db->query("SELECT count(*) as c FROM {PREFIX}views WHERE form_id = :form_id");
        $db->bind("form_id", $form_id);
        $db->execute();

        $count_hash = $db->fetch();
        $num_form_views = $count_hash["c"];
        $next_order = $num_form_views + 1;
        $view_name = (empty($view_name)) ? $LANG["phrase_new_view"] : $view_name;

        if ($create_from_view_id == "blank_view_no_fields" || $create_from_view_id == "blank_view_all_fields") {
            // add the View with default values
            $db->query("
                INSERT INTO {PREFIX}views (form_id, view_name, view_order, is_new_sort_group, group_id)
                VALUES (:form_id, :view_name, :view_order, 'yes', :group_id)
            ");
            $db->bindAll(array(
                "form_id" => $form_id,
                "view_name" => $view_name,
                "view_order" => $next_order,
                "group_id" => $group_id
            ));
            $db->execute();
            $view_id = $db->getInsertId();

            // add the default tab
            ViewTabs::addDefaultTabs($view_id);

            if ($create_from_view_id == "blank_view_all_fields") {
                Views::populateNewViewFields($form_id, $view_id);
            }
        } else {
            $view_info = Views::getView($create_from_view_id);

            // Main View Settings
            $access_type              = $view_info["access_type"];
            $num_submissions_per_page = $view_info["num_submissions_per_page"];
            $default_sort_field       = $view_info["default_sort_field"];
            $default_sort_field_order = $view_info["default_sort_field_order"];
            $may_add_submissions      = $view_info["may_add_submissions"];
            $may_edit_submissions     = $view_info["may_edit_submissions"];
            $may_delete_submissions   = $view_info["may_delete_submissions"];
            $has_standard_filter      = $view_info["has_standard_filter"];
            $has_client_map_filter    = $view_info["has_client_map_filter"];

            $db->query("
                INSERT INTO {PREFIX}views (form_id, access_type, view_name, view_order, is_new_sort_group, group_id,
                    num_submissions_per_page, default_sort_field, default_sort_field_order, may_add_submissions, may_edit_submissions,
                    may_delete_submissions, has_client_map_filter, has_standard_filter)
                VALUES (:form_id, :access_type, :view_name, :view_order, 'yes', :group_id, :num_submissions_per_page,
                    :default_sort_field, :default_sort_field_order, :may_add_submissions, :may_edit_submissions,
                    :may_delete_submissions, :has_client_map_filter, :has_standard_filter)
            ");
            $db->bindAll(array(
                "form_id" => $form_id,
                "access_type" => $access_type,
                "view_name" => $view_name,
                "view_order" => $next_order,
                "group_id" => $group_id,
                "num_submissions_per_page" => $num_submissions_per_page,
                "default_sort_field" => $default_sort_field,
                "default_sort_field_order" => $default_sort_field_order,
                "may_add_submissions" => $may_add_submissions,
                "may_edit_submissions" => $may_edit_submissions,
                "may_delete_submissions" => $may_delete_submissions,
                "has_client_map_filter" => $has_client_map_filter,
                "has_standard_filter" => $has_standard_filter
            ));
            $db->execute();

            $view_id = $db->getInsertId();

            foreach ($view_info["client_info"] as $client_info) {
                $account_id = $client_info["account_id"];
                $db->query("INSERT INTO {PREFIX}client_views (account_id, view_id) VALUES ($account_id, $view_id)");
            }

            // View Tabs
            $tab_labels = array();
            for ($i=1; $i<=6; $i++) {
                $tab_labels[] = $view_info["tabs"][$i]["tab_label"];
            }
            ViewTabs::addTabs($view_id, $tab_labels);

            // with 2.1.0, all View fields are now grouped. We need to duplicate all the groups as well as the fields
            $group_id_map = ViewFields::duplicateViewFieldGroups($create_from_view_id, $view_id);

            $field_view_inserts = array();
            foreach ($view_info["fields"] as $field_info) {
                $field_id      = $field_info["field_id"];
                $new_group_id  = $group_id_map[$field_info["group_id"]];
                $is_editable   = $field_info["is_editable"];
                $is_searchable = $field_info["is_searchable"];
                $list_order    = $field_info["list_order"];
                $is_new_sort_group = $field_info["is_new_sort_group"];
                $field_view_inserts[] = "($view_id, $field_id, $new_group_id, '$is_editable', '$is_searchable', $list_order, '$is_new_sort_group')";
            }

            if (!empty($field_view_inserts)) {
                $field_view_inserts_str = implode(",\n", $field_view_inserts);
                $db->query("
                    INSERT INTO {PREFIX}view_fields (view_id, field_id, group_id, is_editable, is_searchable, list_order, is_new_sort_group)
                    VALUES $field_view_inserts_str
                ");
                $db->execute();
            }

            $view_column_inserts = array();
            foreach ($view_info["columns"] as $field_info) {
                $field_id     = $field_info["field_id"];
                $list_order   = $field_info["list_order"];
                $is_sortable  = $field_info["is_sortable"];
                $auto_size    = $field_info["auto_size"];
                $custom_width = $field_info["custom_width"];
                $truncate     = $field_info["truncate"];
                $view_column_inserts[] = "($view_id, $field_id, $list_order, '$is_sortable', '$auto_size', '$custom_width', '$truncate')";
            }
            if (!empty($view_column_inserts)) {
                $view_column_insert_str = implode(",\n", $view_column_inserts);
                $db->query("
                    INSERT INTO {PREFIX}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
                    VALUES $view_column_insert_str
                ");
            }

            // View Filters
            foreach ($view_info["filters"] as $filter_info) {
                $field_id      = $filter_info["field_id"];
                $filter_type   = $filter_info["filter_type"];
                $operator      = $filter_info["operator"];
                $filter_values = $filter_info["filter_values"];
                $filter_sql    = $filter_info["filter_sql"];

                $db->query("
                    INSERT INTO {PREFIX}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
                    VALUES ($view_id, '$filter_type', $field_id, '$operator', '$filter_values', '$filter_sql')
                ");
            }

            // default submission values
            $submission_defaults = ft_get_new_view_submission_defaults($create_from_view_id);
            foreach ($submission_defaults as $row) {
                $db->query("
                    INSERT INTO {PREFIX}new_view_submission_defaults (view_id, field_id, default_value, list_order)
                    VALUES (:view_id, :field_id, :default_value, :list_order)
                ");
                $db->bindAll(array(
                    "view_id" => $view_id,
                    "field_id" => $row["field_id"],
                    "default_value" => $row["default_value"],
                    "list_order" => $row["list_order"]
                ));
                $db->execute();
            }

            // public View omit list
            $client_ids = ft_get_public_view_omit_list($create_from_view_id);
            foreach ($client_ids as $client_id) {
                $db->query("
                    INSERT INTO {PREFIX}public_view_omit_list (view_id, account_id)
                    VALUES (:view_id, :client_id)
                ");
                $db->bindAll(array(
                    "view_id" => $view_id,
                    "client_id" => $client_id
                ));
                $db->execute();
            }
        }

        extract(Hooks::processHookCalls("end", compact("view_id"), array()), EXTR_OVERWRITE);

        return $view_id;
    }


    /**
     * Finds out what Views are associated with a particular form field. Used when deleting a field.
     *
     * @param integer $field_id
     * @return array $view_ids
     */
    public static function getFieldViews($field_id)
    {
        $db = Core::$db;

        $db->query("SELECT view_id FROM {PREFIX}view_fields WHERE field_id = :field_id");
        $db->bind("field_id", $field_id);
        $db->execute();

        return $db->fetchAll();
    }


    /**
     * This checks to see if a View exists in the database.
     *
     * @param integer $view_id
     * @param boolean
     * @return boolean
     */
    public static function checkViewExists($view_id, $ignore_hidden_views = false)
    {
        $db = Core::$db;

        $view_clause = ($ignore_hidden_views) ? " AND access_type != 'hidden' " : "";

        $db->query("
            SELECT count(*) as c
            FROM {PREFIX}views
            WHERE view_id = :view_id
                  $view_clause
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        $results = $db->fetch();

        return $results["c"] > 0;
    }


    /**
     * Retrieves all information about a View, including associated user and filter info.
     *
     * @param integer $view_id the unique view ID
     * @return array a hash of view information
     */
    public static function getView($view_id, $custom_params = array())
    {
        $db = Core::$db;

        $params = array(
            "include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
        );

        $db->query("SELECT * FROM {PREFIX}views WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();

        $view_info = $db->fetch();
        $view_info["client_info"] = Views::getViewClients($view_id);
        $view_info["columns"]     = ViewColumns::getViewColumns($view_id);
        $view_info["fields"]      = ViewFields::getViewFields($view_id, $params);
        $view_info["filters"]     = ViewFilters::getViewFilters($view_id);
        $view_info["tabs"]        = ViewTabs::getViewTabs($view_id);
        $view_info["client_omit_list"] = (isset($view_info["access_type"]) && $view_info["access_type"] == "public") ?
        ft_get_public_view_omit_list($view_id) : array();

        extract(Hooks::processHookCalls("end", compact("view_id", "view_info"), array("view_info")), EXTR_OVERWRITE);

        return $view_info;
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
