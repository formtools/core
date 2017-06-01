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
 * This is called by the administrator on the main Views tab. It lets them delete an entire group of Views
 * in one go.
 *
 * @param integer $group_id
 */
function ft_delete_view_group($group_id)
{
	global $g_table_prefix, $LANG;

	$query = $db->query("
    SELECT view_id
    FROM   {PREFIX}views
    WHERE  group_id = $group_id
  ");

	// first, delete all the Views
	while ($view_info = mysql_fetch_assoc($query))
	{
		$view_id = $view_info["view_id"];
		ft_delete_view($view_id);
	}

	// next, delete the group
	$db->query("DELETE FROM {PREFIX}list_groups WHERE group_id = $group_id");

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

	$view_info = Views::getView($view_id);

	$db->query("DELETE FROM {PREFIX}client_views WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}view_columns WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}view_fields WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}view_filters WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}view_tabs WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}public_view_omit_list WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}list_groups WHERE group_type = 'view_fields_$view_id'");
	$db->query("DELETE FROM {PREFIX}email_template_edit_submission_views WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}email_template_when_sent_views WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}new_view_submission_defaults WHERE view_id = $view_id");
	$db->query("DELETE FROM {PREFIX}views WHERE view_id = $view_id");

	// hmm... This should be handled better: the user needs to be notified prior to deleting a View to describe all the dependencies
	$db->query("UPDATE {PREFIX}email_templates SET limit_email_content_to_fields_in_view = NULL WHERE limit_email_content_to_fields_in_view = $view_id");

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

	$account_query = $db->query("
    SELECT *
    FROM   {PREFIX}client_views cv, {PREFIX}accounts a
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

		@$db->query("
      UPDATE {PREFIX}list_groups
      SET    group_name = '$group_name',
             list_order = $new_group_order
      WHERE  group_id = $curr_group_id
        ");
		$new_group_order++;

		$order = 1;
		foreach ($ordered_view_ids as $view_id)
		{
			$is_new_sort_group = (in_array($view_id, $new_groups)) ? "yes" : "no";
			$db->query("
        UPDATE {PREFIX}views
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

	// update each of the tabs
	_ft_update_view_main_settings($view_id, $info);
	ViewColumns::updateViewColumnsSettings($view_id, $info);
	ViewFields::updateViewFieldSettings($view_id, $info);
	ViewTabs::updateViewTabSettings($view_id, $info);
	ViewFilters::updateViewFilterSettings($view_id, $info);

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
		$query = $db->query("
      SELECT v.*
      FROM   {PREFIX}views v, {PREFIX}list_groups lg
      WHERE  v.form_id = $form_id AND
             v.group_id = lg.group_id AND
             (v.access_type = 'public' OR
              v.view_id IN (SELECT cv.view_id FROM {PREFIX}client_views cv WHERE account_id = '$account_id'))
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
		$query = $db->query("
      SELECT *
      FROM   {PREFIX}views v, {PREFIX}list_groups lg
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

	$group_query = $db->query("
    SELECT group_id, group_name
    FROM   {PREFIX}list_groups lg
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
			$view_query = $db->query("
        SELECT *
        FROM   {PREFIX}views v
        WHERE  v.group_id = $group_id
               $hidden_views_clause
        ORDER BY v.view_order
      ");
		}
		else
		{
			$view_query = $db->query("
        SELECT v.*
        FROM   {PREFIX}views v
        WHERE  v.form_id = $form_id AND
               v.group_id = $group_id AND
               (v.access_type = 'public' OR v.view_id IN (
                  SELECT cv.view_id
                  FROM   {PREFIX}client_views cv
                  WHERE  account_id = {$params["account_id"]}
               )) AND
               v.view_id NOT IN (
                  SELECT view_id
                  FROM   {PREFIX}public_view_omit_list
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

			$view_info["columns"] = ViewColumns::getViewColumns($view_id);
			$view_info["fields"]  = ViewFields::getViewFields($view_id);
			$view_info["tabs"]    = ViewTabs::getViewTabs($view_id, true);
			$view_info["filters"] = ViewFilters::getViewFilters($view_id);
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

	$result = $db->query("
    UPDATE {PREFIX}views
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
			$db->query("DELETE FROM {PREFIX}client_views WHERE view_id = $view_id");
			$db->query("DELETE FROM {PREFIX}public_view_omit_list WHERE view_id = $view_id");
			break;

		case "public":
			$db->query("DELETE FROM {PREFIX}client_views WHERE view_id = $view_id");
			break;

		case "private":
			$selected_user_ids = isset($info["selected_user_ids"]) ? $info["selected_user_ids"] : array();
			$db->query("DELETE FROM {PREFIX}client_views WHERE view_id = $view_id");
			foreach ($selected_user_ids as $client_id)
				$db->query("INSERT INTO {PREFIX}client_views (account_id, view_id) VALUES ($client_id, $view_id)");

			$db->query("DELETE FROM {PREFIX}public_view_omit_list WHERE view_id = $view_id");
			break;

		case "hidden":
			$db->query("DELETE FROM {PREFIX}client_views WHERE view_id = $view_id");
			$db->query("DELETE FROM {PREFIX}public_view_omit_list WHERE view_id = $view_id");
			break;
	}

	// lastly, add in any default values for new submissions
	$db->query("DELETE FROM {PREFIX}new_view_submission_defaults WHERE view_id = $view_id");

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

		$query = $db->query("
      INSERT INTO {PREFIX}new_view_submission_defaults (view_id, field_id, default_value, list_order)
      VALUES $insert_statement_str
    ");
	}
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

	$query = $db->query("
    SELECT account_id
    FROM   {PREFIX}public_view_omit_list
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

	$db->query("DELETE FROM {PREFIX}public_view_omit_list WHERE view_id = $view_id");

	$client_ids = (isset($info["selected_client_ids"])) ? $info["selected_client_ids"] : array();
	foreach ($client_ids as $account_id)
	{
		$db->query("INSERT INTO {PREFIX}public_view_omit_list (view_id, account_id) VALUES ($view_id, $account_id)");
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
		$view_ids = Views::getViewIds($form_id);
	else
		$view_ids[] = $view_id;

	foreach ($view_ids as $view_id)
	{
		$filters = ViewFilters::getViewFilterSql($view_id);

		// if there aren't any filters, just set the submission count & first submission date to the same
		// as the parent form
		if (empty($filters))
		{
			$_SESSION["ft"]["view_{$view_id}_num_submissions"] = $_SESSION["ft"]["form_{$form_id}_num_submissions"];
		}
		else
		{
			$filter_clause = join(" AND ", $filters);

			$count_query = $db->query("
        SELECT count(*) as c
        FROM   {PREFIX}form_$form_id
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

	$query = $db->query("
    SELECT view_id, view_name
    FROM   {PREFIX}views
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

	$query = $db->query("
    SELECT view_id
    FROM   {PREFIX}views
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

	$query = $db->query("
    SELECT *
    FROM {PREFIX}new_view_submission_defaults
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

