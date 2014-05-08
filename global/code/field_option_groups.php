<?php

/**
 * This file contains all functions relating to managing the field option groups.
 *
 * @copyright Encore Web Studios 2010
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-1
 * @subpackage FieldOptionGroups
 */


// -------------------------------------------------------------------------------------------------


/**
 * Returns all field option groups in the database.
 *
 * @param $page_num the current page number, or "all" for all results.
 * @return array ["results"] an array of option group information
 *               ["num_results"] the total number of option groups in the database.
 */
function ft_get_field_option_groups($page_num = 1)
{
  global $g_table_prefix;

  if ($page_num == "all")
    $limit_clause = "";
  else
  {
    $num_groups_per_page = $_SESSION["ft"]["settings"]["num_field_option_groups_per_page"];

    // determine the LIMIT clause
    $limit_clause = "";
    if (empty($page_num))
      $page_num = 1;
    $first_item = ($page_num - 1) * $num_groups_per_page;
    $limit_clause = "LIMIT $first_item, $num_groups_per_page";
  }

  $result = mysql_query("
    SELECT *
    FROM 	 {$g_table_prefix}field_option_groups
    ORDER BY group_name
     $limit_clause
      ");
   $count_result = mysql_query("
    SELECT count(*) as c
    FROM 	 {$g_table_prefix}field_option_groups
    ORDER BY group_name
         ");
   $count_hash = mysql_fetch_assoc($count_result);

  $groups = array();
  while ($row = mysql_fetch_assoc($result))
    $groups[] = $row;

  $return_hash = array();
  $return_hash["results"] = $groups;
  $return_hash["num_results"]  = $count_hash["c"];

  extract(ft_process_hooks("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

  return $return_hash;
}


/**
 * Returns all options for a particular field option group. If you want to get ALL information
 * about the group, use ft_get_field_option_group
 *
 * @param integer $group_id the field group ID
 * @return array
 */
function ft_get_field_group_options($group_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_options
    WHERE  field_group_id = $group_id
    ORDER BY option_order
      ");

  $options = array();
  while ($row = mysql_fetch_assoc($query))
    $options[] = $row;

  return $options;
}


/**
 * Returns all info about a field option group.
 *
 * @param integer $group_id
 */
function ft_get_field_option_group($group_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_option_groups
    WHERE  group_id = $group_id
      ");

  $info = mysql_fetch_assoc($query);
  $info["options"] = ft_get_field_group_options($group_id);

  return $info;
}


/**
 * This function is called whenever the user adds or modifies a field option group. It checks all existing
 * field option groups to see if an identical set already exists. If it does, it returns the existing
 * field option group ID or creates a new group and returns that ID.
 *
 * @param integer $form_id
 * @param array $option_group_info
 * @return integer $group_id the new or existing field group ID
 */
function ft_create_unique_option_group($form_id, $option_group_info)
{
  global $g_table_prefix;

  $field_groups = ft_get_field_option_groups("all");
  $orientation = isset($option_group_info["orientation"]) ? $option_group_info["orientation"] : "na";

  $already_exists = false;
  $group_id = "";
  foreach ($field_groups["results"] as $field_group)
  {
    $curr_group_id = $field_group["group_id"];

    // when comparing field groups - just compare the orientation and the actual field options. The
    // group name & original form are immaterial. This may lead to a little head-shaking in the UI when
    // they see an inappropriate group name, but it's easily changed
    if ($field_group["field_orientation"] != $orientation)
      continue;

    $curr_group_options = ft_get_field_group_options($curr_group_id);

    if (count($curr_group_options) != count($option_group_info["options"]))
      continue;

    $has_same_option_fields = true;
    for ($i=0; $i<count($curr_group_options); $i++)
    {
      $val = ft_sanitize($curr_group_options[$i]["option_value"]);
      $txt = ft_sanitize($curr_group_options[$i]["option_name"]);

      $val2 = $option_group_info["options"][$i]["value"];
      $txt2 = $option_group_info["options"][$i]["text"];

      if ($val != $val2 || $txt != $txt2)
      {
        $has_same_option_fields = false;
        break;
      }
    }

    if (!$has_same_option_fields)
      continue;

    $already_exists = true;
    $group_id = $curr_group_id;
    break;
  }

  // if this group didn't already exist, add it!
  if (!$already_exists)
  {
    $group_name  = $option_group_info["group_name"];

    $query = "INSERT INTO {$g_table_prefix}field_option_groups (group_name, original_form_id, field_orientation)
      VALUES ('$group_name', $form_id, '$orientation')";
		$result = mysql_query($query) or ft_handle_error($query, mysql_error());

    if (!$result)
      return false;

    $group_id = mysql_insert_id();


    // add the options
    $order = 1;
    foreach ($option_group_info["options"] as $option)
    {
      $value = ft_sanitize($option["value"]);
      $text  = ft_sanitize($option["text"]);

      $query = "
        INSERT INTO {$g_table_prefix}field_options (field_group_id, option_value, option_name, option_order)
        VALUES ($group_id, '$value', '$text', $order)
          ";
  		$result = mysql_query($query) or ft_handle_error($query, mysql_error());

      $order++;
    }
  }

  return $group_id;
}


/**
 * Returns the number of fields that use a particular field option group.
 *
 * @param integer $group_id
 * @return integer the number of fields
 */
function ft_get_num_fields_using_field_option_group($group_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT count(*) as c
    FROM   {$g_table_prefix}form_fields
    WHERE  field_group_id = $group_id
      ");
  $result = mysql_fetch_assoc($query);

  return $result["c"];
}


/**
 * Returns information about fields that use a particular field  option group.
 *
 * @param integer $group_id
 * @return array
 */
function ft_get_fields_using_field_option_group($group_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT f.*, ff.*
    FROM   {$g_table_prefix}form_fields ff, {$g_table_prefix}forms f
    WHERE  ff.field_group_id = $group_id AND
           ff.form_id = f.form_id
    ORDER BY f.form_name, ff.field_title
      ");
  $results = array();
  while ($row = mysql_fetch_assoc($query))
    $results[] = $row;

  return $results;
}


/**
 * Updates a single field option group.
 *
 * @param integer $group_id
 * @param array $info
 */
function ft_update_field_option_group($group_id, $info)
{
  global $g_table_prefix, $LANG;

  $info = ft_sanitize($info);

  $group_name = $info["group_name"];
  $field_orientation = isset($info["field_orientation"]) ? $info["field_orientation"] : "na";

  $query = mysql_query("
    UPDATE {$g_table_prefix}field_option_groups
    SET    group_name = '$group_name',
           field_orientation = '$field_orientation'
    WHERE  group_id = $group_id
    ");

  // update the old field options
  mysql_query("DELETE FROM {$g_table_prefix}field_options WHERE field_group_id = $group_id") or die(mysql_error());
  $num_rows = (isset($info["num_rows"])) ? $info["num_rows"] : 0;
  $order = 1;
  for ($i=1; $i<=$num_rows; $i++)
  {
    if (!isset($info["field_option_value_{$i}"]))
      continue;

    $value = $info["field_option_value_{$i}"];
    $text  = $info["field_option_text_{$i}"];

    mysql_query("
      INSERT INTO {$g_table_prefix}field_options (field_group_id, option_order, option_value, option_name)
      VALUES ($group_id, $order, '$value', '$text')
        ");
    $order++;
  }

  $success = true;
  $message = $LANG["notify_field_option_group_updated"];
  extract(ft_process_hooks("end", compact("group_id", "info"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Creates an identical copy of an existing field option group. This can be handy if the user was using a
 * single group for multiple fields, but one of the form fields changed. They can just create a new copy,
 * tweak it and re-assign the field.
 *
 * If no group ID is passed, it creates a new blank field option group (sorry for the crappy function
 * name).
 *
 * @param integer $group_id
 * @param integer $field_id if this parameter is set, the new field option group will be assigned to
 *    whatever field IDs are specified
 * @return mixed the group ID if successful, false if not
 */
function ft_duplicate_field_option_group($group_id = "", $field_ids = array())
{
  global $g_table_prefix, $LANG;

  // to ensure that all new field option groups have unique names, query the database and find
  // the next free group name of the form "New Group (X)" (where "New Group" is in the language
  // of the current user)
  $groups = ft_get_field_option_groups("all");
  $group_names = array();
  foreach ($groups["results"] as $group_info)
    $group_names[] = $group_info["group_name"];

  $base_new_group_name = $LANG["phrase_new_group"];
  $new_group_name = $base_new_group_name;

  if (in_array($new_group_name, $group_names))
  {
    $count = 1;
    $new_group_name = "$base_new_group_name ($count)";

    while (in_array($new_group_name, $group_names))
    {
      $count++;
      $new_group_name = "$base_new_group_name ($count)";
    }
  }


  if (empty($group_id))
  {
    $query = mysql_query("
      INSERT INTO {$g_table_prefix}field_option_groups (group_name, field_orientation)
      VALUES ('$new_group_name', 'na')
    ");

    if (!$query)
      return false;

    $new_group_id = mysql_insert_id();
  }
  else
  {
    $group_info = ft_get_field_option_group($group_id);
    $group_info = ft_sanitize($group_info);
    $orientation = $group_info["field_orientation"];

    $query = mysql_query("
      INSERT INTO {$g_table_prefix}field_option_groups (group_name, field_orientation)
      VALUES ('$new_group_name', '$orientation')
    ");

    if (!$query)
      return false;

    $new_group_id = mysql_insert_id();

    // add the field options
    foreach ($group_info["options"] as $option_info)
    {
      $order = $option_info["option_order"];
      $value = $option_info["option_value"];
      $name  = $option_info["option_name"];

      mysql_query("
        INSERT INTO {$g_table_prefix}field_options (field_group_id, option_order, option_value, option_name)
        VALUES ($new_group_id, '$order', '$value', '$name')
          ");
    }
  }

  if (!empty($field_ids))
  {
    foreach ($field_ids as $field_id)
    {
      @mysql_query("
        UPDATE {$g_table_prefix}form_fields
        SET    field_group_id = $new_group_id
        WHERE  field_id = $field_id
          ");
    }
  }

  return $new_group_id;
}


/**
 * Deletes a field option group from the database. Note: it only deletes groups that don't have any
 * form fields assigned to it; generally this is prevented from being called unless that condition is
 * met, but it also checks here just in case.
 *
 * @param integer $group_id
 * @return array [0] T/F<br />
 *               [1] error/success message
 */
function ft_delete_field_option_group($group_id)
{
  global $g_table_prefix, $LANG;

  $num_fields = ft_get_num_fields_using_field_option_group($group_id);

  if ($num_fields > 0)
  {
    $placeholders = array("link" => "edit.php?page=form_fields&group_id=$group_id");
    $message = ft_eval_smarty_string($LANG["validation_field_option_group_has_assigned_fields"], $placeholders);
    return array(false, $message);
  }

  mysql_query("DELETE FROM {$g_table_prefix}field_options WHERE field_group_id = $group_id");
  mysql_query("DELETE FROM {$g_table_prefix}field_option_groups WHERE group_id = $group_id");

  $success = true;
  $message = $LANG["notify_field_option_group_deleted"];
  extract(ft_process_hooks("end", compact("group_id"), array("success", "message")), EXTR_OVERWRITE);

  return array(true, $message);
}

