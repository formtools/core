<?php

/**
 * This file contains all top-level methods relating to form Views. See the other View*.class.php files for more
 * specific things within Views (ViewFields, ViewFilters).
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Views
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, Exception;


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

        $view_ids = $db->fetchAll(PDO::FETCH_COLUMN);

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
    public static function createView($form_id, $group_id, $view_name = "", $create_from_view_id = "")
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

        if (empty($create_from_view_id) || $create_from_view_id == "blank_view_no_fields" || $create_from_view_id == "blank_view_all_fields") {
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

            if (empty($create_from_view_id) || $create_from_view_id == "blank_view_all_fields") {
                Views::populateNewViewFields($form_id, $view_id);
            }
        } else {
            $view_info = Views::getView($create_from_view_id);

            // Main View Settings
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
                "access_type" => $view_info["access_type"],
                "view_name" => $view_name,
                "view_order" => $next_order,
                "group_id" => $group_id,
                "num_submissions_per_page" => $view_info["num_submissions_per_page"],
                "default_sort_field" => $view_info["default_sort_field"],
                "default_sort_field_order" => $view_info["default_sort_field_order"],
                "may_add_submissions" => $view_info["may_add_submissions"],
                "may_edit_submissions" => $view_info["may_edit_submissions"],
                "may_delete_submissions" => $view_info["may_delete_submissions"],
                "has_client_map_filter" => $view_info["has_client_map_filter"],
                "has_standard_filter" => $view_info["has_standard_filter"]
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
                $db->execute();
            }

            // View Filters
            foreach ($view_info["filters"] as $filter_info) {
                $db->query("
                    INSERT INTO {PREFIX}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
                    VALUES (:view_id, :filter_type, :field_id, :operator, :filter_values, :filter_sql)
                ");
                $db->bindAll(array(
                    "view_id" => $view_id,
                    "filter_type" => $filter_info["filter_type"],
                    "field_id" => $filter_info["field_id"],
                    "operator" => $filter_info["operator"],
                    "filter_values" => $filter_info["filter_values"],
                    "filter_sql" => $filter_info["filter_sql"]
                ));
                $db->execute();
            }

            // default submission values
            $submission_defaults = Views::getNewViewSubmissionDefaults($create_from_view_id);
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
            $client_ids = Views::getPublicViewOmitList($create_from_view_id);
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
     * This is called by the administrator on the main Views tab. It lets them delete an entire group of Views
     * in one go.
     *
     * @param integer $group_id
     */
    public static function deleteViewGroup($group_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $db->query("
            SELECT view_id
            FROM   {PREFIX}views
            WHERE  group_id = :group_id
        ");
        $db->bind("group_id", $group_id);
        $db->execute();

        foreach ($db->fetchAll(PDO::FETCH_COLUMN) as $view_id) {
            Views::deleteView($view_id);
        }

        // next, delete the group
        ListGroups::deleteListGroup($group_id);

        // TODO: should update order of other View groups

        return array(true, $LANG["notify_view_group_deleted"]);
    }


    /**
     * Deletes a View and updates the list order of the Views in the same View group.
     *
     * @param integer $view_id the unique view ID
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function deleteView($view_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        Views::deleteClientViews($view_id);
        ViewTabs::deleteViewTabs($view_id);
        ViewFields::deleteViewFields($view_id);
        ViewFilters::deleteViewFilters($view_id);
        ViewColumns::deleteViewColumns($view_id);
        Views::deletePublicViewOmitList($view_id);
        ListGroups::deleteByGroupType("view_fields_$view_id");

        $db->query("DELETE FROM {PREFIX}email_template_edit_submission_views WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();

        $db->query("DELETE FROM {PREFIX}email_template_when_sent_views WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();

        $db->query("DELETE FROM {PREFIX}new_view_submission_defaults WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();

        $db->query("DELETE FROM {PREFIX}views WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();

        // hmm... This should be handled better: the user needs to be notified prior to deleting a View to describe
        // all the dependencies
        $db->query("
            UPDATE {PREFIX}email_templates
            SET limit_email_content_to_fields_in_view = NULL
            WHERE limit_email_content_to_fields_in_view = :view_id
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        $success = true;
        $message = $LANG["notify_view_deleted"];
        extract(Hooks::processHookCalls("end", compact("view_id"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    public static function deletePublicViewOmitList($view_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}public_view_omit_list WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();
    }


    /**
     * @param $view_id
     */
    public static function deleteClientViews($view_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}client_views WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();

        Views::deletePublicViewOmitList($view_id);
    }


    public static function deleteClientViewsByAccountId($account_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}client_views WHERE account_id = :account_id");
        $db->bind("account_id", $account_id);
        $db->execute();
    }

    public static function deleteClientViewsByFormId($form_id)
    {
        $view_ids = Views::getViewIds($form_id);
        foreach ($view_ids as $view_id) {
            Views::deleteClientViews($view_id);
        }
    }

    /**
     * Called by the administrator only. Updates the list of clients on a public View's omit list.
     *
     * @param array $info
     * @param integer $view_id
     * @return array [0] T/F, [1] message
     */
    public static function updatePublicViewOmitList($info, $view_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        Views::deletePublicViewOmitList($view_id);

        $client_ids = (isset($info["selected_client_ids"])) ? $info["selected_client_ids"] : array();
        foreach ($client_ids as $account_id) {
            $db->query("INSERT INTO {PREFIX}public_view_omit_list (view_id, account_id) VALUES (:view_id, :account_id)");
            $db->bindAll(array(
                "view_id" => $view_id,
                "account_id" => $account_id
            ));
            $db->execute();
        }

        return array(true, $LANG["notify_public_view_omit_list_updated"]);
    }


    /**
     * Caches the total number of (finalized) submissions in a particular form - or all forms -
     * in the $_SESSION["ft"]["form_{$form_id}_num_submissions"] key. That value is used on the administrators
     * main Forms page to list the form submission count.
     *
     * @param integer $form_id
     */
    public static function cacheViewStats($form_id, $view_id = "")
    {
        $db = Core::$db;

        $view_ids = array();
        if (empty($view_id)) {
            $view_ids = Views::getViewIds($form_id);
        } else {
            $view_ids[] = $view_id;
        }

        foreach ($view_ids as $view_id) {
            $filters = ViewFilters::getViewFilterSql($view_id);

            // if there aren't any filters, just set the submission count & first submission date to the same
            // as the parent form
            if (empty($filters)) {
                Sessions::set("view_{$view_id}_num_submissions", Sessions::get("form_{$form_id}_num_submissions"));
            } else {
                $filter_clause = join(" AND ", $filters);

                try {
                    $db->query("
                        SELECT count(*) as c
                        FROM   {PREFIX}form_$form_id
                        WHERE  is_finalized = 'yes' AND
                               $filter_clause
                    ");
                    $db->execute();
                } catch (Exception $e) {
                    Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
                    exit;
                }

                $info = $db->fetch();
                Sessions::set("view_{$view_id}_num_submissions", $info["c"]);
            }
        }
    }


    /**
     * A very simple getter function that retrieves an an ordered array of view_id => view name hashes for a
     * particular form.
     *
     * @param integer $form_id
     * @return array
     */
    public static function getViewList($form_id)
    {
        $db = Core::$db;

        try {
            $db->query("
                SELECT view_id, view_name
                FROM   {PREFIX}views
                WHERE  form_id = $form_id
                ORDER BY view_order
            ");
        } catch (Exception $e) {
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
            exit;
        }

        $result = $db->fetchAll();

        extract(Hooks::processHookCalls("end", compact("form_id", "result"), array("result")), EXTR_OVERWRITE);

        return $result;
    }


    /**
     * Used internally. This is called to figure out which View should be used by default. It actually
     * just picks the first on in the list of Views.
     *
     * @param integer $form_id
     * @return mixed $view_id the View ID or the empty string if no Views associated with form.
     */
    public static function getDefaultView($form_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT view_id
            FROM   {PREFIX}views
            WHERE  form_id = :form_id
            ORDER BY view_order
            LIMIT 1
        ");
        $db->bind("form_id", $form_id);
        $db->execute();

        $view_id = "";
        $view_info = $db->fetch();

        if (!empty($view_info)) {
            $view_id = $view_info["view_id"];
        }

        return $view_id;
    }


    /**
     * This feature was added in 2.1.0 - it lets administrators define default values for all new submissions
     * created with the View. This was added to solve a problem where submissions were created in a View where
     * that new submission wouldn't meet the criteria for inclusion. But beyond that, this is a handy feature to
     * cut down on configuration time for new data sets.
	 *
	 * N.B. The Submission Pre-Parser relies heavily on this method and the format of the data returned, so
	 * if refactoring check that too.
     *
     * @param $view_id
     * @return array
     */
    public static function getNewViewSubmissionDefaults($view_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM {PREFIX}new_view_submission_defaults
            WHERE view_id = :view_id
            ORDER BY list_order
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        $results = $db->fetchAll();

        extract(Hooks::processHookCalls("end", compact("results", "view_id"), array("results")), EXTR_OVERWRITE);

        return $results;
	}


    /**
     * Returns a list of all clients associated with a particular View.
     *
     * @param integer $view_id the unique View ID
     * @return array $info an array of arrays, each containing the user information.
     */
    public static function getViewClients($view_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}client_views cv, {PREFIX}accounts a
            WHERE  cv.view_id = :view_id
            AND    cv.account_id = a.account_id
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        $account_info = $db->fetchAll();

        extract(Hooks::processHookCalls("end", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

        return $account_info;
    }


    /**
     * Called by administrators on the main View tab. This updates the orders and the grouping of the all form Views.
     *
     * @param integer $form_id the form ID
     * @param array $info the form contents
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function updateViews($info)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $sortable_id = $info["sortable_id"];
        $grouped_info = explode("~", $info["{$sortable_id}_sortable__rows"]);
        $new_groups   = explode(",", $info["{$sortable_id}_sortable__new_groups"]);

        $new_group_order = 1;
        foreach ($grouped_info as $curr_grouped_info) {
            list($curr_group_id, $ordered_view_ids_str) = explode("|", $curr_grouped_info);
            $ordered_view_ids = explode(",", $ordered_view_ids_str);
            $group_name = $info["group_name_{$curr_group_id}"];

            $db->query("
                UPDATE {PREFIX}list_groups
                SET    group_name = :group_name,
                       list_order = :list_order
                WHERE  group_id = :group_id
            ");
            $db->bindAll(array(
                "group_name" => $group_name,
                "list_order" => $new_group_order,
                "group_id" => $curr_group_id
            ));
            $db->execute();

            $new_group_order++;

            $order = 1;
            foreach ($ordered_view_ids as $view_id) {
                $is_new_sort_group = (in_array($view_id, $new_groups)) ? "yes" : "no";
                $db->query("
                    UPDATE {PREFIX}views
                    SET	   view_order = :view_order,
                           group_id = :group_id,
                           is_new_sort_group = :is_new_sort_group
                    WHERE  view_id = :view_id
                ");
                $db->bindAll(array(
                    "view_order" => $order,
                    "group_id" => $curr_group_id,
                    "is_new_sort_group" => $is_new_sort_group,
                    "view_id" => $view_id
                ));
                $db->execute();

                $order++;
            }
        }

        // return success
        return array(true, $LANG["notify_form_views_updated"]);
    }


    /**
     * Updates a single View, called from the Edit View page. This function updates all aspects of the
     * View from the overall settings, field list and custom filters.
     *
     * @param integer $view_id the unique View ID
     * @param array $infohash a hash containing the contents of the Edit View page
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function updateView($view_id, $info)
    {
        // update each of the tabs
        Views::updateViewMainSettings($view_id, $info);

        // remember! View cols & filters are independent of View fields, i.e. the user can choose to apply a filter
        // to a field that isn't shown in the View. Same as show a different column.
        ViewColumns::updateViewColumns($view_id, $info);
        ViewFields::updateViewFields($view_id, $info);
        ViewTabs::updateViewTabs($view_id, $info);
        ViewFilters::updateViewFilters($view_id, $info);

        $success = true;
        $message = Core::$L["notify_view_updated"];
        extract(Hooks::processHookCalls("end", compact("view_id", "info"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }



    /**
     * Called by the Views::updateView function; updates the main settings of the View (found on the
     * first tab). Also updates the may_edit_submissions setting found on the second tab.
     *
     * @param integer $view_id
     * @param array $info
     */
    public static function updateViewMainSettings($view_id, $info)
    {
        $db = Core::$db;

        $view_name = $info["view_name"];

        $num_submissions_per_page = isset($info["num_submissions_per_page"]) ? $info["num_submissions_per_page"] : 10;
        $default_sort_field       = $info["default_sort_field"];
        $default_sort_field_order = $info["default_sort_field_order"];
        $access_type              = $info["access_type"];
        $may_add_submissions      = $info["may_add_submissions"];
		$may_copy_submissions     = $info["may_copy_submissions"];
        $may_edit_submissions     = isset($info["may_edit_submissions"]) ? "yes" : "no"; // (checkbox field)
		$may_delete_submissions   = $info["may_delete_submissions"];

        // do a little error checking on the num submissions field. If it's invalid, just set to to 10 without
        // informing them - it's not really necessary, I don't think
        if (!is_numeric($num_submissions_per_page)) {
            $num_submissions_per_page = 10;
        }

        $db->query("
            UPDATE {PREFIX}views
            SET    access_type = :access_type,
                   view_name = :view_name,
                   num_submissions_per_page = :num_submissions_per_page,
                   default_sort_field = :default_sort_field,
                   default_sort_field_order = :default_sort_field_order,
                   may_add_submissions = :may_add_submissions,
                   may_copy_submissions = :may_copy_submissions,
                   may_edit_submissions = :may_edit_submissions,
                   may_delete_submissions = :may_delete_submissions
            WHERE  view_id = :view_id
        ");
        $db->bindAll(array(
            "access_type" => $access_type,
            "view_name" => $view_name,
            "num_submissions_per_page" => $num_submissions_per_page,
            "default_sort_field" => $default_sort_field,
            "default_sort_field_order" => $default_sort_field_order,
			"may_add_submissions" => $may_add_submissions,
			"may_copy_submissions" => $may_copy_submissions,
            "may_edit_submissions" => $may_edit_submissions,
			"may_delete_submissions" => $may_delete_submissions,
            "view_id" => $view_id
        ));
        $db->execute();


        switch ($access_type) {
            case "admin":
                Views::deleteClientViews($view_id);
                Views::deletePublicViewOmitList($view_id);
                break;

            case "public":
                Views::deleteClientViews($view_id);
                break;

            case "private":
                $selected_user_ids = isset($info["selected_user_ids"]) ? $info["selected_user_ids"] : array();
                Views::deleteClientViews($view_id);
                foreach ($selected_user_ids as $client_id) {
                    $db->query("INSERT INTO {PREFIX}client_views (account_id, view_id) VALUES (:account_id, :view_id)");
                    $db->bindAll(array(
                        "account_id" => $client_id,
                        "view_id" => $view_id
                    ));
                    $db->execute();
                }

                Views::deletePublicViewOmitList($view_id);
                break;

            case "hidden":
                Views::deleteClientViews($view_id);
                Views::deletePublicViewOmitList($view_id);
                break;
        }

        // lastly, add in any default values for new submissions
        $db->query("DELETE FROM {PREFIX}new_view_submission_defaults WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();

        if (!empty($info["new_submissions"]) && $may_add_submissions == "yes") {
            $default_values = array_combine($info["new_submissions"], $info["new_submissions_vals"]);

            $order = 1;
            foreach ($default_values as $field_id => $value) {
                $db->query("
                    INSERT INTO {PREFIX}new_view_submission_defaults (view_id, field_id, default_value, list_order)
                    VALUES (:view_id, :field_id, :default_value, :list_order)
                ");
                $db->bindAll(array(
                    "view_id" => $view_id,
                    "field_id" => $field_id,
                    "default_value" => $value,
                    "list_order" => $order
                ));
                $db->execute();

                $order++;
            }
        }
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
     * Returns an ordered hash of view_id => view name, for a particular form. NOT paginated. If the
     * second account ID is left blank, it's assumed that this is an administrator account doing the
     * calling, and all Views are returned.
     *
     * @param integer $form_id the unique form ID
     * @param integer $user_id the unique user ID (or empty, for administrators)
     * @return array an ordered hash of view_id => view name
     */
    public static function getFormViews($form_id, $account_id = "")
    {
        $db = Core::$db;

        $view_hash = array();

        if (!empty($account_id)) {
            $db->query("
                SELECT v.*
                FROM   {PREFIX}views v, {PREFIX}list_groups lg
                WHERE  v.form_id = :form_id AND
                       v.group_id = lg.group_id AND
                       (v.access_type = 'public' OR
                       v.view_id IN (SELECT cv.view_id FROM {PREFIX}client_views cv WHERE account_id = :account_id))
                ORDER BY lg.list_order, v.view_order
            ");
            $db->bindAll(array(
                "form_id" => $form_id,
                "account_id" => $account_id
            ));
            $db->execute();

            // now run through the omit list, just to confirm this client isn't on it!
            foreach ($db->fetchAll() as $row) {
                $view_id = $row["view_id"];

                if ($row["access_type"] == "public") {
                    $omit_list = Views::getPublicViewOmitList($view_id);
                    if (in_array($account_id, $omit_list)) {
                        continue;
                    }
                }
                $view_hash[] = $row;
            }
        } else {
            $db->query("
                SELECT *
                FROM   {PREFIX}views v, {PREFIX}list_groups lg
                WHERE  v.form_id = $form_id AND
                       v.group_id = lg.group_id
                ORDER BY lg.list_order, v.view_order
            ");
            $db->bind("form_id", $form_id);
            $db->execute();

            $view_hash = $db->fetchAll();
        }

        extract(Hooks::processHookCalls("end", compact("view_hash"), array("view_hash")), EXTR_OVERWRITE);

        return $view_hash;
    }


    /**
     * Returns all Views for a form, grouped appropriately. This function introduces a new way of handling
     * loads of optional params (should have implemented this a long time ago!). The second $custom_params
     *
     * @param integer $form_id
     * @param array a hash with any of the following keys:
     *                       account_id => if this is specified, the results will only return View groups
     *                                     that have Views that a client account has access to
     *                       omit_empty_groups => (default: false)
     *                       omit_hidden_views => (default: false)
     *                       include_client => (default: false). If yes, returns assorted client information
     *                             for those that are mapped to the View
     * @param boolean $omit_empty_groups
     */
    public static function getGroupedViews($form_id, $custom_params = array())
    {
        $db = Core::$db;

        // figure out what settings
        $params = array(
            "account_id"        => (isset($custom_params["account_id"])) ? $custom_params["account_id"] : "",
            "omit_empty_groups" => (isset($custom_params["omit_empty_groups"])) ? $custom_params["omit_empty_groups"] : true,
            "omit_hidden_views" => (isset($custom_params["omit_hidden_views"])) ? $custom_params["omit_hidden_views"] : false,
            "include_clients"   => (isset($custom_params["include_clients"])) ? $custom_params["include_clients"] : false
        );

        $db->query("
            SELECT group_id, group_name
            FROM   {PREFIX}list_groups lg
            WHERE  group_type = :group_type
            ORDER BY lg.list_order
        ");
        $db->bind("group_type", "form_{$form_id}_view_group");
        $db->execute();

        $info = array();
        foreach ($db->fetchAll() as $row) {
            $group_id = $row["group_id"];

            $hidden_views_clause = ($params["omit_hidden_views"]) ? " AND v.access_type != 'hidden'" : "";
            if (empty($params["account_id"])) {
                $db->query("
                    SELECT *
                    FROM   {PREFIX}views v
                    WHERE  v.group_id = :group_id
                           $hidden_views_clause
                    ORDER BY v.view_order
                ");
                $db->bind("group_id", $group_id);
            } else {
                $db->query("
                    SELECT v.*
                    FROM   {PREFIX}views v
                    WHERE  v.form_id = :form_id AND
                           v.group_id = :group_id AND
                           (v.access_type = 'public' OR v.view_id IN (
                              SELECT cv.view_id
                              FROM   {PREFIX}client_views cv
                              WHERE  account_id = :account_id
                           )) AND
                           v.view_id NOT IN (
                              SELECT view_id
                              FROM   {PREFIX}public_view_omit_list
                              WHERE  account_id = :account_id2
                           )
                           $hidden_views_clause
                    ORDER BY v.view_order
                ");
                $db->bindAll(array(
                    "form_id" => $form_id,
                    "group_id" => $group_id,
                    "account_id" => $params["account_id"],
                    "account_id2" => $params["account_id"]
                ));
            }
            $db->execute();

            $views = array();
            foreach ($db->fetchAll() as $view_info) {
                $view_id = $view_info["view_id"];
                if ($params["include_clients"]) {
                    $view_info["client_info"]      = Views::getViewClients($view_id);
                    $view_info["client_omit_list"] = Views::getPublicViewOmitList($view_id);
                }

                $view_info["columns"] = ViewColumns::getViewColumns($view_id);
                $view_info["fields"]  = ViewFields::getViewFields($view_id);
                $view_info["tabs"]    = ViewTabs::getViewTabs($view_id, true);
                $view_info["filters"] = ViewFilters::getViewFilters($view_id);
                $views[] = $view_info;
            }

            if (count($views) > 0 || !$params["omit_empty_groups"]) {
                $curr_group = array(
                    "group" => $row,
                    "views" => $views
                );
                $info[] = $curr_group;
            }
        }

        return $info;
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
        Views::getPublicViewOmitList($view_id) : array();

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
        $num_submissions_per_page = Sessions::getWithFallback("settings.num_submissions_per_page", 10);

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
            INSERT INTO {PREFIX}list_groups (group_type, group_name, custom_data, list_order)
            VALUES (:group_type, :group_name, :custom_data, 1)
        ");
        $db->bindAll(array(
            "group_type" => "form_{$form_id}_view_group",
            "group_name" => $LANG["word_views"],
            "custom_data" => ""
        ));
        $db->execute();
        $group_id = $db->getInsertId();

        $db->query("
            UPDATE {PREFIX}views
            SET group_id = :group_id
            WHERE view_id = :view_id
        ");
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


    /**
     * Returns an array of account IDs of those clients in the omit list for this public View.
     *
     * @param integer $view_id
     * @return array
     */
    public static function getPublicViewOmitList($view_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT account_id
            FROM   {PREFIX}public_view_omit_list
            WHERE  view_id = :view_id
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        return $db->fetchAll(PDO::FETCH_COLUMN);
    }


    /**
     * This figures out what View is currently being used.
     * @param array $request the POST/GET contents
     * @param integer $form_id
     */
    public static function getCurrentView($request, $form_id)
    {
        $session_key = "form_{$form_id}_view_id";
        if (isset($request["view_id"])) {
            $view_id = $request["view_id"];
            Sessions::set($session_key, $view_id);
        } else {
            $view_id = Sessions::getWithFallback($session_key, "");
        }

        // if the View ID isn't set, here - they probably just linked to the page directly from an email, module
        // or elsewhere in the script. For this case, find and use the default View
        if (empty($view_id)) {
            $view_id = Views::getDefaultView($form_id);
        }

        return $view_id;
    }


}
