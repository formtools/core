<?php

/**
 * This file defines all functions related to managing form submissions.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Submissions
 */


// -------------------------------------------------------------------------------------------------


/**
 * Creates a new blank submission in the database and returns the unique submission ID. If the
 * operation fails for whatever reason (e.g. the form doesn't exist), it just returns the empty
 * string.
 *
 * @param integer $form_id
 * @param integer $view_id
 * @param boolean $is_finalized whether the submission is finalized or not.
 */
function ft_create_blank_submission($form_id, $view_id, $is_finalized = false)
{
  global $g_table_prefix;

  if (!ft_check_form_exists($form_id))
    return "";

  $now = ft_get_current_datetime();
  $ip  = $_SERVER["REMOTE_ADDR"];

  // if the administrator has specified any default values for submissions created through this View
  $default_insert_pairs = array(
    "submission_date"    => $now,
    "last_modified_date" => $now,
    "ip_address"         => $ip,
    "is_finalized"       => ($is_finalized) ? "yes" : "no"
  );

  $special_defaults = ft_get_new_view_submission_defaults($view_id);
  if (!empty($special_defaults))
  {
    // find the field's DB column names so we can do our insert
    $field_id_to_value_map = array();
    foreach ($special_defaults as $curr_default_info)
    {
      $field_id_to_value_map[$curr_default_info["field_id"]] = ft_sanitize($curr_default_info["default_value"]);
    }

    $field_ids = array_keys($field_id_to_value_map);
    $field_id_to_column_name_map = ft_get_field_col_by_field_id($form_id, $field_ids);

    while (list($field_id, $col_name) = each($field_id_to_column_name_map))
    {
      $default_insert_pairs[$col_name] = $field_id_to_value_map[$field_id];
    }
  }

  $col_names  = implode(", ", array_keys($default_insert_pairs));
  $col_values = "'" . implode("', '", array_values($default_insert_pairs)) . "'";

  mysql_query("
    INSERT INTO {$g_table_prefix}form_{$form_id} ($col_names)
    VALUES ($col_values)
      ");

  $new_submission_id = mysql_insert_id();
  extract(ft_process_hook_calls("end", compact("form_id", "now", "ip", "new_submission_id"), array()), EXTR_OVERWRITE);

  return $new_submission_id;
}


/**
 * Deletes an individual submission. If the $is_admin value isn't set (or set to FALSE), it checks
 * to see if the currently logged in user is allowed to delete the submission ID.
 *
 * @param integer $form_id
 * @param integer $view_id
 * @param integer $submission_id
 * @param boolean $is_admin
 */
function ft_delete_submission($form_id, $view_id, $submission_id, $is_admin = false)
{
  global $g_table_prefix, $LANG;

  extract(ft_process_hook_calls("start", compact("form_id", "view_id", "submission_id", "is_admin"), array()), EXTR_OVERWRITE);

  $form_info = ft_get_form($form_id);
  $form_fields = ft_get_form_fields($form_id);
  $auto_delete_submission_files = $form_info["auto_delete_submission_files"];

  // send any emails
  ft_send_emails("on_delete", $form_id, $submission_id);

  // loop the form templates to find out if there are any file fields. If there are - and the user
  // configured it - delete any associated files
  $file_delete_problems = array();
  $file_fields_to_delete = array();
  if ($auto_delete_submission_files == "yes")
  {
    $file_field_type_ids = ft_get_file_field_type_ids();
    foreach ($form_fields as $field_info)
    {
      $field_type_id = $field_info["field_type_id"];

      if (!in_array($field_type_id, $file_field_type_ids))
        continue;

      // I really don't like this... what should be done is do a SINGLE query after this loop is complete
      // to return a map of field_id to values. That would then update $file_fields_to_delete
      // with a fraction of the cost
      $submission_info = ft_get_submission_info($form_id, $submission_id);
      $filename = $submission_info[$field_info['col_name']];

      // if no filename was stored, it was empty - just continue
      if (empty($filename))
        continue;

      $file_fields_to_delete[] = array(
        "submission_id" => $submission_id,
        "field_id"      => $field_info["field_id"],
        "field_type_id" => $field_type_id,
        "filename"      => $filename
      );
    }

    if (!empty($file_fields_to_delete))
    {
      list($success, $file_delete_problems) = ft_delete_submission_files($form_id, $file_fields_to_delete, "ft_delete_submission");
    }
  }

  // now delete the submission
  mysql_query("
    DELETE FROM {$g_table_prefix}form_{$form_id}
    WHERE submission_id = $submission_id
      ");

  if ($auto_delete_submission_files == "yes")
  {
    if (empty($file_delete_problems))
    {
      $success = true;
      $message = ($file_fields_to_delete) ? $LANG["notify_submission_and_files_deleted"] : $LANG["notify_submission_deleted"];
    }
    else
    {
      $success = false;
      $message = $LANG["notify_submission_deleted_with_problems"] . "<br /><br />";

      foreach ($file_delete_problems as $problem)
        $message .= "&bull; <b>{$problem["filename"]}</b>: {$problem["error"]}<br />\n";
    }
  }
  else
  {
    $success = true;
    $message = $LANG["notify_submission_deleted"];
  }

  // update sessions to ensure the first submission date and num submissions for this form View are correct
  _ft_cache_form_stats($form_id);
  _ft_cache_view_stats($view_id);

  extract(ft_process_hook_calls("end", compact("form_id", "view_id", "submission_id", "is_admin"), array("success", "message")), EXTR_OVERWRITE);

  // update sessions
  if (isset($_SESSION["ft"]["form_{$form_id}_selected_submissions"]) && in_array($submission_id, $_SESSION["ft"]["form_{$form_id}_selected_submissions"]))
    array_splice($_SESSION["ft"]["form_{$form_id}_selected_submissions"], array_search($submission_id, $_SESSION["ft"]["form_{$form_id}_selected_submissions"]), 1);

  return array($success, $message);
}


/**
 * Deletes multiple form submissions at once.
 *
 * If required, deletes any files that were uploaded along with the original submissions. If one or
 * more files associated with this submission couldn't be deleted (either because they didn't exist
 * or because they didn't have permissions) the submission IS deleted, but it returns an error
 * indicating which files caused problems.
 *
 * @param integer $form_id the unique form ID
 * @param mixed $delete_ids a single submission ID / an array of submission IDs / "all". This column
 *               determines which submissions will be deleted
 * @param integer $view_id (optional) this is only needed if $delete_ids is set to "all". With the advent
 *               of Views, it needs to know which submissions to delete.
 * @return array returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_delete_submissions($form_id, $view_id, $submissions_to_delete, $omit_list, $search_fields, $is_admin)
{
  global $g_table_prefix, $LANG;

  $submission_ids = array();
  if ($submissions_to_delete == "all")
  {
    // get the list of searchable columns for this View. This is needed to ensure that ft_get_search_submission_ids receives
    // the correct info to determine what submission IDs are appearing in this current search.
    $searchable_columns = ft_get_view_searchable_fields($view_id);
    $submission_ids = ft_get_search_submission_ids($form_id, $view_id, "all", "submission_id-ASC", $search_fields, $searchable_columns);
    $submission_ids = array_diff($submission_ids, $omit_list);
  }
  else
  {
    $submission_ids = $submissions_to_delete;
  }

  $submissions_to_delete = $submission_ids;
  extract(ft_process_hook_calls("start", compact("form_id", "view_id", "submissions_to_delete", "omit_list", "search_fields", "is_admin"), array("submission_ids")), EXTR_OVERWRITE);

  $form_info = ft_get_form($form_id);
  $form_fields = ft_get_form_fields($form_id);
  $auto_delete_submission_files = $form_info["auto_delete_submission_files"];

  $submission_ids_qry = array();
  foreach ($submission_ids as $submission_id)
    $submission_ids_qry[] = "submission_id = $submission_id";

  $where_clause = "WHERE " . join(" OR ", $submission_ids_qry);


  // loop the form templates to find out if there are any file fields. If there are - and the user
  // configured it - delete any associated files
  $file_delete_problems = array();
  $form_has_file_field = false;
  if ($auto_delete_submission_files == "yes")
  {
    $file_field_type_ids = ft_get_file_field_type_ids();
    $file_fields_to_delete = array();
    foreach ($submissions_to_delete as $submission_id)
    {
      foreach ($form_fields as $field_info)
      {
        $field_type_id = $field_info["field_type_id"];
        if (!in_array($field_type_id, $file_field_type_ids))
          continue;

        $form_has_file_field = true;
        $submission_info = ft_get_submission_info($form_id, $submission_id);
        $filename = $submission_info[$field_info['col_name']];

        // if no filename was stored, it was empty - just continue
        if (empty($filename))
          continue;

        $file_fields_to_delete[] = array(
          "submission_id" => $submission_id,
          "field_id"      => $field_info["field_id"],
          "field_type_id" => $field_type_id,
          "filename"      => $filename
        );
      }
    }

    if (!empty($file_fields_to_delete))
    {
      list($success, $file_delete_problems) = ft_delete_submission_files($form_id, $file_fields_to_delete, "ft_delete_submissions");
    }
  }


  // now delete the submission
  mysql_query("DELETE FROM {$g_table_prefix}form_{$form_id} $where_clause");

  if ($auto_delete_submission_files == "yes")
  {
    if (empty($file_delete_problems))
    {
      $success = true;
      if (count($submission_ids) > 1)
        $message = ($form_has_file_field) ? $LANG["notify_submissions_and_files_deleted"] : $LANG["notify_submissions_deleted"];
      else
        $message = ($form_has_file_field) ? $LANG["notify_submission_and_files_deleted"] : $LANG["notify_submission_deleted"];
    }
    else
    {
      $success = false;
      if (count($submission_ids) > 1)
        $message = $LANG["notify_submissions_deleted_with_problems"] . "<br /><br />";
      else
        $message = $LANG["notify_submission_deleted_with_problems"] . "<br /><br />";

      foreach ($file_delete_problems as $problem)
        $message .= "&bull; <b>{$problem["filename"]}</b>: {$problem["error"]}<br />\n";
    }
  }
  else
  {
    $success = true;
    if (count($submission_ids) > 1)
      $message = $LANG["notify_submissions_deleted"];
    else
      $message = $LANG["notify_submission_deleted"];
  }

  // TODO update sessions to ensure the first submission date and num submissions for this form View are correct
  _ft_cache_form_stats($form_id);
  _ft_cache_view_stats($form_id, $view_id);

  $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";
  $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
  $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();

  // loop through all submissions deleted and send any emails
  reset($submission_ids);
  foreach ($submission_ids as $submission_id)
    ft_send_emails("on_delete", $form_id, $submission_id);

  $submissions_to_delete = $submission_ids;
  extract(ft_process_hook_calls("end", compact("form_id", "view_id", "submissions_to_delete", "omit_list", "search_fields", "is_admin"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Retrieves everything about a form submission. It contains a lot of meta-information about the field,
 * from the form_fields and view_tabs. If the optional view_id parameter is included, only the fields
 * in the View are returned (AND all system fields, if they're not included).
 *
 * @param integer $form_id the unique form ID
 * @param integer $submission_id the unique submission ID
 * @param integer $view_id an optional view ID parameter
 * @return array Returns an array of hashes. Each index is a separate form field and its value is
 *           a hash of information about it, such as value, field type, field size, etc.
 */
function ft_get_submission($form_id, $submission_id, $view_id = "")
{
  global $g_table_prefix;

  $return_arr = array();

  $form_fields = ft_get_form_fields($form_id);
  $submission  = ft_get_submission_info($form_id, $submission_id);

  $view_fields = (!empty($view_id)) ? ft_get_view_fields($view_id) : array();

  if (empty($submission))
    return array();

  $view_field_ids = array();
  foreach ($view_fields as $view_field)
    $view_field_ids[] = $view_field["field_id"];

  // for each field, combine the meta form info (like field size, type, data type etc) from $form_fields
  // with the info about the submission itself. Also, if there's a View specified, filter out any fields
  // that aren't used in the View
  foreach ($form_fields as $field_info)
  {
    $field_id = $field_info["field_id"];

    // if we're looking at this submission through a View,
    if (!empty($view_id) && !in_array($field_id, $view_field_ids))
      continue;

    // if the submission contains contents for this field, add it
    if (array_key_exists($field_info['col_name'], $submission))
      $field_info["content"] = $submission[$field_info['col_name']];

    // if a view ID is specified, return the view-specific field info as well
    if (!empty($view_id))
    {
      $field_view_info = ft_get_view_field($view_id, $field_id);

      if (!empty($field_view_info))
      {
        foreach ($field_view_info as $key => $value)
          $field_info[$key] = $value;
      }
    }

    $return_arr[] = $field_info;
  }

  // finally, if a View is specified, ensure that the order in which the submission fields are returned
  // is determined by the View. [NOT efficient!]
  if (!empty($view_id))
  {
    $ordered_return_arr = array();

    foreach ($view_fields as $view_field_info)
    {
      $field_id = $view_field_info["field_id"];
      foreach ($return_arr as $field_info)
      {
        if ($field_info["field_id"] == $field_id)
        {
          $ordered_return_arr[] = $field_info;
          break;
        }
      }
    }

    $return_arr = $ordered_return_arr;
  }

  extract(ft_process_hook_calls("end", compact("form_id", "submission_id", "view_id", "return_arr"), array("return_arr")), EXTR_OVERWRITE);

  return $return_arr;
}


/**
 * Retrieves ONLY the submission data itself. If you require "meta" information about the submision
 * such as it's field type, size, database table name etc, use ft_get_submision().
 *
 * @param integer $form_id The unique form ID.
 * @param integer $submission_id The unique submission ID.
 * @return array Returns a hash of submission information.
 */
function ft_get_submission_info($form_id, $submission_id)
{
  global $g_table_prefix;

  // get the form submission info
  $submission_info = mysql_query("
     SELECT *
     FROM   {$g_table_prefix}form_{$form_id}
     WHERE  submission_id = $submission_id
              ");

  $submission = mysql_fetch_assoc($submission_info);

  extract(ft_process_hook_calls("end", compact("form_id", "submission_id", "submission"), array("submission")), EXTR_OVERWRITE);

  return $submission;
}


/**
 * Gets the number of submissions made through a form.
 *
 * @param integer $form_id the form ID
 * @param integer $view_id the View ID
 * @return integer The number of (finalized) submissions
 */
function ft_get_submission_count($form_id, $view_id = "")
{
  global $g_table_prefix;

  $filter_sql_clause = "";
  if (!empty($view_id))
  {
    $filter_sql = ft_get_view_filter_sql($view_id);

    if (!empty($filter_sql))
      $filter_sql_clause = "AND" . join(" AND ", $filter_sql);
  }

  // get the form submission info
  $query = mysql_query("
     SELECT count(*)
     FROM   {$g_table_prefix}form_{$form_id}
     WHERE  is_finalized = 'yes'
            $filter_sql_clause
              ");

  $result = mysql_fetch_array($query);
  $submission_count = $result[0];

  return $submission_count;
}


/**
 * Returns all submission IDs in a search result set. This is used on the item details pages (admin
 * and client) to build the << previous / next >> links. Since the system now properly only searches
 * fields marked as "is_searchable", this function needs the final $search_columns parameter, containing
 * the list of searchable fields (which is View-dependent).
 *
 * @param integer $form_id the unique form ID
 * @param integer $view_id the unique form ID
 * @param mixed   $results_per_page an integer, or "all"
 * @param string  $order a string of form: "{db column}_{ASC|DESC}"
 * @param array   $search_fields an optional hash with these keys:<br/>
 *                  search_field<br/>
 *                  search_date<br/>
 *                  search_keyword<br/>
 * @param array   $search_columns the columns that are being searched
 * @return string an HTML string
 */
function ft_get_search_submission_ids($form_id, $view_id, $results_per_page, $order, $search_fields = array(),
     $search_columns = array())
{
  global $g_table_prefix;

  // determine the various SQL clauses
  $order_by            = _ft_get_search_submissions_order_by_clause($form_id, $order);
  $limit_clause        = _ft_get_limit_clause(1, $results_per_page);
  $filter_clause       = _ft_get_search_submissions_view_filter_clause($view_id);
  $search_where_clause = _ft_get_search_submissions_search_where_clause($form_id, $search_fields, $search_columns);

  // now build our query
  $full_query = "
      SELECT submission_id
      FROM   {$g_table_prefix}form_{$form_id}
      WHERE  is_finalized = 'yes'
             $search_where_clause
             $filter_clause
      ORDER BY $order_by
                ";

  $search_query = mysql_query($full_query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: <i>$full_query</i>", mysql_error());

  $submission_ids = array();
  while ($row = mysql_fetch_assoc($search_query))
    $submission_ids[] = $row["submission_id"];

  return $submission_ids;
}


/**
 * Updates an individual form submission. Called by both clients and administrator.
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the update submission page. The contents of it change for each
 *             form and form View, of course.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_submission($form_id, $submission_id, $infohash)
{
  global $g_table_prefix, $g_multi_val_delimiter, $LANG;

  $success = true;
  $message = $LANG["notify_form_submission_updated"];

  $infohash = ft_sanitize($infohash);
  extract(ft_process_hook_calls("start", compact("form_id", "submission_id", "infohash"), array("infohash")), EXTR_OVERWRITE);

  $field_ids = array();
  if (!empty($infohash["field_ids"]))
    $field_ids = explode(",", $infohash["field_ids"]);

  // perform any server-side validation
  $errors = ft_validate_submission($form_id, $infohash["editable_field_ids"], $infohash);

  // if there are any problems, return right away
  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = implode("<br />", $errors);
    return array($success, $message);
  }

  $form_fields = ft_get_form_fields($form_id);
  $field_types_processing_info = ft_get_field_type_processing_info();

  // this gets all settings for the fields, taking into account whatever has been overridden
  $field_settings = ft_get_form_field_field_type_settings($field_ids, $form_fields);

  $db_column_names = array();
  $now = ft_get_current_datetime();
  $query = array();
  $query[] = "last_modified_date = '$now'";

  $file_fields = array();
  foreach ($form_fields as $row)
  {
    $field_id = $row["field_id"];

    // if the field ID isn't in the page's tab, ignore it
    if (!in_array($field_id, $field_ids))
      continue;

    // if the field ID isn't editable, the person's being BAD and trying to hack a field value. Ignore it.
    if (!in_array($field_id, $infohash["editable_field_ids"]))
      continue;

    // if this is a FILE field that doesn't have any overridden PHP processing code, just store the info
    // about the field. Presumably, the module / field type has registered the appropriate hooks for
    // processing the file. Without it, the module wouldn't work. We pass that field + file into to the hook.
    if ($field_types_processing_info[$row["field_type_id"]]["is_file_field"] == "yes")
    {
      $file_data = array(
        "field_id"   => $field_id,
        "field_info" => $row,
        "data"       => $infohash,
        "code"       => $field_types_processing_info[$row["field_type_id"]]["php_processing"],
        "settings"   => $field_settings[$field_id]
      );

      if (empty($field_types_processing_info[$row["field_type_id"]]["php_processing"]))
      {
        $file_fields[] = $file_data;
        continue;
      }
      else
      {
        $value = ft_process_form_field($file_data);
        $query[] = $row["col_name"] . " = '$value'";
      }
    }

    if ($row["field_name"] == "core__submission_date" || $row["col_name"] == "core__last_modified")
    {
      if (!isset($infohash[$row["field_name"]]) || empty($infohash[$row["field_name"]]))
        continue;
    }

    // see if this field type has any special PHP processing to do
    if (!empty($field_types_processing_info[$row["field_type_id"]]["php_processing"]))
    {
      $data = array(
        "field_info"   => $row,
        "data"         => $infohash,
        "code"         => $field_types_processing_info[$row["field_type_id"]]["php_processing"],
        "settings"     => $field_settings[$field_id],
        "account_info" => isset($_SESSION["ft"]["account"]) ? $_SESSION["ft"]["account"] : array()
      );
      $value = ft_process_form_field($data);
      $query[] = $row["col_name"] . " = '$value'";
    }
    else
    {
      if (isset($infohash[$row["field_name"]]))
      {
        if (is_array($infohash[$row["field_name"]]))
          $query[] = $row["col_name"] . " = '" . implode("$g_multi_val_delimiter", $infohash[$row["field_name"]]) . "'";
        else
          $query[] = $row["col_name"] . " = '" . $infohash[$row["field_name"]] . "'";
      }
      else
        $query[] = $row["col_name"] . " = ''";
    }
  }

  $set_query = join(",\n", $query);

  $query = "
    UPDATE {$g_table_prefix}form_{$form_id}
    SET    $set_query
    WHERE  submission_id = $submission_id
           ";

  $result = mysql_query($query);

  // if there was a problem updating the submission, don't even bother calling the file upload hook. Just exit right away
  if (!$result)
    return array(false, $LANG["notify_submission_not_updated"]);

  // now process any file fields
  extract(ft_process_hook_calls("manage_files", compact("form_id", "submission_id", "file_fields"), array("success", "message")), EXTR_OVERWRITE);

  // send any emails
  ft_send_emails("on_edit", $form_id, $submission_id);

  extract(ft_process_hook_calls("end", compact("form_id", "submission_id", "infohash"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * For use by programmers to finalize a submission (i.e. make it appear in the client's user
 * interface).
 *
 * @param integer $form_id The unique form ID.
 * @param integer $submission_id A unique submission ID.
 * @return boolean $success True on success, false otherwise.
 */
function ft_finalize_submission($form_id, $submission_id)
{
  global $g_table_prefix;

  // check the form_id is valid
  if (!ft_check_form_exists($form_id))
    return false;

  $query = "
    UPDATE {$g_table_prefix}form_$form_id
    SET    is_finalized = 'yes'
    WHERE  submission_id = $submission_id
           ";
  $result = mysql_query($query);

  ft_send_emails("on_submission", $form_id, $submission_id);

  return true;
}


/**
 * Creates and returns a search for any form View, and any subset of its columns, returning results in
 * any column order and for any single page subset (or all pages). The final $search_columns parameter
 * was added most recently to fix bug #173. That parameter lets the caller differentiate between the
 * columns being returned ($columns param) and columns to be searched ($search_columns).
 *
 * @param integer $form_id the unique form ID
 * @param integer $view_id the unique View ID
 * @param mixed $results_per_page an integer, or "all".
 * @param integer $page_num The current page number - or empty string, if this function is returning all
 *              results in one page (e.g. printer friendly page).
 * @param string $order A string of form: "{db column}_{ASC|DESC}"
 * @param mixed $columns An array containing which database columns to search and return, or a string:
 *              "all" - which returns all columns in the form.
 * @param array $search_fields an optional hash with these keys:<br/>
 *                  search_field<br/>
 *                  search_date<br/>
 *                  search_keyword<br/>
 * @param array submission_ids - an optional array containing a list of submission IDs to return.
 *     This may seem counterintuitive to pass the results that it needs to return to the function that
 *     figures out WHICH results to return, but it's actually kinda handy: this function returns exactly
 *     the field information that's needed in the order that's needed.
 * @param array $submission_ids an optional array of submission IDs to return
 * @param array $search_columns an optional array determining which database columns should be included
 *     in the search. Note: this is different from the $columns parameter which just determines which
 *     database columns will be returned. If it's not defined, it's just set to $columns.
 *
 * @return array returns a hash with these keys:<br/>
 *                ["search_query"]       => an array of hashes, each index a search result row<br />
 *                ["search_num_results"] => the number of results in the search (not just the 10 or so
 *                                          that will appear in the current page, listed in the
 *                                          "search_query" key<br />
 *                ["view_num_results"]   => the total number of results in this View, regardless of the
 *                                          current search values.
 */
function ft_search_submissions($form_id, $view_id, $results_per_page, $page_num, $order, $columns_to_return,
                               $search_fields = array(), $submission_ids = array(), $searchable_columns = array())
{
  global $g_table_prefix;

  // for backward compatibility
  if (empty($searchable_columns))
    $searchable_columns = $columns_to_return;

  // determine the various SQL clauses for the searches
  $order_by             = _ft_get_search_submissions_order_by_clause($form_id, $order);
  $limit_clause         = _ft_get_limit_clause($page_num, $results_per_page);
  $select_clause        = _ft_get_search_submissions_select_clause($columns_to_return);
  $filter_clause        = _ft_get_search_submissions_view_filter_clause($view_id);
  $submission_id_clause = _ft_get_search_submissions_submission_id_clause($submission_ids);
  $search_where_clause  = _ft_get_search_submissions_search_where_clause($form_id, $search_fields, $searchable_columns);

  // (1) our main search query that returns a PAGE of submission info
  $search_query = "
      SELECT $select_clause
      FROM   {$g_table_prefix}form_{$form_id}
      WHERE  is_finalized = 'yes'
             $search_where_clause
             $filter_clause
             $submission_id_clause
      ORDER BY $order_by
             $limit_clause
  ";
  $search_result = mysql_query($search_query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>; Query: $search_query; Error: ", mysql_error());

  $search_result_rows = array();
  while ($row = mysql_fetch_assoc($search_result))
    $search_result_rows[] = $row;

  // (2) find out how many results there are in this current search
  $search_results_count_query = mysql_query("
      SELECT count(*) as c
      FROM   {$g_table_prefix}form_{$form_id}
      WHERE  is_finalized = 'yes'
             $search_where_clause
             $filter_clause
             $submission_id_clause
                 ")
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: ", mysql_error());
  $search_num_results_info = mysql_fetch_assoc($search_results_count_query);
  $search_num_results = $search_num_results_info["c"];

  // (3) find out how many results should appear in the View, regardless of the current search criteria
  $view_results_count_query = mysql_query("
      SELECT count(*) as c
      FROM   {$g_table_prefix}form_{$form_id}
      WHERE  is_finalized = 'yes'
             $filter_clause
                 ")
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: ", mysql_error());
  $view_num_results_info = mysql_fetch_assoc($view_results_count_query);
  $view_num_results = $view_num_results_info["c"];

  $return_hash["search_rows"]        = $search_result_rows;
  $return_hash["search_num_results"] = $search_num_results;
  $return_hash["view_num_results"]   = $view_num_results;

  extract(ft_process_hook_calls("end", compact("form_id", "submission_id", "view_id", "results_per_page", "page_num", "order", "columns", "search_fields", "submission_ids", "return_hash"), array("return_hash")), EXTR_OVERWRITE);

  return $return_hash;
}


/**
 * Used in the ft_search_submissions function to abstract away a few minor details.
 *
 * @param $form_id integer
 * @param $order string
 * @return string
 */
function _ft_get_search_submissions_order_by_clause($form_id, $order)
{
  $order_by = "submission_id";
  if (!empty($order))
  {
    // sorting by column, format: col_x-desc / col_y-asc
    list($column, $direction) = explode("-", $order);
    $field_info = ft_get_field_order_info_by_colname($form_id, $column);

    // no field can be found if the administrator just changed the DB field contents and
    // then went back to the submissions page where they'd already done a sort (and had it cached)
    if (!empty($field_info))
    {
      if ($field_info["is_date_field"] == "yes")
      {
        if ($column == "submission_date" || $column == "last_modified_date")
          $order_by = "$column $direction";
        else
          $order_by = "CAST($column as DATETIME) $direction";
      }
      else
      {
        if ($field_info["data_type"] == "number")
          $order_by = "CAST($column as SIGNED) $direction";
        else
          $order_by = "$column $direction";
      }

      // important! If the ORDER BY column wasn't the submission_id, we need to add
      // the submission ID as the secondary sorting column
      if ($column != "submission_id")
        $order_by .= ", submission_id";
    }
  }

  return $order_by;
}


/**
 * Used in the ft_search_submissions function to abstract away a few minor details.
 *
 * @param array $columns
 * @return string
 */
function _ft_get_search_submissions_select_clause($columns)
{
  $select_clause = "";
  if (!is_array($columns) && $columns == "all")
  {
    $select_clause = " * ";
  }
  else
  {
    $columns = array_unique($columns);

    // if submission_id isn't included, add it - it'll be needed at some point
    if (!in_array("submission_id", $columns))
      $columns[] = "submission_id";

    // just in case. This prevents empty column names (which shouldn't get here, but do if something
    // goes wrong) getting into the column list
    $columns = ft_array_remove_empty_els($columns);

    $select_clause = join(", ", $columns);
  }

  return $select_clause;
}


/**
 * Used in the ft_search_submissions function to abstract away a few minor details.
 *
 * @param integer $view_id
 * @return string
 */
function _ft_get_search_submissions_view_filter_clause($view_id)
{
  $view_filters = ft_get_view_filter_sql($view_id);
  $filter_clause = "";
  if (!empty($view_filters))
    $filter_clause = "AND " . join(" AND ", $view_filters);

  return $filter_clause;
}


/**
 * Used in the ft_search_submissions function. This figures out the additional SQL clauses required for
 * a custom search. Note: as of the Dec 2009 build, this function properly only searches those fields
 * marked as "is_searchable" in the database.
 *
 * @param integer $form_id
 * @param array $search_fields
 * @param array $columns the View columns that have been marked as "is_searchable"
 * @return string
 */
function _ft_get_search_submissions_search_where_clause($form_id, $search_fields, $searchable_columns)
{
  global $g_search_form_date_field_format;

  $search_where_clause = "";
  if (!empty($search_fields))
  {
    $clean_search_fields = ft_sanitize($search_fields);

    $search_field   = $clean_search_fields["search_field"];
    $search_date    = $clean_search_fields["search_date"];
    $search_keyword = $clean_search_fields["search_keyword"];

    // search field can either be "all" or a database column name. "submission_date" and "last_modified_date"
    // have special meanings, since they allow for keyword searching within specific date ranges
    if ($search_field == "all")
    {
      if (!empty($search_keyword))
      {
        // if we're searching ALL columns, get all col names. This shouldn't ever get called any more - but
        // I'll leave it in for regression purposes
        $clauses = array();
        if (!is_array($searchable_columns) && $searchable_columns == "all")
        {
          $col_info = ft_get_form_column_names($form_id);
          $col_names = array_keys($col_info);
          unset($col_names["is_finalized"]);
          unset($col_names["submission_date"]);
          unset($col_names["last_modified_date"]);

          foreach ($col_names as $col_name)
            $clauses[] = "$col_name LIKE '%$search_keyword%'";
        }
        else if (is_array($searchable_columns))
        {
          foreach ($searchable_columns as $col_name)
            $clauses[] = "$col_name LIKE '%$search_keyword%'";
        }

        if (!empty($clauses))
          $search_where_clause = "AND (" . join(" OR ", $clauses) . ") ";
      }
    }

    // date field! Date fields actually take two forms: they're either the Core fields (Submission Date and
    // Last Modified Date), which are real DATETIME fields, or custom date fields which are varchars
    else if (preg_match("/\|date$/", $search_field))
    {
      $search_field = preg_replace("/\|date$/", "", $search_field);
      $is_core_date_field = ($search_field == "submission_date" || $search_field == "last_modified_date") ? true : false;
      if (!$is_core_date_field)
        $search_field = "CAST($search_field as DATETIME) ";

      if (!empty($search_date))
      {
        // search by date range
        if (strpos($search_date, "-") !== false)
        {
          $dates = explode(" - ", $search_date);
          $start = $dates[0];
          $end   = $dates[1];
          if ($g_search_form_date_field_format == "d/m/y") {
            list($start_day, $start_month, $start_year) = explode("/", $start);
            list($end_day, $end_month, $end_year)       = explode("/", $end);
          } else {
            list($start_month, $start_day, $start_year) = explode("/", $start);
            list($end_month, $end_day, $end_year)       = explode("/", $end);
          }
          $start_day   = str_pad($start_day, 2, "0", STR_PAD_LEFT);
          $start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
          $end_day     = str_pad($end_day, 2, "0", STR_PAD_LEFT);
          $end_month   = str_pad($end_month, 2, "0", STR_PAD_LEFT);

          $start_date = "{$start_year}-{$start_month}-{$start_day} 00:00:00";
          $end_date   = "{$end_year}-{$end_month}-{$end_day} 23:59:59";
          $search_where_clause = "AND ($search_field >= '$start_date' AND $search_field <= '$end_date') ";
        }

        // otherwise, return a specific day
        else
        {
          if ($g_search_form_date_field_format == "d/m/y") {
            list($day, $month, $year) = explode("/", $search_date);
          } else {
            list($month, $day, $year) = explode("/", $search_date);
          }
          $month = str_pad($month, 2, "0", STR_PAD_LEFT);
          $day   = str_pad($day, 2, "0", STR_PAD_LEFT);

          $start = "{$year}-{$month}-{$day} 00:00:00";
          $end   = "{$year}-{$month}-{$day} 23:59:59";
          $search_where_clause = "AND ($search_field >= '$start' AND $search_field <= '$end') ";
        }

        if (!empty($search_keyword))
        {
          $clauses = array();
          foreach ($searchable_columns as $col_name)
            $clauses[] = "$col_name LIKE '%$search_keyword%'";

          if (!empty($clauses))
            $search_where_clause .= "AND (" . join(" OR ", $clauses) . ") ";
        }
      }
    }

    else
    {
      if (!empty($search_keyword) && !empty($search_field))
        $search_where_clause = "AND $search_field LIKE '%$search_keyword%'";
    }
  }

  return $search_where_clause;
}


/**
 * Used in the ft_search_submissions function to abstract away a few minor details.
 *
 * @param array $submission_ids
 * @return string
 */
function _ft_get_search_submissions_submission_id_clause($submission_ids)
{
  $submission_id_clause = "";
  if (!empty($submission_ids))
  {
    $rows = array();
    foreach ($submission_ids as $submission_id)
      $rows[] = "submission_id = $submission_id";

    $submission_id_clause = "AND (" . join(" OR ", $rows) . ") ";
  }

  return $submission_id_clause;
}


/**
 * This function is used for displaying and exporting the data. Basically it merges all information
 * about a particular field from the view_fields table with the form_fields and field_options table,
 * providing ALL information about a field in a single variable.
 *
 * It accepts the result of the ft_get_view_fields() function as the first parameter and an optional
 * boolean to let it know whether to return ALL results or not.
 *
 * TODO maybe deprecate? Only used mass_edit
 *
 * @param array $view_fields
 * @param boolean $return_all_fields
 */
function ft_get_submission_field_info($view_fields)
{
  $display_fields = array();
  foreach ($view_fields as $field)
  {
    $field_id = $field["field_id"];
    $curr_field_info = array("field_id"    => $field_id,
                             "field_title" => $field["field_title"],
                             "col_name"    => $field["col_name"],
                             "list_order"  => $field["list_order"]);
    $field_info = ft_get_form_field($field_id);
    $curr_field_info["field_info"] = $field_info;
    $display_fields[] = $curr_field_info;
  }

  return $display_fields;
}


/**
 * This checks to see if a particular submission meets the criteria to belong in a particular View.
 * It only applies to those Views that have one or more filters set up, but it works on all Views
 * nonetheless.
 *
 * @param integer $view_id
 * @param integer $view_id
 * @param integer $submission_id
 */
function ft_check_view_contains_submission($form_id, $view_id, $submission_id)
{
  global $g_table_prefix;

  $filter_sql = ft_get_view_filter_sql($view_id);

  if (empty($filter_sql))
    return true;

  $filter_sql_clause = join(" AND ", $filter_sql);

  $query = @mysql_query("
    SELECT count(*) as c
    FROM   {$g_table_prefix}form_{$form_id}
    WHERE  submission_id = $submission_id AND
           ($filter_sql_clause)
      ");

  $result = mysql_fetch_assoc($query);

  return $result["c"] == 1;
}


/**
 * A helper function to find out it a submission is finalized or not.
 *
 * Assumption: form ID and submission ID are both valid & the form is fully set up and configured.
 *
 * @param integer $form_id
 * @param integer $submission_id
 * @return boolean
 */
function ft_check_submission_finalized($form_id, $submission_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT is_finalized
    FROM   {$g_table_prefix}form_$form_id
    WHERE  submission_id = $submission_id
           ");

  $result = mysql_fetch_assoc($query);

  return $result["is_finalized"] == "yes";
}


/**
 * A helper function to find out it a submission is finalized or not.
 *
 * Assumption: form ID and submission ID are both valid & the form is fully set up and configured.
 *
 * @param integer $form_id
 * @param integer $submission_id
 * @return boolean
 */
function ft_check_submission_exists($form_id, $submission_id)
{
  global $g_table_prefix;

  $query = @mysql_query("
    SELECT submission_id
    FROM   {$g_table_prefix}form_$form_id
    WHERE  submission_id = $submission_id
           ");

  if ($query)
    return (mysql_num_rows($query) == 1);
  else
    return null;
}


/**
 * This generic function processes any form field with a field type that requires additional
 * processing, e.g. phone number fields, date fields etc. - anything that needs a little extra PHP
 * in order to convert the form data into
 *
 * This function must
 *
 * @param array $info
 */
function ft_process_form_field($vars)
{
  eval($vars["code"]);
  $value = (isset($value)) ? $value : "";
  return ft_sanitize($value);
}


/**
 * Used for retrieving the data for a mapped form field; i.e. a dropdown, radio group or checkbox group
 * field whose source contents is the contents of a different form field.
 *
 * @param integer $form_id
 * @param array $results a complex data structure
 */
function ft_get_mapped_form_field_data($setting_value)
{
  global $g_table_prefix;

  $trimmed = preg_replace("/form_field:/", "", $setting_value);

  // this prevents anything wonky being shown if the following query fails (for whatever reason)
  $formatted_results = "";

  list($form_id, $field_id, $order) = explode("|", $trimmed);
  if (!empty($form_id) && !empty($field_id) && !empty($order))
  {
    $map = ft_get_field_col_by_field_id($form_id, $field_id);
    $col_name = $map[$field_id];
    $query = @mysql_query("
      SELECT submission_id, $col_name
      FROM   {$g_table_prefix}form_{$form_id}
      ORDER BY $col_name $order
    ");
    if ($query)
    {
      $results = array();
      while ($row = mysql_fetch_assoc($query))
      {
        $results[] = array(
          "option_value" => $row["submission_id"],
          "option_name"  => $row[$col_name]
        );
      }

      // yuck! But we need to force the form field info into the same format as the option lists,
      // so the Field Types don't need to do additional work to display both cases
      $formatted_results = array(
        "type"     => "form_field",
        "form_id"  => $form_id,
        "field_id" => $field_id,
        "options" => array(
          array(
            "group_info" => array(),
            "options" => $results
          )
        )
      );
    }
  }

  return $formatted_results;
}


/**
 * Added in 2.1.0. This lets modules add an icon to a "quicklink" icon row on the Submission Listing page. To add it,
 * they need to define a hook call and return a $quicklinks hash with the following keys:
 *   icon_url
 *   alt_text
 *
 * @param $context "admin" or "client"
 */
function ft_display_submission_listing_quicklinks($context, $page_data)
{
  global $g_root_url;

  $quicklinks = array();
  extract(ft_process_hook_calls("main", compact("context"), array("quicklinks"), array("quicklinks")), EXTR_OVERWRITE);

  if (empty($quicklinks))
    return "";

  echo "<ul id=\"ft_quicklinks\">";

  $num_quicklinks = count($quicklinks);
  for ($i=0; $i<$num_quicklinks; $i++)
  {
    $classes = array();
    if ($i == 0)
      $classes[] = "ft_quicklinks_first";
    if ($i == $num_quicklinks - 1)
      $classes[] = "ft_quicklinks_last";

    $class = implode(" ", $classes);

    $quicklink_info = $quicklinks[$i];
    $icon_url       = isset($quicklink_info["icon_url"]) ? $quicklink_info["icon_url"] : "";
    $title_text     = isset($quicklink_info["title_text"]) ? $quicklink_info["title_text"] : "";
    $onclick        = isset($quicklink_info["onclick"]) ? $quicklink_info["onclick"] : "";
    $title_text = htmlspecialchars($title_text);

    if (empty($icon_url))
      continue;

    echo "<li class=\"$class\" onclick=\"$onclick\"><img src=\"$icon_url\" title=\"$title_text\" /></li>\n";
  }

  echo "</ul>";
}
