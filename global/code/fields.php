<?php

/**
 * This file contains all functions relating to managing form fields in Form Tools. Originally
 * this code was located in forms.php, but due to the size of the file, it's been refactored into this
 * separate file.
 *
 * @copyright Encore Web Studios 2010
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-4
 * @subpackage Fields
 */


// -------------------------------------------------------------------------------------------------


/**
 * Adds new form field(s) for storage in the database. This function was updated in 1.4.6 to allow
 * for adding multiple fields in one go. It ignores all fields that don't include a form field name.
 *
 * Note: this function could use some real improvements to the data validation and error handling.
 * Currently it mostly relies on the JS validation in the form page. This isn't such a huge sin, however,
 * since the Add Fields page is written in entirely all javascript - so it's exceedingly unlikely that
 * this function will receive invalid data. Still, it should be re-examined when I get around to
 * standardizing the error message handling.
 *
 * @param integer $infohash a hash containing the contents of the Edit Form Advanced -> Add Fields page.
 * @param integer $form_id The unique form ID.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_add_form_fields($infohash, $form_id)
{
  global $g_debug, $g_table_prefix, $LANG;

  $success = true;
  $message = "";

  $infohash = ft_sanitize($infohash);

  // grab the "global" values
  $auto_generate_col_names = isset($infohash["auto_generate_col_names"]) ? true : false;
  $num_rows                = isset($infohash["num_fields"]) ? $infohash["num_fields"] : 0; // bah! Consistency!?

  // if for some reason there are no rows, just return
  if ($num_rows == 0)
    return;


  // find out how many fields there are already created for this form. This is used for the field order
  $form_fields = ft_get_form_fields($form_id);
  $form_info   = ft_get_form($form_id);
  $num_fields  = count($form_fields);
  $order = $num_fields + 1;

  // if we're auto-generating the column names, get a list of unique strings (of length $num_fields)
  // in preparation for use
  $unique_col_names = array();
  if ($auto_generate_col_names)
  {
    $existing_col_names = array();
    foreach ($form_fields as $field)
      $existing_col_names[] = $field["col_name"];

    // auto-generated database field names are of the form col_X where X is any integer starting with 1
    $curr_num = 1;
    while (count($unique_col_names) < $num_rows)
    {
       if (!in_array("col_$curr_num", $existing_col_names))
         $unique_col_names[] = "col_$curr_num";

       $curr_num++;
    }
  }


  // loop through $infohash and, if the data is valid, add each form field
  for ($i=1; $i<=$num_rows; $i++)
  {
    // ignore any blank / deleted fields
    if (!isset($infohash["field_name_$i"]) || empty($infohash["field_name_$i"]))
      continue;

    // extract values for use
    $include_on_redirect = isset($infohash["include_on_redirect_$i"]) ? "yes" : "no";
    $field_name    = $infohash["field_name_$i"];
    $field_title   = $infohash["field_title_$i"];
    $field_size    = $infohash["field_size_$i"];
    $data_type     = $infohash["data_type_$i"];

    // figure out the column name
    $col_name = "";
    if ($auto_generate_col_names)
    {
      // grab the next free unique column name
      $col_name = array_shift($unique_col_names);
    }
    else
    {
      // this should never happen, but check for it anyway.
      if (!isset($infohash["col_name_$i"]) || empty($infohash["col_name_$i"]))
        continue;

      $col_name = $infohash["col_name_$i"];
    }

    // add the new field to form_fields
    $query = "INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_size, field_type,
        data_type, field_title, col_name, list_order, include_on_redirect)
      VALUES ($form_id, '$field_name', '$field_size', 'textbox',
        '$data_type', '$field_title', '$col_name', $order, '$include_on_redirect')";

    $result = mysql_query($query)
      or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());


    // if the form already exists, add the field
    if ($form_info['is_complete'] == "yes")
    {
      $new_field_size = "";

      switch ($field_size)
      {
        case "tiny":       $new_field_size = "VARCHAR(5)";   break;
        case "small":      $new_field_size = "VARCHAR(20)";  break;
        case "medium":     $new_field_size = "VARCHAR(255)"; break;
        case "large":      $new_field_size = "TEXT";         break;
        case "very_large": $new_field_size = "MEDIUMTEXT";   break;
        default:           $new_field_size = "VARCHAR(255)"; break;
      }

      list ($is_success, $err_message) = _ft_add_table_column("{$g_table_prefix}form_{$form_id}", $col_name, $new_field_size);

      // if the alter message didn't work, return with an error message
      if (!$is_success)
      {
        $success = false;

        $replacement_info = array("fieldname" => $field_name);
        $message = ft_eval_smarty_string($LANG["notify_form_field_not_added"], $replacement_info);
        if ($g_debug) $message .= " \"$err_message\"";
        return array($success, $message);
      }
    }
  }

  extract(ft_process_hooks("end", compact("infohash", "form_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Deletes unwanted form fields. Called in two instances:
 * 1. by administrator during form building process.<br/>
 * 2. by administrator during form editing process.<br/>
 *
 * If the field being removed is a FILE or and IMAGE field, it checks to see if the
 * auto_delete_submission_files setting for this form is set to "yes". If it is, it removes ALL
 * files that were associated with this field. Otherwise, it leaves all files intact.
 *
 * @param integer $infohash a hash containing the contents of the Edit Form Advanced tab.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_delete_form_fields($infohash, $form_id)
{
  global $g_table_prefix, $LANG;

  // return values
  $success = true;
  $message = "";

  // find out if the form exists
  $form_info = ft_get_form($form_id);
  $form_table_exists = ($form_info["is_complete"] == "yes") ? true : false;

  $remove_field_ids = array();
  $files_to_remove = array();

  // stores the Views IDs of any View that is affected by deleting one of the form field, regardless of the field
  // or form
  $affected_views = array();

  while (list($key, $value) = each($infohash))
  {
    if (preg_match("/^field_(\d+)_remove$/", $key, $match))
    {
      $field_id = $match[1];
      $remove_field_ids[] = $field_id;

      $old_field_info = ft_get_form_field($field_id);
      $field_type = $old_field_info["field_type"];

      // if this if a file or image field, log all files that have been uploaded through this field, so we can
      // remove them if need be
      if ($field_type == "file" || $field_type == "image")
      {
        $filename_hash = ft_get_uploaded_filenames($form_id, $field_id, $field_type);
        $files_to_remove = array_values($filename_hash);
      }

      mysql_query("DELETE FROM {$g_table_prefix}form_fields WHERE field_id = $field_id");

      // get a list of any Views that referenced this form field
      $view_query = mysql_query("SELECT view_id FROM {$g_table_prefix}view_fields WHERE field_id = $field_id");
      while ($row = mysql_fetch_assoc($view_query))
        $affected_views[] = $row["view_id"];

      // delete any View filters that are set up on this form field
      mysql_query("DELETE FROM {$g_table_prefix}view_filters WHERE field_id = $field_id AND form_id = $form_id");

      // if the form already exists, then remove the column
      if ($form_table_exists)
      {
        $drop_column = $old_field_info['col_name'];
        mysql_query("ALTER TABLE {$g_table_prefix}form_$form_id DROP $drop_column");

        // if any Views had this field as the default sort order, reset them to having the submission_date
        // field as the default sort order
        mysql_query("
          UPDATE {$g_table_prefix}views
          SET     default_sort_field = 'submission_date'
          WHERE   default_sort_field = '$drop_column' AND
                  form_id = $form_id
                    ");
      }
    }
  }

  // if required, remove all associated files
  if ($form_info['auto_delete_submission_files'] == "yes")
  {
    // delete them all
    if (!empty($files_to_remove))
    {
      foreach ($files_to_remove as $file)
        @unlink($file);
    }
  }


  // if there are any Views that reference this field, delete the fields too!
  foreach ($remove_field_ids as $field_id)
  {
    $view_ids = ft_get_field_views($field_id);
    foreach ($view_ids as $view_id)
      ft_delete_view_field($view_id, $field_id);
  }

  // update the list_order of this form's fields
  if ($form_table_exists)
    ft_auto_update_form_field_order($form_id);

  // update the order of any Views that referenced this field
  foreach ($affected_views as $view_id)
    ft_auto_update_view_field_order($view_id);

  // determine the return message
  if (count($remove_field_ids) > 1)
    $message = $LANG["notify_form_fields_removed"];
  else
    $message = $LANG["notify_form_field_removed"];

  extract(ft_process_hooks("end", compact("infohash", "form_id", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

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

  $return_info = "";

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

  extract(ft_process_hooks("end", compact("field_id", "options"), array("options")), EXTR_OVERWRITE);

  return $options;
}


/**
 * Retrieves all information about a specific form template field. If the $get_options parameter
 * is set and the field being returned is a multiple select option (radio buttons, select,
 * checkboxes, multi-select), it returns the options in an "options" key.
 *
 * TODO: when needed, this should be updated to (a) return the ordered field options (maybe...)
 * but also the module for each.
 *
 * @param integer $field_id the unique field ID
 * @param boolean $get_options returns
 * @return array A hash of information about this field.
 */
