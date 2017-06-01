<?php

/**
 * This file contains all top-level methods relating to form Views. See the other View*.class.php files for more
 * specific things within Views (ViewFields, ViewFilters).
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Views
 */


// ---------------------------------------------------------------------------------------------------------------------


/**
 * This checks to see if a View exists in the database.
 *
 * @param integer $view_id
 * @param boolean
 * @return boolean
 */
function ft_check_view_exists($view_id, $ignore_hidden_views = false)
{
	global $g_table_prefix;

	$view_clause = ($ignore_hidden_views) ? " AND access_type != 'hidden' " : "";

	$query = mysql_query("
    SELECT count(*) as c
    FROM {$g_table_prefix}views
    WHERE view_id = $view_id
          $view_clause
      ");

	$results = mysql_fetch_assoc($query);

	return $results["c"] > 0;
}


/**
 * Retrieves all information about a View, including associated user and filter info.
 *
 * @param integer $view_id the unique view ID
 * @return array a hash of view information
 */
function ft_get_view($view_id, $custom_params = array())
{
	global $g_table_prefix;

	$params = array(
		"include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
	);

	$query = "SELECT * FROM {$g_table_prefix}views WHERE view_id = $view_id";
	$result = mysql_query($query);

	$view_info = mysql_fetch_assoc($result);
	$view_info["client_info"] = ft_get_view_clients($view_id);
	$view_info["columns"]     = ft_get_view_columns($view_id);
	$view_info["fields"]      = Views::getViewFields($view_id, $params);
	$view_info["filters"]     = ft_get_view_filters($view_id);
	$view_info["tabs"]        = ft_get_view_tabs($view_id);
	$view_info["client_omit_list"] = (isset($view_info["access_type"]) && $view_info["access_type"] == "public") ?
		ft_get_public_view_omit_list($view_id) : array();

	extract(Hooks::processHookCalls("end", compact("view_id", "view_info"), array("view_info")), EXTR_OVERWRITE);

	return $view_info;
}


/**
 * Retrieves a list of all views for a form. As of 2.0.5 this function now always returns ALL Views,
 * instead of the option of a single page.
 *
 * @param integer $form_id the unique form ID
 * @return array a hash of view information
 */
function ft_get_views($form_id)
{
	global $g_table_prefix;

	$result = mysql_query("
    SELECT view_id
    FROM 	 {$g_table_prefix}views
    WHERE  form_id = $form_id
    ORDER BY view_order
      ");

	$view_info = array();
	while ($row = mysql_fetch_assoc($result))
	{
		$view_id = $row["view_id"];
		$view_info[] = ft_get_view($view_id);
	}

	$return_hash["results"] = $view_info;
	$return_hash["num_results"]  = count($view_info);

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
function ft_get_view_ids($form_id, $order_results = false)
{
	global $g_table_prefix;

	if ($order_results)
	{
		$query = mysql_query("
  	  SELECT view_id
  	  FROM   {$g_table_prefix}views v, {$g_table_prefix}list_groups lg
  	  WHERE  v.group_id = lg.group_id AND
  	         form_id = $form_id
  	  ORDER BY lg.list_order, v.view_order
  	");
	}
	else
	{
		$query = mysql_query("SELECT view_id FROM {$g_table_prefix}views WHERE form_id = $form_id");
	}

	$view_ids = array();
	while ($row = mysql_fetch_assoc($query))
		$view_ids[] = $row["view_id"];

	extract(Hooks::processHookCalls("end", compact("view_ids"), array("view_ids")), EXTR_OVERWRITE);

	return $view_ids;
}


/**
 * Returns all tab information for a particular form view. If the second parameter is
 * set to true.
 *
 * @param integer $view_id the unique view ID
 * @return array the array of tab info, ordered by tab_order
 */
function ft_get_view_tabs($view_id, $return_non_empty_tabs_only = false)
{
	global $g_table_prefix;

	$result = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}view_tabs
    WHERE  view_id = $view_id
    ORDER BY tab_number
      ");

	$tab_info = array();
	while ($row = mysql_fetch_assoc($result))
	{
		if ($return_non_empty_tabs_only && empty($row["tab_label"]))
			continue;

		$tab_info[$row["tab_number"]] = array("tab_label" => $row["tab_label"]);
	}

	extract(Hooks::processHookCalls("end", compact("view_id", "tab_info"), array("tab_info")), EXTR_OVERWRITE);

	return $tab_info;
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
function ft_create_new_view($form_id, $group_id, $view_name = "", $create_from_view_id = "")
{
	global $g_table_prefix, $LANG;

	// figure out the next View order number
	$count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}views WHERE form_id = $form_id");
	$count_hash = mysql_fetch_assoc($count_query);
	$num_form_views = $count_hash["c"];
	$next_order = $num_form_views + 1;
	$view_name = (empty($view_name)) ? $LANG["phrase_new_view"] : $view_name;

	if ($create_from_view_id == "blank_view_no_fields" || $create_from_view_id == "blank_view_all_fields")
	{
		// add the View with default values
		mysql_query("
      INSERT INTO {$g_table_prefix}views (form_id, view_name, view_order, is_new_sort_group, group_id)
      VALUES ($form_id, '$view_name', $next_order, 'yes', $group_id)
        ");
		$view_id = mysql_insert_id();

		// add the default tab
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 1, '{$LANG["phrase_default_tab_label"]}')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 2, '')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 3, '')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 4, '')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 5, '')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 6, '')");

		if ($create_from_view_id == "blank_view_all_fields") {
			self::populateNewViewFields($form_id, $view_id);
		}
	}
	else
	{
		$view_info = ft_get_view($create_from_view_id);

		// Main View Settings
		$view_order = $view_info["view_order"];
		$access_type              = $view_info["access_type"];
		$num_submissions_per_page = $view_info["num_submissions_per_page"];
		$default_sort_field       = $view_info["default_sort_field"];
		$default_sort_field_order = $view_info["default_sort_field_order"];
		$may_add_submissions      = $view_info["may_add_submissions"];
		$may_edit_submissions     = $view_info["may_edit_submissions"];
		$may_delete_submissions   = $view_info["may_delete_submissions"];
		$has_standard_filter      = $view_info["has_standard_filter"];
		$has_client_map_filter    = $view_info["has_client_map_filter"];

		mysql_query("
      INSERT INTO {$g_table_prefix}views (form_id, access_type, view_name, view_order, is_new_sort_group, group_id,
        num_submissions_per_page, default_sort_field, default_sort_field_order, may_add_submissions, may_edit_submissions,
        may_delete_submissions, has_client_map_filter, has_standard_filter)
      VALUES ($form_id, '$access_type', '$view_name', $next_order, 'yes', $group_id, $num_submissions_per_page,
        '$default_sort_field', '$default_sort_field_order', '$may_add_submissions', '$may_edit_submissions',
        '$may_delete_submissions', '$has_client_map_filter', '$has_standard_filter')
        ");
		$view_id = mysql_insert_id();

		foreach ($view_info["client_info"] as $client_info)
		{
			$account_id = $client_info["account_id"];
			mysql_query("INSERT INTO {$g_table_prefix}client_views (account_id, view_id) VALUES ($account_id, $view_id)");
		}

		// View Tabs
		$tabs = $view_info["tabs"];
		$tab1 = $tabs[1]["tab_label"];
		$tab2 = $tabs[2]["tab_label"];
		$tab3 = $tabs[3]["tab_label"];
		$tab4 = $tabs[4]["tab_label"];
		$tab5 = $tabs[5]["tab_label"];
		$tab6 = $tabs[6]["tab_label"];
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 1, '$tab1')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 2, '$tab2')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 3, '$tab3')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 4, '$tab4')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 5, '$tab5')");
		mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 6, '$tab6')");


		// with 2.1.0, all View fields are now grouped. We need to duplicate all the groups as well as the fields
		$group_id_map = ft_duplicate_view_field_groups($create_from_view_id, $view_id);

		$field_view_inserts = array();
		foreach ($view_info["fields"] as $field_info)
		{
			$field_id      = $field_info["field_id"];
			$new_group_id  = $group_id_map[$field_info["group_id"]];
			$is_editable   = $field_info["is_editable"];
			$is_searchable = $field_info["is_searchable"];
			$list_order    = $field_info["list_order"];
			$is_new_sort_group = $field_info["is_new_sort_group"];
			$field_view_inserts[] = "($view_id, $field_id, $new_group_id, '$is_editable', '$is_searchable', $list_order, '$is_new_sort_group')";
		}
		if (!empty($field_view_inserts))
		{
			$field_view_inserts_str = implode(",\n", $field_view_inserts);
			mysql_query("
        INSERT INTO {$g_table_prefix}view_fields (view_id, field_id, group_id, is_editable,
          is_searchable, list_order, is_new_sort_group)
        VALUES $field_view_inserts_str
      ");
		}

		$view_column_inserts = array();
		foreach ($view_info["columns"] as $field_info)
		{
			$field_id     = $field_info["field_id"];
			$list_order   = $field_info["list_order"];
			$is_sortable  = $field_info["is_sortable"];
			$auto_size    = $field_info["auto_size"];
			$custom_width = $field_info["custom_width"];
			$truncate     = $field_info["truncate"];
			$view_column_inserts[] = "($view_id, $field_id, $list_order, '$is_sortable', '$auto_size', '$custom_width', '$truncate')";
		}
		if (!empty($view_column_inserts))
		{
			$view_column_insert_str = implode(",\n", $view_column_inserts);
			mysql_query("
        INSERT INTO {$g_table_prefix}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
        VALUES $view_column_insert_str
      ");
		}

		// View Filters
		foreach ($view_info["filters"] as $filter_info)
		{
			$field_id      = $filter_info["field_id"];
			$filter_type   = $filter_info["filter_type"];
			$operator      = $filter_info["operator"];
			$filter_values = $filter_info["filter_values"];
			$filter_sql    = $filter_info["filter_sql"];

			mysql_query("
        INSERT INTO {$g_table_prefix}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
        VALUES ($view_id, '$filter_type', $field_id, '$operator', '$filter_values', '$filter_sql')
          ");
		}

		// default submission values
		$submission_defaults = ft_get_new_view_submission_defaults($create_from_view_id);
		foreach ($submission_defaults as $row)
		{
			$field_id      = $row["field_id"];
			$default_value = $row["default_value"];
			$list_order    = $row["list_order"];

			mysql_query("
    	  INSERT INTO {$g_table_prefix}new_view_submission_defaults (view_id, field_id, default_value, list_order)
    	  VALUES ($view_id, $field_id, '$default_value', $list_order)
    	");
		}

		// public View omit list
		$client_ids = ft_get_public_view_omit_list($create_from_view_id);
		foreach ($client_ids as $client_id)
		{
			mysql_query("
        INSERT INTO {$g_table_prefix}public_view_omit_list (view_id, account_id)
        VALUES ($view_id, $client_id)
      ");
		}
	}

	extract(Hooks::processHookCalls("end", compact("view_id"), array()), EXTR_OVERWRITE);

	return $view_id;
}


/**
 * Returns all information about a View columns.
 *
 * @param integer $view_id
 */
function ft_get_view_columns($view_id)
{
	global $g_table_prefix;

	$query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}view_columns
    WHERE  view_id = $view_id
    ORDER BY list_order
  ");

	$info = array();
	while ($row = mysql_fetch_assoc($query))
		$info[] = $row;

	return $info;
}


/**
 * Verbose name, but this function returns a hash of group_id => tab number for a particular View. In
 * other words, it looks at the View field groups to find out which tab each one belongs to.
 *
 * @return array
 */
function ft_get_view_field_group_tabs($view_id)
{
	global $g_table_prefix;

	$query = mysql_query("
    SELECT group_id, custom_data
    FROM   {$g_table_prefix}list_groups
    WHERE  group_type = 'view_fields_{$view_id}'
  ");

	$map = array();
	while ($row = mysql_fetch_assoc($query))
	{
		$map[$row["group_id"]] = $row["custom_data"];
	}

	return $map;
}


/**
 * Finds out what Views are associated with a particular form field. Used when deleting
 * a field.
 *
 * @param integer $field_id
 * @return array $view_ids
 */
function ft_get_field_views($field_id)
{
	global $g_table_prefix;

	$query = mysql_query("SELECT view_id FROM {$g_table_prefix}view_fields WHERE field_id = $field_id");

	$view_ids = array();
	while ($row = mysql_fetch_assoc($query))
		$view_ids[] = $row["view_id"];

	return $view_ids;
}


/**
 * This is called by the administrator on the main Views tab. It lets them delete an entire group of Views
 * in one go.
 *
 * @param integer $group_id
 */
function ft_delete_view_group($group_id)
{
	global $g_table_prefix, $LANG;

	$query = mysql_query("
    SELECT view_id
    FROM   {$g_table_prefix}views
    WHERE  group_id = $group_id
  ");

	// first, delete all the Views
	while ($view_info = mysql_fetch_assoc($query))
	{
		$view_id = $view_info["view_id"];
		ft_delete_view($view_id);
	}

	// next, delete the group
	mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_id = $group_id");

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
function ft_delete_view($view_id)
{
	global $g_table_prefix, $LANG;

	$view_info = ft_get_view($view_id);

	mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}view_columns WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}view_fields WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}view_filters WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}view_tabs WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_type = 'view_fields_$view_id'");
	mysql_query("DELETE FROM {$g_table_prefix}email_template_edit_submission_views WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}email_template_when_sent_views WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}new_view_submission_defaults WHERE view_id = $view_id");
	mysql_query("DELETE FROM {$g_table_prefix}views WHERE view_id = $view_id");

	// hmm... This should be handled better: the user needs to be notified prior to deleting a View to describe all the dependencies
	mysql_query("UPDATE {$g_table_prefix}email_templates SET limit_email_content_to_fields_in_view = NULL WHERE limit_email_content_to_fields_in_view = $view_id");

	$success = true;
	$message = $LANG["notify_view_deleted"];
	extract(Hooks::processHookCalls("end", compact("view_id"), array("success", "message")), EXTR_OVERWRITE);

	return array($success, $message);
}


/**
 * Returns a list of all clients associated with a particular View.
 *
 * @param integer $view_id the unique View ID
 * @return array $info an array of arrays, each containing the user information.
 */
function ft_get_view_clients($view_id)
{
	global $g_table_prefix;

	$account_query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}client_views cv, {$g_table_prefix}accounts a
    WHERE  cv.view_id = $view_id
    AND    cv.account_id = a.account_id
           ");

	$account_info = array();
	while ($account = mysql_fetch_assoc($account_query))
		$account_info[] = $account;

	extract(Hooks::processHookCalls("end", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

	return $account_info;
}


/**
 * Called by administrators on the main View tab. This updates the orders and the grouping
 * of the all form Views.
 *
 * @param integer $form_id the form ID
 * @param array $info the form contents
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_views($form_id, $info)
{
	global $g_table_prefix, $LANG;

	$sortable_id = $info["sortable_id"];
	$grouped_info = explode("~", $info["{$sortable_id}_sortable__rows"]);
	$new_groups   = explode(",", $info["{$sortable_id}_sortable__new_groups"]);

	$ordered_group_ids = array();
	$new_group_order = 1;
	foreach ($grouped_info as $curr_grouped_info)
	{
		list($curr_group_id, $ordered_view_ids_str) = explode("|", $curr_grouped_info);
		$ordered_view_ids = explode(",", $ordered_view_ids_str);
		$group_name = $info["group_name_{$curr_group_id}"];

		@mysql_query("
      UPDATE {$g_table_prefix}list_groups
      SET    group_name = '$group_name',
             list_order = $new_group_order
      WHERE  group_id = $curr_group_id
        ");
		$new_group_order++;

		$order = 1;
		foreach ($ordered_view_ids as $view_id)
		{
			$is_new_sort_group = (in_array($view_id, $new_groups)) ? "yes" : "no";
			mysql_query("
        UPDATE {$g_table_prefix}views
        SET	   view_order = $order,
               group_id = $curr_group_id,
               is_new_sort_group = '$is_new_sort_group'
        WHERE  view_id = $view_id
          ");

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
function ft_update_view($view_id, $info)
{
	global $LANG;

	// update each of the tabs. Be nice to only update the changed ones...
	_ft_update_view_main_settings($view_id, $info);
	_ft_update_view_columns_settings($view_id, $info);
	_ft_update_view_field_settings($view_id, $info);
	_ft_update_view_tab_settings($view_id, $info);
	_ft_update_view_filter_settings($view_id, $info);

	$success = true;
	$message = $LANG["notify_view_updated"];
	extract(Hooks::processHookCalls("end", compact("view_id", "info"), array("success", "message")), EXTR_OVERWRITE);

	return array($success, $message);
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
function ft_get_form_views($form_id, $account_id = "")
{
	global $g_table_prefix;

	$view_hash = array();

	if (!empty($account_id))
	{
		$query = mysql_query("
      SELECT v.*
      FROM   {$g_table_prefix}views v, {$g_table_prefix}list_groups lg
      WHERE  v.form_id = $form_id AND
             v.group_id = lg.group_id AND
             (v.access_type = 'public' OR
              v.view_id IN (SELECT cv.view_id FROM {$g_table_prefix}client_views cv WHERE account_id = '$account_id'))
      ORDER BY lg.list_order, v.view_order
    ");

		// now run through the omit list, just to confirm this client isn't on it!
		while ($row = mysql_fetch_assoc($query))
		{
			$view_id = $row["view_id"];

			if ($row["access_type"] == "public")
			{
				$omit_list = ft_get_public_view_omit_list($view_id);
				if (in_array($account_id, $omit_list))
					continue;
			}

			$view_hash[] = $row;
		}
	}
	else
	{
		$query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}views v, {$g_table_prefix}list_groups lg
      WHERE  v.form_id = $form_id AND
             v.group_id = lg.group_id
      ORDER BY lg.list_order, v.view_order
        ");

		while ($row = mysql_fetch_assoc($query))
			$view_hash[] = $row;
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
function ft_get_grouped_views($form_id, $custom_params = array())
{
	global $g_table_prefix;

	// figure out what settings
	$params = array(
		"account_id"        => (isset($custom_params["account_id"])) ? $custom_params["account_id"] : "",
		"omit_empty_groups" => (isset($custom_params["omit_empty_groups"])) ? $custom_params["omit_empty_groups"] : true,
		"omit_hidden_views" => (isset($custom_params["omit_hidden_views"])) ? $custom_params["omit_hidden_views"] : false,
		"include_clients"   => (isset($custom_params["include_clients"])) ? $custom_params["include_clients"] : false
	);

	$group_query = mysql_query("
    SELECT group_id, group_name
    FROM   {$g_table_prefix}list_groups lg
    WHERE  group_type = 'form_{$form_id}_view_group'
    ORDER BY lg.list_order
  ");

	$info = array();
	while ($row = mysql_fetch_assoc($group_query))
	{
		$group_id = $row["group_id"];

		$hidden_views_clause = ($params["omit_hidden_views"]) ? " AND v.access_type != 'hidden'" : "";
		if (empty($params["account_id"]))
		{
			$view_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}views v
        WHERE  v.group_id = $group_id
               $hidden_views_clause
        ORDER BY v.view_order
      ");
		}
		else
		{
			$view_query = mysql_query("
        SELECT v.*
        FROM   {$g_table_prefix}views v
        WHERE  v.form_id = $form_id AND
               v.group_id = $group_id AND
               (v.access_type = 'public' OR v.view_id IN (
                  SELECT cv.view_id
                  FROM   {$g_table_prefix}client_views cv
                  WHERE  account_id = {$params["account_id"]}
               )) AND
               v.view_id NOT IN (
                  SELECT view_id
                  FROM   {$g_table_prefix}public_view_omit_list
                  WHERE  account_id = {$params["account_id"]}
               )
               $hidden_views_clause
        ORDER BY v.view_order
          ");
		}

		$views = array();
		while ($view_info = mysql_fetch_assoc($view_query))
		{
			$view_id = $view_info["view_id"];
			if ($params["include_clients"])
			{
				$view_info["client_info"]      = ft_get_view_clients($view_id);
				$view_info["client_omit_list"] = ft_get_public_view_omit_list($view_id);
			}

			$view_info["columns"] = ft_get_view_columns($view_id);
			$view_info["fields"]  = Views::getViewFields($view_id);
			$view_info["tabs"]    = ft_get_view_tabs($view_id, true);
			$view_info["filters"] = ft_get_view_filters($view_id, "all");
			$views[] = $view_info;
		}

		if (count($views) > 0 || !$params["omit_empty_groups"])
		{
			$curr_group = array(
				"group" => $row,
				"views" => $views
			);
			$info[] = $curr_group;
		}
	}

	return $info;
}


// -----------------------------------------------------------------------------------------------------


/**
 * Called by the ft_update_view function; updates the main settings of the View (found on the
 * first tab). Also updates the may_edit_submissions setting found on the second tab.
 *
 * @param integer $view_id
 * @param array $info
 */
function _ft_update_view_main_settings($view_id, $info)
{
	global $g_table_prefix;

	$view_name = $info["view_name"];

	$num_submissions_per_page = isset($info["num_submissions_per_page"]) ? $info["num_submissions_per_page"] : 10;
	$default_sort_field       = $info["default_sort_field"];
	$default_sort_field_order = $info["default_sort_field_order"];
	$access_type              = $info["access_type"];
	$may_delete_submissions   = $info["may_delete_submissions"];
	$may_add_submissions      = $info["may_add_submissions"];
	$may_edit_submissions     = isset($info["may_edit_submissions"]) ? "yes" : "no"; // (checkbox field)

	// do a little error checking on the num submissions field. If it's invalid, just set to to 10 without
	// informing them - it's not really necessary, I don't think
	if (!is_numeric($num_submissions_per_page))
		$num_submissions_per_page = 10;

	$result = mysql_query("
    UPDATE {$g_table_prefix}views
    SET 	 access_type = '$access_type',
           view_name = '$view_name',
           num_submissions_per_page = $num_submissions_per_page,
           default_sort_field = '$default_sort_field',
           default_sort_field_order = '$default_sort_field_order',
           may_delete_submissions = '$may_delete_submissions',
           may_edit_submissions = '$may_edit_submissions',
           may_add_submissions = '$may_add_submissions'
    WHERE  view_id = $view_id
      ");


	switch ($access_type)
	{
		case "admin":
			mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
			mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
			break;

		case "public":
			mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
			break;

		case "private":
			$selected_user_ids = isset($info["selected_user_ids"]) ? $info["selected_user_ids"] : array();
			mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
			foreach ($selected_user_ids as $client_id)
				mysql_query("INSERT INTO {$g_table_prefix}client_views (account_id, view_id) VALUES ($client_id, $view_id)");

			mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
			break;

		case "hidden":
			mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
			mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
			break;
	}

	// lastly, add in any default values for new submissions
	mysql_query("DELETE FROM {$g_table_prefix}new_view_submission_defaults WHERE view_id = $view_id");

	if (!empty($info["new_submissions"]) && $may_add_submissions == "yes")
	{
		$default_values = array_combine($info["new_submissions"], $info["new_submissions_vals"]);

		$insert_statements = array();
		$order = 1;
		while (list($field_id, $value) = each($default_values))
		{
			$insert_statements[] = "($view_id, $field_id, '$value', $order)";
			$order++;
		}
		$insert_statement_str = implode(",\n", $insert_statements);

		$query = mysql_query("
      INSERT INTO {$g_table_prefix}new_view_submission_defaults (view_id, field_id, default_value, list_order)
      VALUES $insert_statement_str
    ");
	}
}


function _ft_update_view_columns_settings($view_id, $info)
{
	global $g_table_prefix;

	$sortable_id  = $info["submission_list_sortable_id"];
	$sortable_rows = explode(",", $info["{$sortable_id}_sortable__rows"]);

	mysql_query("DELETE FROM {$g_table_prefix}view_columns WHERE view_id = $view_id");

	$insert_statements = array();
	$list_order = 1;
	foreach ($sortable_rows as $row_id)
	{
		// if the user didn't select a field for this row, ignore it
		if (empty($info["field_id_{$row_id}"]))
			continue;

		$field_id     = $info["field_id_{$row_id}"];
		$is_sortable  = (isset($info["is_sortable_{$row_id}"])) ? "yes" : "no";

		$auto_size    = "";
		$custom_width = "";
		if (isset($info["auto_size_{$row_id}"]))
		{
			$auto_size = "yes";
		}
		else
		{
			$auto_size = "no";

			// validate the custom width field
			if (!isset($info["custom_width_{$row_id}"]))
				$auto_size = "yes";
			else
			{
				$custom_width = trim($info["custom_width_{$row_id}"]);
				if (!is_numeric($custom_width))
				{
					$auto_size = "yes";
					$custom_width = "";
				}
			}
		}

		$truncate = $info["truncate_{$row_id}"];

		$insert_statements[] = "($view_id, $field_id, $list_order, '$is_sortable', '$auto_size', '$custom_width', '$truncate')";
		$list_order++;
	}

	if (!empty($insert_statements))
	{
		$insert_statement_str = implode(",\n", $insert_statements);
		$query = mysql_query("
      INSERT INTO {$g_table_prefix}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
      VALUES $insert_statement_str
    ") or die(mysql_error());
	}
}


/**
 * Called by the ft_update_view function; updates the tabs available in the View.
 *
 * @param integer $view_id
 * @param array $info
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 */
function _ft_update_view_tab_settings($view_id, $info)
{
	global $g_table_prefix, $LANG;

	@mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][0]}' WHERE view_id = $view_id AND tab_number = 1");
	@mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][1]}' WHERE view_id = $view_id AND tab_number = 2");
	@mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][2]}' WHERE view_id = $view_id AND tab_number = 3");
	@mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][3]}' WHERE view_id = $view_id AND tab_number = 4");
	@mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][4]}' WHERE view_id = $view_id AND tab_number = 5");
	@mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][5]}' WHERE view_id = $view_id AND tab_number = 6");

	return array(true, $LANG["notify_form_tabs_updated"]);
}


