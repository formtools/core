<?php

/**
 * This file defines all functionality relating to generic list groups.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage ListGroups
 */


// -------------------------------------------------------------------------------------------------


/**
 * Inserts a new list group.
 *
 * @param $account_id
 */
function ft_add_list_group($group_type, $group_name, $next_order = "")
{
  global $g_table_prefix;

  $group_name = ft_sanitize($group_name);

  if (empty($next_order))
  {
    // get the next list_order for this group
    $query = mysql_query("
      SELECT list_order
      FROM   {$g_table_prefix}list_groups
      WHERE  group_type = '$group_type'
      ORDER BY list_order DESC LIMIT 1
    ");
    $result = mysql_fetch_assoc($query);

    $next_order = (!isset($result["list_order"])) ? 1 : $result["list_order"] + 1;
  }

  $query = mysql_query("
    INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
    VALUES ('$group_type', '$group_name', '', $next_order)
  ");

  $group_id = mysql_insert_id();

  return array(
    "group_id"   => $group_id,
    "group_name" => $group_name
  );
}


/**
 * Deletes a list group. Returns a boolean to indicate whether the deletion was successful or not.
 *
 * @param $group_id
 */
function ft_delete_list_group($group_id)
{
  global $g_table_prefix;

  return mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_id = $group_id");
}