function ft_get_form_field($field_id, $get_options = false)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}form_fields
    WHERE  field_id = $field_id
           ");

  $info = mysql_fetch_assoc($query);

  // append any custom field settings (like custom image manager fields)
  $query = mysql_query("
    SELECT setting_name, setting_value
    FROM   {$g_table_prefix}field_settings
    WHERE  field_id = $field_id
           ");
  $settings = array();
  while ($row = mysql_fetch_assoc($query))
    $settings[$row["setting_name"]] = $row["setting_value"];

  $info["settings"] = $settings;

  // lastly, if required, append any options for this field
  if ($get_options)
  {
    $field_type = $info["field_type"];
    $multi_select_fields = array("radio-buttons", "select", "checkboxes", "multi-select");

    if (in_array($field_type, $multi_select_fields))
    {
      $info["options"] = ft_get_field_options($field_id);
    }
  }

  extract(ft_process_hooks("end", compact("field_id", "info"), array("info")), EXTR_OVERWRITE);

  return $info;
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
 * Retrieves all custom settings for an individual form field from the field_settings table.
 *
 * @param integer $field_id the unique field ID
 * @param string $module the module folder name
 * @return mixed if the module parameter is set, it returns a HASH of values (setting_name => setting_value);
 *     if not, it returns an array of hashes, containing setting_name, setting_value and module keys.
 */
function ft_get_form_field_settings($field_id, $module)
{
  global $g_table_prefix;

  $settings = array();
  if (empty($module))
  {
    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_settings
      WHERE  field_id = $field_id
             ");

    while ($row = mysql_fetch_assoc($query))
      $settings[] = $row;
  }
  else
  {
    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_settings
      WHERE  field_id = $field_id AND
             module = '$module'
             ");

    while ($row = mysql_fetch_assoc($query))
      $settings[$row["setting_name"]] = $row["setting_value"];
  }

  extract(ft_process_hooks("end", compact("field_id", "module", "settings"), array("settings")), EXTR_OVERWRITE);

  return $settings;
}


/**
 * Retrieves all field information about a form, ordered by list_order.
 *
 * @param integer $form_id the unique form ID
 * @return array an array of hash information
 */
function ft_get_form_fields($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id
    ORDER BY list_order
           ") or die(mysql_error());

  $infohash = array();
  while ($row = mysql_fetch_assoc($query))
    $infohash[] = $row;

  extract(ft_process_hooks("end", compact("form_id", "infohash"), array("infohash")), EXTR_OVERWRITE);

  return $infohash;
}


/**
 * A getter function to retrieve everything about a form field from the database column name. This is used in
 * the ft_search_submissions function.
 *
 * @param integer $form_id
 * @param string $col_name
 */
function ft_get_form_field_by_colname($form_id, $col_name)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id AND
           col_name = '$col_name'
    LIMIT 1
      ");

  $infohash = array();
  while ($row = mysql_fetch_assoc($query))
    $infohash = $row;

  return $infohash;
}