/**
 * Returns an array of account IDs of those clients in the omit list for this public View.
 *
 * @param integer $view_id
 * @return array
 */
function ft_get_public_view_omit_list($view_id)
{
	global $g_table_prefix;

	$query = mysql_query("
    SELECT account_id
    FROM   {$g_table_prefix}public_view_omit_list
    WHERE  view_id = $view_id
      ");

	$client_ids = array();
	while ($row = mysql_fetch_assoc($query))
		$client_ids[] = $row["account_id"];

	return $client_ids;
}


/**
 * Called by the administrator only. Updates the list of clients on a public View's omit list.
 *
 * @param array $info
 * @param integer $view_id
 * @return array [0] T/F, [1] message
 */
function ft_update_public_view_omit_list($info, $view_id)
{
	global $g_table_prefix, $LANG;

	mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");

	$client_ids = (isset($info["selected_client_ids"])) ? $info["selected_client_ids"] : array();
	foreach ($client_ids as $account_id)
	{
		mysql_query("INSERT INTO {$g_table_prefix}public_view_omit_list (view_id, account_id) VALUES ($view_id, $account_id)");
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
function _ft_cache_view_stats($form_id, $view_id = "")
{
	global $g_table_prefix;

	$view_ids = array();
	if (empty($view_id))
		$view_ids = ft_get_view_ids($form_id);
	else
		$view_ids[] = $view_id;

	foreach ($view_ids as $view_id)
	{
		$filters = ft_get_view_filter_sql($view_id);

		// if there aren't any filters, just set the submission count & first submission date to the same
		// as the parent form
		if (empty($filters))
		{
			$_SESSION["ft"]["view_{$view_id}_num_submissions"] = $_SESSION["ft"]["form_{$form_id}_num_submissions"];
		}
		else
		{
			$filter_clause = join(" AND ", $filters);

			$count_query = mysql_query("
        SELECT count(*) as c
        FROM   {$g_table_prefix}form_$form_id
        WHERE  is_finalized = 'yes' AND
        $filter_clause
          ")
			or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__, mysql_error());

			$info = mysql_fetch_assoc($count_query);
			$_SESSION["ft"]["view_{$view_id}_num_submissions"] = $info["c"];
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
function ft_get_view_list($form_id)
{
	global $g_table_prefix;

	$query = mysql_query("
    SELECT view_id, view_name
    FROM   {$g_table_prefix}views
    WHERE  form_id = $form_id
    ORDER BY view_order
      ") or dir(mysql_error());

	$result = array();
	while ($row = mysql_fetch_assoc($query))
		$result[] = $row;

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
function ft_get_default_view($form_id)
{
	global $g_table_prefix;

	$query = mysql_query("
    SELECT view_id
    FROM   {$g_table_prefix}views
    WHERE  form_id = $form_id
    ORDER BY view_order
    LIMIT 1
      ");

	$view_id = "";
	$view_info = mysql_fetch_assoc($query);

	if (!empty($view_info))
		$view_id = $view_info["view_id"];

	return $view_id;
}


/**
 * This feature was added in 2.1.0 - it lets administrators define default values for all new submissions
 * created with the View. This was added to solve a problem where submissions were created in a View where
 * that new submission wouldn't meet the criteria for inclusion. But beyond that, this is a handy feature to
 * cut down on configuration time for new data sets.
 *
 * @param $view_id
 * @return array
 */
function ft_get_new_view_submission_defaults($view_id)
{
	global $g_table_prefix;

	$query = mysql_query("
    SELECT *
    FROM {$g_table_prefix}new_view_submission_defaults
    WHERE view_id = $view_id
    ORDER BY list_order
  ");

	$info = array();
	while ($row = mysql_fetch_assoc($query))
	{
		$info[] = $row;
	}

	return $info;
}

