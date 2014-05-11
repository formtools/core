<?php

/**
 * This file contains all functions relating to managing the Option Lists. Before 2.1.0, Option Lists
 * were called "Field Option Groups" & this file was named field_option_groups.php
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage OptionLists
 */


// -------------------------------------------------------------------------------------------------


/**
 * Returns all list options in the database.
 *
 * @param $page_num the current page number, or "all" for all results.
 * @return array ["results"] an array of option group information
 *               ["num_results"] the total number of option groups in the database.
 */
function ft_get_option_lists($page_num = 1, $order = "option_list_name-ASC")
{
  global $g_table_prefix;

  if ($page_num == "all")
    $limit_clause = "";
  else
  {
    $num_option_lists_per_page = isset($_SESSION["ft"]["settings"]["num_option_lists_per_page"]) ?
      $_SESSION["ft"]["settings"]["num_option_lists_per_page"] : 10;

    // determine the LIMIT clause
    $limit_clause = "";
    if (empty($page_num))
      $page_num = 1;
    $first_item = ($page_num - 1) * $num_option_lists_per_page;
    $limit_clause = "LIMIT $first_item, $num_option_lists_per_page";
  }

  $order_clause = _ft_get_option_list_order_clause($order);

  $result = mysql_query("
    SELECT *
    FROM 	 {$g_table_prefix}option_lists
    $order_clause
    $limit_clause
      ");
  $count_result = mysql_query("
    SELECT count(*) as c
    FROM 	 {$g_table_prefix}option_lists
         ");
  $count_hash = mysql_fetch_assoc($count_result);

  $option_lists = array();
  while ($row = mysql_fetch_assoc($result))
    $option_lists[] = $row;

  $return_hash = array();
  $return_hash["results"] = $option_lists;
  $return_hash["num_results"]  = $count_hash["c"];

  extract(ft_process_hook_calls("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

  return $return_hash;
}


/**
 * Awkward name, but there you go. It returns all options in an option list. If you want to get ALL
 * information about the group (e.g. the group name etc), use ft_get_option_list().
 *
 * Option lists may or may not be grouped - but for consistency on the backend, even ungrouped option
 * lists are stored in an "empty" group. It's a little ugly, I know - but overall it keeps the code
 * simple (i.e. you don't have to do two things depending on whether they're grouped or not). Plus the
 * UI on the option lists page was easy to hide/show the grouping functionality. With this in mind,
 * this function returns the GROUPED option lists as an array with the following structure (there will
 * only ever be a single top level array index for ungrouped option lists):
 *
 *   [0] => array(
 *            "group_info" => array(
 *                              "group_id"   => X,
 *                              "group_type" => "...",
 *                              "group_name" => "...",
 *                              "list_order" => Y
 *                            ),
 *            "options" => array(
 *              - an array of the option info directly from the ft_field_options table.
 *            )
 *          )
 *   [1] => ...
 *
 * Whether or not the option list is grouped is found in the "is_grouped" field in the ft_option_lists
 * table. That info is not returned by this function - only by ft_get_option_list().
 *
 * @param integer $list_id the option list ID
 * @return array
 */
function ft_get_option_list_options($list_id)
{
  global $g_table_prefix;

  $group_query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}list_groups
    WHERE  group_type = 'option_list_{$list_id}'
    ORDER BY list_order
  ");

  $info = array();
  while ($row = mysql_fetch_assoc($group_query))
  {
    $group_id = $row["group_id"];
    $option_query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_options
      WHERE  list_group_id = $group_id
      ORDER BY option_order
    ");
    $options = array();
    while ($curr_option = mysql_fetch_assoc($option_query))
      $options[] = $curr_option;

    $curr_group = array(
      "group_info" => $row,
      "options"    => $options
    );
    $info[] = $curr_group;
  }

  return $info;
}


/**
 * Returns the number of options in an option list - regardless of whether the option list
 * is grouped or not.
 *
 * @param integer $list_id
 * @return integer the option count
 */
function ft_get_num_options_in_option_list($list_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT count(*) as c
    FROM   {$g_table_prefix}field_options
    WHERE  list_id = $list_id
      ");

  $result = mysql_fetch_assoc($query);
  return $result["c"];
}


/**
 * Returns all info about an option list.
 *
 * @param integer $list_id
 */
function ft_get_option_list($list_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}option_lists
    WHERE  list_id = $list_id
      ");

  $info = mysql_fetch_assoc($query);
  $info["options"] = ft_get_option_list_options($list_id);

  return $info;
}



/**
 * Returns the total number of option lists in the database.
 *
 * @return integer
 */
function ft_get_num_option_lists()
{
  global $g_table_prefix;

  $query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}option_lists");
  $result = mysql_fetch_assoc($query);
  return $result["c"];
}


/**
 * This function is called whenever the user adds an option list through the Add External form process. It checks
 * all existing option lists to see if an identical set already exists. If it does, it returns the existing
 * option list ID and if not, creates a new one and returns that ID.
 *
 * @param integer $form_id
 * @param array $option_list_info
 * @return integer $list_id the new or existing option list ID
 */
function ft_create_unique_option_list($form_id, $option_list_info)
{
  global $g_table_prefix;

  $existing_option_lists = ft_get_option_lists("all");

  $already_exists = false;
  $list_id = "";
  foreach ($existing_option_lists["results"] as $existing_option_list)
  {
    $curr_list_id = $existing_option_list["list_id"];

    // when comparing field groups, just compare the actual field options. The option list name & original
    // form aren't considered. This may lead to a little head-shaking in the UI when they see an inappropriate
    // option list name, but it's easily changed
    $grouped_option_list_info = ft_get_option_list_options($curr_list_id);

    // $curr_options contains an array of hashes. Each hash contains information about the group & info about
    // the options in that group. Since we're just comparing a brand new list, we know that it only has one group:
    // hence, rule out those option lists with more than one group
    if (count($grouped_option_list_info) > 1)
      continue;

    // fringe case. Technically, a user may have created an Option List then deleted all options & groups.
    if (count($grouped_option_list_info) == 0)
      continue;

    $curr_options = $grouped_option_list_info[0]["options"];
    if (count($curr_options) != count($option_list_info["options"]))
      continue;

    $has_same_option_fields = true;
    for ($i=0; $i<count($curr_options); $i++)
    {
      $val = ft_sanitize($curr_options[$i]["option_value"]);
      $txt = ft_sanitize($curr_options[$i]["option_name"]);

      $val2 = $option_list_info["options"][$i]["value"];
      $txt2 = $option_list_info["options"][$i]["text"];

      if ($val != $val2 || $txt != $txt2)
      {
        $has_same_option_fields = false;
        break;
      }
    }

    if (!$has_same_option_fields)
      continue;

    $already_exists = true;
    $list_id = $curr_list_id;
    break;
  }

  // if this group didn't already exist, add it!
  if (!$already_exists)
  {
    $option_list_name = $option_list_info["option_list_name"];

    $query = "INSERT INTO {$g_table_prefix}option_lists (option_list_name, is_grouped, original_form_id)
      VALUES ('$option_list_name', 'no', $form_id)";
    $result = mysql_query($query) or ft_handle_error($query, mysql_error());

    if (!$result)
      return false;

    $list_id = mysql_insert_id();

    // now add the list group entry
    $query = mysql_query("
      INSERT INTO {$g_table_prefix}list_groups (group_type, list_order)
      VALUES ('option_list_{$list_id}', 1)
    ") or die(mysql_error());
    $list_group_id = mysql_insert_id();

    // add the options
    $order = 1;
    foreach ($option_list_info["options"] as $option)
    {
      $value = ft_sanitize($option["value"]);
      $text  = ft_sanitize($option["text"]);

      $query = "
        INSERT INTO {$g_table_prefix}field_options (list_id, list_group_id, option_value, option_name, option_order)
        VALUES ($list_id, $list_group_id, '$value', '$text', $order)
          ";
      $result = mysql_query($query) or ft_handle_error($query, mysql_error());
      $order++;
    }
  }

  return $list_id;
}


/**
 * Returns the number of fields that use a particular field option group.
 *
 * @param integer $group_id
 * @return integer the number of fields
 */
function ft_get_num_fields_using_option_list($list_id)
{
  global $g_table_prefix;

  // technically it's possible for a single field to reference the same option list multiple times
  $query = mysql_query("
    SELECT COUNT(DISTINCT field_id) as c
    FROM   {$g_table_prefix}field_settings fs, {$g_table_prefix}field_type_settings fts
    WHERE  fs.setting_value = $list_id AND
           fs.setting_id = fts.setting_id AND
           fts.field_type = 'option_list_or_form_field'
      ");
  $result = mysql_fetch_assoc($query);

  return $result["c"];
}


/**
 * Returns information about fields that use a particular option list. If the second parameter is set,
 * it returns the information grouped by form instead.
 *
 * @param integer $list_id
 * @param array
 * @return array
 */
function ft_get_fields_using_option_list($list_id, $custom_params = array())
{
  global $g_table_prefix;

  $params = array(
    "group_by_form" => (isset($custom_params["group_by_form"])) ? $custom_params["group_by_form"] : false
  );

  $query = mysql_query("
    SELECT field_id
    FROM   {$g_table_prefix}field_settings fs, {$g_table_prefix}field_type_settings fts
    WHERE  fs.setting_value = $list_id AND
           fs.setting_id = fts.setting_id AND
           fts.field_type = 'option_list_or_form_field'
      ");

  $field_ids = array();
  while ($row = mysql_fetch_assoc($query))
    $field_ids[] = $row["field_id"];

  if (empty($field_ids))
    return array();

  $field_id_str = implode(",", $field_ids);
  $query = mysql_query("
    SELECT f.*, ff.*
    FROM   {$g_table_prefix}form_fields ff, {$g_table_prefix}forms f
    WHERE  field_id IN ($field_id_str) AND
           f.form_id = ff.form_id
    ORDER BY f.form_name, ff.field_title
      ") or die(mysql_error());

  $results = array();
  while ($row = mysql_fetch_assoc($query))
    $results[] = $row;

  if ($params["group_by_form"])
  {
    $grouped_results = array();
    foreach ($results as $row)
    {
      if (!array_key_exists($row["form_id"], $grouped_results))
      {
        $grouped_results[$row["form_id"]] = array(
          "form_name" => $row["form_name"],
          "form_id"   => $row["form_id"],
          "fields"    => array()
        );
      }
      $grouped_results[$row["form_id"]]["fields"][] = $row;
    }

    $results = $grouped_results;
  }

  return $results;
}


/**
 * Updates a single field option group.
 *
 * @param integer $group_id
 * @param array $info
 */
function ft_update_option_list($list_id, $info)
{
  global $g_table_prefix, $LANG;

  $info = ft_sanitize($info);

  $option_list_name = $info["option_list_name"];
  $is_grouped       = isset($info["is_grouped"]) ? $info["is_grouped"] : "no";

  $query = mysql_query("
    UPDATE {$g_table_prefix}option_lists
    SET    option_list_name  = '$option_list_name',
           is_grouped = '$is_grouped'
    WHERE  list_id = $list_id
      ");

  // remove the old field options & list groups, we're going to insert new ones
  @mysql_query("DELETE FROM {$g_table_prefix}field_options WHERE list_id = $list_id");
  @mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_type = 'option_list_{$list_id}'");

  $sortable_id = $info["sortable_id"];
  $new_groups    = explode(",", $info["{$sortable_id}_sortable__new_groups"]);
  $grouped_rows  = explode("~", $info["{$sortable_id}_sortable__rows"]);
  $deleted_group = isset($info["{$sortable_id}_sortable__delete_group"]) ? $info["{$sortable_id}_sortable__delete_group"] : "";

  // the logic here is a bit complex, but the general idea is that this code works for both grouped and
  // ungrouped option lists. Ungrouped option lists are still grouped in a single group - behind the scenes.
  $new_group_order = 1;

  if ($is_grouped == "no")
  {
    $empty_group_info = ft_add_list_group("option_list_{$list_id}", "", 1);
    $empty_group_id = $empty_group_info["group_id"];
  }

  $order = 1;
  foreach ($grouped_rows as $curr_grouped_info)
  {
    list($curr_group_id, $ordered_row_ids_str) = explode("|", $curr_grouped_info);

    // if the user's deleting a group, we just ignore the group info so it's not re-created
    if ($curr_group_id == $deleted_group)
      continue;

    $ordered_row_ids = explode(",", $ordered_row_ids_str);

    if ($is_grouped == "yes")
    {
      $group_name = $info["group_name_{$curr_group_id}"];
      $new_group_info = ft_add_list_group("option_list_{$list_id}", $group_name, $new_group_order);
      $curr_group_id = $new_group_info["group_id"];
      $new_group_order++;
    }
    else
    {
      $curr_group_id = $empty_group_id;
    }

    // now add the rows in this group
    foreach ($ordered_row_ids as $i)
    {
      if (!isset($info["field_option_value_{$i}"]))
        continue;

      $value = $info["field_option_value_{$i}"];
      $text  = $info["field_option_text_{$i}"];
      $is_new_sort_group = (in_array($i, $new_groups)) ? "yes" : "no";

      mysql_query("
        INSERT INTO {$g_table_prefix}field_options (list_id, option_order, option_value, option_name,
          is_new_sort_group, list_group_id)
        VALUES ($list_id, $order, '$value', '$text', '$is_new_sort_group', $curr_group_id)
          ");
      $order++;
    }
  }

  $success = true;
  $message = $LANG["notify_option_list_updated"];
  extract(ft_process_hook_calls("end", compact("list_id", "info"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Creates an identical copy of an existing Option List, or creates a new blank one. This can be handy if
 * the user was using a single group for multiple fields, but one of the form fields changed. They can just
 * create a new copy, tweak it and re-assign the field.
 *
 * If no Option List ID is passed in the first param, it creates a new blank Option List (sorry for the crappy
 * function name).
 *
 * @param integer $list_id
 * @param integer $field_id if this parameter is set, the new Option List will be assigned to whatever
 *   field IDs are specified. Note: this only works for Field Types that have a single
 * @return mixed the list ID if successful, false if not
 */
function ft_duplicate_option_list($list_id = "", $field_ids = array())
{
  global $g_table_prefix, $LANG;

  // to ensure that all new field option groups have unique names, query the database and find the next free
  // group name of the form "New Option List (X)" (where "New Option List" is in the language of the current user)
  $lists = ft_get_option_lists("all");
  $list_names = array();
  foreach ($lists["results"] as $list_info)
    $list_names[] = $list_info["option_list_name"];

  $base_new_option_list = $LANG["phrase_new_option_list"];
  $new_option_list_name = $base_new_option_list;

  if (in_array($new_option_list_name, $list_names))
  {
    $count = 2;
    $new_option_list_name = "$base_new_option_list ($count)";

    while (in_array($new_option_list_name, $list_names))
    {
      $count++;
      $new_option_list_name = "$base_new_option_list ($count)";
    }
  }

  if (empty($list_id))
  {
    $query = mysql_query("
      INSERT INTO {$g_table_prefix}option_lists (option_list_name, is_grouped)
      VALUES ('$new_option_list_name', 'no')
    ");

    if (!$query)
      return false;

    $new_list_id = mysql_insert_id();
  }
  else
  {
    $option_list_info = ft_get_option_list($list_id);
    $is_grouped = $option_list_info["is_grouped"];

    $query = mysql_query("
      INSERT INTO {$g_table_prefix}option_lists (option_list_name, is_grouped)
      VALUES ('$new_option_list_name', '$is_grouped')
    ");

    if (!$query)
      return false;

    $new_list_id = mysql_insert_id();

    // add add the option groups and their field options
    foreach ($option_list_info["options"] as $grouped_option_info)
    {
      $group_info = $grouped_option_info["group_info"];
      $options    = $grouped_option_info["options"];

      $group_type = "option_list_{$new_list_id}";
      $group_name = $group_info["group_name"];
      $list_order = $group_info["list_order"];
      $new_list_group_info = ft_add_list_group($group_type, $group_name, $list_order);
      $new_list_group_id = $new_list_group_info["group_id"];

      foreach ($options as $option_info)
      {
        $option_info = ft_sanitize($option_info);
        $order = $option_info["option_order"];
        $value = $option_info["option_value"];
        $name  = $option_info["option_name"];
        $is_new_sort_group = $option_info["is_new_sort_group"];

        mysql_query("
          INSERT INTO {$g_table_prefix}field_options (list_id, list_group_id, option_order,
            option_value, option_name, is_new_sort_group)
          VALUES ($new_list_id, $new_list_group_id, '$order', '$value', '$name', '$is_new_sort_group')
            ") or die(mysql_error());
      }
    }
  }

  // if we need to map this new option list to a field - or fields, loop through them and add them
  // one by one. Note: field types may use multiple Option Lists, which makes this extremely difficult. But
  // to make it as generic as possible, this code picks the first Option List field for the field type (as determined
  // by the setting list order)
  if (!empty($field_ids))
  {
    foreach ($field_ids as $field_id)
    {
      $field_type_id = ft_get_field_type_id_by_field_id($field_id);
      $field_settings = ft_get_field_type_settings($field_type_id);

      $option_list_setting_id = "";
      foreach ($field_settings as $field_setting_info)
      {
      	if ($field_setting_info["field_type"] == "option_list_or_form_field")
      	{
      	  $option_list_setting_id = $field_setting_info["setting_id"];
      	  break;
      	}
      }

      // this should ALWAYS have found a setting, but just in case...
      if (!empty($option_list_setting_id))
      {
      	mysql_query("DELETE FROM {$g_table_prefix}field_settings WHERE field_id = $field_id AND setting_id = $option_list_setting_id");
        @mysql_query("
          INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
          VALUES ($field_id, $option_list_setting_id, $new_list_id)
           ");
      }
    }
  }

  return $new_list_id;
}


/**
 * Deletes an option list from the database. Note: it only deletes lists that don't have any
 * form fields assigned to them; generally this is prevented from being called unless that condition is
 * met, but it also checks here just in case.
 *
 * @param integer $list_id
 * @return array [0] T/F<br />
 *               [1] error/success message
 */
function ft_delete_option_list($list_id)
{
  global $g_table_prefix, $LANG;

  // slight behavioural change in 2.1.0. Now you CAN delete Option Lists that are used by one or more fields.
  // It just clears any references, thus leaving those fields incompletely configured (which isn't the end of
  // the world!)
  $fields = ft_get_fields_using_option_list($list_id);
  foreach ($fields as $field_info)
  {
    $field_id      = $field_info["field_id"];
    $field_type_id = $field_info["field_type_id"];
    $settings = ft_get_field_type_settings($field_type_id);

    $setting_ids = array();
    foreach ($settings as $setting_info)
    {
      if ($setting_info["field_type"] == "option_list_or_form_field")
      {
      	$setting_ids[] = $setting_info["setting_id"];
      }
    }
    if (empty($setting_ids))
      continue;

    $setting_id_str = implode(",", $setting_ids);

    // now we delete any entries in the field_settings table with field_id, setting_id and a NUMERIC value for the
    // setting_value column. That column is also
    mysql_query("
      DELETE FROM {$g_table_prefix}field_settings
      WHERE field_id = $field_id AND
            setting_id IN ($setting_id_str) AND
            setting_value NOT LIKE 'form_field%'
    ");
  }

  mysql_query("DELETE FROM {$g_table_prefix}field_options WHERE list_id = $list_id");
  mysql_query("DELETE FROM {$g_table_prefix}option_lists WHERE list_id = $list_id");
  mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_type = 'option_list_{$list_id}'");

  $success = true;
  $message = $LANG["notify_option_list_deleted"];
  extract(ft_process_hook_calls("end", compact("list_id"), array("success", "message")), EXTR_OVERWRITE);

  return array(true, $message);
}


/**
 * This returns the IDs of the previous and next Option Lists, as determined by the administrators current
 * sort.
 *
 * Not happy with this function! Getting this info is surprisingly tricky, once you throw in the sort clause.
 * Still, the number of client accounts are liable to be quite small, so it's not such a sin.
 *
 * @param integer $list_id
 * @param array $search_criteria
 * @return hash prev_option_list_id => the previous account ID (or empty string)
 *              next_option_list_id => the next account ID (or empty string)
 */
function ft_get_option_list_prev_next_links($list_id, $order = "")
{
  global $g_table_prefix;

  $order_clause = _ft_get_option_list_order_clause($order);

  // get the clients
  $client_query_result = mysql_query("
    SELECT list_id
    FROM   {$g_table_prefix}option_lists
    $order_clause
           ");

  $sorted_list_ids = array();
  while ($row = mysql_fetch_assoc($client_query_result))
  {
    $sorted_list_ids[] = $row["list_id"];
  }
  $current_index = array_search($list_id, $sorted_list_ids);

  $return_info = array("prev_option_list_id" => "", "next_option_list_id" => "");
  if ($current_index === 0)
  {
    if (count($sorted_list_ids) > 1)
    {
      $return_info["next_option_list_id"] = $sorted_list_ids[$current_index+1];
    }
  }
  else if ($current_index === count($sorted_list_ids)-1)
  {
    if (count($sorted_list_ids) > 1)
      $return_info["prev_option_list_id"] = $sorted_list_ids[$current_index-1];
  }
  else
  {
    $return_info["prev_option_list_id"] = $sorted_list_ids[$current_index-1];
    $return_info["next_option_list_id"] = $sorted_list_ids[$current_index+1];
  }

  return $return_info;
}


/**
 * Used in a couple of places, so I moved it here.
 *
 * @param string $order
 */
function _ft_get_option_list_order_clause($order)
{
  switch ($order)
  {
    case "list_id-DESC":
      $order_clause = "list_id DESC";
      break;
    case "list_id-ASC":
      $order_clause = "list_id ASC";
      break;
    case "option_list_name-ASC":
      $order_clause = "option_list_name ASC";
      break;
    case "option_list_name-DESC":
      $order_clause = "option_list_name DESC";
      break;

    default:
      $order_clause = "option_list_name ASC";
      break;
  }

  return "ORDER BY $order_clause";
}