/**
 * The field_settings table stores custom overridden settings from the settings table for a particular form
 * field. This function retrieves all settings (or a single setting) for a field, taking into account whether
 * it's been overridden or not.
 *
 * For example, if the field is an image field, the Image Manager defines a lot of default settings for image
 * fields - like default upload URL, thumbnail sizes and so on. However, since every one of those settings
 * can be manually overridden for a single image field, this function returns either the original value OR
 * the custom overridden value.
 *
 * TODO BUG: The optional 3rd param apparently has no affect...
 *
 * @param integer $field_id
 * @param string $module the module folder or "core"
 * @param string $setting_name (optional)
 */
function ft_get_extended_field_settings($field_id, $module = "core", $setting_name = "")
{
  $module_settings = ft_get_module_settings("", $module);
  $custom_settings = ft_get_form_field_settings($field_id, $module);

  $settings = array();
  while (list($setting_name, $setting_value) = each($module_settings))
  {
    if (array_key_exists($setting_name, $custom_settings))
      $settings[$setting_name] = $custom_settings[$setting_name];
    else
      $settings[$setting_name] = $module_settings[$setting_name];
  }

  extract(ft_process_hooks("end", compact("field_id", "module", "setting_name"), array("settings")), EXTR_OVERWRITE);

  return $settings;
}


