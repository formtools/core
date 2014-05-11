<?php

/**
 * This file contains all functions relating to managing form fields in Form Tools. Originally
 * this code was located in forms.php, but due to the size of the file, it's been refactored into this
 * separate file.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Fields
 */


// -------------------------------------------------------------------------------------------------


/**
 * Adds new form field(s) to the database. This was totally re-written in 2.1.0, for the new Edit Fields
 * page.
 *
 * @param integer $infohash a hash containing the contents of the Edit Form Advanced -> Add Fields page.
 * @param integer $form_id The unique form ID
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_add_form_fields($form_id, $fields)
{
  global $g_debug, $g_table_prefix, $LANG, $g_field_sizes;

  $success = true;
  $message = "";
  $fields = ft_sanitize($fields);

  foreach ($fields as $field_info)
  {
    $field_name    = $field_info["form_field_name"];
    $field_size    = $field_info["field_size"];
    $field_type_id = $field_info["field_type_id"];
    $display_name  = $field_info["display_name"];
    $include_on_redirect = $field_info["include_on_redirect"];
    $list_order = $field_info["list_order"];
    $col_name   = $field_info["col_name"];
    $is_new_sort_group = $field_info["is_new_sort_group"];

    // in order for the field to be added, it needs to have the label, name, size and column name. Otherwise they're
    // ignored
    if (empty($display_name) || empty($field_name) || empty($field_size) || empty($col_name))
      continue;

    // add the new field to form_fields
    $query = "
      INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_size, field_type_id,
        data_type, field_title, col_name, list_order, is_new_sort_group, include_on_redirect)
      VALUES ($form_id, '$field_name', '$field_size', $field_type_id,
        'string', '$display_name', '$col_name', $list_order, '$is_new_sort_group', '$include_on_redirect')
    ";

    $result = mysql_query($query)
      or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

    $new_field_id = mysql_insert_id();

    $new_field_size = $g_field_sizes[$field_size]["sql"];
    list($is_success, $err_message) = _ft_add_table_column("{$g_table_prefix}form_{$form_id}", $col_name, $new_field_size);

    // if the alter table didn't work, return with an error message and remove the entry we just added to the form_fields table
    if (!$is_success)
    {
    	if (!empty($new_field_id) && is_numeric($new_field_id))
    	{
        mysql_query("
          DELETE FROM {$g_table_prefix}form_fields
          WHERE field_id = $new_field_id
          LIMIT 1
        ");
    	}
      $success = false;
      $replacement_info = array("fieldname" => $field_name);
      $message = ft_eval_smarty_string($LANG["notify_form_field_not_added"], $replacement_info);
      if ($g_debug) $message .= " <i>\"$err_message\"</i>";
      return array($success, $message);
    }
  }

  extract(ft_process_hook_calls("end", compact("infohash", "form_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Deletes unwanted form fields. Called by administrator when creating an external form and when
 * editing a form.
 *
 * Note: field types that require additional functionality when deleting a field type (e.g.
 * file fields which need to delete uploaded files), they need to define the appropriate hook.
 * Generally this means the "delete_fields" hook in the ft_update_form_fields_tab() function.
 *
 * @param integer $form_id
 * @param array an array of field IDs to delete
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_delete_form_fields($form_id, $field_ids)
{
  global $g_table_prefix, $LANG;

  // default return values
  $success = true;
  $message = "";

  // find out if the form exists and is complete
  $form_info = ft_get_form($form_id);
  $form_table_exists = ($form_info["is_complete"] == "yes") ? true : false;

  // stores the Views IDs of any View that is affected by deleting one of the form field, regardless of the field or form
  $affected_views = array();
  $removed_field_ids = array();

  $deleted_field_info = array();
  foreach ($field_ids as $field_id)
  {
    $field_id = trim($field_id);
    if (empty($field_id))
      continue;

    // ignore brand new fields - nothing to delete!
    if (preg_match("/^NEW/", $field_id))
      continue;

    $old_field_info = ft_get_form_field($field_id);
    $deleted_field_info[] = $old_field_info;

    @mysql_query("DELETE FROM {$g_table_prefix}form_fields WHERE field_id = $field_id");
    if (!$form_table_exists)
      continue;

    mysql_query("DELETE FROM {$g_table_prefix}new_view_submission_defaults WHERE field_id = $field_id");

    // see if this field had been flagged as an email field (either as the email field, first or last name).
    // if it's the email field, delete the whole row. If it's either the first or last name, just empty the value
    $query = mysql_query("SELECT form_email_id FROM {$g_table_prefix}form_email_fields WHERE email_field_id = $field_id");
    while ($row = mysql_fetch_assoc($query))
    {
      ft_unset_field_as_email_field($row["email_form_id"]);
    }
    mysql_query("UPDATE {$g_table_prefix}form_email_fields SET first_name_field_id = '' WHERE first_name_field_id = $field_id");
    mysql_query("UPDATE {$g_table_prefix}form_email_fields SET last_name_field_id = '' WHERE last_name_field_id = $field_id");

    // get a list of any Views that referenced this form field
    $view_query = mysql_query("SELECT view_id FROM {$g_table_prefix}view_fields WHERE field_id = $field_id");
    while ($row = mysql_fetch_assoc($view_query))
    {
      $affected_views[] = $row["view_id"];
      ft_delete_view_field($row["view_id"], $field_id);
    }

    $drop_column = $old_field_info["col_name"];
    mysql_query("ALTER TABLE {$g_table_prefix}form_$form_id DROP $drop_column");

    // if any Views had this field as the default sort order, reset them to having the submission_date
    // field as the default sort order
    mysql_query("
      UPDATE {$g_table_prefix}views
      SET     default_sort_field = 'submission_date',
              default_sort_field_order = 'desc'
      WHERE   default_sort_field = '$drop_column' AND
              form_id = $form_id
                ");

    $removed_field_ids[] = $field_id;
  }

  // update the list_order of this form's fields
  if ($form_table_exists)
    ft_auto_update_form_field_order($form_id);

  // update the order of any Views that referenced this field
  foreach ($affected_views as $view_id)
    ft_auto_update_view_field_order($view_id);

  // determine the return message
  if (count($removed_field_ids) > 1)
    $message = $LANG["notify_form_fields_removed"];
  else
    $message = $LANG["notify_form_field_removed"];

  extract(ft_process_hook_calls("end", compact("deleted_field_info", "form_id", "field_ids", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Helper function to return a field's database column name, based on its form field name.
 *
 * @param integer $form_id
 * @param string $field_name_or_names this can be a single field name or an array of field names
 * @return string the database column name, empty string if not found or an array of database column
 *     names if the $field_name_or_names was an array of field names
 */
