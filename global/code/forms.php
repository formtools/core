<?php

/**
 * This file contains all functions relating to managing forms within Form Tools.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Forms
 */


// -------------------------------------------------------------------------------------------------



/**
 * Called by client accounts, allowing them to update the num_submissions_per_page and auto email
 * settings.
 *
 * @param array $infohash a hash containing the various form values to update.
 */
function ft_client_update_form_settings($infohash)
{
  global $g_table_prefix, $LANG;

  extract(ft_process_hook_calls("start", compact("infohash"), array("infohash")), EXTR_OVERWRITE);

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

  extract(ft_process_hook_calls("end", compact("infohash", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Added in 2.1.0, this creates an Internal form with a handful of custom settings.
 *
 * @param $info the POST request containing the form name, number of fields and access type.
 */
function ft_create_internal_form($request)
{
  global $LANG, $g_table_prefix;

  $rules = array();
  $rules[] = "required,form_name,{$LANG["validation_no_form_name"]}";
  $rules[] = "required,num_fields,{$LANG["validation_no_num_form_fields"]}";
  $rules[] = "digits_only,num_fields,{$LANG["validation_invalid_num_form_fields"]}";
  $rules[] = "required,access_type,{$LANG["validation_no_access_type"]}";

  $errors = validate_fields($request, $rules);
  if (!empty($errors))
  {
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array(false, $message);
  }

  $info = ft_sanitize($request);
  $config = array(
    "form_type"    => "internal",
    "form_name"    => $info["form_name"],
    "access_type"  => $info["access_type"]
  );

  // set up the entry for the form
  list($success, $message, $new_form_id) = ft_setup_form($config);

  $form_data = array(
    "form_tools_form_id" => $new_form_id,
    "form_tools_display_notification_page" => false
  );

  for ($i=1; $i<=$info["num_fields"]; $i++)
  {
    $form_data["field{$i}"] = $i;
  }
  ft_initialize_form($form_data);

  $infohash = array();
  $form_fields = ft_get_form_fields($new_form_id);

  $order = 1;

  // if the user just added a form with a lot of fields (over 50), the database row size will be too
  // great. Varchar fields (which with utf-8 equates to 1220 bytes) in a table can have a combined row
  // size of 65,535 bytes, so 53 is the max. The client-side validation limits the number of fields to
  // 1000. Any more will throw an error.
  $field_size_clause = ($info["num_fields"] > 50) ? ", field_size = 'small'" : "";

  $field_name_prefix = ft_sanitize($LANG["word_field"]);
  foreach ($form_fields as $field_info)
  {
    if (preg_match("/field(\d+)/", $field_info["field_name"], $matches))
    {
      $field_id  = $field_info["field_id"];
      mysql_query("
        UPDATE {$g_table_prefix}form_fields
        SET    field_title = '$field_name_prefix $order',
              col_name = 'col_$order'
              $field_size_clause
        WHERE  field_id = $field_id
      ");
      $order++;
    }
  }

  ft_finalize_form($new_form_id);

  // if the form has an access type of "private" add whatever client accounts the user selected
  if ($info["access_type"] == "private")
  {
    $selected_client_ids = $info["selected_client_ids"];
    $queries = array();
    foreach ($selected_client_ids as $client_id)
      $queries[] = "($client_id, $new_form_id)";

    if (!empty($queries))
    {
      $insert_values = implode(",", $queries);
      mysql_query("
        INSERT INTO {$g_table_prefix}client_forms (account_id, form_id)
        VALUES $insert_values
          ");
    }
  }

  return array(true, $LANG["notify_internal_form_created"], $new_form_id);
}


/**
 * Completely removes a form from the database. This includes deleting all form fields, emails, Views,
 * View fields, View tabs, View filters, client-form, client-view and public omit list (form & View),
 * and anything else !
 *
 * It also includes an optional parameter to remove all files that were uploaded through file fields in the
 * form; defaulted to FALSE.
 *
 * @param integer $form_id the unique form ID
 * @param boolean $remove_associated_files A boolean indicating whether or not all files that were
 *              uploaded via file fields in this form should be removed as well.
 */
function ft_delete_form($form_id, $remove_associated_files = false)
{
  global $g_table_prefix;

  extract(ft_process_hook_calls("start", compact("form_id"), array()), EXTR_OVERWRITE);
  $form_fields = ft_get_form_fields($form_id, array("include_field_type_info" => true));

  $success = true;
  $message = "";

  $file_delete_problems = array();
  if ($remove_associated_files)
  {
    $submission_id_query = mysql_query("SELECT submission_id FROM {$g_table_prefix}form_{$form_id}");
    $file_fields_to_delete = array();
    while ($row = mysql_fetch_assoc($submission_id_query))
    {
      $submission_id = $row["submission_id"];

      foreach ($form_fields as $form_field_info)
      {
        if ($form_field_info["is_file_field"] == "no")
          continue;

        // I really don't like this... what should be done is do a SINGLE query after this loop is complete
        // to return a map of field_id to values. That would then update $file_fields_to_delete
        // with a fraction of the cost
        $submission_info = ft_get_submission_info($form_id, $submission_id);
        $filename = $submission_info[$form_field_info["col_name"]];

        // if no filename was stored, it was empty - just continue
        if (empty($filename))
          continue;

        $file_fields_to_delete[] = array(
          "submission_id" => $submission_id,
          "field_id"      => $form_field_info["field_id"],
          "field_type_id" => $field_type_id,
          "filename"      => $filename
        );
      }
    }

    if (!empty($file_fields_to_delete))
      list($success, $file_delete_problems) = ft_delete_submission_files($form_id, $file_fields_to_delete, "ft_delete_form");
  }

  // remove the table
  $query = "DROP TABLE IF EXISTS {$g_table_prefix}form_$form_id";
  mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  // remove any reference to the form in form_fields
  mysql_query("DELETE FROM {$g_table_prefix}form_fields WHERE form_id = $form_id")
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());

  // remove any reference to the form in forms table
  mysql_query("DELETE FROM {$g_table_prefix}forms WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}client_forms WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}form_export_templates WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}form_email_fields WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}public_form_omit_list WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}multi_page_form_urls WHERE form_id = $form_id");
  mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_type = 'form_{$form_id}_view_group'");

  // delete all email templates for the form
  $email_templates = ft_get_email_template_list($form_id);
  foreach ($email_templates as $email_template_info)
  {
    ft_delete_email_template($email_template_info["email_id"]);
  }

  // delete all form Views
  $views_result = mysql_query("SELECT view_id FROM {$g_table_prefix}views WHERE form_id = $form_id");
  while ($info = mysql_fetch_assoc($views_result))
  {
    ft_delete_view($info["view_id"]);
  }

  // remove any field settings
  foreach ($form_fields as $field_info)
  {
    $field_id = $field_info["field_id"];
    mysql_query("DELETE FROM {$g_table_prefix}field_settings WHERE field_id = $field_id");
  }

  // as with many things in the script, potentially we need to return a vast range of information from this last function. But
  // we'l limit
  if (!$success)
    $message = $file_delete_problems;

  return array($success, $message);
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
  global $g_table_prefix, $g_field_sizes, $g_db_table_charset, $LANG;

  $form_fields = ft_get_form_fields($form_id);
  $query = "
    CREATE TABLE {$g_table_prefix}form_$form_id (
      submission_id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
      PRIMARY KEY(submission_id),\n";

  foreach ($form_fields as $field)
  {
    // don't add system fields (submission ID, Date, Last Modified & IP address)
    if ($field["is_system_field"] == "yes")
      continue;

    $sql_size = $g_field_sizes[$field["field_size"]]["sql"];
    $query .= "{$field['col_name']} $sql_size,\n";
  }

  $query .= "submission_date DATETIME NOT NULL,
            last_modified_date DATETIME NOT NULL,
            ip_address VARCHAR(15),
            is_finalized ENUM('yes','no') default 'yes')
            DEFAULT CHARSET=$g_db_table_charset";

  $result = mysql_query($query);
  if (!$result)
  {
  	return array(
  	  "success" => "0",
  	  "message" => $LANG["notify_create_form_failure"],
  	  "sql_error" => mysql_error()
  	);
  }

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
  $result = mysql_query($query);
  if (!$result)
  {
  	return array(
  	  "success"   => "0",
  	  "sql_error" => mysql_error()
  	);
  }

  // finally, add the default View
  ft_add_default_view($form_id);

  extract(ft_process_hook_calls("end", compact("form_id"), array()), EXTR_OVERWRITE);

  return array(
  	"success" => 1,
  	"message" => ""
  );
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
  global $g_table_prefix, $g_root_dir, $g_multi_val_delimiter, $LANG, $g_default_datetime_format;

  $textbox_field_type_id = ft_get_field_type_id_by_identifier("textbox");
  $date_field_type_id    = ft_get_field_type_id_by_identifier("date");
  $date_field_type_datetime_setting_id = ft_get_field_type_setting_id_by_identifier($date_field_type_id, "display_format");
  $date_field_type_timezone_setting_id = ft_get_field_type_setting_id_by_identifier($date_field_type_id, "apply_timezone_offset");

  $display_notification_page = isset($form_data["form_tools_display_notification_page"]) ?
    $form_data["form_tools_display_notification_page"] : true;

  // escape the incoming values
  $form_data = ft_sanitize($form_data);
  $form_id = $form_data["form_tools_form_id"];

  // check the form ID is valid
  if (!ft_check_form_exists($form_id, true))
  {
    $page_vars = array("message_type" => "error", "error_code" => 100);
    ft_display_page("error.tpl", $page_vars);
    exit;
  }

  $form_info = ft_get_form($form_id, true);

  // if this form has already been completed, exit with an error message
  if ($form_info["is_complete"] == "yes")
  {
    $page_vars = array("message_type" => "error", "error_code" => 101);
    ft_display_page("error.tpl", $page_vars);
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
  unset($form_data["form_tools_display_notification_page"]);

  $order = 1;

  // add the submission ID system field ("ID" can be changed by the user via the interface)
  $query = mysql_query("
    INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_test_value, field_type_id, is_system_field,
        data_type, field_title, col_name, list_order, is_new_sort_group)
    VALUES ($form_id, 'core__submission_id', '', $textbox_field_type_id, 'yes', 'number', '{$LANG["word_id"]}',
        'submission_id', '$order', 'yes')
  ");

  if (!$query)
  {
    $page_vars = array("message_type" => "error", "error_code" => 102, "error_type" => "system",
      "debugging" => "<b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, failed query: " . mysql_error());
    ft_display_page("error.tpl", $page_vars);
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
      INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_type_id, is_system_field,
        field_test_value, data_type, list_order, is_new_sort_group)
      VALUES ($form_id, '$key', 1, 'no', '$value', 'string', '$order', 'yes')
                ");

    if (!$query)
    {
    	$page_vars = array("message_type" => "error", "error_code" => 103, "error_type" => "system",
        "debugging" => "<b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, failed query: " . mysql_error());
      ft_display_page("error.tpl", $page_vars);
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
      INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_type_id, is_system_field,
        field_test_value, data_type, list_order)
      VALUES ($form_id, '$key', 8, 'no', '{$LANG["word_file_b_uc"]}', 'string', '$order')
                ");

    if (!$query)
    {
      $page_vars = array("message_type" => "error", "error_code" => 104, "error_type" => "system",
        "debugging" => "<b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, failed query: " . mysql_error());
      ft_display_page("error.tpl", $page_vars);
      exit;
    }

    $order++;
  }

  // add the Submission Date, Last Modified Date and IP Address system fields. For the date fields, we also
  // add in a custom formatting to display the full datetime. This is because the default date formatting is date only -
  // I think that's probably going to be more useful as a default than a datetime - hence the extra work here

  // submission date
  $order1 = $order;
  $query = mysql_query("
    INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_test_value, field_type_id, is_system_field,
      field_title, data_type, col_name, list_order)
    VALUES ($form_id, 'core__submission_date', '', $date_field_type_id, 'yes', '{$LANG["word_date"]}',
      'date', 'submission_date', '$order1')
      ");
  $submission_date_field_id = mysql_insert_id();
  mysql_query("
    INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
    VALUES ($submission_date_field_id, $date_field_type_datetime_setting_id, '$g_default_datetime_format')
      ");
  mysql_query("
    INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
    VALUES ($submission_date_field_id, $date_field_type_timezone_setting_id, 'yes')
      ");

  // last modified date
  $order2 = $order+1;
  $query = mysql_query("
    INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_test_value, field_type_id, is_system_field,
      field_title, data_type, col_name, list_order)
    VALUES ($form_id, 'core__last_modified', '', $date_field_type_id, 'yes', '{$LANG["phrase_last_modified"]}',
      'date', 'last_modified_date', '$order2')
      ");
  $last_modified_date_field_id = mysql_insert_id();
  mysql_query("
    INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
    VALUES ($last_modified_date_field_id, $date_field_type_datetime_setting_id, '$g_default_datetime_format')
      ");
  mysql_query("
    INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
    VALUES ($last_modified_date_field_id, $date_field_type_timezone_setting_id, 'yes')
      ");

  // ip address
  $order3 = $order+2;
  $query = mysql_query("
    INSERT INTO {$g_table_prefix}form_fields (form_id, field_name, field_test_value, field_type_id, is_system_field,
      field_title, data_type, col_name, list_order)
    VALUES ($form_id, 'core__ip_address', '', $textbox_field_type_id, 'yes', '{$LANG["phrase_ip_address"]}',
      'number', 'ip_address', '$order3')
      ");


  if (!$query)
  {
    $page_vars = array("message_type" => "error", "error_code" => 105, "error_type" => "system",
      "debugging" => "<b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, failed query: " . mysql_error());
    ft_display_page("error.tpl", $page_vars);
    exit;
  }

  // finally, set this form's "is_initialized" value to "yes", so the administrator can proceed to
  // the next step of the Add Form process.
  mysql_query("
    UPDATE  {$g_table_prefix}forms
    SET     is_initialized = 'yes'
    WHERE   form_id = $form_id
              ");

  // alert a "test submission complete" message. The only time this wouldn't be outputted would be
  // if this function is being called programmatically, like with the blank_form module
  if ($display_notification_page)
  {
    $page_vars = array();
    $page_vars["message"] = $LANG["processing_init_complete"];
    $page_vars["message_type"] = "notify";
    $page_vars["title"] = $LANG["phrase_test_submission_received"];
    ft_display_page("error.tpl", $page_vars);
    exit;
  }

}


/**
 * Retrieves all information about single form; all associated client information is stored in the
 * client_info key, as an array of hashes. Note: this function returns information about any form - complete or
 * incomplete.
 *
 * @param integer $form_id the unique form ID
 * @return array a hash of form information. If the form isn't found, it returns an empty array
 */
function ft_get_form($form_id)
{
  global $g_table_prefix;

  $form_id = ft_sanitize($form_id);

  $query = @mysql_query("SELECT * FROM {$g_table_prefix}forms WHERE form_id = $form_id");
  $form_info = @mysql_fetch_assoc($query);

  if (empty($form_info))
    return array();

  $form_info["client_info"] = ft_get_form_clients($form_id);
  $form_info["client_omit_list"] = ($form_info["access_type"] == "public") ? ft_get_public_form_omit_list($form_id) : array();

  $query = mysql_query("SELECT * FROM {$g_table_prefix}multi_page_form_urls WHERE form_id = $form_id ORDER BY page_num");
  $form_info["multi_page_form_urls"] = array();
  while ($row = mysql_fetch_assoc($query))
    $form_info["multi_page_form_urls"][] = $row;

  extract(ft_process_hook_calls("end", compact("form_id", "form_info"), array("form_info")), EXTR_OVERWRITE);

  return $form_info;
}


/**
 * A simple function to return a list of (completed, finalized) forms, ordered by form name.
 *
 * @return array
 */
function ft_get_form_list()
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}forms
    WHERE  is_complete = 'yes' AND
           is_initialized = 'yes'
    ORDER BY form_name ASC
  ");

  $results = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $results[] = $row;
  }

  return $results;
}


/**
 * Returns a list of (completed, finalized) forms, ordered by form name, and all views, ordered
 * by view_order. This is handy for any time you need to just output the list of forms & their Views.
 *
 * @return array
 */
function ft_get_form_view_list()
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT form_id, form_name
    FROM   {$g_table_prefix}forms
    WHERE  is_complete = 'yes' AND
          is_initialized = 'yes'
    ORDER BY form_name ASC
  ");

  $results = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $form_id = $row["form_id"];
    $view_query = mysql_query("
      SELECT view_id, view_name
      FROM   {$g_table_prefix}views
      WHERE  form_id = $form_id
    ");

    $views = array();
    while ($row2 = mysql_fetch_assoc($view_query))
    {
      $views[] = array(
        "view_id"   => $row2["view_id"],
        "view_name" => $row2["view_name"]
      );
    }

    $results[] = array(
      "form_id"   => $form_id,
      "form_name" => $row["form_name"],
      "views"     => $views
    );
  }

  return $results;
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
 * Returns the name of a form. Generally used in presentation situations.
 *
 * @param integer $form_id
 */
function ft_get_form_name($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("SELECT form_name FROM {$g_table_prefix}forms WHERE form_id = $form_id");
  $result = mysql_fetch_assoc($query);

  return $result["form_name"];
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
      WHERE  cf.form_id = $form_id AND
            cf.account_id = a.account_id
            ");

    while ($row = mysql_fetch_assoc($account_query))
      $accounts[] = $row;
  }

  extract(ft_process_hook_calls("end", compact("form_id", "accounts"), array("accounts")), EXTR_OVERWRITE);

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
 * lets you only return custom form fields (everything excep submission ID, submission date,
 * last modified date, IP address and is_finalized)
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
    SELECT col_name, field_title, is_system_field
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
    if ($col_info["is_system_field"] == "yes" && $omit_system_fields)
      continue;

    if (!empty($view_id) && !in_array($col_info["col_name"], $view_col_names))
      continue;

    $col_names[$col_info["col_name"]] = $col_info["field_title"];
  }

  return $col_names;
}


/**
 * This function sets up the main form values in preparation for a test submission by the actual
 * form. It is called from step 2 of the form creation page for totally new forms - forms that don't
 * have an
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

  $info = ft_sanitize($info);

  // check required $info fields. This changes depending on the form type (external / internal). Validation
  // for the internal forms is handled separately [inelegant!]
  $rules = array();
  if ($info["form_type"] == "external")
  {
    $rules[] = "required,form_name,{$LANG["validation_no_form_name"]}";
    $rules[] = "required,access_type,{$LANG["validation_no_access_type"]}";
  }
  $errors = validate_fields($info, $rules);

  // if there are errors, piece together an error message string and return it
  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array($success, $message, "");
  }

  // extract values
  $form_type       = $info["form_type"];
  $access_type     = $info["access_type"];
  $submission_type = (isset($info["submission_type"])) ? "'{$info["submission_type"]}'" : "NULL";
  $user_ids        = isset($info["selected_client_ids"]) ? $info["selected_client_ids"] : array();
  $form_name       = trim($info["form_name"]);
  $is_multi_page_form = isset($info["is_multi_page_form"]) ? $info["is_multi_page_form"] : "no";
  $redirect_url       = isset($info["redirect_url"]) ? trim($info["redirect_url"]) : "";
  $phrase_edit_submission = ft_sanitize($LANG["phrase_edit_submission"]);


  if ($is_multi_page_form == "yes")
    $form_url = $info["multi_page_urls"][0];
  else
  {
    // this won't be defined for Internal forms
    $form_url = isset($info["form_url"]) ? $info["form_url"] : "";
  }

  $now = ft_get_current_datetime();
  $query = "
    INSERT INTO {$g_table_prefix}forms (form_type, access_type, submission_type, date_created, is_active, is_complete,
      is_multi_page_form, form_name, form_url, redirect_url, edit_submission_page_label)
    VALUES ('$form_type', '$access_type', $submission_type, '$now', 'no', 'no', '$is_multi_page_form', '$form_name',
      '$form_url', '$redirect_url', '$phrase_edit_submission')
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
  mysql_query("DELETE FROM {$g_table_prefix}multi_page_form_urls WHERE form_id = $new_form_id");
  if ($is_multi_page_form == "yes")
  {
    $page_num = 1;
    foreach ($info["multi_page_urls"] as $url)
    {
      if (empty($url))
        continue;

      mysql_query("INSERT INTO {$g_table_prefix}multi_page_form_urls (form_id, form_url, page_num) VALUES ($new_form_id, '$url', $page_num)");
      $page_num++;
    }
  }

  return array($success, $message, $new_form_id);
}


/**
 * This function updates the main form values in preparation for a test submission by the actual
 * form. It is called from step 2 of the form creation page when UPDATING an existing, incomplete
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
//  $rules[] = "required,form_url,{$LANG["validation_no_form_url"]}";
  $errors = validate_fields($infohash, $rules);

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array ($success, $message, "");
  }

  // extract values
  $submission_type = $infohash["submission_type"];
  $access_type  = isset($infohash['access_type']) ? $infohash['access_type'] : "public";
  $client_ids   = isset($infohash['selected_client_ids']) ? $infohash['selected_client_ids'] : array();
  $form_id      = $infohash["form_id"];
  $form_name    = trim($infohash['form_name']);
  $is_multi_page_form   = isset($infohash["is_multi_page_form"]) ? $infohash["is_multi_page_form"] : "no";
  $redirect_url = isset($infohash['redirect_url']) ? trim($infohash['redirect_url']) : "";

  if ($is_multi_page_form == "yes")
    $form_url = $infohash["multi_page_urls"][0];
  else
    $form_url = $infohash["form_url"];


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

  // set the multi-page form URLs
  mysql_query("DELETE FROM {$g_table_prefix}multi_page_form_urls WHERE form_id = $form_id");
  if ($is_multi_page_form == "yes")
  {
    $page_num = 1;
    foreach ($infohash["multi_page_urls"] as $url)
    {
      if (empty($url))
        continue;

      mysql_query("INSERT INTO {$g_table_prefix}multi_page_form_urls (form_id, form_url, page_num) VALUES ($form_id, '$url', $page_num)");
      $page_num++;
    }
  }

  extract(ft_process_hook_calls("end", compact("infohash", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Called on step 5 of the Add Form process. It processes the Mass Smart Filled field values, add / updates the
 * appropriate field types, field sizes and option lists.
 *
 * @param integer $form_id
 * @param array a hash of values for the form. This includes all option list data.
 */
function ft_set_form_field_types($form_id, $info)
{
  global $g_table_prefix;

  extract(ft_process_hook_calls("start", compact("info", "form_id"), array("info")), EXTR_OVERWRITE);

  $textbox_field_type_id = ft_get_field_type_id_by_identifier("textbox");

  // set a 10 minute maximum execution time for this request. For long forms it can take a long time. 10 minutes
  // is extremely excessive, but what the hey
  @set_time_limit(600);

  $info = ft_sanitize($info);
  $form_fields = ft_get_form_fields($form_id);

  // update the field types and sizes
  $option_lists = array();
  foreach ($form_fields as $field_info)
  {
    if ($field_info["is_system_field"] == "yes")
      continue;

    $field_id = $field_info["field_id"];

    // update all the field types
    $field_type_id = $textbox_field_type_id;
    if (isset($info["field_{$field_id}_type"]))
      $field_type_id = $info["field_{$field_id}_type"];

    $field_size = "medium";
    if (isset($info["field_{$field_id}_size"]))
      $field_size = $info["field_{$field_id}_size"];

    mysql_query("
      UPDATE {$g_table_prefix}form_fields
      SET    field_type_id = $field_type_id,
             field_size = '$field_size'
      WHERE  field_id = $field_id
        ");

    // if this field is an Option List field, store all the option list info. We'll add them at the end
    if (isset($info["field_{$field_id}_num_options"]) && is_numeric($info["field_{$field_id}_num_options"]))
    {
      $num_options = $info["field_{$field_id}_num_options"];
      $options = array();
      for ($i=1; $i<=$num_options; $i++)
      {
        $options[] = array(
          "value" => $info["field_{$field_id}_opt{$i}_val"],
          "text" => $info["field_{$field_id}_opt{$i}_txt"]
        );
      }

      $option_lists[$field_id] = array(
        "field_type_id"    => $field_type_id,
        "option_list_name" => $field_info["field_title"],
        "options"          => $options
      );
    }
  }

  // finally, if there were any Option List defined for any of the form field, add the info!
  if (!empty($option_lists))
  {
    $field_types = ft_get_field_types();
    $field_type_id_to_option_list_map = array();
    foreach ($field_types as $field_type_info)
    {
      $field_type_id_to_option_list_map[$field_type_info["field_type_id"]] = $field_type_info["raw_field_type_map_multi_select_id"];
    }

    while (list($field_id, $option_list_info) = each($option_lists))
    {
      $list_id = ft_create_unique_option_list($form_id, $option_list_info);
      $raw_field_type_map_multi_select_id = $field_type_id_to_option_list_map[$option_list_info["field_type_id"]];
      if (is_numeric($list_id))
      {
        mysql_query("
          INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
          VALUES ($field_id, $raw_field_type_map_multi_select_id, $list_id)
            ");
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
 * @param integer $infohash a hash containing the contents of the Edit Form Main tab.
 * @return array returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_form_main_tab($infohash, $form_id)
{
  global $g_table_prefix, $LANG;

  $infohash = ft_sanitize($infohash);

  extract(ft_process_hook_calls("start", compact("infohash", "form_id"), array("infohash")), EXTR_OVERWRITE);

  $success = true;
  $message = $LANG["notify_form_updated"];

  // check required POST fields
  $rules = array();
  $rules[] = "required,form_name,{$LANG["validation_no_form_name"]}";
  $rules[] = "required,edit_submission_page_label,{$LANG["validation_no_edit_submission_page_label"]}";

  $errors = validate_fields($infohash, $rules);

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array ($success, $message, "");
  }

  $is_active = "";
  if (!empty($infohash["active"]))
    $is_active = "is_active = '{$infohash['active']}',";

  $form_name       = $infohash["form_name"];
  $form_type       = $infohash["form_type"];
  $submission_type = $infohash["submission_type"];
  $client_ids      = isset($infohash["selected_client_ids"]) ? $infohash["selected_client_ids"] : array();
  $is_multi_page_form = isset($infohash["is_multi_page_form"]) ? $infohash["is_multi_page_form"] : "no";
  $access_type     = $infohash["access_type"];

  if ($submission_type == "direct")
    $is_multi_page_form = "no";

  if ($is_multi_page_form == "yes")
    $form_url = $infohash["multi_page_urls"][0];
  else
    $form_url = $infohash["form_url"];

  $redirect_url = isset($infohash["redirect_url"]) ? $infohash["redirect_url"] : "";
  $auto_delete_submission_files = $infohash["auto_delete_submission_files"];
  $submission_strip_tags = $infohash["submission_strip_tags"];
  $edit_submission_page_label = $infohash["edit_submission_page_label"];
  $add_submission_button_label = $infohash["add_submission_button_label"];

  $query = "
    UPDATE {$g_table_prefix}forms
    SET    $is_active
          form_type = '$form_type',
          submission_type = '$submission_type',
          is_multi_page_form = '$is_multi_page_form',
          form_url = '$form_url',
          form_name = '$form_name',
          redirect_url = '$redirect_url',
          access_type = '$access_type',
          auto_delete_submission_files ='$auto_delete_submission_files',
          submission_strip_tags = '$submission_strip_tags',
          edit_submission_page_label = '$edit_submission_page_label',
          add_submission_button_label = '$add_submission_button_label'
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
        $client_id_clause = implode(" AND ", $client_clauses);
        mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE form_id = $form_id AND $client_id_clause");

        // also delete any orphaned records in the View omit list
        $view_ids = ft_get_view_ids($form_id);
        foreach ($view_ids as $view_id)
        {
          mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id AND $client_id_clause");
        }
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

  // if this is a multi-page form, add the list of pages in the form. One minor thing to note: the first page in the form
  // is actually stored in two locations: one in the main "form_url" value in the form, and two, here in the multi_page_form_urls
  // table. It's not necessary, of course, but it makes the code a little simpler
  if ($is_multi_page_form == "yes")
  {
    $page_num = 1;
    foreach ($infohash["multi_page_urls"] as $url)
    {
      if (empty($url))
        continue;

      mysql_query("INSERT INTO {$g_table_prefix}multi_page_form_urls (form_id, form_url, page_num) VALUES ($form_id, '$url', $page_num)");
      $page_num++;
    }
  }

  extract(ft_process_hook_calls("end", compact("infohash", "form_id", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Called by administrators; updates the content stored on the "Fields" tab in the Edit Form pages.
 *
 * @param integer $form_id the unique form ID
 * @param array $infohash a hash containing the contents of the Edit Form Display tab
 * @return array returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_form_fields_tab($form_id, $infohash)
{
  global $g_table_prefix, $g_root_url, $g_root_dir, $g_debug, $LANG, $g_field_sizes;

  $success = true;
  $message = $LANG["notify_field_changes_saved"];

  $infohash = ft_sanitize($infohash);
  extract(ft_process_hook_calls("start", compact("infohash", "form_id"), array("infohash")), EXTR_OVERWRITE);

  // stores the cleaned-up version of the POST content
  $field_info = array();

  $sortable_id = $infohash["sortable_id"];
  $field_ids = explode(",", $infohash["{$sortable_id}_sortable__rows"]);
  $order = $infohash["sortable_row_offset"];

  $new_sort_groups = explode(",", $infohash["{$sortable_id}_sortable__new_groups"]);

  foreach ($field_ids as $field_id)
  {
    $is_new_field = preg_match("/^NEW/", $field_id) ? true : false;
    $display_name        = (isset($infohash["field_{$field_id}_display_name"])) ? $infohash["field_{$field_id}_display_name"] : "";
    $form_field_name     = (isset($infohash["field_{$field_id}_name"])) ? $infohash["field_{$field_id}_name"] : "";
    $include_on_redirect = (isset($infohash["field_{$field_id}_include_on_redirect"])) ? "yes" : "no";
    $field_size          = (isset($infohash["field_{$field_id}_size"])) ? $infohash["field_{$field_id}_size"] : "";
    $col_name            = (isset($infohash["col_{$field_id}_name"])) ? $infohash["col_{$field_id}_name"] : "";
    $old_field_size      = (isset($infohash["old_field_{$field_id}_size"])) ? $infohash["old_field_{$field_id}_size"] : "";
    $old_col_name        = (isset($infohash["old_col_{$field_id}_name"])) ? $infohash["old_col_{$field_id}_name"] : "";
    $is_system_field     = (in_array($field_id, $infohash["system_fields"])) ? "yes" : "no";

    // this is only sent for non-system fields
    $field_type_id       = isset($infohash["field_{$field_id}_type_id"]) ? $infohash["field_{$field_id}_type_id"] : "";

    // won't be defined for new fields
    $old_field_type_id   = (isset($infohash["old_field_{$field_id}_type_id"])) ? $infohash["old_field_{$field_id}_type_id"] : "";

    $field_info[] = array(
      "is_new_field"        => $is_new_field,
      "field_id"            => $field_id,
      "display_name"        => $display_name,
      "form_field_name"     => $form_field_name,
      "field_type_id"       => $field_type_id,
      "old_field_type_id"   => $old_field_type_id,
      "include_on_redirect" => $include_on_redirect,
      "is_system_field"     => $is_system_field,
      "list_order"          => $order,
      "is_new_sort_group"   => (in_array($field_id, $new_sort_groups)) ? "yes" : "no",

      // column name info
      "col_name"            => $col_name,
      "old_col_name"        => $old_col_name,
      "col_name_changed"    => ($col_name != $old_col_name) ? "yes" : "no",

      // field size info
      "field_size"          => $field_size,
      "old_field_size"      => $old_field_size,
      "field_size_changed"  => ($field_size != $old_field_size) ? "yes" : "no"
    );
    $order++;
  }
  reset($infohash);

  // delete any extended field settings for those fields whose field type just changed. Two comments:
  //   1. this is compatible with editing the fields in the dialog window. When that happens & the user updates
  //      it, the code updates the old_field_type_id info in the page so this is never called.
  //   2. with the addition of Shared Characteristics, this only deletes fields that aren't mapped between the
  //      two fields types (old and new)
  $changed_fields = array();
  foreach ($field_info as $curr_field_info)
  {
    if ($curr_field_info["is_new_field"] || $curr_field_info["is_system_field"] == "yes" ||
       $curr_field_info["field_type_id"] == $curr_field_info["old_field_type_id"])
      continue;

    $changed_fields[] = $curr_field_info;
  }

  if (!empty($changed_fields))
  {
    $field_type_settings_shared_characteristics = ft_get_settings("field_type_settings_shared_characteristics");
    $field_type_map = ft_get_field_type_id_to_identifier();

    $shared_settings = array();
    foreach ($changed_fields as $changed_field_info)
    {
      $field_id = $changed_field_info["field_id"];
      $shared_settings[] = ft_get_shared_field_setting_info($field_type_map, $field_type_settings_shared_characteristics, $field_id, $changed_field_info["field_type_id"], $changed_field_info["old_field_type_id"]);
      ft_delete_extended_field_settings($field_id);
      ft_delete_field_validation($field_id);
    }

    foreach ($shared_settings as $setting)
    {
      foreach ($setting as $setting_info)
      {
        $field_id      = $setting_info["field_id"];
        $setting_id    = $setting_info["new_setting_id"];
        $setting_value = ft_sanitize($setting_info["setting_value"]);
        mysql_query("
          INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
          VALUES ($field_id, $setting_id, '$setting_value')
        ");
      }
    }
  }

  // the database column name and size field both affect the form's actual database table structure. If either
  // of those changed, we need to update the database
  $db_col_changes     = array();
  $db_col_change_hash = array(); // added later. Could use refactoring...
  $table_name = "{$g_table_prefix}form_{$form_id}";
  foreach ($field_info as $curr_field_info)
  {
    if ($curr_field_info["col_name_changed"] == "no" && $curr_field_info["field_size_changed"] == "no")
      continue;

    if ($curr_field_info["is_new_field"])
      continue;

    $field_id       = $curr_field_info["field_id"];
    $old_col_name   = $curr_field_info["old_col_name"];
    $new_col_name   = $curr_field_info["col_name"];
    $new_field_size = $curr_field_info["field_size"];
    $new_field_size_sql = $g_field_sizes[$new_field_size]["sql"];

    list($is_success, $err_message) = _ft_alter_table_column($table_name, $old_col_name, $new_col_name, $new_field_size_sql);
    if ($is_success)
    {
      $db_col_changes[$field_id] = array(
        "col_name"   => $new_col_name,
        "field_size" => $new_field_size
      );
    }

    // if there was a problem, return an error immediately
    else
    {
      // if there have already been successful database column name changes already made,
      // update the database. This helps prevent things getting out of whack
      if (!empty($db_col_changes))
      {
        while (list($field_id, $changes) = each($db_col_changes))
        {
          $col_name   = $changes["col_name"];
          $field_size = $changes["field_size"];

          @mysql_query("
            UPDATE {$g_table_prefix}form_fields
            SET    col_name   = '$col_name',
                   field_size = '$field_size'
            WHERE  field_id = $field_id
                      ");
        }
      }
      $message = $LANG["validation_db_not_updated_invalid_input"];
      if ($g_debug) $message .= " \"$err_message\"";
        return array(false, $message);
    }
  }

  // now update the fields, and, if need be, the form's database table
  foreach ($field_info as $field)
  {
    if ($field["is_new_field"])
      continue;

    $field_id      = $field["field_id"];
    $display_name  = $field["display_name"];
    $field_name    = $field["form_field_name"];
    $field_type_id = $field["field_type_id"];
    $include_on_redirect = $field["include_on_redirect"];
    $is_system_field = $field["is_system_field"];
    $field_size      = $field["field_size"];
    $col_name        = $field["col_name"];
    $list_order      = $field["list_order"];
    $is_new_sort_group = $field["is_new_sort_group"];

    if ($is_system_field == "yes")
    {
      $query = "
        UPDATE {$g_table_prefix}form_fields
        SET    field_title = '$display_name',
               include_on_redirect = '$include_on_redirect',
               list_order = $list_order,
               is_new_sort_group = '$is_new_sort_group'
        WHERE  field_id = $field_id
                  ";
    }
    else
    {
      $query = "
        UPDATE {$g_table_prefix}form_fields
        SET    field_name = '$field_name',
               field_title = '$display_name',
               field_size = '$field_size',
               col_name = '$col_name',
               field_type_id  = '$field_type_id',
               include_on_redirect = '$include_on_redirect',
               list_order = $list_order,
               is_new_sort_group = '$is_new_sort_group'
        WHERE  field_id = $field_id
                  ";
    }

    mysql_query($query)
      or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__ . ": <i>$query</i>", mysql_error());
  }

  // if any of the database column names just changed we need to update any View filters that relied on them
  if (!empty($db_col_changes))
  {
    while (list($field_id, $changes) = each($db_col_changes))
    {
      ft_update_field_filters($field_id);
    }
  }

  // okay! now add any new fields that the user just added
  $new_fields = array();
  foreach ($field_info as $curr_field)
  {
    if ($curr_field["is_new_field"])
    {
      $new_fields[] = $curr_field;
    }
  }
  if (!empty($new_fields))
  {
    list($is_success, $error) = ft_add_form_fields($form_id, $new_fields);

    // if there was a problem adding any of the new fields, inform the user
    if (!$is_success)
    {
      $success = false;
      $message = $error;
    }
  }

  // Lastly, delete the specified fields. Since some field types (e.g. files) may have additional functionality
  // needed at this stage (e.g. deleting the actual files that had been uploaded via the form). This occurs regardless
  // of whether the add fields step worked or not
  $deleted_field_ids = explode(",", $infohash["{$sortable_id}_sortable__deleted_rows"]);
  extract(ft_process_hook_calls("delete_fields", compact("deleted_field_ids", "infohash", "form_id"), array()), EXTR_OVERWRITE);

  // now actually delete the fields
  ft_delete_form_fields($form_id, $deleted_field_ids);

  extract(ft_process_hook_calls("end", compact("infohash", "field_info", "form_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Simple helper function to examine a form and see if it contains a file upload field.
 *
 * @param integer $form_id
 * @return boolean
 */
function ft_check_form_has_file_upload_field($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT count(*) as c
    FROM   {$g_table_prefix}form_fields ff, {$g_table_prefix}field_types fft
    WHERE  ff.form_id = $form_id AND
           ff.field_type_id = fft.field_type_id AND
           fft.is_file_field = 'yes'
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

  extract(ft_process_hook_calls("end", compact("table", "old_col_name", "new_col_name", "col_type"), array()), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Caches the total number of (finalized) submissions in a particular form - or all forms -
 * in the $_SESSION["ft"]["form_{$form_id}_num_submissions"] key. That value is used on the administrators
 * main Forms page to list the form submission count.
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
 * @param boolean $is_admin whether or not the user retrieving the data is an administrator or not.
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

  extract(ft_process_hook_calls("start", compact("account_id", "is_admin", "search_criteria"), array("search_criteria")), EXTR_OVERWRITE);

  $search_criteria["account_id"] = $account_id;
  $search_criteria["is_admin"]   = $is_admin;
  $results = _ft_get_search_form_sql_clauses($search_criteria);

  // get the form IDs. All info about the forms will be retrieved in a separate query
  $form_query = mysql_query("
    SELECT form_id
    FROM   {$g_table_prefix}forms
    {$results["where_clause"]}
    {$results["order_clause"]}
          ");

  // now retrieve the basic info (id, first and last name) about each client assigned to this form. This
  // takes into account whether it's a public form or not and if so, what clients are in the omit list
  $client_omitted_from_public_forms = $results["client_omitted_from_public_forms"];
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

  extract(ft_process_hook_calls("end", compact("account_id", "is_admin", "search_criteria", "form_info"), array("form_info")), EXTR_OVERWRITE);

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

  extract(ft_process_hook_calls("end", compact("clients_id", "form_id"), array("client_ids")), EXTR_OVERWRITE);

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


/**
 * This returns the IDs of the previous and next forms, as determined by the administrators current
 * search and sort.
 *
 * Not happy with this function! Getting this info is surprisingly tricky, once you throw in the sort clause.
 * Still, the number of client accounts are liable to be quite small, so it's not such a sin.
 *
 * @param integer $form_id
 * @param array $search_criteria
 * @return hash prev_form_id => the previous account ID (or empty string)
 *              next_form_id => the next account ID (or empty string)
 */
function ft_get_form_prev_next_links($form_id, $search_criteria = array())
{
  global $g_table_prefix;

  $results = _ft_get_search_form_sql_clauses($search_criteria);

  $query = mysql_query("
    SELECT form_id
    FROM   {$g_table_prefix}forms
    {$results["where_clause"]}
    {$results["order_clause"]}
  ");

  $sorted_form_ids = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $sorted_form_ids[] = $row["form_id"];
  }
  $current_index = array_search($form_id, $sorted_form_ids);

  $return_info = array("prev_form_id" => "", "next_form_id" => "");
  if ($current_index === 0)
  {
    if (count($sorted_form_ids) > 1)
      $return_info["next_form_id"] = $sorted_form_ids[$current_index+1];
  }
  else if ($current_index === count($sorted_form_ids)-1)
  {
    if (count($sorted_form_ids) > 1)
      $return_info["prev_form_id"] = $sorted_form_ids[$current_index-1];
  }
  else
  {
    $return_info["prev_form_id"] = $sorted_form_ids[$current_index-1];
    $return_info["next_form_id"] = $sorted_form_ids[$current_index+1];
  }

  return $return_info;
}


/**
 * Used in ft_search_forms and ft_get_form_prev_next_links, this function looks at the
 * current search and figures out the WHERE and ORDER BY clauses so that the calling function
 * can retrieve the appropriate form results in the appropriate order.
 *
 * @param array $search_criteria
 * @return array $clauses
 */
function _ft_get_search_form_sql_clauses($search_criteria)
{
  global $g_table_prefix;

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
    case "form_type-ASC":
      $order_clause = "form_type ASC";
      break;
    case "form_type-DESC":
      $order_clause = "form_type DESC";
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

  if (!empty($search_criteria["account_id"]))
  {
    $account_id = $search_criteria["account_id"];
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

  $admin_clause = (!$search_criteria["is_admin"]) ? "is_complete = 'yes' AND is_initialized = 'yes'" : "";

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

  return array(
    "order_clause" => $order_clause,
    "where_clause" => $where_clause,
    "client_omitted_from_public_forms" => $client_omitted_from_public_forms
  );
}
