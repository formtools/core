<?php

/**
 * Code related to View filters.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage ViewFields
 */


// ---------------------------------------------------------------------------------------------------------------------


namespace FormTools;

use PDOException;


class ViewFilters
{

    /**
     * Retrieves all filters for a View. If you just want the SQL, use ft_get_view_filter_sql instead, which
     * returns an array of the SQL needed to query the form table. This function returns all info about the
     * filter.
     *
     * @param integer $client_id The unique user ID
     * @param string $filter_type "standard" or "client_map". If left blank (or set to "all") it returns all
     *      View filters.
     * @return array This function returns an array of multi-dimensional arrays of hashes.
     *      Each index of the main array contains the filters for
     */
    public static function getViewFilters($view_id, $filter_type = "all")
    {
        $db = Core::$db;

        $filter_type_clause = "";
        if ($filter_type == "standard") {
            $filter_type_clause = "AND filter_type = 'standard'";
        } else if ($filter_type == "client_map") {
            $filter_type_clause = "AND filter_type = 'client_map'";
        }

        $db->query("
            SELECT *
            FROM   {PREFIX}view_filters
            WHERE  view_id = :view_id
                   $filter_type_clause
            ORDER BY filter_id
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        return $db->fetchAll();
    }


    public static function deleteViewFilters($view_id) {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}view_filters WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();
    }


