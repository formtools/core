<?php

/**
 * This file defines all user account functions used by the client accounts. Also see accounts.php (for
 * general functions) and administrator.php for functions used by administrator accounts.
 *
 * @copyright Benjamin Keen 2014
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Clients
 */


// -------------------------------------------------------------------------------------------------

use FormTools\Accounts;
use FormTools\Settings;



/**
 * Performs a simple search of the client list, returning ALL results (not in pages).
 *
 * @param array $search_criteria optional search / sort criteria. Keys are:
 *                               "order" - (string) client_id-ASC, client_id-DESC, last_name-DESC,
 *                                         last_name-ASC, email-ASC, email-DESC
 *                               "keyword" - (string) searches the client name and email fields.
 *                               "status" - (string) "account_status", "disabled", or empty (all)
 */
function ft_search_clients($search_criteria = array())
{
	global $g_table_prefix;

	extract(Hooks::processHookCalls("start", compact("search_criteria"), array("search_criteria")), EXTR_OVERWRITE);

	if (!isset($search_criteria["order"]))
		$search_criteria["order"] = "client_id-DESC";

	$order_clause = _ft_get_client_order_clause($search_criteria["order"]);

	$status_clause = "";
	if (isset($search_criteria["status"]))
	{
		switch ($search_criteria["status"])
		{
			case "active":
				$status_clause = "account_status = 'active' ";
				break;
			case "disabled":
				$status_clause = "account_status = 'disabled'";
				break;
			case "pending":
				$status_clause = "account_status = 'pending'";
				break;
			default:
				$status_clause = "";
				break;
		}
	}

	$keyword_clause = "";
	if (isset($search_criteria["keyword"]) && !empty($search_criteria["keyword"]))
	{
		$string = $search_criteria["keyword"];
		$fields = array("last_name", "first_name", "email", "account_id");

		$clauses = array();
		foreach ($fields as $field)
			$clauses[] = "$field LIKE '%$string%'";

		$keyword_clause = implode(" OR ", $clauses);
	}

	// add up the where clauses
	$where_clauses = array("account_type = 'client'");
	if (!empty($status_clause)) $where_clauses[] = "($status_clause)";
	if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";

	$where_clause = "WHERE " . implode(" AND ", $where_clauses);

	// get the clients
	$client_query_result = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}accounts
    $where_clause
    $order_clause
           ");

	$clients = array();
	while ($client = mysql_fetch_assoc($client_query_result))
		$clients[] = $client;

	extract(Hooks::processHookCalls("end", compact("search_criteria", "clients"), array("clients")), EXTR_OVERWRITE);

	return $clients;
}


/**
 * This returns the IDs of the previous and next client accounts, as determined by the administrators current
 * search and sort.
 *
 * Not happy with this function! Getting this info is surprisingly tricky, once you throw in the sort clause.
 * Still, the number of client accounts are liable to be quite small, so it's not such a sin.
 *
 * @param integer $account_id
 * @param array $search_criteria
 * @return hash prev_account_id => the previous account ID (or empty string)
 *              next_account_id => the next account ID (or empty string)
 */
function ft_get_client_prev_next_links($account_id, $search_criteria = array())
{
	global $g_table_prefix;

	$keyword_clause = "";
	if (isset($search_criteria["keyword"]) && !empty($search_criteria["keyword"]))
	{
		$string = $search_criteria["keyword"];
		$fields = array("last_name", "first_name", "email", "account_id");

		$clauses = array();
		foreach ($fields as $field)
			$clauses[] = "$field LIKE '%$string%'";

		$keyword_clause = implode(" OR ", $clauses);
	}

	// add up the where clauses
	$where_clauses = array("account_type = 'client'");
	if (!empty($status_clause)) $where_clauses[] = "($status_clause)";
	if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";

	$where_clause = "WHERE " . implode(" AND ", $where_clauses);

	$order_clause = _ft_get_client_order_clause($search_criteria["order"]);

	// get the clients
	$client_query_result = mysql_query("
    SELECT account_id
    FROM   {$g_table_prefix}accounts
    $where_clause
    $order_clause
           ");

	$sorted_account_ids = array();
	while ($row = mysql_fetch_assoc($client_query_result))
	{
		$sorted_account_ids[] = $row["account_id"];
	}
	$current_index = array_search($account_id, $sorted_account_ids);

	$return_info = array("prev_account_id" => "", "next_account_id" => "");
	if ($current_index === 0)
	{
		if (count($sorted_account_ids) > 1)
			$return_info["next_account_id"] = $sorted_account_ids[$current_index+1];
	}
	else if ($current_index === count($sorted_account_ids)-1)
	{
		if (count($sorted_account_ids) > 1)
			$return_info["prev_account_id"] = $sorted_account_ids[$current_index-1];
	}
	else
	{
		$return_info["prev_account_id"] = $sorted_account_ids[$current_index-1];
		$return_info["next_account_id"] = $sorted_account_ids[$current_index+1];
	}

	return $return_info;
}


/**
 * Used in a couple of places, so I stuck it here. (Refactor this hideousness!)
 *
 * @param string $order
 * @return string the ORDER BY clause
 */
function _ft_get_client_order_clause($order = "")
{
	$order_clause = "";
	switch ($order)
	{
		case "client_id-DESC":
			$order_clause = "account_id DESC";
			break;
		case "client_id-ASC":
			$order_clause = "account_id ASC";
			break;
		case "first_name-DESC":
			$order_clause = "first_name DESC";
			break;
		case "first_name-ASC":
			$order_clause = "first_name ASC";
			break;
		case "last_name-DESC":
			$order_clause = "last_name DESC";
			break;
		case "last_name-ASC":
			$order_clause = "last_name ASC";
			break;
		case "email-DESC":
			$order_clause = "email DESC";
			break;
		case "email-ASC":
			$order_clause = "email ASC";
			break;
		case "status-DESC":
			$order_clause = "account_status DESC";
			break;
		case "status-ASC":
			$order_clause = "account_status ASC";
			break;
		case "last_logged_in-DESC":
			$order_clause = "last_logged_in DESC";
			break;
		case "last_logged_in-ASC":
			$order_clause = "last_logged_in ASC";
			break;

		default:
			$order_clause = "account_id DESC";
			break;
	}

	if (!empty($order_clause))
		$order_clause = "ORDER BY $order_clause";

	return $order_clause;
}
