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


// -----------------------------------------------------------------------------------------------------


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
    $db = Core::$db;
	$LANG = Core::$L;

    Views::deletePublicViewOmitList($view_id);

	$client_ids = (isset($info["selected_client_ids"])) ? $info["selected_client_ids"] : array();
	foreach ($client_ids as $account_id) {
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