    /**
     * Returns an array of SQL filters for a View.
     *
     * @param integer $view_id
     * @return array
     */
    public static function getViewFilterSql($view_id)
    {
        $db = Core::$db;

        $is_client_account = Sessions::exists("account.account_type") && Sessions::get("account.account_type") == "client";

        $placeholders = array();
        if ($is_client_account) {
            $account_info = Sessions::get("account");

            $placeholders = array(
                "account_id"   => $account_info["account_id"],
                "first_name"   => $account_info["first_name"],
                "last_name"    => $account_info["last_name"],
                "email"        => $account_info["email"],
                "settings__company_name" => $account_info["settings"]["company_name"]
            );
        }

        extract(Hooks::processHookCalls("start", compact("placeholders", "is_client_account"), array("placeholders", "is_client_account")), EXTR_OVERWRITE);

        $db->query("
            SELECT filter_type, filter_sql
            FROM   {PREFIX}view_filters
            WHERE  view_id = :view_id
            ORDER BY filter_id
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        $infohash = array();
        foreach ($db->fetchAll() as $filter) {
            if ($filter["filter_type"] == "standard") { // TODO move to constants
                $infohash[] = $filter["filter_sql"];
            } else {
                // if this is a client account, evaluate the Client Map placeholders
                if ($is_client_account) {
                    $infohash[] = General::evalSmartyString($filter["filter_sql"], $placeholders);
                }
            }
        }

        return $infohash;
    }


    /**
     * Called by the ft_update_view function; updates the filters assigned to the View.
     *
     * @param integer $view_id
     * @param array $info
     */
    public static function updateViewFilters($view_id, $info)
    {
        $LANG = Core::$L;
        $debug_enabled = Core::isDebugEnabled();

        // hmm... weird.
        $form_id = $info["form_id"];

        // delete all old filters for this View. The two update view filter functions that follow re-insert
        // the most recent View info
        ViewFilters::deleteViewFilters($view_id);

        // get a hash of field_id => col name for use in building the SQL statements
        $form_fields = Fields::getFormFields($form_id, array("include_field_type_info" => true));
        $field_columns = array();
        for ($i=0; $i<count($form_fields); $i++) {
            $field_columns[$form_fields[$i]["field_id"]] = array(
                "col_name"      => $form_fields[$i]["col_name"],
                "is_date_field" => $form_fields[$i]["is_date_field"]
            );
        }

        $standard_filter_errors   = ViewFilters::updateViewStandardFilters($view_id, $info, $field_columns);
        $client_map_filter_errors = ViewFilters::updateViewClientMapFilters($view_id, $info, $field_columns);

        if (empty($standard_filter_errors) && empty($client_map_filter_errors)) {
            return array(true, $LANG["notify_filters_updated"]);
        } else {
            $success = false;
            $message = $LANG["notify_filters_not_updated"];

            $errors = array_merge($standard_filter_errors, $client_map_filter_errors);
            if ($debug_enabled) {
                array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
                $message .= "<br /><br />" . join("<br />", $errors);
            }
            return array($success, $message);
        }
    }


    public static function updateViewStandardFilters($view_id, $info, $field_columns)
    {
        $db = Core::$db;

        // note that we call this MAX_standard_filters, not num_standard_filters. This is because
        // the value passed from the page may not be accurate. The JS doesn't reorder everything when
        // the user deletes a row, so the value passed is the total number of rows that CAN be passed. Some rows
        // may be empty, though
        $max_standard_filters = $info["num_standard_filters"];
        $errors = array();

        // stores the actual number of standard filters added
        $num_standard_filters = 0;

        // loop through all standard filters and add each to the database
        for ($i=1; $i<=$max_standard_filters; $i++) {

            // if this filter doesn't have a field specified, just ignore the row
            if (!isset($info["standard_filter_{$i}_field_id"]) || empty($info["standard_filter_{$i}_field_id"])) {
                continue;
            }

            $field_id = $info["standard_filter_{$i}_field_id"];
            $col_name = $field_columns[$field_id]["col_name"];

            // date field
            if ($field_columns[$field_id]["is_date_field"] == "yes") {
                $values   = $info["standard_filter_{$i}_filter_date_values"];
                $operator = $info["standard_filter_{$i}_operator_date"];

                // build the SQL statement
                $sql_operator = ($operator == "after") ? ">" : "<";
                $sql = "$col_name $sql_operator '$values'";
            } else {
                $values   = $info["standard_filter_{$i}_filter_values"];
                $operator = $info["standard_filter_{$i}_operator"];

                // build the SQL statement(s)
                $sql_operator = "";
                switch ($operator) {
                    case "equals":
                        $sql_operator = "=";
                        $null_test = "IS NULL";
                        $join = " OR ";
                        break;
                    case "not_equals":
                        $sql_operator = "!=";
                        $null_test = "IS NOT NULL";
                        $join = " AND ";
                        break;
                    case "like":
                        $sql_operator = "LIKE";
                        $null_test = "IS NULL";
                        $join = " OR ";
                        break;
                    case "not_like":
                        $sql_operator = "NOT LIKE";
                        $null_test = "IS NOT NULL";
                        $join = " AND ";
                        break;
                }

                $sql_statements_arr = array();
                $values_arr = explode("|", $values);

                foreach ($values_arr as $value) {

                    // if this is a LIKE operator (not_like, like), wrap the value in %..%
                    $escaped_value = $value;
                    if ($operator == "like" || $operator == "not_like") {
                        $escaped_value = "%$value%";
                    }

                    $trimmed_value = trim($value);

                    // NOT LIKE and != need to be handled separately. By default, Form Tools sets new blank field values to NULL.
                    // But SQL queries that test for != "Yes" or NOT LIKE "Yes" should intuitively return ALL results without
                    // "Yes" - and that includes NULL values. So, we need to add an additional check to also return null values
                    if ($operator == "not_like" || $operator == "not_equals") {
                        // empty string being searched AGAINST; i.e. checking the field is NOT empty or LIKE empty
                        if (empty($trimmed_value)) {
                            $sql_statements_arr[] = "$col_name $sql_operator '$escaped_value' AND $col_name IS NOT NULL";
                        } else {
                            $sql_statements_arr[] = "$col_name $sql_operator '$escaped_value' OR $col_name IS NULL";
                        }
                    } else {
                        // if the value is EMPTY, we need to add an additional IS NULL / IS NOT NULL check
                        if (empty($trimmed_value)) {
                            $sql_statements_arr[] = "$col_name $sql_operator '$escaped_value' OR $col_name $null_test";
                        } else {
                            $sql_statements_arr[] = "$col_name $sql_operator '$escaped_value'";
                        }
                    }
                }

                $sql = join($join, $sql_statements_arr);
            }
            $sql = "(" . addslashes($sql) . ")";

            try {
                $db->query("
                    INSERT INTO {PREFIX}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
                    VALUES      ($view_id, 'standard', $field_id, '$operator', '$values', '$sql')
                ");
                $db->bindAll(array(
                    "view_id" => $view_id,
                    "field_id" => $field_id,
                    "operator" => $operator,
                    "filter_values" => $values,
                    "filter_sql" => $sql
                ));
                $db->execute();

                // assumption... this doesn't run if an exception is thrown in the block above
                $num_standard_filters++;

            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // keep track of whether this View has a standard filter or not
        $has_standard_filter = "no";
        if ($num_standard_filters > 0) {
            $has_standard_filter = "yes";
        }

        $db->query("UPDATE {PREFIX}views SET has_standard_filter = :has_standard_filter WHERE view_id = :view_id");
        $db->bindAll(array(
            "has_standard_filter" => $has_standard_filter,
            "view_id" => $view_id
        ));
        $db->execute();

        return $errors;
    }


    public static function updateViewClientMapFilters($view_id, $info, $field_columns)
    {
        $db = Core::$db;

        // note that we call this MAX_client_map_filters, not num_client_map_filters. This is because
        // the value passed from the page may not be accurate. The JS doesn't reorder everything when
        // the user deletes a row, so the value passed is the total number of rows that CAN be passed. Some rows
        // may be empty, though
        $max_client_map_filters = $info["num_client_map_filters"];
        $errors = array();

        // stores the actual number of client map filters added
        $num_client_map_filters = 0;

        // loop through all client map filters and add each to the database
        for ($i=1; $i<=$max_client_map_filters; $i++) {

            // if this filter doesn't have a field or a client field specified,
            if (!isset($info["client_map_filter_{$i}_field_id"]) || empty($info["client_map_filter_{$i}_field_id"]) ||
                !isset($info["client_map_filter_{$i}_client_field"]) || empty($info["client_map_filter_{$i}_client_field"])) {
                continue;
            }

            $field_id     = $info["client_map_filter_{$i}_field_id"];
            $operator     = $info["client_map_filter_{$i}_operator"];
            $client_field = $info["client_map_filter_{$i}_client_field"];

            // build the SQL statement(s)
            $sql_operator = "";
            switch ($operator) {
                case "equals":
                    $sql_operator = "=";
                    break;
                case "not_equals":
                    $sql_operator = "!=";
                    break;
                case "like":
                    $sql_operator = "LIKE";
                    break;
                case "not_like":
                    $sql_operator = "NOT LIKE";
                    break;
            }

            $col_name = $field_columns[$field_id]["col_name"];
            $original_client_field = $client_field;

            // now we're going to build the actual SQL query that contains the Smarty placeholders for the account info.
            // first, convert the client field name to a Smarty variable.
            $sql_client_field = "{\$$client_field}";

            // second, if this is a LIKE operator (not_like, like), wrap the value even further with a %...%
            if ($operator == "like" || $operator == "not_like") {
                $sql_client_field = "%$sql_client_field%";
            }

            $sql = addslashes("($col_name $sql_operator '$sql_client_field')");

            try {
                $db->query("
                    INSERT INTO {PREFIX}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
                    VALUES      (:view_id, 'client_map', :field_id, :operator, :original_client_field, :sql)
                ");

                $db->bindAll(array(
                    "view_id" => $view_id,
                    "field_id" => $field_id,
                    "operator" => $operator,
                    "filter_values" => $original_client_field,
                    "filter_sql" => $sql
                ));
                $db->execute();

                // assumption doesn't execute if exception above
                $num_client_map_filters++;
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }
        }

        // keep track of whether this View has a client map filter or not
        $has_client_map_filter = "no";
        if ($num_client_map_filters > 0) {
            $has_client_map_filter = "yes";
        }

        $db->query("UPDATE {PREFIX}views SET has_client_map_filter = :has_client_map_filter WHERE view_id = :view_id");
        $db->bindAll(array(
            "has_client_map_filter" => $has_client_map_filter,
            "view_id" => $view_id
        ));
        $db->execute();

        return $errors;
    }

}