function ft_get_field_col_by_field_name($form_id, $field_name_or_names)
{
  global $g_table_prefix;

  $form_id             = ft_sanitize($form_id);
  $field_name_or_names = ft_sanitize($field_name_or_names);

  $return_info = "";

  if (is_array($field_name_or_names))
  {
    $return_info = array();
    foreach ($field_name_or_names as $field_name)
    {
      $query = mysql_query("
        SELECT col_name
        FROM   {$g_table_prefix}form_fields
        WHERE  form_id = $form_id AND
               field_name = '$field_name'
        ");
      $result = mysql_fetch_assoc($query);
      $return_info[] = (isset($result["col_name"])) ? $result["col_name"] : "";
    }
  }
  else
  {
    $query = mysql_query("
      SELECT col_name
      FROM   {$g_table_prefix}form_fields
      WHERE  form_id = $form_id AND
             field_name = '$field_name'
      ");
    $result = mysql_fetch_assoc($query);

    $return_info = (isset($result["col_name"])) ? $result["col_name"] : "";
  }

  return $return_info;
}


/**
 * Another getter function. This one finds out the column name for a field or fields,
 * based on their field IDs.
 *
 * Bah! This should return a single bloody col_name string when passed a single field_id. Refactor!
 *
 * @param integer $form_id
 * @param mixed $field_id_or_ids integer or array of integers (field IDs)
 * @return array a hash of field_ids to col_names (only one key-value paid if single field ID passed)
 */
function ft_get_field_col_by_field_id($form_id, $field_id_or_ids)
{
  global $g_table_prefix;

  $form_id         = ft_sanitize($form_id);
  $field_id_or_ids = ft_sanitize($field_id_or_ids);

  $field_id_str = "";
  if (is_array($field_id_or_ids))
    $field_id_str = implode(",", $field_id_or_ids);
  else
    $field_id_str = $field_id_or_ids;

  $query = mysql_query("
    SELECT field_id, col_name
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id AND
           field_id IN ($field_id_str)
  ");

  $return_info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $return_info[$row["field_id"]] = $row["col_name"];
  }

  return $return_info;
}


/**
 * Returns the field title by the field ID.
 *
 * @param integer $field_id
 * @return string the field title
 */
function ft_get_field_title_by_field_id($field_id)
{
  global $g_table_prefix;

  $return_info = "";
  $query = mysql_query("
    SELECT field_title
    FROM   {$g_table_prefix}form_fields
    WHERE  field_id = '$field_id'
    ");
  $result = mysql_fetch_assoc($query);
  $return_info = (isset($result["field_title"])) ? $result["field_title"] : "";

  return $return_info;
}


/**
 * Returns the field type ID by the field ID.
 *
 * @param integer $field_id
 * @return integer the field ID
 */
function ft_get_field_type_id_by_field_id($field_id)
{
  global $g_table_prefix;

  $field_type_id = "";
  $query = mysql_query("
    SELECT field_type_id
    FROM   {$g_table_prefix}form_fields
    WHERE  field_id = '$field_id'
    ");
  $result = mysql_fetch_assoc($query);
  $field_type_id = (isset($result["field_type_id"])) ? $result["field_type_id"] : "";

  return $field_type_id;
}


/**
 * Returns the field title by the field database column string.
 *
 * @param integer $form_id
 * @param string $col_name
 * @return string
 */
function ft_get_field_title_by_field_col($form_id, $col_name)
{
  global $g_table_prefix;

  $form_id  = ft_sanitize($form_id);
  $col_name = ft_sanitize($col_name);

  $query = mysql_query("
    SELECT field_title
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id AND
           col_name = '$col_name'
    ");
  $result = mysql_fetch_assoc($query);

  $return_info = (isset($result["field_title"])) ? $result["field_title"] : "";
  return $return_info;
}


/**
 * Returns all the field options for a particular multi-select field.
 *
 * @param integer $form_id the unique field ID.
 * @return array an array of field hashes
 */
function ft_get_field_options($field_id)
{
  global $g_table_prefix;

  // get the field option group ID
  $query = mysql_query("
    SELECT field_group_id
    FROM   {$g_table_prefix}form_fields
    WHERE  field_id = $field_id
      ");
  $result = mysql_fetch_assoc($query);
  $group_id = $result["field_group_id"];

  if (!$group_id)
    return array();

  $option_query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_options
    WHERE  field_group_id = $group_id
      ");

  $options = array();
  while ($row = mysql_fetch_assoc($option_query))
    $options[] = $row;

  extract(ft_process_hook_calls("end", compact("field_id", "options"), array("options")), EXTR_OVERWRITE);

  return $options;
}


/**
 * Retrieves all information about a specific form template field.
 *
 * @param integer $field_id the unique field ID
 * @return array A hash of information about this field.
 */
function ft_get_form_field($field_id, $custom_params = array())
{
  global $g_table_prefix;

  $params = array(
    "include_field_type_info"   => (isset($custom_params["include_field_type_info"])) ? $custom_params["include_field_type_info"] : false,
    "include_field_settings"    => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false,
    "evaluate_dynamic_settings" => (isset($custom_params["evaluate_dynamic_settings"])) ? $custom_params["evaluate_dynamic_settings"] : false
  );

  if ($params["include_field_type_info"])
  {
    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}form_fields ff, {$g_table_prefix}field_types ft
      WHERE  ff.field_id = $field_id AND
             ff.field_type_id = ft.field_type_id
    ");
  }
  else
  {
    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}form_fields
      WHERE  field_id = $field_id
    ");
  }
  $info = mysql_fetch_assoc($query);

  if ($params["include_field_settings"])
  {
    $info["settings"] = ft_get_form_field_settings($field_id, $params["evaluate_dynamic_settings"]);
  }

  extract(ft_process_hook_calls("end", compact("field_id", "info"), array("info")), EXTR_OVERWRITE);

  return $info;
}


/**
 * A getter function to retrieve everything about a form field from the database column name. This
 * is just a wrapper for ft_get_form_field().
 *
 * @param integer $form_id
 * @param string $col_name
 * @param array
 */
function ft_get_form_field_by_colname($form_id, $col_name, $params = array())
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id AND
           col_name = '$col_name'
    LIMIT 1
      ");

  $info = mysql_fetch_assoc($query);

  if (empty($info))
    return array();

  $field_id = $info["field_id"];
  return ft_get_form_field($field_id, $params);
}


/**
 * Returns the field ID.
 *
 * @param string $field_name
 * @param integer $form_id
 * @return integer $field_id
 */
function ft_get_form_field_id_by_field_name($field_name, $form_id)
{
  global $g_table_prefix;

  $form_id    = ft_sanitize($form_id);
  $field_name = ft_sanitize($field_name);

  $query = mysql_query("
    SELECT field_id
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id AND
           field_name = '$field_name'
      ");
  $result = mysql_fetch_assoc($query);

  $field_id = (isset($result["field_id"])) ? $result["field_id"] : "";
  return $field_id;
}


/**
 * Returns either a string (the field name), if a single field ID is passed, or a hash of field_id => field_names
 * if an array is passed.
 *
 * @param mixed $field_id_or_ids
 * @return mixed
 */
function ft_get_form_field_name_by_field_id($field_id_or_ids)
{
  global $g_table_prefix;

  $field_ids = array();
  if (is_array($field_id_or_ids))
    $field_ids = $field_id_or_ids;
  else
    $field_ids[] = $field_id_or_ids;

  $field_id_str = implode(",", $field_ids);

  $query = mysql_query("
    SELECT field_id, field_name
    FROM   {$g_table_prefix}form_fields
    WHERE  field_id IN ($field_id_str)
      ");

  $return_info = "";
  if (is_array($field_id_or_ids))
  {
  	$result = mysql_fetch_assoc($query);
  	$return_info = $result["field_name"];
  }
  else
  {
  	$return_info = array();
  	while ($row = mysql_fetch_assoc($query))
  	{
  		$return_info[$row["field_id"]] = $row["field_name"];
  	}
  }

  return $return_info;
}


/**
 * Retrieves all custom settings for an individual form field from the field_settings table.
 *
 * @param integer $field_id the unique field ID
 * @return array an array of hashes
 */
function ft_get_form_field_settings($field_id, $evaluate_dynamic_fields = false)
{
  global $g_table_prefix;

  if ($evaluate_dynamic_fields)
  {
    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_settings fs, {$g_table_prefix}field_type_settings fts
      WHERE  fs.setting_id = fts.setting_id AND
             field_id = $field_id
    ");
  }
  else
  {
    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_settings
      WHERE  field_id = $field_id
    ");
  }

  $settings = array();
  while ($row = mysql_fetch_assoc($query))
  {
    if ($evaluate_dynamic_fields && $row["default_value_type"] == "dynamic")
    {
      $settings[$row["setting_id"]] = "";
      $parts = explode(",", $row["setting_value"]);
      if (count($parts) == 2)
      {
        $settings[$row["setting_id"]] = ft_get_settings($parts[0], $parts[1]);
      }
    }
    else
    {
      $settings[$row["setting_id"]] = $row["setting_value"];
    }
  }

  extract(ft_process_hook_calls("end", compact("field_id", "settings"), array("settings")), EXTR_OVERWRITE);

  return $settings;
}


/**
 * Retrieves all field information about a form, ordered by list_order. The 2nd and 3rd optional
 * parameters let you return a subset of the fields for a particular page. This function is purely
 * concerned with the raw fields themselves: not how they are arbitrarily grouped in a View. To
 * retrieve the grouped fields list for a View, use ft_get_view_fields().
 *
 * @param integer $form_id the unique form ID
 * @param array $custom_settings optional settings
 * @return array an array of hash information
 */
function ft_get_form_fields($form_id, $custom_params = array())
{
  global $g_table_prefix;

  $params = array(
    "page"                      => (isset($custom_params["page"])) ? $custom_params["page"] : 1,
    "num_fields_per_page"       => (isset($custom_params["num_fields_per_page"])) ? $custom_params["num_fields_per_page"] : "all",
    "include_field_type_info"   => (isset($custom_params["include_field_type_info"])) ? $custom_params["include_field_type_info"] : false,
    "include_field_settings"    => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false,
    "evaluate_dynamic_settings" => (isset($custom_params["evaluate_dynamic_settings"])) ? $custom_params["evaluate_dynamic_settings"] : false,
    "field_ids"                 => (isset($custom_params["field_ids"])) ? $custom_params["field_ids"] : "all"
  );

  $limit_clause = _ft_get_limit_clause($params["page"], $params["num_fields_per_page"]);

  if ($params["include_field_type_info"])
  {
    $query = mysql_query("
      SELECT ff.*, ft.field_type_name, ft.is_file_field, ft.is_date_field
      FROM   {$g_table_prefix}form_fields ff, {$g_table_prefix}field_types ft
      WHERE  ff.form_id = $form_id AND
             ff.field_type_id = ft.field_type_id
      ORDER BY ff.list_order
      $limit_clause
    ");
  }
  else
  {
  	$field_id_clause = "";
  	if ($params["field_ids"] != "all")
  	{
  		$field_id_clause = "AND field_id IN (" . implode(",", $params["field_ids"]) . ")";
  	}

    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}form_fields
      WHERE  form_id = $form_id
             $field_id_clause
      ORDER BY list_order
      $limit_clause
    ");
  }

  $infohash = array();
  while ($row = mysql_fetch_assoc($query))
  {
    if ($params["include_field_settings"])
    {
      $row["settings"] = ft_get_form_field_settings($row["field_id"], $params["evaluate_dynamic_settings"]);
    }
    $infohash[] = $row;
  }

  extract(ft_process_hook_calls("end", compact("form_id", "infohash"), array("infohash")), EXTR_OVERWRITE);

  return $infohash;
}


/**
 * Returns the total number of form fields in a form.
 *
 * @param integer $form_id
 */
function ft_get_num_form_fields($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT count(*) as c
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id
  ");

  $info = mysql_fetch_assoc($query);

  return $info["c"];
}


/**
 * A getter function to retrieve everything about a form field from the database column name. This is used in
 * the ft_search_submissions function.
 *
 * @param integer $form_id
 * @param string $col_name
 * @return array
 */
function ft_get_field_order_info_by_colname($form_id, $col_name)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT ff.data_type, ft.is_date_field
    FROM   {$g_table_prefix}form_fields ff, {$g_table_prefix}field_types ft
    WHERE  ff.form_id = $form_id AND
           ff.col_name = '$col_name' AND
           ff.field_type_id = ft.field_type_id
      ");

  $infohash = array();
  while ($row = mysql_fetch_assoc($query))
    $infohash = $row;

  return $infohash;
}


/**
 * This function was totally rewritten in 2.1.0 for the new field settings structure. The ft_form_fields table
 * stores all the main settings for form fields which are shared across all fields, regardless of their
 * type. But some field types have "extended" settings, i.e. settings that only relate to that field type;
 * e.g. file upload fields allow for custom file upload URL & folders. Extended settings can now be created
 * by the administrator for any form field type through the Custom Fields module.
 *
 * Inheritance
 * -----------
 * When editing a field, the user has the option of checking the "Use Default" option for each field. If that's
 * checked, it will always inherit the setting value from the "default_value" setting value, defined in the
 * Custom Fields field type setting. Database-wise, if that value is checked, nothing is stored in the database:
 * this keeps the DB size as trim as possible.
 *
 * This function always returns all extended settings for a field, even those that use the default. The format
 * is:
 *
 *   array(
 *     array(
 *       "setting_id"    => X,
 *       "setting_value" => "...",
 *       "uses_default"  => true/false
 *     ),
 *     ...
 *   );
 *
 * @param integer $field_id
 * @param string $setting_id (optional)
 * @param boolean $convert_dynamic_values defaults to false just in case...
 * @return array an array of hashes
 */
function ft_get_extended_field_settings($field_id, $setting_id = "", $convert_dynamic_values = false)
{
  // get whatever custom settings are defined for this field
  $custom_settings = ft_get_form_field_settings($field_id);

  // now get a list of all available settings for this field type
  $field_type_id = ft_get_field_type_id($field_id);
  $field_type_settings = ft_get_field_type_settings($field_type_id);
  $settings = array();
  foreach ($field_type_settings as $curr_setting)
  {
    $curr_setting_id = $curr_setting["setting_id"];
    if (!empty($setting_id) && $setting_id != $curr_setting_id)
      continue;

    $uses_default  = true;
    $setting_value_type = $curr_setting["default_value_type"];
    $setting_value      = $curr_setting["default_value"];
    if (array_key_exists($curr_setting_id, $custom_settings))
    {
      $uses_default  = false;
      $setting_value = $custom_settings[$curr_setting_id];
    }

    if ($convert_dynamic_values && $setting_value_type == "dynamic")
    {
      $parts = explode(",", $setting_value);
      if (count($parts) == 2)
        $setting_value = ft_get_settings($parts[0], $parts[1]);
    }

    $settings[] = array(
      "setting_id"    => $curr_setting_id,
      "setting_value" => $setting_value,
      "uses_default"  => $uses_default
    );
  }

  extract(ft_process_hook_calls("end", compact("field_id", "setting_name"), array("settings")), EXTR_OVERWRITE);

  return $settings;
}


/**
 * ft_get_extended_field_settings() doesn't quite do what I need, so I added this secondary function. It's
 * similar to ft_get_form_field_field_type_settings(), except for a single field.
 *
 * All it does is return all settings for a form field TAKING INTO ACCOUNT what's been overridden.
 *
 * Note: it returns the information as a hash of identifier => value pairs. This is fine, because no two field
 * settings for a single field type may have the same identifier.
 *
 * @param $field_id
 * @return array a hash of [identifier] = values
 */
function ft_get_field_settings($field_id)
{
  global $g_table_prefix;

  if (empty($field_id) || !is_numeric($field_id))
  	return array();

  // get the overridden settings
  $query = "
    SELECT fts.field_type_id, fs.field_id, fts.field_setting_identifier, fs.setting_value
    FROM   {$g_table_prefix}field_type_settings fts, {$g_table_prefix}field_settings fs
    WHERE  fts.setting_id = fs.setting_id AND
           fs.field_id = $field_id
    ORDER BY fs.field_id
      ";
  $result = mysql_query($query);

  $overridden_settings = array();
  while ($row = mysql_fetch_assoc($result))
  {
    $overridden_settings[$row["field_setting_identifier"]] = $row["setting_value"];
  }

  $field_type_id = ft_get_field_type_id_by_field_id($field_id);
  $default_field_type_settings = ft_get_field_type_settings($field_type_id);

  // now overlay the two and return all field settings for all fields
  $complete_settings = array();
  foreach ($default_field_type_settings as $setting_info)
  {
    $identifier         = $setting_info["field_setting_identifier"];
    $default_value_type = $setting_info["default_value_type"];
    if ($default_value_type == "static")
      $value = $setting_info["default_value"];
    else
    {
      $parts = explode(",", $setting_info["default_value"]);

      // dynamic setting values should ALWAYS be of the form "setting_name,module_folder/'core'". If they're
      // not, just ignore it
      if (count($parts) != 2)
        $value = "";
      else
        $value = ft_get_settings($parts[0], $parts[1]);
    }

    // if the field has been overwritten use that instead!
    if (isset($overridden_settings[$identifier]))
      $value = $overridden_settings[$identifier];

    $complete_settings[$identifier] = $value;
  }

  return $complete_settings;
}


function ft_get_field_setting($field_id, $setting_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT setting_value
    FROM   {$g_table_prefix}field_settings
    WHERE  field_id = $field_id AND
           setting_id = $setting_id
  ");

  $result = mysql_fetch_assoc($query);

  return (isset($result["setting_value"])) ? $result["setting_value"] : "";
}


/**
 * Deletes any extended field settings for a particular form field. Not thrilled about the "extended" in this
 * function name, but I wanted to emphasize that this function deletes ONLY the settings in the field_settings
 * table and not the actual values in the form_fields table.
 *
 * @param integer $field_id
 */
function ft_delete_extended_field_settings($field_id)
{
  global $g_table_prefix;

  mysql_query("DELETE FROM {$g_table_prefix}field_settings WHERE field_id = $field_id");

  extract(ft_process_hook_calls("end", compact("field_id"), array()), EXTR_OVERWRITE);
}


/**
 * Called on the Add External Form Step 4 page. It reorders the form fields and their groupings.
 *
 * @param integer $form_id
 * @param integer $infohash the POST data from the form
 * @param boolean $set_default_form_field_names if true, this tell the function to rename the columns
 */
function ft_update_form_fields($form_id, $infohash, $set_default_form_field_names = false)
{
  global $g_table_prefix, $g_debug;

  $infohash = ft_sanitize($infohash);

  $sortable_id = $infohash["sortable_id"];
  $sortable_rows       = explode(",", $infohash["{$sortable_id}_sortable__rows"]);
  $sortable_new_groups = explode(",", $infohash["{$sortable_id}_sortable__new_groups"]);

  extract(ft_process_hook_calls("start", compact("infohash", "form_id"), array("infohash")), EXTR_OVERWRITE);

  // get a list of the system fields so we don't overwrite anything special
  $existing_form_field_info = ft_get_form_fields($form_id);
  $system_field_ids = array();
  foreach ($existing_form_field_info as $form_field)
  {
    if ($form_field["is_system_field"] == "yes")
      $system_field_ids[] = $form_field["field_id"];
  }

  $order = 1;
  $custom_col_num = 1;
  foreach ($sortable_rows as $field_id)
  {
    $set_clauses = array("list_order = $order");
    if ($set_default_form_field_names && !in_array($field_id, $system_field_ids))
    {
      $set_clauses[] = "col_name = 'col_$custom_col_num'";
      $custom_col_num++;
    }

    if (isset($infohash["field_{$field_id}_display_name"]))
      $set_clauses[] = "field_title = '" . $infohash["field_{$field_id}_display_name"] . "'";

    if (isset($infohash["field_{$field_id}_size"]))
      $set_clauses[] = "field_size = '" . $infohash["field_{$field_id}_size"] . "'";

    $is_new_sort_group = (in_array($field_id, $sortable_new_groups)) ? "yes" : "no";
    $set_clauses[] = "is_new_sort_group = '$is_new_sort_group'";

    $set_clauses_str = implode(",\n", $set_clauses);

    mysql_query("
      UPDATE {$g_table_prefix}form_fields
      SET    $set_clauses_str
      WHERE  field_id = $field_id AND
             form_id = $form_id
                ");
    $order++;
  }
}


/**
 * This can be called at any junction for any form. It re-orders the form field list_orders based
 * on the current order. Basically, it's used whenever a form field is deleted to ensure that there
 * are no gaps in the list_order.
 *
 * @param integer $form_id
 */
function ft_auto_update_form_field_order($form_id)
{
  global $g_table_prefix;

  // we rely on this function returning the field by list_order
  $form_fields = ft_get_form_fields($form_id);

  $count = 1;
  foreach ($form_fields as $field_info)
  {
    $field_id = $field_info["field_id"];

    mysql_query("
      UPDATE {$g_table_prefix}form_fields
      SET    list_order = $count
      WHERE  form_id = $form_id AND
             field_id = $field_id
        ");
    $count++;
  }
}


/**
 * Adds/updates all options for a given field. This is called when the user edits fields from the dialog
 * window on the Fields tab. It updates all information about a field: including the custom settings.
 *
 * @param integer $form_id The unique form ID
 * @param integer $field_id The unique field ID
 * @param integer $info a hash containing tab1 and/or tab2 indexes, containing all the latest values for
 *                the field
 * @param array [0] success/fail (boolean), [1] empty string for success, or error message
 */
function ft_update_field($form_id, $field_id, $tab_info)
{
  global $g_table_prefix, $g_field_sizes, $g_debug, $LANG;

  $tab_info = ft_sanitize($tab_info);
  $existing_form_field_info = ft_get_form_field($field_id);

  // TAB 1: this tab contains the standard settings shared by all fields, regardless of type: display text,
  // form field name, field type, pass on, field size, data type and database col name
  $db_col_name_changes = array();
  if (is_array($tab_info["tab1"]))
  {
    $info = $tab_info["tab1"];
    $display_name = _ft_extract_array_val($info, "edit_field__display_text");

    // bit weird. this field is a checkbox, so if it's not checked it won't be in the request and
    // _ft_extract_array_val returns an empty string
    $include_on_redirect = _ft_extract_array_val($info, "edit_field__pass_on");
    $include_on_redirect = (empty($include_on_redirect)) ? "no" : "yes";

    if ($existing_form_field_info["is_system_field"] == "yes")
    {
      $query = "
        UPDATE {$g_table_prefix}form_fields
        SET    field_title = '$display_name',
               include_on_redirect = '$include_on_redirect'
        WHERE  field_id = $field_id
      ";
      $result = mysql_query($query);
      if (!$result)
      {
        return array(false, $LANG["phrase_query_problem"] . $query);
      }
    }
    else
    {
      $field_name    = _ft_extract_array_val($info, "edit_field__field_name");
      $field_type_id = _ft_extract_array_val($info, "edit_field__field_type");
      $field_size    = _ft_extract_array_val($info, "edit_field__field_size");
      $data_type     = _ft_extract_array_val($info, "edit_field__data_type");
      $col_name      = _ft_extract_array_val($info, "edit_field__db_column");

      $query = mysql_query("
        UPDATE {$g_table_prefix}form_fields
        SET    field_name = '$field_name',
               field_type_id = '$field_type_id',
               field_size = '$field_size',
               field_title = '$display_name',
               include_on_redirect = '$include_on_redirect',
               col_name = '$col_name'
        WHERE  field_id = $field_id
          ");

      // if the column name or field size just changed, we need to "physically" update the form's database table
      // If this fails, we rollback both the field TYPE and the field size.
      // BUG The *one* potential issue here is if the user just deleted a field type, then updated a field which - for
      // whatever reason - fails. But this is very much a fringe case
      $old_field_size    = $existing_form_field_info["field_size"];
      $old_col_name      = $existing_form_field_info["col_name"];
      $old_field_type_id = $existing_form_field_info["field_type_id"];
      if ($old_field_size != $field_size || $old_col_name != $col_name)
      {
        $new_field_size_sql = $g_field_sizes[$field_size]["sql"];
        $table_name = "{$g_table_prefix}form_{$form_id}";

        list($is_success, $err_message) = _ft_alter_table_column($table_name, $old_col_name, $col_name, $new_field_size_sql);
        if ($is_success)
        {
          if ($old_col_name != $col_name)
            $db_col_name_changes[] = $field_id;
        }
        else
        {
          $query = mysql_query("
            UPDATE {$g_table_prefix}form_fields
            SET    field_type_id = '$old_field_type_id',
                   field_size    = '$old_field_size',
                   col_name      = '$old_col_name'
            WHERE  field_id = $field_id
              ");
          return array(false, $LANG["phrase_query_problem"] . $err_message);
        }
      }

      // if the field type just changed, the field-specific settings are orphaned. Drop them. In this instance, the
      // client-side code ensures that the contents of the second tab are always passed so the code below will add
      // any default values that are needed
      if ($old_field_type_id != $field_type_id)
      {
        ft_delete_extended_field_settings($field_id);
      }
    }
  }

  // if any of the database column names just changed we need to update any View filters that relied on them
  if (!empty($db_col_name_changes))
  {
    foreach ($db_col_name_changes as $field_id)
    {
      ft_update_field_filters($field_id);
    }
  }

  // TAB 2: update the custom field settings for this field type. tab2 can be any of these values:
  //  1. a string "null": indicating that the user didn't change anything on the tab)
  //  2. the empty string: indicating that things DID change, but nothing is being passed on. This can happen
  //                      when the user checked the "Use Default Value" for all fields on the tab & the tab
  //                      doesn't contain an option list or form field
  //  3. an array of values
  if (isset($tab_info["tab2"]) && $tab_info["tab2"] != "null")
  {
    $info = is_array($tab_info["tab2"]) ? $tab_info["tab2"] : array();

    // since the second tab is being updated, we can rely on all the latest & greatest values being passed
    // in the request, so clean out all old values
    ft_delete_extended_field_settings($field_id);

    // convert the $info (which is an array of hashes) into a friendlier hash. This makes detecting for Option
    // List fields much easier
    $setting_hash = array();
    for ($i=0; $i<count($info); $i++)
    {
      $setting_hash[$info[$i]["name"]] = $info[$i]["value"];
    }

    $new_settings = array();
    while (list($setting_name, $setting_value) = each($setting_hash))
    {
      // ignore the additional field ID and field order rows that are custom to Option List / Form Field types. They'll
      // be handled below
      if (preg_match("/edit_field__setting_(\d)+_field_id/", $setting_name) || preg_match("/edit_field__setting_(\d)+_field_order/", $setting_name))
        continue;

      // TODO BUG. newlines aren't surviving this... why was it added? double quotes? single quotes?
      $setting_value = ft_sanitize(stripslashes($setting_value));
      $setting_id    = preg_replace("/edit_field__setting_/", "", $setting_name);

      // if this field is being mapped to a form field, we serialize the form ID, field ID and order into a single var and
      // give it a "form_field:" prefix, so we know exactly what the data contains & we can select the appropriate form ID
      // and not Option List ID on re-editing. This keeps everything pretty simple, rather than spreading the data amongst
      // multiple fields
      if (preg_match("/^ft/", $setting_value))
      {
        $setting_value = preg_replace("/^ft/", "", $setting_value);
        $setting_value = "form_field:$setting_value|" . $setting_hash["edit_field__setting_{$setting_id}_field_id"] . "|"
          . $setting_hash["edit_field__setting_{$setting_id}_field_order"];
      }

      $new_settings[] = "($field_id, $setting_id, '$setting_value')";
    }

    if (!empty($new_settings))
    {
      $new_settings_str = implode(",", $new_settings);
      $query = "
        INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
        VALUES $new_settings_str
      ";

      $result = @mysql_query($query) or die($query . " - " . mysql_error());
      if (!$result)
      {
        return array(false, $LANG["phrase_query_problem"] . $query . ", " . mysql_error());
      }
    }
  }

  if (isset($tab_info["tab3"]) && $tab_info["tab3"] != "null")
  {
    $validation = is_array($tab_info["tab3"]) ? $tab_info["tab3"] : array();
    mysql_query("DELETE FROM {$g_table_prefix}field_validation WHERE field_id = $field_id");
    $new_rules = array();
    foreach ($validation as $rule_info)
    {
    	// ignore the checkboxes - we don't need 'em
    	if (!preg_match("/^edit_field__v_(.*)_message$/", $rule_info["name"], $matches))
    	  continue;

      $rule_id = $matches[1];
      $error_message = ft_sanitize($rule_info["value"]);

      mysql_query("
        INSERT INTO {$g_table_prefix}field_validation (rule_id, field_id, error_message)
        VALUES ($rule_id, $field_id, '$error_message')
      ");
    }
  }

  $success = true;
  $message = $LANG["notify_form_field_options_updated"];
  extract(ft_process_hook_calls("end", compact("field_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Returns all files associated with a particular form field or fields. Different field types may store the
 * files differently, so EVERY file upload module needs to add a hook to this function to return the
 * appropriate information.
 *
 * The module functions should return an array of hashes with the following structure:
 *    array(
 *      "submission_id" =>
 *      "field_id"      =>
 *      "field_type_id" =>
 *      "folder_path"   =>
 *      "folder_url"    =>
 *      "filename"      =>
 *    ),
 *    ...
 *
 * @param integer $form_id the unique form ID
 * @param array $field_ids an array of field IDs
 */
function ft_get_uploaded_files($form_id, $field_ids)
{
  $uploaded_files = array();
  extract(ft_process_hook_calls("start", compact("form_id", "field_ids"), array("uploaded_files")), EXTR_OVERWRITE);
  return $uploaded_files;
}


/**
 * This update any filter SQL for a single field ID. This is called whenever the administrator changes
 * one or more database column names (e.g. using the "Smart Fill" option). It ensures data integrity
 * for the View filters.
 *
 * @param integer $field_id
 * @param array $info
 */
function ft_update_field_filters($field_id)
{
  global $g_table_prefix, $g_debug, $LANG;

  // get any filters that are associated with this field
  $affected_filters = mysql_query("SELECT * FROM {$g_table_prefix}view_filters WHERE field_id = $field_id");

  // get form field
  $field_info = ft_get_form_field($field_id, array("include_field_type_info" => true));
  $col_name      = $field_info["col_name"];
  $field_type_id = $field_info["field_type_id"];

  // loop through all of the affected filters & update the SQL
  while ($filter_info = mysql_fetch_assoc($affected_filters))
  {
    $filter_id     = $filter_info["filter_id"];
    $filter_values = $filter_info["filter_values"];
    $operator      = $filter_info["operator"];

    if ($field_info["is_date_field"] == "yes")
    {
      $sql_operator = ($operator == "after") ? ">" : "<";
      $sql = "$col_name $sql_operator '$filter_values'";
    }
    else
    {
      $sql_operator = "";
      switch ($operator)
      {
        case "equals":     $sql_operator = "LIKE ";    $join = " OR ";  break;
        case "not_equals": $sql_operator = "NOT LIKE"; $join = " AND "; break;
        case "like":       $sql_operator = "LIKE";     $join = " OR ";  break;
        case "not_like":   $sql_operator = "NOT LIKE"; $join = " AND "; break;
      }
      $sql_statements_arr = array();
      $values_arr = explode("|", $filter_values);

      foreach ($values_arr as $value)
      {
        // if this is a LIKE operator (not_like, like), wrap the value in %..%
        if ($operator == "like" || $operator == "not_like")
          $value = "%$value%";
        $sql_statements_arr[] = "$col_name $sql_operator '$value'";
      }

      $sql = join($join, $sql_statements_arr);
    }
    $sql = "(" . addslashes($sql) . ")";

    $query = mysql_query("
      UPDATE {$g_table_prefix}view_filters
      SET    filter_sql = '$sql'
      WHERE  filter_id = $filter_id
        ");
  }
}


/**
 * This is called when the user updates the field type on the Edit Field Options page. It deletes all old
 * now-irrelevant settings, but retains values that will not change based on field type.
 *
 * @param integer $form_id
 * @param integer $field_id
 * @param string $new_field_type
 */
function ft_change_field_type($form_id, $field_id, $new_field_type)
{
  global $g_table_prefix;

  $field_info = ft_get_form_field($field_id);

  // if the field just changes from one multi-select field to another (radio, checkboxes, select or multi-select)
  // don't delete the field_option group: it's probable that they just wanted to switch the appearance.
  $old_field_type = $field_info["field_type"];
  $multi_select_types = array("select", "multi-select", "radio-buttons", "checkboxes");

  $clauses = array("field_type = '$new_field_type'");
  if (!in_array($old_field_type, $multi_select_types) || !in_array($new_field_type, $multi_select_types))
    $clauses[] = "field_group_id = NULL";
  if ($new_field_type == "file")
    $clauses[] = "field_size = 'medium'";

  $clauses_str = implode(",", $clauses);

  mysql_query("DELETE FROM {$g_table_prefix}field_settings WHERE field_id = $field_id");
  mysql_query("
    UPDATE {$g_table_prefix}form_fields
    SET    $clauses_str
    WHERE  field_id = $field_id
      ") or die(mysql_error());

  // if the user just changed to a file type, ALWAYS set the database field size to "medium"
  if ($old_field_type != $new_field_type && $new_field_type == "file")
  {
    _ft_alter_table_column("{$g_table_prefix}form_{$form_id}", $field_info["col_name"], $field_info["col_name"], "VARCHAR(255)");
  }
}

