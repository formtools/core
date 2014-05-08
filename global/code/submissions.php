<?php

/**
 * This file defines all functions related to managing form submissions.
 *
 * @copyright Encore Web Studios 2008
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-0
 * @subpackage Submissions
 */


// -------------------------------------------------------------------------------------------------


/**
 * Creates a new blank submission in the database and returns the unique submission ID. If the
 * operation fails for whatever reason (e.g. the form doesn't exist), it just returns the empty
 * string.
 *
 * @param integer $form_id
 * @param boolean $is_finalized whether the submission is finalized or not.
 */
function ft_create_blank_submission($form_id, $is_finalized = false)
{
  global $g_table_prefix;

  if (!ft_check_form_exists($form_id))
    return "";

  $now = ft_get_current_datetime();
  $ip  = $_SERVER["REMOTE_ADDR"];

  mysql_query("
    INSERT INTO {$g_table_prefix}form_{$form_id} (submission_date, last_modified_date, ip_address)
    VALUES ('$now', '$now', '$ip')
      ");

  return mysql_insert_id();
}


/**
 * Deletes an individual submission. If the $is_admin value isn't set (or set to FALSE), it checks
 * to see if the currently logged in user is allowed to delete the submission ID.
 *
 * @param integer $form_id
 * @param integer $view_id
 * @param integer $submission_id
 * @param boolean $is_admin TODO
 */
function ft_delete_submission($form_id, $view_id, $submission_id, $is_admin = false)
{
  global $g_table_prefix, $LANG;

	$form_info = ft_get_form($form_id);
	$form_fields = ft_get_form_fields($form_id);

	$auto_delete_submission_files = $form_info["auto_delete_submission_files"];
	$file_delete_problems = array();
	$form_has_file_field = false;

	// send any emails
  ft_send_emails("on_delete", $form_id, $submission_id);

	// loop the form templates to find out if there are any file fields. If there are - and the user
	// configured it - delete any associated files
	foreach ($form_fields as $field_info)
	{
		$field_type = $field_info["field_type"];

		if ($field_type == "file" || $field_type == "image")
		{
			$form_has_file_field = true;

			// store the filename we're about to delete BEFORE deleting it. The reason being,
			// if the delete_file_submission function can't find the file, it updates the database record
			// (i.e. overwrites the file name with "") and returns a message indicating what happened.
			// If this wasn't done, in the event of a file being removed/renamed by another process, the
			// user could NEVER remove the filename from their interface. This seems the least inelegant
			// solution. By storing the filename here, we can display it to the user to explain what
			// happened.
			if ($auto_delete_submission_files == "no")
				continue;

			$submission_info = ft_get_submission_info($form_id, $submission_id);
			$filename = $submission_info[$field_info['col_name']];

			// if no filename was stored, it was empty - just continue
			if (empty($filename))
				continue;

		  if ($field_type == "file")
			  list($success, $message) = ft_delete_file_submission($form_id, $submission_id, $field_info['field_id']);
			else if ($field_type == "image")
			  list($success, $message) = img_delete_image_file_submission($form_id, $submission_id, $field_info['field_id']);

			if (!$success)
				$file_delete_problems[] = array($filename, $message);
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
			$message = ($form_has_file_field) ? $LANG["notify_submission_and_files_deleted"] : $LANG["notify_submission_deleted"];
		}
		else
		{

			$success = false;
			$message = $LANG["notify_submission_deleted_with_problems"] . "<br /><br />";

			foreach ($file_delete_problems as $problem)
				$message .= "&bull; <b>{$problem[0]}</b>: {$problem[1]}<br />\n";
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
 * Assumption: if deleting an image field, the Image Manager module has been imported.
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
	  $submission_ids = ft_get_search_submission_ids($form_id, $view_id, "all", "submission_id-ASC", $search_fields);
	  $submission_ids = array_diff($submission_ids, $omit_list);
	}
	else
	{
    $submission_ids = $submissions_to_delete;
	}

	$form_info = ft_get_form($form_id);
	$form_fields = ft_get_form_fields($form_id);

	$auto_delete_submission_files = $form_info["auto_delete_submission_files"];

	$submission_ids_qry = array();
	foreach ($submission_ids as $submission_id)
  	$submission_ids_qry[] = "submission_id = $submission_id";

	$where_clause = "WHERE " . join(" OR ", $submission_ids_qry);


	$file_delete_problems = array();
	$form_has_file_field  = false;

	// loop the form templates to find out if there are any file fields. If there are - and the user
	// configured it - delete any associated files
	foreach ($form_fields as $field_info)
	{
		$field_type = $field_info["field_type"];

		if ($field_type == "file" || $field_type == "image")
		{
			$form_has_file_field = true;

			// store the filename we're about to delete BEFORE deleting it. The reason being,
			// if the delete_file_submission function can't find the file, it updates the database record
			// (i.e. overwrites the file name with "") and returns a message indicating what happened.
			// If this wasn't done, in the event of a file being removed/renamed by another process, the
			// user could NEVER remove the filename from their interface. This seems the least inelegant
			// solution. By storing the filename here, we can display it to the user to explain what
			// happened.
			if ($auto_delete_submission_files == "no")
				continue;

			foreach ($submission_ids as $submission_id)
			{
				$submission_info = ft_get_submission_info($form_id, $submission_id);
				$filename = $submission_info[$field_info["col_name"]];

				// if no filename was stored, it was empty - just continue
				if (empty($filename))
					continue;

			  if ($field_type == "file")
				  list($success, $message) = ft_delete_file_submission($form_id, $submission_id, $field_info['field_id']);
				else if ($field_type == "image")
				  list($success, $message) = img_delete_image_file_submission($form_id, $submission_id, $field_info['field_id']);

				if (!$success)
					$file_delete_problems[] = array($filename, $message);
			}
		}
	}


	// now delete the submission
	mysql_query("
		 DELETE FROM {$g_table_prefix}form_{$form_id}
		 $where_clause
							");

	if ($auto_delete_submission_files == "yes")
	{
		if (empty($file_delete_problems))
		{
			$success = true;

			if (count($submission_ids) > 1)
				$message = ($form_has_file_field) ? $LANG["notify_submissions_and_files_deleted"] :
					$LANG["notify_submissions_deleted"];
			else
				$message = ($form_has_file_field) ? $LANG["notify_submission_and_files_deleted"] :
					$LANG["notify_submission_deleted"];
		}
		else
		{
			$success = false;

			if (count($submission_ids) > 1)
				$message = $LANG["notify_submissions_deleted_with_problems"] . "<br /><br />";
			else
				$message = $LANG["notify_submission_deleted_with_problems"] . "<br /><br />";

			foreach ($file_delete_problems as $problem)
				$message .= "&bull; <b>{$problem[0]}</b>: $problem[1]<br />\n";
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


	// update sessions to ensure the first submission date and num submissions for this form View are correct
	_ft_cache_form_stats($form_id);
	_ft_cache_view_stats($view_id);

	$_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";
	$_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
	$_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();

	// loop through all submissions deleted and send any emails
	reset($submission_ids);
	foreach ($submission_ids as $submission_id)
	  ft_send_emails("on_delete", $form_id, $submission_id);


	return array($success, $message);
}


/**
 * Deletes a file that has been uploaded through a particular form submission file field.
 *
 * Now say that 10 times fast.
 *
 * @param integer $form_id the unique form ID
 * @param integer $submission_id a unique submission ID
 * @param integer $field_id a unique form field ID
 * @param boolean $force_delete this forces the file to be deleted from the database, even if the
 *                file itself doesn't exist or doesn't have the right permissions.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_delete_file_submission($form_id, $submission_id, $field_id, $force_delete = false)
{
	global $g_table_prefix, $LANG;

	// get the column name and upload folder for this field
	$field_info = ft_get_form_field($field_id);
	$extended_field_settings = ft_get_extended_field_settings($field_id);

	$col_name    = $field_info["col_name"];
	$file_folder = $extended_field_settings["file_upload_dir"];

	// if the column name wasn't found, the $field_id passed in was invalid. Return false.
	if (empty($col_name))
		return array(false, $LANG["notify_submission_no_field_id"]);

	$query = "
		SELECT $col_name
		FROM   {$g_table_prefix}form_{$form_id}
		WHERE  submission_id = $submission_id
						";

	$result = mysql_query($query);
	$file_info = mysql_fetch_row($result);
	$file = $file_info[0];

	$update_database_record = false;
	$success = true;
	$message = "";

	if (!empty($file))
	{
	  if ($force_delete)
	  {
	  	@unlink("$file_folder/$file");
			$message = $LANG["notify_file_deleted"];
			$update_database_record = true;
	  }
	  else
	  {
	    if (@unlink("$file_folder/$file"))
	    {
				$success = true;
				$message = $LANG["notify_file_deleted"];
				$update_database_record = true;
	    }
	    else
	    {
			  if (!is_file("$file_folder/$file"))
			  {
					$success = false;
					$update_database_record = false;
					$replacements = array("js_link" => "return ms.delete_submission_file($field_id, 'file', true)");
					$message = ft_eval_smarty_string($LANG["notify_file_not_deleted_no_exist"], $replacements);
			  }
			  else if (is_file("$file_folder/$file") && (!is_readable("$file_folder/$file") || !is_writable("$file_folder/$file")))
			  {
					$success = false;
					$update_database_record = false;
					$replacements = array("js_link" => "return ms.delete_submission_file($field_id, 'file', true)");
					$message = ft_eval_smarty_string($LANG["notify_file_not_deleted_permissions"], $replacements);
			  }
			  else
			  {
					$success = false;
					$update_database_record = false;
					$replacements = array("js_link" => "return ms.delete_submission_file($field_id, 'file', true)");
					$message = ft_eval_smarty_string($LANG["notify_file_not_deleted_unknown_error"], $replacements);
			  }
	    }
	  }
	}

	// if need be, update the database record to remove the reference to the file in the database. Generally this
	// should always work, but in case something funky happened, like the permissions on the file were changed to
	// forbid deleting, I think it's best if the record doesn't get deleted to remind the admin/client it's still
	// there.
	if ($update_database_record)
	{
		$query = mysql_query("
			UPDATE {$g_table_prefix}form_{$form_id}
			SET    $col_name = ''
			WHERE  submission_id = $submission_id
						 ");
	}

	return array($success, $message);
}


/**
 * Retrieves everything about a form submission for use in a display / edit form submission page.
 * It contains all meta-information about the field, from the form_fields and view_tabs. If the
 * optional view_id parameter is included, only the fields in the View are returned (AND all system
 * fields, if they're not included).
 *
 * @param integer $form_id the unique form ID
 * @param integer $submission_id the unique submission ID
 * @param integer $view_id an optional view ID parameter
 * @return array Returns an array of hashes. Each index is a separate form field and it's value is
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

	return $return_arr;
}


/**
 * Retrieves ONLY the submission data itself. If you require "meta" information about the submision
 * such as it's field type, size, database table name etc, use {@link
 * http://www.formtools.org/developerdoc/1-4-6/Submissions/_code---submissions.php.html#functionget_submission
 * get_submission}.
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
	    $filter_sql_clause = join(" AND ", $filter_sql);
	}

	// get the form submission info
	$query = mysql_query("
		 SELECT count(*)
		 FROM   {$g_table_prefix}form_{$form_id}
		        $filter_sql_clause
		 WHERE  is_finalized = 'yes'
							");

	$result = mysql_fetch_array($query);
	$submission_count = $result[0];

	return $submission_count;
}


/**
 * Returns all submission IDs in a search result set. This is used on the item details pages (admin
 * and client) to build the << previous / next >> links.
 *
 * @param integer $form_id The unique form ID
 * @param integer $view_id The unique form ID
 * @param mixed   $results_per_page an integer, or "all"
 * @param string  $order A string of form: "{db column}_{ASC|DESC}"
 * @param array   $search_fields an optional hash with these keys:<br/>
 *                  search_field<br/>
 *                  search_date<br/>
 *                  search_keyword<br/>
 * @return string an HTML string
 */
function ft_get_search_submission_ids($form_id, $view_id, $results_per_page, $order, $search_fields = array())
{
	global $g_table_prefix;

	// sorting by column, format: col_x-desc / col_y-asc
	list($column, $direction) = split("-", $order);
	$field_info = ft_get_form_field_by_colname($form_id, $column);

	if ($field_info["data_type"] == "number")
		$order_by = "CAST($column as SIGNED) $direction";
	else
		$order_by = "$column $direction";

	// determine the LIMIT clause
	$limit_clause = "";
	if ($results_per_page != "all")
	{
		if (empty($page_num))
			$page_num = 1;
		$first_item = ($page_num - 1) * $results_per_page;

		$limit_clause = "LIMIT $first_item, $results_per_page";
	}

	// any filters?
	$view_filters = ft_get_view_filter_sql($view_id);
	$filter_clause = "";
	if (!empty($view_filters))
	  $filter_clause = "AND " . join(" AND ", $view_filters);

	// if search fields were included, build an addition to the WHERE clause
	$search_where_clause = "";
	if (!empty($search_fields))
	{
		$clean_search_fields = ft_sanitize($search_fields);

		$search_field   = $clean_search_fields["search_field"];
		$search_date    = $clean_search_fields["search_date"];
		$search_keyword = $clean_search_fields["search_keyword"];

		// search field can either be "all" or a database column name. "submission_date"
		// has a special meaning in that it allows searching by specific date ranges
		switch ($search_field)
		{
			case "all":
				if (!empty($search_keyword))
				{
					// get all columns
					$col_info = ft_get_form_column_names($form_id);
					$col_names = array_keys($col_info);
					unset($col_names["is_finalized"]);
					unset($col_names["submission_date"]);

					$clauses = array();
					foreach ($col_names as $col_name)
						$clauses[] = "$col_name LIKE '%$search_keyword%'";

					$search_where_clause = "AND (" . join(" OR ", $clauses) . ") ";
				}
				break;

			case "submission_date":
				if (!empty($search_date))
				{
					// search by number of days
					if (is_numeric($search_date))
					{
						$days = $search_date;
						$search_where_clause = "AND (DATE_SUB(curdate(), INTERVAL $days DAY) < submission_date) ";
					}

					// otherwise, return a specific month
					else
					{
						list($month, $year) = split("_", $search_date);

						$month_start = mktime(0, 0, 0, $month, 1, $year);
						$month_end   = mktime(0, 0, 0, $month+1, 1, $year);

						$start = date("Y-m-d", $month_start);
						$end   = date("Y-m-d", $month_end);

						$search_where_clause = "AND (submission_date > '$start' AND submission_date < '$end') ";
					}

					if (!empty($search_keyword))
					{
						// get all columns
						$col_info = ft_get_form_column_names($form_id);
						$col_names = array_keys($col_info);
						unset($col_names["is_finalized"]);
						unset($col_names["submission_date"]);

						$clauses = array();
						foreach ($col_names as $col_name)
							$clauses[] = "$col_name LIKE '%$search_keyword%'";

						$search_where_clause .= "AND (" . join(" OR ", $clauses) . ") ";
					}
				}
				break;

			// here, the user is searching one of their own custom fields
			default:
				if (!empty($search_keyword) && !empty($search_field))
					$search_where_clause = "AND $search_field LIKE '%$search_keyword%'";
				break;
		}
	}

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
 * This updates all field types, including files. Note: it does not DELETE files - that's handled
 * separately by ft_delete_file_submission.
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the update submission page. The contents of it change for each
 *             form content.
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

	// assumes that each tab as at least a single field (UPDATE button should be hidden in those cases)
  $field_ids = split(",", $infohash["field_ids"]);

	$form_fields = ft_get_form_fields($form_id);
	$db_column_names = array();

  $now = ft_get_current_datetime();
	$query = array();
	$query[] = "last_modified_date = '$now'";

	$file_fields = array();

	$submission_date_changed = false;

	foreach ($form_fields as $row)
	{
	  // if the field ID isn't in the page's tab, ignore it
    if (!in_array($row["field_id"], $field_ids))
      continue;

    // if the field ID isn't editable, the person's being BAD and trying to hack a field value. Ignore it.
    if (!in_array($row["field_id"], $infohash["editable_field_ids"]))
      continue;

		// keep track of the file fields & their IDs. These will be used to upload the files (if need be)
		if ($row['field_type'] == "file")
			$file_fields[] = array("field_id" => $row['field_id'], "col_name" => $row['col_name'], "field_type" => "file");
		else if ($row["field_type"] == "image")
		  $file_fields[] = array("field_id" => $row['field_id'], "col_name" => $row['col_name'], "field_type" => "image");
		else
		{
			// if this is the Submission Date or Last Modified Date fields, check that the information the user has
			// supplied is a valid MySQL datetime. If it's invalid or empty, we DON'T update the value
			if ($row["col_name"] == "submission_date" || $row["col_name"] == "last_modified_date")
			{
				if (!isset($infohash[$row["col_name"]]) || empty($infohash[$row["col_name"]]) || !ft_is_valid_datetime($infohash[$row["col_name"]]))
				  continue;

				$submission_date_changed = true;
			}

			if (isset($infohash[$row["col_name"]]))
			{
				if (is_array($infohash[$row["col_name"]]))
					$query[] = $row["col_name"] . " = '" . join("$g_multi_val_delimiter", $infohash[$row["col_name"]]) . "'";
				else
					$query[] = $row["col_name"] . " = '" . $infohash[$row["col_name"]] . "'";
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

	if (!$result)
		return array(false, $LANG["notify_submission_not_updated"]);


	// now the submission exists in the database, upload any files
	if (!empty($file_fields))
	{
		$problem_files = array();

		while (list($form_field_name, $fileinfo) = each($_FILES))
		{
			// if nothing was included in this field, just ignore it
			if (empty($fileinfo['name']))
				continue;

		  foreach ($file_fields as $field_info)
		  {
		    $field_id   = $field_info["field_id"];
		    $col_name   = $field_info["col_name"];
		    $field_type = $field_info["field_type"];

		    if ($col_name == $form_field_name)
		    {
		      if ($field_type == "file")
		      {
						list($success2, $message2) = ft_upload_submission_file($form_id, $submission_id, $field_id, $fileinfo);
						if (!$success2)
							$problem_files[] = array($fileinfo['name'], $message2);
		      }
		      else if ($field_type == "image")
		      {
						list ($success2, $message2) = ft_upload_submission_image($form_id, $submission_id, $field_id, $fileinfo);
						if (!$success2)
							$problem_files[] = array($fileinfo['name'], $message2);
		      }
		    }
		  }
		}

		if (!empty($problem_files))
		{
			$message = $LANG["notify_submission_updated_file_problems"] . "<br /><br />";
			foreach ($problem_files as $problem)
				$message .= "&bull; <b>{$problem[0]}</b>: $problem[1]<br />\n";

			return array(false, $message);
		}
	}

	// if the submission date just changed, update sessions in case it was the FIRST submission (this updates the
	// search date dropdown)
	if ($submission_date_changed)
		_ft_cache_form_stats($form_id);

  // send any emails
  ft_send_emails("on_edit", $form_id, $submission_id);

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

	return true;
}


/**
 * Creates and returns a search for any form View, and any subset of its columns, returning results in
 * any column order and for any single page subset (or all pages).
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
 * @param array $submission_ids an optional array containing a list of submission IDs to return. This may
 *              seem counterintuitive to pass the results that it needs to return to the function that
 *              figures out WHICH results to return, but it's actually kinda handy: this function returns
 *              exactly the field information that's needed in the order that's needed.
 * @return array returns a hash with these keys:<br/>
 *                ["search_query"]       => an array of hashes, each index a search result row<br />
 *                ["search_num_results"] => the number of results in the search (not just the 10 or so
 *                                          that will appear in the current page, listed in the
 *                                          "search_query" key<br />
 *                ["view_num_results"]   => the total number of results in this View, regardless of the
 *                                          current search values.
 */
function ft_search_submissions($form_id, $view_id, $results_per_page, $page_num, $order, $columns, $search_fields = array(), $submission_ids = array())
{
	global $g_table_prefix;

	// sorting by column, format: col_x-desc / col_y-asc
	list($column, $direction) = split("-", $order);
	$field_info = ft_get_form_field_by_colname($form_id, $column);

	if ($field_info["data_type"] == "number")
		$order_by = "CAST($column as SIGNED) $direction";
	else
		$order_by = "$column $direction";

	// determine the LIMIT clause
	$limit_clause = "";
	if ($results_per_page != "all")
	{
		if (empty($page_num))
			$page_num = 1;
		$first_item = ($page_num - 1) * $results_per_page;

		$limit_clause = "LIMIT $first_item, $results_per_page";
	}

	$select_clause = "";
	if (!is_array($columns) && $columns == "all")
	{
		$select_clause = " * ";
	}
	else
	{
		// if submission_id isn't included, add it - it'll be needed at some point
		if (!in_array("submission_id", $columns))
			$columns[] = "submission_id";

		$select_clause = join(", ", $columns);
	}

	// any filters?
	$view_filters = ft_get_view_filter_sql($view_id);
	$filter_clause = "";
	if (!empty($view_filters))
	  $filter_clause = "AND " . join(" AND ", $view_filters);

	// submission IDs?
	$submission_id_clause = "";
	if (!empty($submission_ids))
	{
	  $rows = array();
	  foreach ($submission_ids as $submission_id)
	    $rows[] = "submission_id = $submission_id";

	  $submission_id_clause = "AND (" . join(" OR ", $rows) . ") ";
	}

	// if search fields were included, build an addition to the WHERE clause
	$search_where_clause = "";
	if (!empty($search_fields))
	{
		$clean_search_fields = ft_sanitize($search_fields);

		$search_field   = $clean_search_fields["search_field"];
		$search_date    = $clean_search_fields["search_date"];
		$search_keyword = $clean_search_fields["search_keyword"];

		// search field can either be "all" or a database column name. "submission_date" and "last_modified_date"
		// have special meanings, since they allow for searching by specific date ranges
		if ($search_field == "all")
		{
			if (!empty($search_keyword))
			{
				// if we're searching ALL columns, get all the name
	      if (!is_array($columns) && $columns == "all")
	      {
					$col_info = ft_get_form_column_names($form_id);
					$col_names = array_keys($col_info);
					unset($col_names["is_finalized"]);
					unset($col_names["submission_date"]);
					unset($col_names["last_modified_date"]);

					$clauses = array();
					foreach ($col_names as $col_name)
						$clauses[] = "$col_name LIKE '%$search_keyword%'";
	      }
	      else
	      {
	        $clauses = array();
					foreach ($columns as $col_name)
						$clauses[] = "$col_name LIKE '%$search_keyword%'";
	      }

		  	$search_where_clause = "AND (" . join(" OR ", $clauses) . ") ";
			}
		}
		else if ($search_field == "submission_date" || $search_field == "last_modified_date")
		{

			if (!empty($search_date))
			{
				// search by number of days
				if (is_numeric($search_date))
				{
					$days = $search_date;
					$search_where_clause = "AND (DATE_SUB(curdate(), INTERVAL $days DAY) < $search_field) ";
				}

				// otherwise, return a specific month
				else
				{
					list($month, $year) = split("_", $search_date);

					$month_start = mktime(0, 0, 0, $month, 1, $year);
					$month_end   = mktime(0, 0, 0, $month+1, 1, $year);

					$start = date("Y-m-d", $month_start);
					$end   = date("Y-m-d", $month_end);

					$search_where_clause = "AND ($search_field > '$start' AND $search_field < '$end') ";
				}

				if (!empty($search_keyword))
				{
					// get all columns
					$col_info = ft_get_form_column_names($form_id);
					$col_names = array_keys($col_info);
					unset($col_names["is_finalized"]);
					unset($col_names["submission_date"]);

					$clauses = array();
					foreach ($col_names as $col_name)
						$clauses[] = "$col_name LIKE '%$search_keyword%'";

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

	// Queries: [1] the main search query that returns a page of submission info
	$search_query = mysql_query("
			SELECT $select_clause
			FROM   {$g_table_prefix}form_{$form_id}
			WHERE  is_finalized = 'yes'
						 $search_where_clause
						 $filter_clause
						 $submission_id_clause
			ORDER BY $order_by
						 $limit_clause
						    ")
		or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: ", mysql_error());

	$search_result_rows = array();
	while ($row = mysql_fetch_assoc($search_query))
	  $search_result_rows[] = $row;


  // [2] find out how many results there are in this current search
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


  // [3] find out how many results should appear in the View, regardless of the current search criteria
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

	return $return_hash;
}


/**
 * This function is used for displaying and exporting the data. Basically it merges all information
 * about a particular field from the view_fields table with the form_fields and field_options table,
 * providing ALL information about a field in a single variable. This functionality is needed repeatedly
 * on multiple pages throughout the script, hence the abstraction.
 *
 * It accepts the result of the ft_get_view_fields() function as the first parameter and an optional
 * boolean to let it know whether to return ALL results or not.
 *
 * @param array $view_fields
 * @param boolean $return_all_fields
 */
function ft_get_submission_field_info($view_fields, $return_all_fields = false)
{
	$display_fields = array();
	foreach ($view_fields as $field)
	{
		$field_id = $field["field_id"];

		if ($field['is_column'] == "yes" || $return_all_fields)
	  {
	    $curr_field_info = array('field_id'    => $field_id,
	                             'is_sortable' => $field['is_sortable'],
	                             'field_title' => $field['field_title'],
	                             'col_name'    => $field['col_name'],
	                             'list_order'  => $field['list_order']);

			$field_info = ft_get_form_field($field_id, true);
		  $curr_field_info["field_info"] = $field_info;

		  $display_fields[] = $curr_field_info;
	  }
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

  $query = mysql_query("
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