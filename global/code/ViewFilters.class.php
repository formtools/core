<?php

/**
 * Code related to View filters.
 *
 * DB view_filters DB structure:
 *   -- Standard filter:
 *        filter_values stores the string the user entered in the UI
 *        filter_sql looks like (col_name = 'Arbitrary string'), or if they enter a string like "one|two", it
 *                   would be: (col_name = 'one' OR col_name = 'two')
 *   -- Client Map filter:
 *        filter_values stores the field in the user's table that's being mapped to. Note, this can include
 *                   fields defined in the Extended Client Fields module
 *        filter_sql looks like (col_name = '$company_name'). Where col_name is the field in the current form,
 *               and $company_name is just the company_name field in variable format.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage ViewFilters
 */


namespace FormTools;

use Exception;


class ViewFilters
{

	/**
	 * Retrieves all filters for a View. If you just want the SQL, use ViewFilters::getViewFilterSql instead, which
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


	public static function deleteViewFilters($view_id)
	{
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

			// we escape single quotes in the placeholder strings because they're going to be located within single
			// quotes, e.g. col_X = 'placeholder value here'
			// PDO handles escaping the broader values but not these
			$placeholders = array(
				"account_id" => str_replace("'", "''", $account_info["account_id"]),
				"first_name" => str_replace("'", "''", $account_info["first_name"]),
				"last_name" => str_replace("'", "''", $account_info["last_name"]),
				"email" => str_replace("'", "''", $account_info["email"]),
				"settings__company_name" => str_replace("'", "''", $account_info["settings"]["company_name"])
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
			if ($filter["filter_type"] == "standard") {
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
	 * Called by the Views::updateView function; updates the filters assigned to the View.
	 *
	 * @param integer $view_id
	 * @param array $info
	 * @return array
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
		for ($i = 0; $i < count($form_fields); $i++) {
			$field_columns[$form_fields[$i]["field_id"]] = array(
				"col_name" => $form_fields[$i]["col_name"],
				"is_date_field" => $form_fields[$i]["is_date_field"]
			);
		}

		$standard_filter_errors = ViewFilters::updateViewStandardFilters($view_id, $info, $field_columns);
		$client_map_filter_errors = ViewFilters::updateViewClientMapFilters($view_id, $info, $field_columns);

		if (empty($standard_filter_errors) && empty($client_map_filter_errors)) {
			return array(true, $LANG["notify_filters_updated"]);
		} else {
			$success = false;
			$message = $LANG["notify_filters_not_updated"];

			$errors = array_merge($standard_filter_errors, $client_map_filter_errors);
			if ($debug_enabled) {
				$rows = array();
				foreach ($errors as $error) {
					$rows[] = "&bull;&nbsp; $error";
				}
				$message .= "<br /><br />" . join("<br />", $rows);
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
		for ($i = 1; $i <= $max_standard_filters; $i++) {

			// if this filter doesn't have a field specified, just ignore the row
			if (!isset($info["standard_filter_{$i}_field_id"]) || empty($info["standard_filter_{$i}_field_id"])) {
				continue;
			}

			$field_id = $info["standard_filter_{$i}_field_id"];
			$col_name = $field_columns[$field_id]["col_name"];

			// date fields need special SQL
			if ($field_columns[$field_id]["is_date_field"] == "yes") {
				$values = $info["standard_filter_{$i}_filter_date_values"];
				$operator = $info["standard_filter_{$i}_operator_date"];
				$sql_operator = ($operator == "after") ? ">" : "<";
				$sql = "$col_name $sql_operator '$values'";
			} else {
				$values = $info["standard_filter_{$i}_filter_values"];
				$operator = $info["standard_filter_{$i}_operator"];
				$sql = self::getStandardFilterSql($col_name, $values, $operator);
			}

			try {
				$db->query("
                    INSERT INTO {PREFIX}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
                    VALUES (:view_id, 'standard', :field_id, :operator, :filter_values, :filter_sql)
                ");
				$db->bindAll(array(
					"view_id" => $view_id,
					"field_id" => $field_id,
					"operator" => $operator,
					"filter_values" => $values,
					"filter_sql" => "($sql)"
				));
				$db->execute();

				$num_standard_filters++;
			} catch (Exception $e) {
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
		for ($i = 1; $i <= $max_client_map_filters; $i++) {

			// if this filter doesn't have a field or a client field specified,
			if (!isset($info["client_map_filter_{$i}_field_id"]) || empty($info["client_map_filter_{$i}_field_id"]) ||
				!isset($info["client_map_filter_{$i}_client_field"]) || empty($info["client_map_filter_{$i}_client_field"])) {
				continue;
			}

			$field_id = $info["client_map_filter_{$i}_field_id"];
			$operator = $info["client_map_filter_{$i}_operator"];
			$client_field = $info["client_map_filter_{$i}_client_field"];
			$col_name = $field_columns[$field_id]["col_name"];
			$original_client_field = $client_field;

			$filter_sql = self::getClientMapFilterSql($col_name, $client_field, $operator);

			try {
				$db->query("
                    INSERT INTO {PREFIX}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
                    VALUES (:view_id, 'client_map', :field_id, :operator, :filter_values, :filter_sql)
                ");

				$db->bindAll(array(
					"view_id" => $view_id,
					"field_id" => $field_id,
					"operator" => $operator,
					"filter_values" => $original_client_field,
					"filter_sql" => $filter_sql
				));
				$db->execute();

				$num_client_map_filters++;
			} catch (Exception $e) {
				$errors[] = $e->getMessage();
			}
		}

		// keep track of whether this View has a client map filter or not
		$has_client_map_filter = "no";
		if ($num_client_map_filters > 0) {
			$has_client_map_filter = "yes";
		}

		$db->query("
            UPDATE {PREFIX}views
            SET has_client_map_filter = :has_client_map_filter
            WHERE view_id = :view_id
        ");
		$db->bindAll(array(
			"has_client_map_filter" => $has_client_map_filter,
			"view_id" => $view_id
		));
		$db->execute();

		return $errors;
	}


	/**
	 * This update any filter SQL for a single field ID. This is called whenever the administrator changes one or more
	 * database column names (e.g. using the "Smart Fill" option). It ensures data integrity for the View filters.
	 *
	 * @param integer $field_id
	 * @param array $info
	 */
	public static function updateFieldFilters($field_id)
	{
		$db = Core::$db;

		// get any filters that are associated with this field
		$db->query("SELECT * FROM {PREFIX}view_filters WHERE field_id = :field_id");
		$db->bind("field_id", $field_id);
		$db->execute();
		$filters = $db->fetchAll();

		// get the latest form field info
		$field_info = Fields::getFormField($field_id, array("include_field_type_info" => true));
		$col_name = $field_info["col_name"];

		// loop through all of the affected filters & update the SQL
		foreach ($filters as $filter_info) {
			$filter_type = $filter_info["filter_type"];
			$filter_values = $filter_info["filter_values"];
			$operator = $filter_info["operator"];

			if ($field_info["is_date_field"] == "yes") {
				$sql_operator = ($operator == "after") ? ">" : "<";
				$sql = "$col_name $sql_operator '$filter_values'";
			} else {
				if ($filter_type == "standard") {
					$sql = self::getStandardFilterSql($col_name, $filter_values, $operator);
				} else {
					$sql = self::getClientMapFilterSql($col_name, $filter_values, $operator);
				}
			}

			$db->query("
                UPDATE {PREFIX}view_filters
                SET    filter_sql = :filter_sql
                WHERE  filter_id = :filter_id
            ");
			$db->bindAll(array(
				"filter_sql" => "($sql)",
				"filter_id" => $filter_info["filter_id"]
			));
			$db->execute();
		}
	}


	private static function getStandardFilterSql($col_name, $values, $operator)
	{
		if ($operator == "equals") {
			$sql_operator = "=";
			$null_test = "IS NULL";
			$join = " OR ";
		} else if ($operator == "not_equals") {
			$sql_operator = "!=";
			$null_test = "IS NOT NULL";
			$join = " AND ";
		} else if ($operator == "like") {
			$sql_operator = "LIKE";
			$null_test = "IS NULL";
			$join = " OR ";
		} else {  // not_like
			$sql_operator = "NOT LIKE";
			$null_test = "IS NOT NULL";
			$join = " AND ";
		}

		$sql_statements_arr = array();
		$values_arr = explode("|", $values);

		foreach ($values_arr as $value) {

			// if this is a LIKE operator (not_like, like), wrap the value in %..%
			$escaped_value = str_replace("'", "''", $value);
			if ($operator == "like" || $operator == "not_like") {
				$escaped_value = "%$escaped_value%";
			}

			$trimmed_value = trim($value);

			// NOT LIKE and != need to be handled separately. By default, Form Tools sets new blank field values to NULL.
			// But SQL queries that test for != "Yes" or NOT LIKE "Yes" should intuitively return ALL results without
			// "Yes" - and that includes NULL values. So, we need to add an additional check to also return null values
			if ($operator == "not_like" || $operator == "not_equals") {
				// empty string being searched AGAINST; i.e. checking the field is NOT empty or LIKE empty
				if (empty($trimmed_value)) {
					$sql_statements_arr[] = "($col_name $sql_operator '$escaped_value' AND $col_name IS NOT NULL)";
				} else {
					$sql_statements_arr[] = "($col_name $sql_operator '$escaped_value' OR $col_name IS NULL)";
				}
			} else {
				// if the value is EMPTY, we need to add an additional IS NULL / IS NOT NULL check
				if (empty($trimmed_value)) {
					$sql_statements_arr[] = "($col_name $sql_operator '$escaped_value' OR $col_name $null_test)";
				} else {
					$sql_statements_arr[] = "($col_name $sql_operator '$escaped_value')";
				}
			}
		}

		return implode($join, $sql_statements_arr);
	}


	private static function getClientMapFilterSql($col_name, $client_field, $operator)
	{
		$map = array(
			"equals" => "=",
			"not_equals" => "!=",
			"like" => "LIKE",
			"not_like" => "NOT LIKE"
		);

		// should never occur
		if (!array_key_exists($operator, $map)) {
			return "";
		}

		$sql_operator = $map[$operator];

		// now we're going to build the actual SQL query that contains the Smarty placeholders for the account info.
		// first, convert the client field name to a Smarty variable
		$sql_client_field = "{\$$client_field}";

		// second, if this is a LIKE operator (not_like, like), wrap the value even further with a %...%
		if ($operator == "like" || $operator == "not_like") {
			$sql_client_field = "%$sql_client_field%";
		}

		// no escaping is needed, note
		return "($col_name $sql_operator '$sql_client_field')";
	}

}
