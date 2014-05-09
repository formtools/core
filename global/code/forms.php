<?php

/**
 * This file contains all functions relating to managing forms within Form Tools.
 *
 * @copyright Encore Web Studios 2010
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-3
 * @subpackage Forms
 */


// -------------------------------------------------------------------------------------------------



/**
 * Called by client accounts, allowing them to update the num_submissions_per_page and auto email
 * settings.
 *
 * @param array $infohash A hash containing the various form values to update.
 */
function ft_client_update_form_settings($infohash)
{
  global $g_table_prefix, $LANG;

  extract(ft_process_hooks("start", compact("infohash"), array("infohash")), EXTR_OVERWRITE);

  $success = true;
  $message = $LANG["notify_form_settings_updated"];

  // validate $infohash fields
  $rules = array();
  $rules[] = "required,form_id,{$LANG["validation_no_form_id"]}";
  $rules[] = "required,is_active,{$LANG["validation_is_form_active"]}";
  $rules[] = "required,num_submissions_per_page,{$LANG["validation_no_num_submissions_per_page"]}";
  $rules[] = "digits_only,num_submissions_per_page,{$LANG["validation_invalid_num_submissions_per_page"]}";
  $errors = validate_fields($infohash, $rules);

  $query = "
      UPDATE {$g_table_prefix}forms
      SET    is_active = '{$infohash['is_active']}',
             auto_email_admin = '{$infohash['auto_email_admin']}',
             auto_email_user = '{$infohash['auto_email_user']}',
             num_submissions_per_page = '{$infohash['num_submissions_per_page']}',
             printer_friendly_format = '{$infohash['printer_friendly_format']}',
             hide_printer_friendly_empty_fields = '{$infohash['hide_empty_fields']}'
      WHERE  form_id = '{$infohash['form_id']}'
            ";

  $result = mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  if (!$result)
  {
    $success = false;
    $message = $LANG["notify_form_not_updated_notify_admin"];
    return array($success, $message);
  }

  extract(ft_process_hooks("end", compact("infohash", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Completely removes a form from the database. This includes deleting all form fields, emails, Views,
 * View fields, View tabs, View filters, client-form, client-view and public omit list (form & View) mappings.
 * It also includes an optional parameter to remove all files that were uploaded through file fields in the
 * form; defaulted to FALSE.
 *
 * TODO update the file deleting code for the Image Manager.
 *
 * @param integer $form_id the unique form ID
 * @param boolean $remove_associated_files A boolean indicating whether or not all files that were
 *              uploaded via file fields in this form should be removed as well.
 */
function ft_delete_form($form_id, $remove_associated_files = false)
{
  global $g_table_prefix;

  extract(ft_process_hooks("start", compact("form_id"), array()), EXTR_OVERWRITE);

  if ($remove_associated_files)
  {
    // get the names and paths of all uploaded files
    $form_template = ft_get_form_fields($form_id);
    $file_field_hash = array(); // field_id => upload folder path
    while ($field = mysql_fetch_assoc($form_template))
    {
      if ($field['field_type'] == "file")
        $file_field_hash[$field['field_id']] = $field['file_upload_dir'];
    }

    // now determine all files + paths and remove them
    if (!empty($file_field_hash))
    {
      foreach ($file_field_hash as $field_id => $upload_dir)
      {
        $uploaded_files = ft_get_uploaded_filenames($form_id, $field_id);

        if (!empty($uploaded_files))
        {
          foreach ($uploaded_files as $file)
            @unlink("$upload_dir/$file");
        }
      }
    }
  }

  // remove the table
  $query = "DROP TABLE IF EXISTS {$g_table_prefix}form_$form_id";
  mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  // get a list of all field IDs in this form. This is used to delete them later
  $query = "
    SELECT field_id, field_type
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id
            ";
  $field_id_query = mysql_query($query);

  // remove any reference to the form in form_fields
  mysql_query("DELETE FROM {$g_table_prefix}form_fields WHERE form_id = $form_id")
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  // remove any reference to the form in forms table
  mysql_query("DELETE FROM {$g_table_prefix}forms WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}client_forms WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}email_templates WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}form_export_templates WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}public_form_omit_list WHERE form_id = $form_id");

  // get a list of all associated Views
  $views_result = mysql_query("SELECT view_id FROM {$g_table_prefix}views WHERE form_id = $form_id");
  $view_ids = array();
  $view_ids_mysql = array();
  while ($info = mysql_fetch_assoc($views_result))
  {
    $view_ids[] = $info["view_id"];
    $view_ids_mysql[] = "view_id = {$info["view_id"]}";
  }

  // if there are any associated Views, delete all related info
  if (!empty($view_ids))
  {
    $view_ids_mysql_str = join(" OR ", $view_ids_mysql);

    mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE $view_ids_mysql_str");
    mysql_query("DELETE FROM {$g_table_prefix}view_fields WHERE $view_ids_mysql_str");
    mysql_query("DELETE FROM {$g_table_prefix}view_tabs WHERE $view_ids_mysql_str");
    mysql_query("DELETE FROM {$g_table_prefix}view_filters WHERE $view_ids_mysql_str");
    mysql_query("DELETE FROM {$g_table_prefix}views WHERE $view_ids_mysql_str");
    mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE $view_ids_mysql_str");
  }

  // remove any fields in field_options table
  $field_ids = array();
  while ($field_info = mysql_fetch_array($field_id_query))
  {
    if ($field_info['field_type'] == "select" || $field_info['field_type'] == "multi-select" ||
        $field_info['field_type'] == "radio-buttons" || $field_info['field_type'] == "checkboxes")
      $field_ids[] = $field_info[0];
  }
  if (!empty($field_ids))
  {
    foreach ($field_ids as $field_id)
    {
      mysql_query("DELETE FROM {$g_table_prefix}field_options WHERE field_id = $field_id");
      mysql_query("DELETE FROM {$g_table_prefix}field_settings WHERE field_id = $field_id");
    }
  }
}


/**
 * This function "finalizes" the form, i.e. marks it as completed and ready to go.
 *
 * This is where the excitement happens. This function is called when the user has completed step
 * 4 of the Add Form process, after the user is satisfied that the data that is stored is correct.
 * This function does the following:
 * <ul>
 * <li>Adds a new record to the <b>form_admin_fields</b> table listing which of the database fields are
 * to be visible in the admin interface panel for this form.</li>
 * <li>Creates a new form table with the column information specified in infohash.</li>
 * </ul>
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the Step 4 Add Form page.
 */
function ft_finalize_form($form_id)
{
  global $g_table_prefix;

  $form_fields = ft_get_form_fields($form_id);
  $query = "
    CREATE TABLE {$g_table_prefix}form_$form_id (
      submission_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
      PRIMARY KEY(submission_id),\n";

  foreach ($form_fields as $field)
  {
    // don't add system fields (submission ID, date & IP address)
    if ($field["field_type"] == "system")
      continue;


    $query .= "{$field['col_name']} ";
    switch ($field["field_size"])
    {
      case "tiny":
        $query .= "VARCHAR(5),\n";
        break;
      case "small":
        $query .= "VARCHAR(20),\n";
        break;
      case "medium":
        $query .= "VARCHAR(255),\n";
        break;
      case "large":
        $query .= "TEXT,\n";
        break;
      case "very_large":
        $query .= "MEDIUMTEXT,\n";
        break;
    }
  }

  $query .= "submission_date DATETIME NOT NULL,
             last_modified_date DATETIME NOT NULL,
             ip_address VARCHAR(15),
             is_finalized ENUM('yes','no') default 'yes')
             TYPE=MyISAM DEFAULT CHARSET=utf8";

  mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, line " . __LINE__ . ": <i>" . nl2br($query) . "</i>", mysql_error());

  $now = ft_get_current_datetime();

  // now the form is complete. Update it as is_complete and enabled
  $query = "
      UPDATE {$g_table_prefix}forms
      SET    is_initialized = 'yes',
             is_complete = 'yes',
             is_active = 'yes',
             date_created = '$now'
      WHERE  form_id = $form_id
           ";
  mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  // finally, add the default View
  ft_add_default_view($form_id);

  extract(ft_process_hooks("end", compact("form_id"), array()), EXTR_OVERWRITE);
}


/**
 * Called by test form submission during form setup procedure. This stores a complete form submission
 * in the database for examination and pruning by the administrator. Error / notification messages are
 * displayed in the language of the currently logged in administrator.
 *
 * It works with both submissions sent through process.php and the API.
 *
 * @param array $form_data a hash of the COMPLETE form data (i.e. all fields)
 */
function ft_initialize_form($form_data)
{
  global $g_table_prefix, $g_root_dir, $g_multi_val_delimiter, $LANG;

  // escape the incoming values
  $form_data = ft_sanitize($form_data);
  $form_id = $form_data["form_tools_form_id"];

  // check the form ID is valid
  if (!ft_check_form_exists($form_id, true))
  {
    $page_vars = array("message_type" => "error", "error_code" => 100);
    ft_display_page("../../global/smarty/messages.tpl", $page_vars);
    exit;
  }

  $form_info = ft_get_form($form_id);


  // if this form has already been completed, exit with an error message
  if ($form_info["is_complete"] == "yes")
  {
    $page_vars = array("message_type" => "error", "error_code" => 101);
    ft_display_page("../../global/smarty/messages.tpl", $page_vars);
    exit;
  }

  // since this form is still incomplete, remove any old records from form_fields concerning this form
  $query = mysql_query("
    DELETE FROM {$g_table_prefix}form_fields
    WHERE  form_id = $form_id
           ");

  // remove irrelevant key-values
  unset($form_data["form_tools_initialize_form"]);
  unset($form_data["form_tools_submission_id"]);
  unset($form_data["form_tools_form_id"]);

  $order = 1;

  // add the submission ID system field. Note: "Submission ID" is hardcoded in English; this is on purpose - "ID"
  // is the displayed value & can be translated/changed by the administrator
  $query = mysql_query("
      INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_test_value, field_type,
          data_type, field_title, col_name, list_order)
      VALUES ($form_id, 'Submission ID', '', 'system', 'number', '{$LANG["word_id"]}',
          'submission_id', '$order')
    ");

  if (!$query)
  {
    $page_vars = array("message_type" => "error", "error_code" => 102, "error_type" => "system",
      "debugging" => "<b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, failed query: " . mysql_error());
    ft_display_page("../../global/smarty/messages.tpl", $page_vars);
    exit;
  }

  $order++;

  while (list($key, $value) = each($form_data))
  {
    // if the value is an array, it's either a checkbox field or a multi-select field. Just
    // comma-separate them
    if (is_array($value))
      $value = join("$g_multi_val_delimiter", $value);

    $query = mysql_query("
      INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_test_value, data_type, list_order)
      VALUES ($form_id, '$key', '$value', 'string', '$order')
                ");

    if (!$query)
    {
      $page_vars = array("message_type" => "error", "error_code" => 103, "error_type" => "system",
        "debugging" => "<b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, failed query: " . mysql_error());
      ft_display_page("../../global/smarty/messages.tpl", $page_vars);
      exit;
    }

    $order++;
  }

  // now see if any files were uploaded, too. ** don't actually upload the file, just allocate a
  // spot for the filename string in the database. The user will have to configure the field settings
  // later
  while (list($key, $fileinfo) = each($_FILES))
  {
    $query = mysql_query("
      INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_test_value, data_type, list_order)
      VALUES ($form_id, '$key', '{$LANG["word_file_b_uc"]}', 'string', '$order')
                ");

    if (!$query)
    {
      $page_vars = array("message_type" => "error", "error_code" => 104, "error_type" => "system",
        "debugging" => "<b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, failed query: " . mysql_error());
      ft_display_page("../../global/smarty/messages.tpl", $page_vars);
      exit;
    }

    $order++;
  }

  // add the submission date, last modified date and IP address system fields
  $order1 = $order;
  $order2 = $order+1;
  $order3 = $order+2;
  $query = mysql_query("
    INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_test_value, field_type,
      field_title, data_type, col_name, list_order)
    VALUES ($form_id, 'Date', '', 'system', '{$LANG["word_date"]}', 'date', 'submission_date', '$order1'),
           ($form_id, 'Last Modified', '', 'system', '{$LANG["phrase_last_modified"]}', 'date', 'last_modified_date', '$order2'),
           ($form_id, 'IP Address', '', 'system', '{$LANG["phrase_ip_address"]}', 'number', 'ip_address', '$order3')
      ");

  if (!$query)
  {
    $page_vars = array("message_type" => "error", "error_code" => 105, "error_type" => "system",
      "debugging" => "<b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, failed query: " . mysql_error());
    ft_display_page("../../global/smarty/messages.tpl", $page_vars);
    exit;
  }

  // finally, set this form's "is_initialized" value to "yes", so the administrator can proceed to
  // the next step of the Add Form process.
  mysql_query("
    UPDATE  {$g_table_prefix}forms
    SET     is_initialized = 'yes'
    WHERE   form_id = $form_id
              ");

  // alert a "test submission complete" message
  $page_vars = array();
  $page_vars["message"] = $LANG["processing_init_complete"];
  $page_vars["message_type"] = "notify";
  $page_vars["title"] = $LANG["phrase_test_submission_received"];
  ft_display_page("../../global/smarty/messages.tpl", $page_vars);
  exit;
}


/**
 * Retrieves all information about single form; all associated client information is stored in the
 * client_info key, as an array of hashes.
 *
 * @param integer $form_id the unique form ID
 * @return array A hash of form information
 */
function ft_get_form($form_id)
{
  global $g_table_prefix;

  ft_check_form_exists($form_id);

  $form_id = ft_sanitize($form_id);

  $query = mysql_query("SELECT * FROM {$g_table_prefix}forms WHERE form_id = $form_id");
  $form_info = mysql_fetch_assoc($query);
  $form_info["client_info"] = ft_get_form_clients($form_id);
  $form_info["client_omit_list"] = ($form_info["access_type"] == "public") ? ft_get_public_form_omit_list($form_id) : array();

  $query = mysql_query("SELECT * FROM {$g_table_prefix}multi_page_form_urls WHERE form_id = $form_id ORDER BY page_num");
  $form_info["multi_page_form_urls"] = array();
  while ($row = mysql_fetch_assoc($query))
    $form_info["multi_page_form_urls"][] = $row;

  extract(ft_process_hooks("end", compact("form_id", "form_info"), array("form_info")), EXTR_OVERWRITE);

  return $form_info;
}


/**
 * Basically a wrapper function for ft_search_forms, which returns ALL forms, regardless of
 * what client it belongs to.
 *
 * @return array
 */
function ft_get_forms()
{
  return ft_search_forms($account_id = "", true);
}


/**
 * Returns an array of account information of all clients associated with a particular form. This
 * function is smart enough to return the complete list, depending on whether the form has public access
 * or not. If it's a public access form, it takes into account those clients on the form omit list.
 *
 * @param integer $form
 * @return array
 */
function ft_get_form_clients($form_id)
{
  global $g_table_prefix;

  $access_type_query = mysql_query("SELECT access_type FROM {$g_table_prefix}forms WHERE form_id = $form_id");
  $access_type_info = mysql_fetch_assoc($access_type_query);
  $access_type = $access_type_info["access_type"];

  $accounts = array();

  if ($access_type == "public")
  {
    $client_omit_list = ft_get_public_form_omit_list($form_id);
    $all_clients = ft_get_client_list();

    foreach ($all_clients as $client_info)
    {
      $client_id = $client_info["account_id"];
      if (!in_array($client_id, $client_omit_list))
        $accounts[] = $client_info;
    }
  }
  else
  {
    $account_query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}client_forms cf, {$g_table_prefix}accounts a
      WHERE  cf.form_id = $form_id
      AND    cf.account_id = a.account_id
             ");

    while ($row = mysql_fetch_assoc($account_query))
      $accounts[] = $row;
  }

  extract(ft_process_hooks("end", compact("form_id", "accounts"), array("accounts")), EXTR_OVERWRITE);

  return $accounts;
}


/**
 * Simple function to find out how many forms are in the database, regardless of status or anything else.
 *
 * @return integer the number of forms.
 */
function ft_get_form_count()
{
  global $g_table_prefix;

  $query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}forms");
  $result = mysql_fetch_assoc($query);

  return $result["c"];
}


/**
 * Returns all the column names for a particular form. The optional $view_id field lets you return
 * only those columns that are associated with a particular View. The second optional setting
 * lets you only return custom form fields (not submission ID, submission date, last modified date,
 * IP address & is_finalized)
 *
 * N.B. Updated in 2.0.0 to query the form_fields table instead of the actual form table and extract
 * the form column names from that. This should be quicker & allows us to return the columns in the
 * appropriate list_order.
 *
 * @param integer $form_id the unique form ID
 * @param integer $view_id (optional) if supplied, returns only those columns that appear in a
 *     particular View
 * @param boolean $omit_system_fields
 * @return array A hash of form: [DB column name] => [column display name]. If the database
 *     column doesn't have a display name (like with submission_id) the value is set to the same
 *     as the key.
 */
function ft_get_form_column_names($form_id, $view_id = "", $omit_system_fields = false)
{
  global $g_table_prefix;

  $result = mysql_query("
    SELECT col_name, field_title, field_type
    FROM {$g_table_prefix}form_fields
    WHERE form_id = $form_id
    ORDER BY list_order
      ");

  $view_col_names = array();
  if (!empty($view_id))
  {
    $view_fields = ft_get_view_fields($view_id);
    foreach ($view_fields as $field_info)
      $view_col_names[] = $field_info["col_name"];
  }

  $col_names = array();
  while ($col_info = mysql_fetch_assoc($result))
  {
    if ($col_info["field_type"] == "system" && $omit_system_fields)
      continue;

    if (!empty($view_id) && !in_array($col_info["col_name"], $view_col_names))
      continue;

    $col_names[$col_info["col_name"]] = $col_info["field_title"];
  }

  return $col_names;
}


/**
 * This, like ft_get_search_months is used for building the date dropdown in the search submissions
 * form. This function returns the number of days that have passed since the FIRST submission in a
 * particular form View. That value is used to generate a
 *
 * @param integer $view_id
 * @return array
 */
function ft_get_search_days($view_id)
{
  $first_submission_date = $_SESSION["ft"]["view_{$view_id}_first_submission_date"];

  // if there is no submission date to base this on, just return an empty array. This would happen with
  // forms that don't have any submissions or form Views that have filters that don't let through any
  // submissions
  if (empty($first_submission_date))
    return "";

  $first_submission_unixtime = ft_convert_datetime_to_timestamp($first_submission_date);

  $now = date("U");
  $difference_secs = $now - $first_submission_unixtime;
  $difference_days = $difference_secs / (60 * 60 * 24);

  return ceil($difference_days);
}


/**
 * This function is used in building the date range search dropdown on the main submission listing page
 * for all Form Tools accounts.
 *
 * It examines sessions to find the date of the FIRST submission for this form. This information is
 * stored in $_SESSION["ft"]["view_X_first_submission_date"] and is a MySQL datetime.
 *
 * @param integer $form_id the unique form ID.
 * @return array Based on the info in sessions, it returns an array of search months, most recent
 *             first.
 *
 *             For example, if the current date was Jan 2007 and the form was created in Nov 2006,
 *             it returns:<br/>
 *             "1_2007" => "January 2007",<br/>
 *             "2_2007" => "December 2006",<br/>
 *             "3_2007" => "November 2006"<br/>
 *             If the form creation date was earlier, it would return more months - UP TO 12 months,
 *             no more.
 *
 *             If there are no results, it returns an empty array.
 */
function ft_get_search_months($view_id)
{
  $first_submission_date = $_SESSION["ft"]["view_{$view_id}_first_submission_date"];

  // if there is no submission date to base this on, just return an empty array. This would happen with
  // forms that don't have any submissions or form Views that have filters that don't let through any
  // submissions
  if (empty($first_submission_date))
    return array();

  $date_info = split(" ", $first_submission_date);
  list($year, $month, $day) = split("-", $date_info[0]);
  $unix_date_created = mktime(0, 0, 0, $month, 0, $year);
  $first_submission_start_month_unixtime = mktime(0, 0, 0, $month, 0, $year);
  $current_unixtime = date("U");
  $seconds_in_month = 30 * 24 * 60 * 60;

  $count = 0;

  $search_months = array();
  while ($count < 12 && $current_unixtime > $first_submission_start_month_unixtime)
  {
    // get the localized date info for current_unixtime
    $date_info = ft_get_date(0, ft_get_current_datetime($current_unixtime), "n F Y");
    list($month, $label_month, $year) = split(" ", $date_info);
    $search_months["{$month}_$year"] = "$label_month $year";

    $current_unixtime -= $seconds_in_month;
    $count++;
  }

  return $search_months;
}


/**
 * This function sets up the main form values in preparation for a test submission by the actual
 * form. It is called from step 1 of the form creation page for NEW forms.
 *
 * @param array $info this parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the step 1 add form page.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 *               [2]: new form ID (success only)
 */
function ft_setup_form($info)
{
  global $g_table_prefix, $g_debug, $LANG;

  $success = true;
  $message = "";

  // check required $info fields
  $rules = array();
  $rules[] = "required,form_name,{$LANG["validation_no_form_name"]}";
  $rules[] = "required,form_url,{$LANG["validation_no_form_url"]}";
  $rules[] = "required,access_type,{$LANG["validation_no_access_type"]}";
  $errors = validate_fields($info, $rules);

  // if there are errors, piece together an error message string and return it
  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array ($success, $message, "");
  }

  // extract values
  $user_ids     = isset($info["selected_client_ids"]) ? $info["selected_client_ids"] : array();
  $form_name    = trim($info["form_name"]);
  $form_url     = trim($info["form_url"]);
  $is_multi_page_form   = isset($info["is_multi_page_form"]) ? "yes" : "no";
  $redirect_url = trim($info["redirect_url"]);
  $access_type  = $info["access_type"];
  $phrase_edit_submission = ft_sanitize(mb_strtoupper($LANG["phrase_edit_submission"]));

  $now = ft_get_current_datetime();
  $query = "
     INSERT INTO {$g_table_prefix}forms (access_type, date_created, is_active, is_complete,
       is_multi_page_form, form_name, form_url, redirect_url, edit_submission_page_label)
     VALUES ('$access_type', '$now', 'no', 'no', '$is_multi_page_form', '$form_name', '$form_url', '$redirect_url',
       '$phrase_edit_submission')
           ";

  $result = mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  $new_form_id = mysql_insert_id();

  // now store which clients are assigned to this form [remove any old ones first]
  foreach ($user_ids as $user_id)
  {
    $query = mysql_query("
       INSERT INTO {$g_table_prefix}client_forms (account_id, form_id)
       VALUES  ($user_id, $new_form_id)
                         ");
  }

  // if this is a multi-page form, add the list of pages in the form
  if ($is_multi_page_form == "yes")
  {
    $num_pages_in_multi_page_form = $info["num_pages_in_multi_page_form"];

    for ($page_num=2; $page_num<=$num_pages_in_multi_page_form; $page_num++)
    {
      $form_url = isset($info["form_url_{$page_num}"]) ? $info["form_url_{$page_num}"] : "";

      if (empty($form_url))
        continue;

      mysql_query("INSERT INTO {$g_table_prefix}multi_page_form_urls (form_id, form_url, page_num) VALUES ($new_form_id, '$form_url', $page_num)");
    }
  }

  return array($success, $message, $new_form_id);
}


/**
 * This function updates the main form values in preparation for a test submission by the actual
 * form. It is called from step 1 of the form creation page when UPDATING an existing, incomplete
 * form.
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the step 2 add form page.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_set_form_main_settings($infohash)
{
  global $g_table_prefix, $LANG;

  $success = true;
  $message = "";

  // check required infohash fields
  $rules = array();
  $rules[] = "required,form_name,{$LANG["validation_no_form_name"]}";
  $rules[] = "required,form_url,{$LANG["validation_no_form_url"]}";
  $errors = validate_fields($infohash, $rules);

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array ($success, $message, "");
  }

  // extract values
  $access_type  = isset($infohash['access_type']) ? $infohash['access_type'] : "public";
  $client_ids   = isset($infohash['selected_client_ids']) ? $infohash['selected_client_ids'] : array();
  $form_id      = $infohash["form_id"];
  $form_name    = trim($infohash['form_name']);
  $form_url     = trim($infohash['form_url']);
  $is_multi_page_form   = isset($infohash["is_multi_page_form"]) ? "yes" : "no";
  $redirect_url = trim($infohash['redirect_url']);

  // all checks out, so update the new form
  $query = mysql_query("
     UPDATE {$g_table_prefix}forms
     SET    access_type = '$access_type',
            is_active = 'no',
            is_complete = 'no',
            is_multi_page_form = '$is_multi_page_form',
            form_name = '$form_name',
            form_url = '$form_url',
            redirect_url = '$redirect_url'
     WHERE  form_id = $form_id
           ")
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  $query = mysql_query("
    DELETE FROM {$g_table_prefix}client_forms
    WHERE form_id = $form_id
                       ");

  foreach ($client_ids as $client_id)
  {
    $query = mysql_query("
       INSERT INTO {$g_table_prefix}client_forms (account_id, form_id)
       VALUES  ($client_id, $form_id)
                         ");
  }

  mysql_query("DELETE FROM {$g_table_prefix}multi_page_form_urls WHERE form_id = $form_id");

  // if this is a multi-page form, add the list of pages in the form
  if ($is_multi_page_form == "yes")
  {
    $num_pages_in_multi_page_form = $infohash["num_pages_in_multi_page_form"];

    for ($page_num=2; $page_num<=$num_pages_in_multi_page_form; $page_num++)
    {
      $form_url = isset($infohash["form_url_{$page_num}"]) ? $infohash["form_url_{$page_num}"] : "";

      if (empty($form_url))
        continue;

      mysql_query("INSERT INTO {$g_table_prefix}multi_page_form_urls (form_id, form_url, page_num) VALUES ($form_id, '$form_url', $page_num)");
    }
  }

  extract(ft_process_hooks("end", compact("infohash", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * This function is called on Step 3 of the Add Form process and updates the order, display,
 * display name, field size and "pass on" value.
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the step 3 add form page.
 */
function ft_set_form_database_settings($infohash, $form_id)
{
  global $g_table_prefix, $g_debug;

  $infohash = ft_sanitize($infohash);
  $field_info = array();

  extract(ft_process_hooks("start", compact("infohash", "form_id"), array("infohash")), EXTR_OVERWRITE);

  // loop through $infohash and for each field_X_order values, store the various values
  while (list($key, $val) = each($infohash))
  {
    // find the field id
    preg_match("/^field_(\d+)$/", $key, $match);

    if (!empty($match[1]))
    {
      $field_id = $match[1];

      // field type (size)
      $field_size = "";
      if (isset($infohash["field_{$field_id}_size"]))
        $field_size = $infohash["field_{$field_id}_size"];

      // display name
      $field_display_name = "";
      if (isset($infohash["field_{$field_id}_display_name"]))
        $field_display_name = $infohash["field_{$field_id}_display_name"];

      $field_info[] = array($field_id, $field_size, $field_display_name);
    }
  }
  reset($infohash);

  foreach ($field_info as $field)
  {
    $query = "
      UPDATE {$g_table_prefix}form_fields
      SET    field_size = '{$field[1]}',
             field_title = '{$field[2]}'
      WHERE  field_id = {$field[0]}
             ";
   mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());
  }
}


/**
 * Called on step 5 of the Add Form process. It processes the Mass Smart Filled field values, add / updates the
 * appropriate field option groups etc.
 *
 * @param integer $form_id
 */
function ft_set_form_field_types($form_id, $info)
{
  global $g_table_prefix;

  extract(ft_process_hooks("start", compact("info", "form_id"), array("info")), EXTR_OVERWRITE);

  // set a 5 minute maximum execution time for this request
  @set_time_limit(300);

  $info = ft_sanitize($info);
  $form_fields = ft_get_form_fields($form_id);

  // update the field types
  $field_option_groups = array();
  foreach ($form_fields as $field_info)
  {
    $field_id = $field_info["field_id"];

    // update all the field types. If it's not passed along, default it to a textbox
    $field_type = "textbox";
    if (isset($info["field_{$field_id}_type"]))
      $field_type = $info["field_{$field_id}_type"];

    // update the database - but ONLY for non system fields!
    mysql_query("
      UPDATE {$g_table_prefix}form_fields
      SET    field_type = '$field_type'
      WHERE  field_id = $field_id AND
             field_type != 'system'
        ");

    if ($field_type == "radio-buttons" || $field_type == "checkboxes")
    {
      $orientation = (isset($info["field_{$field_id}_orientation"])) ? $info["field_{$field_id}_orientation"] : "horizontal";
      $num_options = (isset($info["field_{$field_id}_num_options"])) ? $info["field_{$field_id}_num_options"] : 0;

      $options = array();
      for ($i=1; $i<=$num_options; $i++)
        $options[] = array("value" => $info["field_{$field_id}_opt{$i}_val"], "text" => $info["field_{$field_id}_opt{$i}_txt"]);

      $field_option_groups[$field_id] = array(
        "group_name"  => $field_info["field_title"],
        "orientation" => $orientation,
        "options"     => $options
      );
    }

    if ($field_type == "select" || $field_type == "multi-select")
    {
      $num_options = (isset($info["field_{$field_id}_num_options"])) ? $info["field_{$field_id}_num_options"] : 0;

      $options = array();
      for ($i=1; $i<=$num_options; $i++)
        $options[] = array("value" => $info["field_{$field_id}_opt{$i}_val"], "text" => $info["field_{$field_id}_opt{$i}_txt"]);

      $field_option_groups[$field_id] = array(
        "group_name"  => $field_info["field_title"],
        "options"     => $options
      );
    }
  }

  // finally, if there was an option group defined for this field, add the info!
  if (!empty($field_option_groups))
  {
    while (list($field_id, $option_group_info) = each($field_option_groups))
    {
      $group_id = ft_create_unique_option_group($form_id, $option_group_info);

      if (is_numeric($group_id))
      {
        mysql_query("
          UPDATE {$g_table_prefix}form_fields
          SET    field_group_id = $group_id
          WHERE  field_id = $field_id
            ") or die(mysql_error());
      }
    }
  }
}


/**
 * "Uninitializes" a form, letting the user to resend the test submission.
 *
 * @param integer $form_id The unique form ID
 */
function ft_uninitialize_form($form_id)
{
  global $g_table_prefix;

  mysql_query("
    UPDATE  {$g_table_prefix}forms
    SET     is_initialized = 'no'
    WHERE   form_id = $form_id
      ");
}


/**
 * Called by administrators; updates the content stored on the "Main" tab in the Edit Form pages.
 *
 * @param integer $infohash A hash containing the contents of the Edit Form Main tab.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_form_main_tab($infohash, $form_id)
{
  global $g_table_prefix, $LANG;

  $infohash = ft_sanitize($infohash);

  extract(ft_process_hooks("start", compact("infohash", "form_id"), array("infohash")), EXTR_OVERWRITE);

  $success = true;
  $message = $LANG["notify_form_updated"];

  // check required POST fields
  $rules = array();
  $rules[] = "required,form_name,{$LANG["validation_no_form_name"]}";
  $rules[] = "required,form_url,{$LANG["validation_no_form_url"]}";
  $rules[] = "required,edit_submission_page_label,{$LANG["validation_no_edit_submission_page_label"]}";
  $errors = validate_fields($infohash, $rules);

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array ($success, $message, "");
  }

  $client_ids      = isset($infohash["selected_client_ids"]) ? $infohash["selected_client_ids"] : array();
  $is_multi_page_form = isset($infohash["is_multi_page_form"]) ? "yes" : "no";
  $access_type     = $infohash["access_type"];
  $form_name       = $infohash["form_name"];
  $form_url        = $infohash["form_url"];
  $redirect_url    = $infohash["redirect_url"];
  $edit_submission_page_label = $infohash["edit_submission_page_label"];
  $auto_delete_submission_files = $infohash["auto_delete_submission_files"];
  $submission_strip_tags = $infohash["submission_strip_tags"];

  $is_active = "";
  if (!empty($infohash["active"]))
    $is_active = "is_active = '{$infohash['active']}',";

  $query = "
    UPDATE {$g_table_prefix}forms
    SET    $is_active
           is_multi_page_form = '$is_multi_page_form',
           access_type = '$access_type',
           form_url = '$form_url',
           form_name = '$form_name',
           redirect_url = '$redirect_url',
           auto_delete_submission_files ='$auto_delete_submission_files',
           submission_strip_tags = '$submission_strip_tags',
           edit_submission_page_label = '$edit_submission_page_label'
    WHERE  form_id = $form_id
           ";

  $result = mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  // finally, update the list of clients associated with this form
  mysql_query("DELETE FROM {$g_table_prefix}client_forms WHERE form_id = $form_id");
  foreach ($client_ids as $client_id)
  {
    $query = mysql_query("
       INSERT INTO {$g_table_prefix}client_forms (account_id, form_id)
       VALUES  ($client_id, $form_id)
         ");
  }

  // since the client list may have just changed, do a little cleanup on the database data
  switch ($access_type)
  {
    // no changes needed!
    case "public":
      break;

    // delete all client_view, client_form, public_form_omit_list, and public_view_omit_list entries concerning this form &
    // it's Views. Since only the administrator can see the form, no client can see any of it's sub-parts
    case "admin":
      mysql_query("DELETE FROM {$g_table_prefix}client_forms WHERE form_id = $form_id");
      mysql_query("DELETE FROM {$g_table_prefix}public_form_omit_list WHERE form_id = $form_id");

      $view_ids = ft_get_view_ids($form_id);
      foreach ($view_ids as $view_id)
      {
        mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
        mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
      }
      break;

    // remove any records from the client_view and public_view_omit_list tables concerned clients NOT associated
    // with this form.
    case "private":
      mysql_query("DELETE FROM {$g_table_prefix}public_form_omit_list WHERE form_id = $form_id");

      $client_clauses = array();
      foreach ($client_ids as $client_id)
        $client_clauses[] = "account_id != $client_id";

      // there WERE clients associated with this form. Delete the ones that AREN'T associated
      if (!empty($client_clauses))
      {
        $where_clause = "WHERE " . join(" AND ", $client_clauses);

        mysql_query("DELETE FROM {$g_table_prefix}client_views $where_clause");
        mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list $where_clause");
      }

      // for some reason, the administrator has assigned NO clients to this private form. So, delete all clients
      // associated with the Views
      else
      {
        $view_ids = ft_get_view_ids($form_id);
        foreach ($view_ids as $view_id)
        {
          mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
          mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
        }
      }
      break;
  }

  // update the multi-page form URLs
  mysql_query("DELETE FROM {$g_table_prefix}multi_page_form_urls WHERE form_id = $form_id");

  // if this is a multi-page form, add the list of pages in the form
  if ($is_multi_page_form == "yes")
  {
    $num_pages_in_multi_page_form = $infohash["num_pages_in_multi_page_form"];
    $page_num = 2;

    for ($i=1; $i<=$num_pages_in_multi_page_form; $i++)
    {
      $form_url = isset($infohash["form_url_{$i}"]) ? $infohash["form_url_{$i}"] : "";

      if (empty($form_url))
        continue;

      mysql_query("INSERT INTO {$g_table_prefix}multi_page_form_urls (form_id, form_url, page_num) VALUES ($form_id, '$form_url', $page_num)");
      $page_num++;
    }
  }

  extract(ft_process_hooks("end", compact("infohash", "form_id", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Called by administrators; updates the content stored on the "Fields" tab in the Edit Form
 * pages. Note: it does NOT update the
 *
 * @param integer $form_id the unique form ID
 * @param array $infohash a hash containing the contents of the Edit Form Display tab
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_form_fields_tab($form_id, $infohash)
{
  global $g_table_prefix, $g_root_url, $g_root_dir, $g_debug, $LANG;

  $infohash = ft_sanitize($infohash);
  extract(ft_process_hooks("start", compact("infohash", "form_id"), array("infohash")), EXTR_OVERWRITE);

  $success = true;
  $message = "";

  // loop through $infohash and for each field_X_order values, store the various values
  $field_info = array();

  // stores those fields whose field types just changed. This is used to remove any now-redundant extended
  // field settings
  $changed_field_types_field_ids = array();

  while (list($key, $val) = each($infohash))
  {
    // find the field id
    preg_match("/^field_(\d+)$/", $key, $match);

    if (!empty($match[1]))
    {
      $field_id = $match[1];

      $display_name    = (isset($infohash["field_{$field_id}_display_name"])) ? $infohash["field_{$field_id}_display_name"] : "";
      $form_field_name = (isset($infohash["field_{$field_id}_name"])) ? $infohash["field_{$field_id}_name"] : "";
      $field_type      = $infohash["field_{$field_id}_type"];
      $include_on_redirect = (isset($infohash["field_{$field_id}_include_on_redirect"])) ? "yes" : "no";

      if ($field_type != $infohash["old_field_{$field_id}_type"])
        $changed_field_types_field_ids[] = $field_id;

      $field_info[] = array(
        "field_id" => $field_id,
        "display_name" => $display_name,
        "form_field_name" => $form_field_name,
        "field_type" => $field_type,
        "include_on_redirect" => $include_on_redirect
      );
    }
  }
  reset($infohash);

  // delete any extended fields settings for those fields who's field type just changed
  foreach ($changed_field_types_field_ids as $field_id)
    ft_delete_extended_field_settings($field_id);

  // now update the fields
  foreach ($field_info as $field)
  {
    $field_id     = $field["field_id"];
    $display_name = $field["display_name"];
    $field_name   = $field["form_field_name"];
    $field_type   = $field["field_type"];
    $include_on_redirect = $field["include_on_redirect"];

    if ($field_type == "system")
    {
      $query = "
        UPDATE {$g_table_prefix}form_fields
        SET    field_title = '$display_name',
               field_type  = '$field_type',
               include_on_redirect = '$include_on_redirect'
        WHERE  field_id = $field_id
                  ";
    }
    else
    {
      $query = "
        UPDATE {$g_table_prefix}form_fields
        SET    field_name = '$field_name',
               field_title = '$display_name',
               field_type  = '$field_type',
               include_on_redirect = '$include_on_redirect'
        WHERE  field_id = $field_id
                  ";
    }

    mysql_query($query)
      or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

    // if this is an image or file field, ensure that the field size in the database is a
    // TEXT and VARCHAR(255), respectively
    if ($field_type == "image" || $field_type == "file")
    {
      $field_info = ft_get_form_field($field_id);
      $col_name = $field_info["col_name"];
      $table    = "{$g_table_prefix}form_{$form_id}";

      if ($field_type == "image" && $field_info["field_size"] != "large")
      {
        // update the size in the database
        mysql_query("
          UPDATE {$g_table_prefix}form_fields
          SET    field_size = 'large'
          WHERE  field_id = $field_id
            ");

        // "physically" update the size of the field
        _ft_alter_table_column($table, $col_name, $col_name, "TEXT");
      }

      if ($field_type == "file" && $field_info["field_size"] != "medium")
      {
        // update the size in the database
        mysql_query("
          UPDATE {$g_table_prefix}form_fields
          SET    field_size = 'medium'
          WHERE  field_id = $field_id
            ");

        // "physically" update the size of the field
        _ft_alter_table_column($table, $col_name, $col_name, "VARCHAR(255)");
      }
    }
  }

  $success = true;
  $message = $LANG["notify_field_settings_updated"];

  extract(ft_process_hooks("end", compact("infohash", "form_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Called by administrators; updates the content stored on the Database tab in the Edit
 * Form pages. It updates the following info about the form fields:
 *           - include on redirect<br />
 *           - form field<br />
 *           - field size<br />
 *           - field type<br />
 *           - database column
 * @param integer $infohash A hash containing the contents of the Edit Form Database tab.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br />
 *               [1]: message string<br/>
 */
function ft_update_form_database_tab($infohash)
{
  global $g_debug, $g_table_prefix, $LANG;

  $form_id = $infohash["form_id"];
  $infohash = ft_sanitize($infohash);

  extract(ft_process_hooks("start", compact("infohash", "form_id"), array("infohash")), EXTR_OVERWRITE);

  // get the field IDs we're working with
  $field_ids = array();
  while (list($key, $val) = each($infohash))
  {
    // find the field id (field type is always required)
    preg_match("/^field_(\d+)_type$/", $key, $match);

    if (!empty($match[1]))
      $field_ids[] = $match[1];
  }
  reset($infohash);


  // now gather all the info about the fields
  $field_info = array();
  foreach ($field_ids as $field_id)
  {
    // is this field to be included on redirect or not?
    if (isset($infohash["field_{$field_id}_include_on_redirect"]) && !empty($infohash["field_{$field_id}_include_on_redirect"]))
      $include_on_redirect = "yes";
    else
      $include_on_redirect = "no";

    // field type (size)
    if (isset($infohash["field_{$field_id}_size"]))
      $field_size = $infohash["field_{$field_id}_size"];
    else
      $field_size = "";

    // field data type (string/number)
    if (isset($infohash["field_{$field_id}_data_type"]))
      $data_type = $infohash["field_{$field_id}_data_type"];
    else
      $data_type = "";

    // database column name
    if (isset($infohash["col_{$field_id}_name"]))
      $col_name = $infohash["col_{$field_id}_name"];
    else
      $col_name = "";

    // field type (system, etc) - this is passed in a hidden field
    $field_type = $infohash["field_{$field_id}_type"];

    $field_info[] = array("field_id" => $field_id,
                          "include_on_redirect" => $include_on_redirect,
                          "field_size" => $field_size,
                          "data_type" => $data_type,
                          "col_name" => $col_name,
                          "field_type" => $field_type);
  }
  reset($infohash);


  // if the form actually exists (i.e. the user isn't in the middle of setting it up!), update the form table
  $form_info = ft_get_form($form_id);

  // this keeps track of any database column changes, in case of error
  $db_col_changes = array();
  $db_col_change_hash = array(); // added later. Could use refactoring

  if ($form_info["is_complete"] == "yes")
  {
    // update each db column in turn
    foreach ($field_info as $field)
    {
      // ignore system fields
      if ($field["field_type"] == "system")
        continue;

      $old_field_info = ft_get_form_field($field["field_id"]);

      // if any physical aspect of the form (column name, field type) needs to be changed, change it
      if (($old_field_info['col_name'] != $field["col_name"]) || ($old_field_info['field_size'] != $field["field_size"]))
      {
      	$db_col_change_hash[$old_field_info['col_name']] = $field["col_name"];

        $new_field_size = "";
        switch ($field["field_size"])
        {
          case "tiny":       $new_field_size = "VARCHAR(5)";   break;
          case "small":      $new_field_size = "VARCHAR(20)";  break;
          case "medium":     $new_field_size = "VARCHAR(255)"; break;
          case "large":      $new_field_size = "TEXT";         break;
          case "very_large": $new_field_size = "MEDIUMTEXT";   break;
          default:           $new_field_size = "VARCHAR(255)"; break;
        }

        list ($is_success, $err_message) = _ft_alter_table_column("{$g_table_prefix}form_{$form_id}", $old_field_info["col_name"], $field["col_name"], $new_field_size);

        if ($is_success)
          $db_col_changes[$field["field_id"]] = $field["col_name"];


        // if there was a problem, return an error immediately
        else
        {
          // if there have already been successful database column name changes already made,
          // update the database. This prevents things getting out of whack
          if (!empty($db_col_changes))
          {
            while (list($field_id, $col_name) = each($db_col_changes))
            {
              $query = mysql_query("
                UPDATE {$g_table_prefix}form_fields
                SET    col_name = '$col_name'
                WHERE  field_id = $field_id
                          ");
            }
          }

          $success = false;
          $message = $LANG["validation_db_not_updated_invalid_input"];
          if ($g_debug) $message .= " \"$err_message\"";
          return array($success, $message);
        }
      }
    }
  }

  // update the form template table values
  foreach ($field_info as $field)
  {
    if ($field["field_type"] == "system")
    {
      $query = "
        UPDATE {$g_table_prefix}form_fields
        SET    include_on_redirect = '{$field["include_on_redirect"]}'
        WHERE  field_id = {$field["field_id"]}
                  ";
    }
    else
    {
      $query = "
        UPDATE {$g_table_prefix}form_fields
        SET    include_on_redirect = '{$field["include_on_redirect"]}',
               field_size = '{$field["field_size"]}',
               data_type  = '{$field["data_type"]}',
               col_name   = '{$field["col_name"]}'
        WHERE  field_id = {$field["field_id"]}
                  ";
    }

    mysql_query($query)
      or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());
  }

  // lastly, if any of the database column names just changed we need to update any View filters
  // that relied on them
  if (!empty($db_col_changes))
  {
    while (list($field_id, $col_name) = each($db_col_changes))
      ft_update_field_filters($field_id);
  }

  $success = true;
  $message = $LANG["notify_fields_updated"];
  extract(ft_process_hooks("end", compact("infohash", "form_id", "db_col_changes", "db_col_change_hash"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Simple helper function to examine a form and see if it contains a file upload field. *** Note, this
 * means either a regular file or an image through the Image Manager module.
 *
 * @param integer $form_id
 * @return boolean
 */
function ft_check_form_has_file_upload_field($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT count(*) as c
    FROM   {$g_table_prefix}form_fields
    WHERE  form_id = $form_id AND
           (field_type = 'file' OR field_type = 'image')
      ");

  $result = mysql_fetch_assoc($query);
  $count = $result["c"];

  return $count > 0;
}


/**
 * This checks to see the a form exists in the database. It's just used to confirm a form ID is valid.
 *
 * @param integer $form_id
 * @param boolean $allow_incompleted_forms an optional value to still return TRUE for incomplete forms
 * @return boolean
 */
function ft_check_form_exists($form_id, $allow_incompleted_forms = false)
{
  global $g_table_prefix;

  $query = mysql_query("SELECT * FROM {$g_table_prefix}forms WHERE form_id = $form_id");

  $is_valid_form_id = false;
  if ($query && mysql_num_rows($query) > 0)
  {
    $info = mysql_fetch_assoc($query);

    if ((!empty($info) && $allow_incompleted_forms) ||
        ($info["is_initialized"] == "yes" && $info["is_complete"] == "yes"))
      $is_valid_form_id = true;
  }

  return $is_valid_form_id;
}


// ---------------------------------------- helpers -----------------------------------------------


/**
 * Helper function to add a new data column the end of a table.
 *
 * @param string $table The name of the table to alter.
 * @param string $col_name The new column name.
 * @param string $col_type The new column data type.
 * @return array Array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function _ft_add_table_column($table, $col_name, $col_type)
{
  $success = true;
  $message = "";

  $result = mysql_query("
    ALTER TABLE $table
    ADD         $col_name $col_type
           ");

  if (!$result)
  {
    $success = false;
    $message = mysql_error();
  }

  return array($success, $message);
}


/**
 * Helper function to change the name and type of an existing MySQL table.
 *
 * @param string $table The name of the table to alter.
 * @param string $old_col_name The old column name.
 * @param string $new_col_name The new column name.
 * @param string $col_type The new column data type.
 * @return array Array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function _ft_alter_table_column($table, $old_col_name, $new_col_name, $col_type)
{
  global $g_table_prefix;

  $success = true;
  $message = "";

  $result = mysql_query("
    ALTER TABLE $table
    CHANGE      $old_col_name $new_col_name $col_type
           ");

  if (!$result)
  {
    $success = false;
    $message = mysql_error();
  }

  return array($success, $message);
}


/**
 * Caches the total number of (finalized) submissions in a particular form - or all forms -
 * in the $_SESSION["ft"]["form_{$form_id}_num_submissions"] key. That value is used on the administrators
 * main Forms page to list the form submission count. It also stores the earliest form submission date, which
 * isn't used directly, but it's copied by the _ft_cache_view_stats function for all Views that don't have a
 * filter.
 *
 * @param integer $form_id
 */
function _ft_cache_form_stats($form_id = "")
{
  global $g_table_prefix;

  $where_clause = "";
  if (!empty($form_id))
    $where_clause = "AND form_id = $form_id";

  $query = mysql_query("
    SELECT form_id
    FROM   {$g_table_prefix}forms
    WHERE  is_complete = 'yes'
    $where_clause
           ");

  // loop through all forms, extract the submission count and first submission date
  while ($form_info = mysql_fetch_assoc($query))
  {
    $form_id = $form_info["form_id"];

    $count_query = mysql_query("
      SELECT count(*) as c
      FROM   {$g_table_prefix}form_$form_id
      WHERE  is_finalized = 'yes'
        ")
        or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__, mysql_error());

    $info = mysql_fetch_assoc($count_query);
    $_SESSION["ft"]["form_{$form_id}_num_submissions"] = $info["c"];

    $first_date_query = mysql_query("
      SELECT submission_date
      FROM   {$g_table_prefix}form_$form_id
      WHERE  is_finalized = 'yes'
      ORDER BY submission_date ASC
      LIMIT 1
        ") or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__, mysql_error());

    $info = mysql_fetch_assoc($first_date_query);
    $_SESSION["ft"]["form_{$form_id}_first_submission_date"] = $info["submission_date"];
  }
}


/**
 * Retrieves information about all forms associated with a particular account. Since 2.0.0
 * this function lets you SEARCH the forms, but it still returns all results - not a page worth
 * (the reason being: the vast majority of people use Form Tools for a small number of forms < 100)
 * so the form tables are displaying via DHTML, with all results actually returned and hidden in the
 * page ready to be displayed.
 *
 * @param integer $account_id if blank, return all finalized forms, otherwise returns the forms
 *              associated with this particular client.
 * @param boolean $is_admin Whether or not the user retrieving the data is an administrator or not.
 *              If it is, ALL forms are retrieved - even those that aren't yet finalized.
 * @param array $search_criteria an optional hash with any of the following keys:
 *                 "status"  - (string) online / offline
 *                 "keyword" - (any string)
 *                 "order"   - (string) form_id-DESC, form_id-ASC, form_name-DESC, form-name-ASC,
 *                             status-DESC, status-ASC
 * @return array returns an array of form hashes
 */
function ft_search_forms($account_id = "", $is_admin = false, $search_criteria = array())
{
  global $g_table_prefix;

  extract(ft_process_hooks("start", compact("account_id", "is_admin", "search_criteria"), array("search_criteria")), EXTR_OVERWRITE);

  if (!isset($search_criteria["order"]))
    $search_criteria["order"] = "form_id-DESC";

  // verbose, but at least it prevents any invalid sorting...
  $order_clause = "";
  switch ($search_criteria["order"])
  {
    case "form_id-DESC":
      $order_clause = "form_id DESC";
      break;
    case "form_id-ASC":
      $order_clause = "form_id ASC";
      break;
    case "form_name-ASC":
      $order_clause = "form_name ASC";
      break;
    case "form_name-DESC":
      $order_clause = "form_name DESC";
      break;
    case "status-DESC":
      $order_clause = "(is_initialized = 'no' AND is_complete = 'no'), is_active = 'no', is_active = 'yes'";
      break;
    case "status-ASC":
      $order_clause = "is_active = 'yes', is_active = 'no', (is_initialized = 'no' AND is_complete = 'no')";
      break;

    default:
      $order_clause = "form_id DESC";
      break;
  }
  $order_clause = "ORDER BY $order_clause";

  $status_clause = "";
  if (isset($search_criteria["status"]))
  {
    switch ($search_criteria["status"])
    {
      case "online":
        $status_clause = "is_active = 'yes' ";
        break;
      case "offline":
        $status_clause = "(is_active = 'no' AND is_complete = 'yes')";
        break;
      case "incomplete":
        $status_clause = "(is_initialized = 'no' OR is_complete = 'no')";
        break;
      default:
        $status_clause = "";
        break;
    }
  }

  $keyword_clause = "";
  if (isset($search_criteria["keyword"]) && !empty($search_criteria["keyword"]))
  {
    $search_criteria["keyword"] = trim($search_criteria["keyword"]);
    $string = ft_sanitize($search_criteria["keyword"]);
    $fields = array("form_name", "form_url", "redirect_url", "form_id");

    $clauses = array();
    foreach ($fields as $field)
      $clauses[] = "$field LIKE '%$string%'";

    $keyword_clause = join(" OR ", $clauses);
  }

  // if a user ID has been specified, find out which forms have been assigned to this client
  // so we can limit our query
  $form_clause = "";

  // this var is populated ONLY for searches on a particular client account. It stores those public forms on
  // which the client is on the Omit List. This value is used at the end of this function to trim the results
  // returned to NOT include those forms
  $client_omitted_from_public_forms = array();

  if (!empty($account_id))
  {
    // a bit weird, but necessary. This adds a special clause to the query so that when it searches for a
    // particular account, it also (a) returns all public forms and (b) only returns those forms that are
    // completed. This is because incomplete forms are still set to access_type = "public".
    // Note: this does NOT take into account the public_form_omit_list - that's handled afterwards, to
    // keep the SQL as simple as possible
    $is_public_clause = "(access_type = 'public')";
    $is_setup_clause = "is_complete = 'yes' AND is_initialized = 'yes'";

    // first, grab all those forms that are explicitly associated with this client
    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}client_forms
      WHERE  account_id = $account_id
        ");

    $form_clauses = array();
    while ($result = mysql_fetch_assoc($query))
      $form_clauses[] = "form_id = {$result['form_id']}";

    if (count($form_clauses) > 1)
      $form_clause = "(((" . join(" OR ", $form_clauses) . ") OR $is_public_clause) AND ($is_setup_clause))";
    else
      $form_clause = isset($form_clauses[0]) ? "(({$form_clauses[0]} OR $is_public_clause) AND ($is_setup_clause))" :
        "($is_public_clause AND ($is_setup_clause))";

    // see if this client account has been omitted from any public forms. If it is, this will be used to
    // filter the results
    $query = mysql_query("SELECT form_id FROM {$g_table_prefix}public_form_omit_list WHERE account_id = $account_id");
    while ($row = mysql_fetch_assoc($query))
      $client_omitted_from_public_forms[] = $row["form_id"];
  }

  $admin_clause = (!$is_admin) ? "is_complete = 'yes' AND is_initialized = 'yes'" : "";


  // add up the where clauses
  $where_clauses = array();
  if (!empty($status_clause))  $where_clauses[] = $status_clause;
  if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";
  if (!empty($form_clause))    $where_clauses[] = $form_clause;
  if (!empty($admin_clause))   $where_clauses[] = $admin_clause;

  if (!empty($where_clauses))
    $where_clause = "WHERE " . join(" AND ", $where_clauses);
  else
    $where_clause = "";

  // get the form IDs. All info about the forms will be retrieved in a separate query
  $form_query = mysql_query("
    SELECT form_id
    FROM   {$g_table_prefix}forms
    $where_clause
    $order_clause
           ");

  // now retrieve the basic info (id, first and last name) about each client assigned to this form. This
  // takes into account whether it's a public form or not and if so, what clients are in the omit list
  $form_info = array();
  while ($row = mysql_fetch_assoc($form_query))
  {
    $form_id = $row["form_id"];

    // if this was a search for a single client, filter out those public forms which include their account ID
    // on the form omit list
    if (!empty($client_omitted_from_public_forms) && in_array($form_id, $client_omitted_from_public_forms))
      continue;

    $form_info[] = ft_get_form($form_id);
  }

  extract(ft_process_hooks("end", compact("account_id", "is_admin", "search_criteria", "form_info"), array("form_info")), EXTR_OVERWRITE);

  return $form_info;
}


/**
 * Returns an array of account IDs of those clients in the omit list for this public form.
 *
 * @param integer $form_id
 * @return array
 */
function ft_get_public_form_omit_list($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT account_id
    FROM   {$g_table_prefix}public_form_omit_list
    WHERE form_id = $form_id
      ");

  $client_ids = array();
  while ($row = mysql_fetch_assoc($query))
    $client_ids[] = $row["account_id"];

  extract(ft_process_hooks("end", compact("clients_id", "form_id"), array("client_ids")), EXTR_OVERWRITE);

  return $client_ids;
}


/**
 * Called by the administrator only. Updates the list of clients on a public form's omit list.
 *
 * @param array $info
 * @param integer $form_id
 * @return array [0] T/F, [1] message
 */
function ft_update_public_form_omit_list($info, $form_id)
{
  global $g_table_prefix, $LANG;

  mysql_query("DELETE FROM {$g_table_prefix}public_form_omit_list WHERE form_id = $form_id");

  $client_ids = (isset($info["selected_client_ids"])) ? $info["selected_client_ids"] : array();
  foreach ($client_ids as $account_id)
    mysql_query("INSERT INTO {$g_table_prefix}public_form_omit_list (form_id, account_id) VALUES ($form_id, $account_id)");

  return array(true, $LANG["notify_public_form_omit_list_updated"]);
}