/**
 * Deletes any extended field settings for a particular form field.
 *
 * @param integer $field_id
 */
function ft_delete_extended_field_settings($field_id)
{
  global $g_table_prefix;

  mysql_query("DELETE FROM {$g_table_prefix}field_settings WHERE field_id = $field_id");

  extract(ft_process_hooks("end", compact("field_id"), array()), EXTR_OVERWRITE);
}


/**
 * Reorders template fields and updates the corresponding column name field.
 *
 * Called by administrator in Add Form Step 3 page and on Advanced tab when editing. For the special
 * system fields (submission ID, submission Date and IP address), we don't want to override the
 * default DB table column names. To prevent this, we let this function know which fields are system
 * fields by passing hidden values:<br/>
 *
 * <input type="hidden" name="field_X_system" value="1" />
 *
 * @param integer $infohash A hash containing the contents of the Edit Form Advanced tab.
 * @param boolean $set_default_form_field_names if true, this renames the columns
 */
function ft_reorder_form_fields($infohash, $form_id, $set_default_form_field_names = false)
{
  global $g_table_prefix;

  $new_order = array();

  // loop through $infohash and for each field_X_order values, log the new order
  while (list($key, $val) = each($infohash))
  {
    // find the field id
    preg_match("/^field_(\d+)_order$/", $key, $match);

    if (!empty($match[1]))
    {
      $field_id = $match[1];

      // update the $account_order
      $new_order[$field_id] = $val;
    }
  }
  asort($new_order);
  reset($infohash);

  // now loop through the correct_order array and update the column names
  $order = 1;
  $custom_col_num = 1;
  while (list($key, $value) = each($new_order))
  {
    $col_name_qry = "";
    if ($set_default_form_field_names)
    {
      if (!isset($infohash["field_{$key}_system"]))
      {
        $col_name_qry = ", col_name = 'col_$custom_col_num' ";
        $custom_col_num++;
      }
    }

    mysql_query("
      UPDATE {$g_table_prefix}form_fields
      SET    list_order = $order
             $col_name_qry
      WHERE  field_id = $key AND
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
 * Adds/updates all options for a given field. This is used for textboxes, textareas, system, WYWISYG and
 * password fields. For other field types, see the corresponding function:
 *
 *     file:                                     ft_update_field_file_settings()
 *     radios/checkboxes/single & multi-select:  ft_update_multi_field_settings()
 *
 * @param integer $form_id The unique form ID
 * @param integer $field_id The unique field ID
 * @param integer $info a hash containing the contents of the Edit Form Advanced tab
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_field($form_id, $field_id, $info)
{
  global $g_table_prefix, $g_debug, $LANG;

  $info = ft_sanitize($info);
  $field_title         = $info["field_title"];
  $field_type          = $info["field_type"];
  $include_on_redirect = (isset($info["include_on_redirect"])) ? "yes" : "no";

  $old_field_info = ft_get_form_field($field_id, false);

  // update each field
  if ($field_type == "system")
  {
    mysql_query("
      UPDATE {$g_table_prefix}form_fields
      SET    field_title = '$field_title',
             include_on_redirect = '$include_on_redirect'
      WHERE  field_id = $field_id
        ");
  }
  else
  {
    $field_size = $info["field_size"];
    mysql_query("
      UPDATE {$g_table_prefix}form_fields
      SET    field_size = '$field_size',
             field_title = '$field_title',
             field_type = '$field_type',
             include_on_redirect = '$include_on_redirect'
      WHERE  field_id = $field_id
        ");

    // if the field size just changed, update the database table too
    if ($old_field_info["field_size"] != $field_size)
    {
      $new_field_size = "";
      switch ($field_size)
      {
        case "tiny":       $new_field_size = "VARCHAR(5)";   break;
        case "small":      $new_field_size = "VARCHAR(20)";  break;
        case "medium":     $new_field_size = "VARCHAR(255)"; break;
        case "large":      $new_field_size = "TEXT";         break;
        case "very_large": $new_field_size = "MEDIUMTEXT";   break;
        default:           $new_field_size = "VARCHAR(255)"; break;
      }
      _ft_alter_table_column("{$g_table_prefix}form_{$form_id}", $old_field_info["col_name"], $old_field_info["col_name"], $new_field_size);
    }
  }

  $success = true;
  $message = $LANG["notify_form_field_options_updated"];
  extract(ft_process_hooks("end", compact("field_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * This function is called on the update field options page for radio buttons, checkboxes, select
 * and multi-select fields.
 *
 * @param integer $form_id
 * @param integer $field_id
 * @param array $info
 */
function ft_update_multi_field_settings($form_id, $field_id, $info)
{
  global $g_table_prefix, $g_debug, $LANG;

  $info = ft_sanitize($info);
  $field_title         = $info["field_title"];
  $field_type          = $info["field_type"];
  $include_on_redirect = (isset($info["include_on_redirect"])) ? "yes" : "no";
  $group_id            = (isset($info["group_id"]) && !empty($info["group_id"])) ? $info["group_id"] : "NULL";
  $field_size          = $info["field_size"];

  $old_field_info = ft_get_form_field($field_id, false);

  mysql_query("
    UPDATE {$g_table_prefix}form_fields
    SET    field_size = '$field_size',
           field_title = '$field_title',
           field_type = '$field_type',
           include_on_redirect = '$include_on_redirect',
           field_group_id = $group_id
    WHERE  field_id = $field_id
      ");

  // if the field size just changed, update the database table too
  if ($old_field_info["field_size"] != $field_size)
  {
    $new_field_size = "";
    switch ($field_size)
    {
      case "tiny":       $new_field_size = "VARCHAR(5)";   break;
      case "small":      $new_field_size = "VARCHAR(20)";  break;
      case "medium":     $new_field_size = "VARCHAR(255)"; break;
      case "large":      $new_field_size = "TEXT";         break;
      case "very_large": $new_field_size = "MEDIUMTEXT";   break;
      default:           $new_field_size = "VARCHAR(255)"; break;
    }
    _ft_alter_table_column("{$g_table_prefix}form_{$form_id}", $old_field_info["col_name"], $old_field_info["col_name"], $new_field_size);
  }

  $success = true;
  $message = $LANG["notify_form_field_options_updated"];
  extract(ft_process_hooks("end", compact("field_id", "info"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Adds/updates all options for a file.
 *
 * If the upload folder is invalid, it returns an error.
 *
 * @param integer $infohash A hash containing the contents of the Edit Form Advanced tab.
 * @param integer $field_id The unique field ID.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_field_file_settings($form_id, $field_id, $infohash)
{
  global $g_table_prefix, $g_debug, $LANG;

  $infohash = ft_sanitize($infohash);

  // first, update the common settings
  $field_title         = $infohash["field_title"];
  $include_on_redirect = (isset($infohash["include_on_redirect"])) ? "yes" : "no";

  $old_field_info = ft_get_form_field($field_id, false);

  mysql_query("
    UPDATE {$g_table_prefix}form_fields
    SET    field_size = 'medium',
           field_title = '$field_title',
           field_type = 'file',
           include_on_redirect = '$include_on_redirect'
    WHERE  field_id = $field_id
      ");

  // ensure the database field size is "medium"
  _ft_alter_table_column("{$g_table_prefix}form_{$form_id}", $old_field_info["col_name"], $old_field_info["col_name"], "VARCHAR(255)");

  // delete old settings
  $old_extended_settings = ft_get_extended_field_settings($field_id, "core");
  ft_delete_extended_field_settings($field_id);

  $new_settings = array();
  $num_settings = $infohash["num_settings"];
  for ($i=1; $i<=$num_settings; $i++)
  {
    // if this row was deleted or not specified, skip it
    if (!isset($infohash["row_{$i}"]) || empty($infohash["row_{$i}"]))
      continue;

    switch ($infohash["row_{$i}"])
    {
      case "file_upload_folder":
        $new_settings["file_upload_dir"] = $infohash["file_upload_dir_{$i}"];
        $new_settings["file_upload_url"] = $infohash["file_upload_url_{$i}"];
        break;
      case "file_upload_max_size":
        $new_settings["file_upload_max_size"] = $infohash["file_upload_max_size_{$i}"];
        break;
      case "file_upload_filetypes":
        $new_settings["file_upload_filetypes"] = $infohash["file_upload_filetypes_{$i}"];
        break;
    }
  }

  // add the new settings
  while (list($key, $value) = each($new_settings))
  {
    mysql_query("
      INSERT INTO {$g_table_prefix}field_settings (field_id, setting_name, setting_value, module)
      VALUES ($field_id, '$key', '$value', 'core')
        ");
  }

  // all right! Database update complete, let's see if the file upload folder info changed, and if so, move the files.
  $new_extended_settings = ft_get_extended_field_settings($field_id);

  // (1) they just REMOVED a custom file upload folder
  if (isset($old_field_info["settings"]["file_upload_dir"]) && !isset($new_settings["file_upload_dir"]))
    ft_move_field_files($field_id, $old_field_info["settings"]["file_upload_dir"], $new_extended_settings["file_upload_dir"]);

  // (2) just ADDED a new custom file upload folder
  else if (!isset($old_field_info["settings"]["file_upload_dir"]) && isset($new_settings["file_upload_dir"]))
    ft_move_field_files($field_id, $new_extended_settings["file_upload_dir"], $new_settings["file_upload_dir"]);

  // (3) the custom file upload folder CHANGED
  else if (isset($old_field_info["settings"]["file_upload_dir"]) && isset($new_settings["file_upload_dir"]) &&
    $old_field_info["settings"]["file_upload_dir"] != $new_settings["file_upload_dir"])
    ft_move_field_files($field_id, $old_field_info["settings"]["file_upload_dir"], $new_settings["file_upload_dir"]);


  $success = true;
  $message = $LANG["notify_image_field_settings_updated"];
  extract(ft_process_hooks("end", compact("infohash", "field_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Returns all files associated with a particular form field. This is compatible with both the
 * built-in "File" field as well as the Image fields of the Image Manager
 *
 * TODO check all references to this function. The third param has been added as an optional field,
 * but if all calling contexts have that value available (field type: "file" or "image") then make the field
 * required.
 *
 * @param integer $form_id the unique form ID.
 * @param integer $field_id the unique field ID.
 * @return array a hash of [submission ID] => [path (not URL!) + filename]
 */
function ft_get_uploaded_filenames($form_id, $field_id, $field_type = "")
{
  global $g_table_prefix;

  // get the column name for this field
  $field_info = ft_get_form_field($field_id);
  $col_name   = $field_info["col_name"];
  $extended_field_settings = ft_get_extended_field_settings($field_id);
  $folder = $extended_field_settings["file_upload_dir"];

  // if col_name is empty, the field doesn't exist - so the user is probably just setting up the form.
  // Just return an empty array.
  if (empty($col_name))
    return array();

  $query = "
    SELECT submission_id, $col_name
    FROM   {$g_table_prefix}form_{$form_id}
    WHERE  $col_name != ''
           ";
  $result = mysql_query($query);

  $filename_hash = array();
  while ($record = mysql_fetch_assoc($result))
  {
    if (!empty($record[$col_name]))
      $filename_hash[$record["submission_id"]] = "$folder/{$record[$col_name]}";
  }

  return $filename_hash;
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
  $field_info = ft_get_form_field($field_id);
  $col_name   = $field_info["col_name"];
  $field_type = $field_info["field_type"];

  // loop through all of the affected filters & update the SQL
  while ($filter_info = mysql_fetch_assoc($affected_filters))
  {
    $filter_id     = $filter_info["filter_id"];
    $filter_values = $filter_info["filter_values"];
    $operator      = $filter_info["operator"];

    // date field
    if ($field_type == "submission_date" || $field_type == "last_modified_date")
    {
      $sql_operator = ($operator == "after") ? ">" : "<";
      $sql = "$col_name $sql_operator '$values'";
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
