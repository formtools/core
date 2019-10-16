<?php

/**
 * This file defines all functions related to managing form submissions.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Submissions
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use Exception, PDO;


class Submissions
{

	/**
	 * This function processes the form submissions - after the form has been set up in the database.
	 */
	public static function processFormSubmission($form_data)
	{
		$db = Core::$db;
		$LANG = Core::$L;
		$api_enabled = Core::isAPIAvailable();
		$multi_val_delimiter = Core::getMultiFieldValDelimiter();
		$query_str_multi_val_separator = Core::getQueryStrMultiValSeparator();

		// ensure the incoming values are escaped
		$form_id = is_numeric($form_data["form_tools_form_id"]) ? $form_data["form_tools_form_id"] : 0;
		$form_info = Forms::getForm($form_id);

		// do we have a form for this id?
		if (!Forms::checkFormExists($form_id)) {
			$page_vars = array(
				"message_type" => "error",
				"message" => $LANG["processing_invalid_form_id"]
			);
			Themes::displayPage("error.tpl", $page_vars);
			exit;
		}

		extract(Hooks::processHookCalls("start", compact("form_info", "form_id", "form_data"), array("form_data")), EXTR_OVERWRITE);

		// check to see if this form has been completely set up
		if ($form_info["is_complete"] == "no") {
			$page_vars = array(
				"message_type" => "error",
				"message" => $LANG["processing_form_incomplete"]
			);
			Themes::displayPage("error.tpl", $page_vars);
			exit;
		}

		// check to see if this form has been disabled
		if ($form_info["is_active"] == "no") {
			if (isset($form_data["form_tools_inactive_form_redirect_url"])) {
				header("location: {$form_data["form_tools_inactive_form_redirect_url"]}");
				exit;
			}
			$page_vars = array(
				"message_type" => "error",
				"message" => $LANG["processing_form_disabled"]
			);
			Themes::displayPage("error.tpl", $page_vars);
			exit;
		}

		// do we have a form for this id?
		if (!Forms::checkFormExists($form_id)) {
			$page_vars = array(
				"message_type" => "error",
				"message" => $LANG["processing_invalid_form_id"]
			);
			Themes::displayPage("error.tpl", $page_vars);
			exit;
		}

		// was there a reCAPTCHA response? If so, a recaptcha was just submitted. This generally implies the
		// form page included the API, so check it was entered correctly. If not, return the user to the webpage
		if (isset($api_enabled) && isset($form_data["g-recaptcha-response"])) {

			$api = new API(array("init_core" => false));
			$api->includeRecaptchaLib();
			$resp = $api->validateRecaptcha($form_data["g-recaptcha-response"]);

			if (!$resp->isSuccess()) {

				// since we need to pass all the info back to the form page we do it by storing the data in sessions.
				// Ensure they're enabled.
				Core::startSessions("api_form");
				$_SESSION["form_tools_form_data"] = $form_data;
				$_SESSION["form_tools_form_data"]["api_recaptcha_error"] = $resp->getErrorCodes();

				// if there's a form_tools_form_url specified, redirect to that
				if (isset($form_data["form_tools_form_url"])) {
					header("location: {$form_data["form_tools_form_url"]}");
					exit;

					// if not, see if the server has the redirect URL specified
				} else if (isset($_SERVER["HTTP_REFERER"])) {
					header("location: {$_SERVER["HTTP_REFERER"]}");
					exit;

					// no luck! Throw an error
				} else {
					$page_vars = array("message_type" => "error", "message" => $LANG["processing_no_form_url_for_recaptcha"]);
					Themes::displayPage("error.tpl", $page_vars);
					exit;
				}
			}
		}

		// get a list of the custom form fields (i.e. non-system) for this form
		$form_fields = Fields::getFormFields($form_id, array("include_field_type_info" => true));

		$custom_form_fields = array();
		$file_fields = array();
		foreach ($form_fields as $field_info) {
			$field_id = $field_info["field_id"];
			$is_system_field = $field_info["is_system_field"];
			$field_name = $field_info["field_name"];

			// ignore system fields
			if ($is_system_field == "yes") {
				continue;
			}

			if ($field_info["is_file_field"] == "no") {
				$custom_form_fields[$field_name] = array(
					"field_id" => $field_id,
					"col_name" => $field_info["col_name"],
					"field_title" => $field_info["field_title"],
					"include_on_redirect" => $field_info["include_on_redirect"],
					"field_type_id" => $field_info["field_type_id"],
					"is_date_field" => $field_info["is_date_field"]
				);
			} else {
				$file_fields[] = array(
					"field_id" => $field_id,
					"field_info" => $field_info
				);
			}
		}

		// now examine the contents of the POST/GET submission and get a list of those fields
		// which we're going to update
		$valid_form_fields = array();
		foreach ($form_data as $field_name => $value) {

			// if this field is included, store the value for adding to DB
			if (!array_key_exists($field_name, $custom_form_fields)) {
				continue;
			}
			$curr_form_field = $custom_form_fields[$field_name];

			$cleaned_value = $value;
			if (is_array($value)) {
				if ($form_info["submission_strip_tags"] == "yes") {
					for ($i = 0; $i < count($value); $i++) {
						$value[$i] = strip_tags($value[$i]);
					}
				}
				$cleaned_value = implode("$multi_val_delimiter", $value);
			} else {
				if ($form_info["submission_strip_tags"] == "yes") {
					$cleaned_value = strip_tags($value);
				}
			}

			$valid_form_fields[$curr_form_field["col_name"]] = $cleaned_value;
		}

		$now = General::getCurrentDatetime();
		$ip_address = $_SERVER["REMOTE_ADDR"];

		// now tack on the system fields
		$valid_form_fields["submission_date"] = $now;
		$valid_form_fields["last_modified_date"] = $now;
		$valid_form_fields["ip_address"] = $ip_address;
		$valid_form_fields["is_finalized"] = "yes";

		list ($col_names_str, $placeholders_str, $valid_form_fields) = $db->getInsertStatementParams($valid_form_fields);

		// add the submission to the database (if form_tools_ignore_submission key isn't set by either the form or a module)
		$submission_id = "";
		if (!isset($form_data["form_tools_ignore_submission"])) {
			try {
				$db->query("
                    INSERT INTO {PREFIX}form_$form_id ($col_names_str)
                    VALUES ($placeholders_str)
                ");
				$db->bindAll($valid_form_fields);
				$db->execute();
			} catch (Exception $e) {
				Themes::displayPage("error.tpl", array(
					"message_type" => "error",
					"error_code" => 304,
					"error_type" => "system",
					"debugging" => "Failed query in <b>" . __CLASS__ . ", " . __FILE__ . "</b>, line " . __LINE__ . ", error: " . $e->getMessage()
				));
				exit;
			}

			$submission_id = $db->getInsertId();
			extract(Hooks::processHookCalls("end", compact("form_id", "submission_id"), array()), EXTR_OVERWRITE);
		}


		$redirect_query_params = array();

		// build the redirect query parameter array
		foreach ($form_fields as $field_info) {
			if ($field_info["include_on_redirect"] == "no" || $field_info["is_file_field"] == "yes") {
				continue;
			}

			switch ($field_info["col_name"]) {
				case "submission_id":
					$redirect_query_params[] = "submission_id=$submission_id";
					break;
				case "submission_date":
					$settings = Settings::get();
					$submission_date_formatted = General::getDate($settings["default_timezone_offset"], $now, $settings["default_date_format"]);
					$redirect_query_params[] = "submission_date=" . rawurlencode($submission_date_formatted);
					break;
				case "last_modified_date":
					$settings = Settings::get();
					$submission_date_formatted = General::getDate($settings["default_timezone_offset"], $now, $settings["default_date_format"]);
					$redirect_query_params[] = "last_modified_date=" . rawurlencode($submission_date_formatted);
					break;
				case "ip_address":
					$redirect_query_params[] = "ip_address=$ip_address";
					break;

				default:
					$field_name = $field_info["field_name"];

					// if $value is an array, convert it to a string, separated by $g_query_str_multi_val_separator
					if (isset($form_data[$field_name])) {
						if (is_array($form_data[$field_name])) {
							$value_str = join($query_str_multi_val_separator, $form_data[$field_name]);
							$redirect_query_params[] = "$field_name=" . rawurlencode($value_str);
						} else {
							$redirect_query_params[] = "$field_name=" . rawurlencode($form_data[$field_name]);
						}
					}
					break;
			}
		}

		// only upload files & send emails if we're not ignoring the submission
		if (!isset($form_data["form_tools_ignore_submission"])) {
			// now process any file fields. This is placed after the redirect query param code block above to allow whatever file upload
			// module to append the filename to the query string, if needed
			extract(Hooks::processHookCalls("manage_files", compact("form_id", "submission_id", "file_fields", "redirect_query_params"), array("success", "message", "redirect_query_params")), EXTR_OVERWRITE);

			// send any emails
			Emails::sendEmails("on_submission", $form_id, $submission_id);
		}

		// if the redirect URL has been specified either in the database or as part of the form
		// submission, redirect the user [form submission form_tools_redirect_url value overrides
		// database value]
		if (!empty($form_info["redirect_url"]) || !empty($form_data["form_tools_redirect_url"])) {
			// build redirect query string
			$redirect_url = (isset($form_data["form_tools_redirect_url"]) && !empty($form_data["form_tools_redirect_url"]))
				? $form_data["form_tools_redirect_url"] : $form_info["redirect_url"];

			$query_str = "";
			if (!empty($redirect_query_params)) {
				$query_str = join("&", $redirect_query_params);
			}

			if (!empty($query_str)) {
				// only include the ? if it's not already there
				if (strpos($redirect_url, "?")) {
					$redirect_url .= "&" . $query_str;
				} else {
					$redirect_url .= "?" . $query_str;
				}
			}

			General::redirect($redirect_url);
		}

		// the user should never get here! This means that the no redirect URL has been specified
		$page_vars = array("message_type" => "error", "message" => $LANG["processing_no_redirect_url"]);
		Themes::displayPage("error.tpl", $page_vars);
		exit;
	}


	/**
	 * Creates a new blank submission in the database and returns the unique submission ID. If the
	 * operation fails for whatever reason (e.g. the form doesn't exist), it just returns the empty
	 * string.
	 * @param $form_id
	 * @param $view_id
	 * @param bool $is_finalized
	 * @param array $account_placeholders
	 * @return string
	 */
	public static function createBlankSubmission($form_id, $view_id, $is_finalized = false, $account_placeholders = array())
	{
		$db = Core::$db;

		if (!Forms::checkFormExists($form_id)) {
			return "";
		}

		$now = General::getCurrentDatetime();
		$ip = $_SERVER["REMOTE_ADDR"];

		$default_insert_pairs = array(
			array(
				"placeholder" => ":submission_date",
				"col_name" => "submission_date",
				"value" => $now
			),
			array(
				"placeholder" => ":last_modified_date",
				"col_name" => "last_modified_date",
				"value" => $now
			),
			array(
				"placeholder" => ":ip_address",
				"col_name" => "ip_address",
				"value" => $ip
			),
			array(
				"placeholder" => ":is_finalized",
				"col_name" => "is_finalized",
				"value" => ($is_finalized) ? "yes" : "no"
			)
		);

		$special_defaults = Views::getNewViewSubmissionDefaults($view_id);
		if (!empty($special_defaults)) {

			// find the field's DB column names so we can do our insert
			$field_id_to_value_map = array();
			foreach ($special_defaults as $curr_default_info) {
				$field_id_to_value_map[$curr_default_info["field_id"]] = $curr_default_info["default_value"];
			}

			$field_ids = array_keys($field_id_to_value_map);
			$field_id_to_column_name_map = Fields::getFieldColByFieldId($form_id, $field_ids);

			foreach ($field_id_to_column_name_map as $field_id => $col_name) {

				// used to convert Client Map placeholders (user data + Extended Client Fields data)
				$value = General::evalSmartyString($field_id_to_value_map[$field_id], $account_placeholders);

				$default_insert_pairs[] = array(
					"placeholder" => ":{$col_name}",
					"col_name" => $col_name,
					"value" => $value
				);
			}
		}

		$col_names = implode(", ", array_column($default_insert_pairs, "col_name"));
		$placeholders = implode(", ", array_column($default_insert_pairs, "placeholder"));

		// create a map of placeholder => value
		$values = array();
		foreach ($default_insert_pairs as $row) {
			$values[$row["placeholder"]] = $row["value"];
		}

		$db->query("
            INSERT INTO {PREFIX}form_{$form_id} ($col_names)
            VALUES ($placeholders)
        ");
		$db->bindAll($values);
		$db->execute();

		$new_submission_id = $db->getInsertId();
		extract(Hooks::processHookCalls("end", compact("form_id", "now", "ip", "new_submission_id"), array()), EXTR_OVERWRITE);

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
	public static function deleteSubmission($form_id, $view_id, $submission_id, $is_admin = false)
	{
		$db = Core::$db;
		$LANG = Core::$L;

		extract(Hooks::processHookCalls("start", compact("form_id", "view_id", "submission_id", "is_admin"), array()), EXTR_OVERWRITE);

		$form_info = Forms::getForm($form_id);
		$form_fields = Fields::getFormFields($form_id);
		$auto_delete_submission_files = $form_info["auto_delete_submission_files"];

		// send any emails
		Emails::sendEmails("on_delete", $form_id, $submission_id);

		// loop the form templates to find out if there are any file fields. If there are - and the user
		// configured it - delete any associated files
		$file_delete_problems = array();
		$file_fields_to_delete = array();
		if ($auto_delete_submission_files == "yes") {
			$file_field_type_ids = FieldTypes::getFileFieldTypeIds();
			foreach ($form_fields as $field_info) {
				$field_type_id = $field_info["field_type_id"];

				if (!in_array($field_type_id, $file_field_type_ids)) {
					continue;
				}

				// I really don't like this... what should be done is do a SINGLE query after this loop is complete
				// to return a map of field_id to values. That would then update $file_fields_to_delete
				// with a fraction of the cost
				$submission_info = Submissions::getSubmissionInfo($form_id, $submission_id);
				$filename = $submission_info[$field_info['col_name']];

				// if no filename was stored, it was empty - just continue
				if (empty($filename))
					continue;

				$file_fields_to_delete[] = array(
					"submission_id" => $submission_id,
					"field_id" => $field_info["field_id"],
					"field_type_id" => $field_type_id,
					"filename" => $filename
				);
			}

			if (!empty($file_fields_to_delete)) {
				list($success, $file_delete_problems) = Files::deleteSubmissionFiles($form_id, $file_fields_to_delete, "Submissions::deleteSubmission");
			}
		}

		// now delete the submission
		$db->query("
            DELETE FROM {PREFIX}form_{$form_id}
            WHERE submission_id = :submission_id
        ");
		$db->bind("submission_id", $submission_id);
		$db->execute();

		if ($auto_delete_submission_files == "yes") {
			if (empty($file_delete_problems)) {
				$success = true;
				$message = ($file_fields_to_delete) ? $LANG["notify_submission_and_files_deleted"] : $LANG["notify_submission_deleted"];
			} else {
				$success = false;
				$message = $LANG["notify_submission_deleted_with_problems"] . "<br /><br />";
				foreach ($file_delete_problems as $problem) {
					$message .= "&bull; <b>{$problem["filename"]}</b>: {$problem["error"]}<br />\n";
				}
			}
		} else {
			$success = true;
			$message = $LANG["notify_submission_deleted"];
		}

		// update sessions to ensure the first submission date and num submissions for this form View are correct
		Forms::cacheFormStats($form_id);
		Views::cacheViewStats($view_id);

		extract(Hooks::processHookCalls("end", compact("form_id", "view_id", "submission_id", "is_admin"), array("success", "message")), EXTR_OVERWRITE);

		// update sessions
		$session_key = "form_{$form_id}_selected_submissions";
		if (Sessions::exists($session_key)) {
			$selected = Sessions::get($session_key);
			if (in_array($submission_id, Sessions::get($session_key))) {

				// TODO check this. Presumably need a Sessions::set()
				array_splice($selected, array_search($submission_id, $selected), 1);
			}
		}

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
	 * @param integer $view_id the unique view ID
	 * @param mixed $submissions_to_delete a single submission ID / an array of submission IDs / "all". This column
	 *               determines which submissions will be deleted
	 * @return array returns array with indexes:<br/>
	 *               [0]: true/false (success / failure)<br/>
	 *               [1]: message string<br/>
	 */
	public static function deleteSubmissions($form_id, $view_id, $submissions_to_delete, $omit_list, $search_fields)
	{
		$db = Core::$db;
		$LANG = Core::$L;

		if ($submissions_to_delete == "all") {
			// get the list of searchable columns for this View. This is needed to ensure that get_search_submission_ids receives
			// the correct info to determine what submission IDs are appearing in this current search.
			$searchable_columns = ViewFields::getViewSearchableFields($view_id);
			$submission_ids = Submissions::getSearchSubmissionIds($form_id, $view_id, "submission_id-ASC", $search_fields, $searchable_columns);
			$submission_ids = array_diff($submission_ids, $omit_list);
		} else {
			$submission_ids = $submissions_to_delete;
		}

		$submissions_to_delete = $submission_ids;

		// if a user is refreshing the page after a delete request, this will be empty
		if (empty($submission_ids)) {
			return array(false, "");
		}

		extract(Hooks::processHookCalls("start", compact("form_id", "view_id", "submissions_to_delete", "omit_list", "search_fields", "is_admin"), array("submission_ids")), EXTR_OVERWRITE);

		$form_info = Forms::getForm($form_id);
		$form_fields = Fields::getFormFields($form_id);
		$auto_delete_submission_files = $form_info["auto_delete_submission_files"];

		$submission_ids_qry = array();
		foreach ($submission_ids as $submission_id) {
			$submission_ids_qry[] = "submission_id = $submission_id";
		}

		$where_clause = "WHERE " . join(" OR ", $submission_ids_qry);


		// loop the form templates to find out if there are any file fields. If there are - and the user
		// configured it - delete any associated files
		$file_delete_problems = array();
		$form_has_file_field = false;
		if ($auto_delete_submission_files == "yes") {
			$file_field_type_ids = FieldTypes::getFileFieldTypeIds();
			$file_fields_to_delete = array();
			foreach ($submissions_to_delete as $submission_id) {
				foreach ($form_fields as $field_info) {
					$field_type_id = $field_info["field_type_id"];
					if (!in_array($field_type_id, $file_field_type_ids)) {
						continue;
					}

					$form_has_file_field = true;
					$submission_info = Submissions::getSubmissionInfo($form_id, $submission_id);
					$filename = $submission_info[$field_info['col_name']];

					// if no filename was stored, it was empty - just continue
					if (empty($filename)) {
						continue;
					}

					$file_fields_to_delete[] = array(
						"submission_id" => $submission_id,
						"field_id" => $field_info["field_id"],
						"field_type_id" => $field_type_id,
						"filename" => $filename
					);
				}
			}

			if (!empty($file_fields_to_delete)) {
				list($success, $file_delete_problems) = Files::deleteSubmissionFiles($form_id, $file_fields_to_delete);
			}
		}

		// now delete the submissions
		$db->query("DELETE FROM {PREFIX}form_{$form_id} $where_clause");
		$db->execute();

		if ($auto_delete_submission_files == "yes") {
			if (empty($file_delete_problems)) {
				$success = true;
				if (count($submission_ids) > 1) {
					$message = ($form_has_file_field) ? $LANG["notify_submissions_and_files_deleted"] : $LANG["notify_submissions_deleted"];
				} else {
					$message = ($form_has_file_field) ? $LANG["notify_submission_and_files_deleted"] : $LANG["notify_submission_deleted"];
				}
			} else {
				$success = false;
				if (count($submission_ids) > 1) {
					$message = $LANG["notify_submissions_deleted_with_problems"] . "<br /><br />";
				} else {
					$message = $LANG["notify_submission_deleted_with_problems"] . "<br /><br />";
				}
				foreach ($file_delete_problems as $problem) {
					$message .= "&bull; <b>{$problem["filename"]}</b>: {$problem["error"]}<br />\n";
				}
			}
		} else {
			$success = true;
			if (count($submission_ids) > 1) {
				$message = $LANG["notify_submissions_deleted"];
			} else {
				$message = $LANG["notify_submission_deleted"];
			}
		}

		// TODO update sessions to ensure the first submission date and num submissions for this form View are correct
		Forms::cacheFormStats($form_id);
		Views::cacheViewStats($form_id, $view_id);

		Sessions::set("form_{$form_id}_select_all_submissions", "");
		Sessions::set("form_{$form_id}_selected_submissions", array());
		Sessions::set("form_{$form_id}_all_submissions_selected_omit_list", array());

		// loop through all submissions deleted and send any emails
		reset($submission_ids);
		foreach ($submission_ids as $submission_id) {
			Emails::sendEmails("on_delete", $form_id, $submission_id);
		}

		$submissions_to_delete = $submission_ids;
		extract(Hooks::processHookCalls("end", compact("form_id", "view_id", "submissions_to_delete", "omit_list", "search_fields", "is_admin"), array("success", "message")), EXTR_OVERWRITE);

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
	public static function getSubmission($form_id, $submission_id, $view_id = "")
	{
		$return_arr = array();

		$form_fields = Fields::getFormFields($form_id);
		$submission = Submissions::getSubmissionInfo($form_id, $submission_id);

		$view_fields = (!empty($view_id)) ? ViewFields::getViewFields($view_id) : array();

		if (empty($submission)) {
			return array();
		}

		$view_field_ids = array();
		foreach ($view_fields as $view_field) {
			$view_field_ids[] = $view_field["field_id"];
		}

		// for each field, combine the meta form info (like field size, type, data type etc) from $form_fields
		// with the info about the submission itself. Also, if there's a View specified, filter out any fields
		// that aren't used in the View
		foreach ($form_fields as $field_info) {
			$field_id = $field_info["field_id"];

			// if we're looking at this submission through a View,
			if (!empty($view_id) && !in_array($field_id, $view_field_ids)) {
				continue;
			}

			// if the submission contains contents for this field, add it
			if (array_key_exists($field_info['col_name'], $submission)) {
				$field_info["content"] = $submission[$field_info['col_name']];
			}

			// if a view ID is specified, return the view-specific field info as well
			if (!empty($view_id)) {
				$field_view_info = ViewFields::getViewField($view_id, $field_id);

				if (!empty($field_view_info)) {
					foreach ($field_view_info as $key => $value) {
						$field_info[$key] = $value;
					}
				}
			}

			$return_arr[] = $field_info;
		}

		// finally, if a View is specified, ensure that the order in which the submission fields are returned
		// is determined by the View. [NOT efficient!]
		if (!empty($view_id)) {
			$ordered_return_arr = array();

			foreach ($view_fields as $view_field_info) {
				$field_id = $view_field_info["field_id"];
				foreach ($return_arr as $field_info) {
					if ($field_info["field_id"] == $field_id) {
						$ordered_return_arr[] = $field_info;
						break;
					}
				}
			}
			$return_arr = $ordered_return_arr;
		}

		extract(Hooks::processHookCalls("end", compact("form_id", "submission_id", "view_id", "return_arr"), array("return_arr")), EXTR_OVERWRITE);

		return $return_arr;
	}


	/**
	 * Retrieves ONLY the submission data itself. If you require "meta" information about the submission
	 * such as it's field type, size, database table name etc, use Submissions::getSubmission().
	 *
	 * @param integer $form_id The unique form ID.
	 * @param integer $submission_id The unique submission ID.
	 * @return array Returns a hash of submission information.
	 */
	public static function getSubmissionInfo($form_id, $submission_id)
	{
		$db = Core::$db;

		// get the form submission info
		$db->query("
            SELECT *
            FROM   {PREFIX}form_{$form_id}
            WHERE  submission_id = :submission_id
        ");
		$db->bind("submission_id", $submission_id);
		$db->execute();

		$submission = $db->fetch();

		extract(Hooks::processHookCalls("end", compact("form_id", "submission_id", "submission"), array("submission")), EXTR_OVERWRITE);

		return $submission;
	}


	public static function getSubmissionByField($form_id, $submission_id, $view_id)
	{
		$submission = self::getSubmission($form_id, $submission_id, $view_id);
		$data = array();
		foreach ($submission as $field) {
			$data[$field["field_name"]] = $field["content"];
		}

		return $data;
	}


	/**
	 * Gets the number of submissions made through a form.
	 *
	 * @param integer $form_id the form ID
	 * @param integer $view_id the View ID
	 * @return integer The number of (finalized) submissions
	 */
	public static function getSubmissionCount($form_id, $view_id = "")
	{
		$db = Core::$db;

		$filter_sql_clause = "";
		if (!empty($view_id)) {
			$filter_sql = ViewFilters::getViewFilterSql($view_id);
			if (!empty($filter_sql)) {
				$filter_sql_clause = "AND" . join(" AND ", $filter_sql);
			}
		}

		// get the form submission info
		$db->query("
            SELECT count(*)
            FROM   {PREFIX}form_{$form_id}
            WHERE  is_finalized = 'yes'
                   $filter_sql_clause
        ");
		$db->execute();

		$result = $db->fetch(PDO::FETCH_NUM);
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
	 * @param mixed $results_per_page an integer, or "all"
	 * @param string $order a string of form: "{db column}_{ASC|DESC}"
	 * @param array $search_fields an optional hash with these keys:<br/>
	 *                  search_field<br/>
	 *                  search_date<br/>
	 *                  search_keyword<br/>
	 * @param array $search_columns the columns that are being searched
	 * @return string an HTML string
	 */
	public static function getSearchSubmissionIds($form_id, $view_id, $order, $search_fields = array(), $search_columns = array())
	{
		$db = Core::$db;

		// determine the various SQL clauses
		$order_by = self::getSearchSubmissionsOrderByClause($form_id, $order);
		$filter_clause = self::getSearchSubmissionsViewFilterClause($view_id);
		$search_where_clause = self::getSearchSubmissionsSearchWhereClause($form_id, $search_fields, $search_columns);

		// now build our query
		try {
			$db->query("
                SELECT submission_id
                FROM   {PREFIX}form_{$form_id}
                WHERE  is_finalized = 'yes'
                       $search_where_clause
                       $filter_clause
                ORDER BY $order_by
            ");
			$db->execute();
		} catch (Exception $e) {
			Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
			exit;
		}

		return $db->fetchAll(PDO::FETCH_COLUMN);
	}


	public static function isAllSelected($form_id)
	{
		$all_selected_key = "form_{$form_id}_select_all_submissions";
		return Sessions::exists($all_selected_key) && Sessions::get($all_selected_key) == 1;
	}


	public static function getSelectedSubmissions($form_id)
	{
		$submissions_to_delete = Sessions::get("form_{$form_id}_selected_submissions");
		$omit_list = array();

		if (Submissions::isAllSelected($form_id)) {
			$submissions_to_delete = "all";
			$omit_list = Sessions::get("form_{$form_id}_all_submissions_selected_omit_list");
		}

		return array($submissions_to_delete, $omit_list);
	}


	public static function clearSelected($form_id)
	{
		Sessions::set("form_{$form_id}_selected_submissions", array());
		Sessions::set("form_{$form_id}_all_submissions_selected_omit_list", array());
		Sessions::set("form_{$form_id}_select_all_submissions", "");
	}


	/**
	 * Updates an individual form submission.
	 *
	 * @param int
	 * @param int
	 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
	 *             various fields from the update submission page. The contents of it change for each
	 *             form and form View, of course.
	 * @param bool
	 * @return array Returns array with indexes:<br/>
	 *               [0]: true/false (success / failure)<br/>
	 *               [1]: message string<br/>
	 */
	public static function updateSubmission($form_id, $submission_id, $infohash, $validate = true)
	{
		$db = Core::$db;
		$LANG = Core::$L;

		$success = true;
		$message = $LANG["notify_form_submission_updated"];

		extract(Hooks::processHookCalls("start", compact("form_id", "submission_id", "infohash"), array("infohash")), EXTR_OVERWRITE);

		$field_ids = (!empty($infohash["field_ids"])) ? explode(",", $infohash["field_ids"]) : array();

		if ($validate) {
			// perform any server-side validation
			$errors = FieldValidation::validateSubmission($infohash["editable_field_ids"], $infohash);

			// if there are any problems, return right away
			if (!empty($errors)) {
				return array(false, General::getErrorListHTML($errors));
			}
		}

		$form_fields = Fields::getFormFields($form_id);
		$field_types_processing_info = FieldTypes::getFieldTypeProcessingInfo();

		// this gets all settings for the fields, taking into account whatever has been overridden
		$field_settings = FieldTypes::getFormFieldFieldTypeSettings($field_ids, $form_fields);

		$now = General::getCurrentDatetime();

		$set_statements = array(
			"last_modified_date = :last_modified_date"
		);
		$bindings = array(
			"submission_id" => $submission_id,
			"last_modified_date" => $now
		);

		$counter = 1;
		$file_fields = array();
		foreach ($form_fields as $form_field) {

			// if the field ID isn't in the page's tab, ignore it
			if (!in_array($form_field["field_id"], $field_ids)) {
				continue;
			}

			// if the field ID isn't editable, the person's being BAD and trying to hack a field value. Ignore it.
			if (!in_array($form_field["field_id"], $infohash["editable_field_ids"])) {
				continue;
			}

			if ($form_field["field_name"] == "core__submission_date" || $form_field["col_name"] == "core__last_modified") {
				if (!isset($infohash[$form_field["field_name"]]) || empty($infohash[$form_field["field_name"]])) {
					continue;
				}
			}

			list ($value, $file_field) = self::getSaveFieldValueFromUpdateRequest($infohash, $form_field, $field_settings, $field_types_processing_info);
			if (!is_null($value)) {
				$set_statements[] = "{$form_field["col_name"]} = :col_{$counter}";
				$bindings["col_{$counter}"] = $value;
				$counter++;
			}
			if (!empty($file_field)) {
				$file_fields[] = $file_field;
			}
		}

		$statements = join(",\n", $set_statements);

		try {
			$db->query("
                UPDATE {PREFIX}form_{$form_id}
                SET    $statements
                WHERE  submission_id = :submission_id
            ");
			$db->bindAll($bindings);
			$db->execute();
		} catch (Exception $e) {

			// if there was a problem updating the submission, don't even bother calling the file upload hook. Just exit right away
			return array(false, $LANG["notify_submission_not_updated"]);
		}

		// track whether the update from the core was successful
		$core_update_success = $success;

		// now process any file fields
		extract(Hooks::processHookCalls("manage_files", compact("form_id", "submission_id", "file_fields"), array("success", "message")), EXTR_OVERWRITE);

		// send any emails
		Emails::sendEmails("on_edit", $form_id, $submission_id);

		extract(Hooks::processHookCalls("end", compact("form_id", "submission_id", "infohash"), array("success", "message")), EXTR_OVERWRITE);

		// very ugly, but refactoring was a little too impactful at this stage. The first two params are the overall success and
		// error message; the third tells the calling code whether the core update action was successful. This allows the
		// calling code to know if the error that occurred was a file upload / external module. This is needed due to
		// this issue: https://github.com/formtools/core/issues/475
		return array(
			$success,
			$message,
			$core_update_success
		);
	}


	/**
	 * For use by programmers to finalize a submission (i.e. make it appear in the client's user
	 * interface).
	 *
	 * @param integer $form_id The unique form ID.
	 * @param integer $submission_id A unique submission ID.
	 * @return boolean $success True on success, false otherwise.
	 */
	public static function finalizeSubmission($form_id, $submission_id)
	{
		$db = Core::$db;

		// check the form_id is valid
		if (!Forms::checkFormExists($form_id)) {
			return false;
		}

		$db->query("
            UPDATE {PREFIX}form_$form_id
            SET    is_finalized = 'yes'
            WHERE  submission_id = $submission_id
        ");
		$db->bind("submission_id", $submission_id);
		$db->execute();

		Emails::sendEmails("on_submission", $form_id, $submission_id);

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
	public static function searchSubmissions($form_id, $view_id, $results_per_page, $page_num, $order, $columns_to_return,
											 $search_fields = array(), $submission_ids = array(), $searchable_columns = array())
	{
		$db = Core::$db;

		// for backward compatibility
		if (empty($searchable_columns)) {
			$searchable_columns = $columns_to_return;
		}

		// determine the various SQL clauses for the searches
		$order_by = Submissions::getSearchSubmissionsOrderByClause($form_id, $order);
		$limit_clause = General::getQueryPageLimitClause($page_num, $results_per_page);
		$select_clause = Submissions::getSearchSubmissionsSelectClause($columns_to_return);
		$filter_clause = Submissions::getSearchSubmissionsViewFilterClause($view_id);
		$submission_id_clause = Submissions::getSearchSubmissionsSubmissionIdClause($submission_ids);
		$search_where_clause = Submissions::getSearchSubmissionsSearchWhereClause($form_id, $search_fields, $searchable_columns);

		// (1) our main search query that returns a PAGE of submission info
		try {
			$db->query("
                SELECT $select_clause
                FROM   {PREFIX}form_{$form_id}
                WHERE  is_finalized = 'yes'
                       $search_where_clause
                       $filter_clause
                       $submission_id_clause
                ORDER BY $order_by
                       $limit_clause
            ");
			$db->execute();
		} catch (Exception $e) {
			Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
			exit;
		}

		$search_result_rows = $db->fetchAll();

		// (2) find out how many results there are in this current search
		try {
			$db->query("
                SELECT count(*)
                FROM   {PREFIX}form_{$form_id}
                WHERE  is_finalized = 'yes'
                       $search_where_clause
                       $filter_clause
                       $submission_id_clause
            ");
			$db->execute();
		} catch (Exception $e) {
			Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
			exit;
		}

		$search_num_results = $db->fetch(PDO::FETCH_COLUMN);

		// (3) find out how many results should appear in the View, regardless of the current search criteria
		try {
			$db->query("
                SELECT count(*)
                FROM   {PREFIX}form_{$form_id}
                WHERE  is_finalized = 'yes'
                $filter_clause
            ");
			$db->execute();
		} catch (Exception $e) {
			Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
			exit;
		}

		$view_num_results = $db->fetch(PDO::FETCH_COLUMN);

		$return_hash["search_rows"] = $search_result_rows;
		$return_hash["search_num_results"] = $search_num_results;
		$return_hash["view_num_results"] = $view_num_results;

		extract(Hooks::processHookCalls("end", compact("form_id", "submission_id", "view_id", "results_per_page", "page_num", "order", "columns", "search_fields", "submission_ids", "return_hash"), array("return_hash")), EXTR_OVERWRITE);

		return $return_hash;
	}


	/**
	 * This function is used for displaying and exporting the data. Basically it merges all information
	 * about a particular field from the view_fields table with the form_fields and field_options table,
	 * providing ALL information about a field in a single variable.
	 *
	 * It accepts the result of the Views::getViewFields() function as the first parameter and an optional
	 * boolean to let it know whether to return ALL results or not.
	 *
	 * TODO maybe deprecate? Only used mass_edit
	 *
	 * @param array $view_fields
	 * @param boolean $return_all_fields
	 */
	public static function getSubmissionFieldInfo($view_fields)
	{
		$display_fields = array();
		foreach ($view_fields as $field) {
			$field_id = $field["field_id"];
			$curr_field_info = array(
				"field_id" => $field_id,
				"field_title" => $field["field_title"],
				"col_name" => $field["col_name"],
				"list_order" => $field["list_order"]
			);
			$field_info = Fields::getFormField($field_id);
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
	public static function checkViewContainsSubmission($form_id, $view_id, $submission_id)
	{
		$db = Core::$db;

		$filter_sql = ViewFilters::getViewFilterSql($view_id);

		if (empty($filter_sql)) {
			return true;
		}

		$filter_sql_clause = join(" AND ", $filter_sql);

		try {
			$db->query("
                SELECT count(*)
                FROM   {PREFIX}form_{$form_id}
                WHERE  submission_id = :submission_id AND
                       ($filter_sql_clause)
            ");
			$db->bind("submission_id", $submission_id);
			$db->execute();
		} catch (Exception $e) {
			return false;
		}

		return $db->fetch(PDO::FETCH_COLUMN) == 1;
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
	public static function checkSubmissionFinalized($form_id, $submission_id)
	{
		$db = Core::$db;

		$db->query("
            SELECT is_finalized
            FROM   {PREFIX}form_$form_id
            WHERE  submission_id = :submission_id
        ");
		$db->bind("submission_id", $submission_id);
		$db->execute();

		$result = $db->fetch();

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
	public static function checkSubmissionExists($form_id, $submission_id)
	{
		$db = Core::$db;

		try {
			$db->query("
                SELECT submission_id
                FROM   {PREFIX}form_$form_id
                WHERE  submission_id = :submission_id
            ");
			$db->bind("submission_id", $submission_id);
			$db->execute();
		} catch (Exception $e) {
			return null;
		}

		return $db->numRows() === 1;
	}


	/**
	 * This generic function processes any form field with a field type that requires additional
	 * processing, e.g. phone number fields, date fields etc. - anything that needs a little extra PHP
	 * in order to convert the form data into.
	 *
	 * @param array $info
	 */
	public static function processFormField($vars)
	{
		eval($vars["code"]);
		return (isset($value)) ? $value : "";
	}


	/**
	 * Used for retrieving the data for a mapped form field; i.e. a dropdown, radio group or checkbox group
	 * field whose source contents is the contents of a different form field.
	 *
	 * @param integer $form_id
	 * @param array $results a complex data structure
	 */
	public static function getMappedFormFieldData($setting_value)
	{
		$db = Core::$db;

		$trimmed = preg_replace("/form_field:/", "", $setting_value);

		// this prevents anything wonky being shown if the following query fails (for whatever reason)
		$formatted_results = "";

		list($form_id, $field_id, $order) = explode("|", $trimmed);
		if (!empty($form_id) && !empty($field_id) && !empty($order)) {
			$map = Fields::getFieldColByFieldId($form_id, $field_id);
			$col_name = $map[$field_id];

			try {
				$db->query("
                    SELECT submission_id, $col_name
                    FROM   {PREFIX}form_{$form_id}
                    WHERE  is_finalized = 'yes'
                    ORDER BY $col_name $order
                ");
				$db->execute();
			} catch (Exception $e) {
				Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
				exit;
			}

			$results = array();
			foreach ($db->fetchAll() as $row) {
				if (empty($row[$col_name])) {
					continue;
				}
				$results[] = array(
					"option_value" => $row["submission_id"],
					"option_name" => $row[$col_name]
				);
			}

			// yuck! But we need to force the form field info into the same format as the option lists,
			// so the Field Types don't need to do additional work to display both cases
			$formatted_results = array(
				"type" => "form_field",
				"form_id" => $form_id,
				"field_id" => $field_id,
				"options" => array(
					array(
						"group_info" => array(),
						"options" => $results
					)
				)
			);
		}

		return $formatted_results;
	}


	/**
	 * Added in 2.1.0. This lets modules add icons to a "quicklink" icon row on the Submission Listing page. To add it,
	 * they need to define a hook call and return a $quicklinks hash with the following keys:
	 *   icon_url
	 *   alt_text
	 *
	 * @param $context "admin" or "client"
	 */
	public static function displaySubmissionListingQuicklinks($context)
	{
		$quicklinks = array();

		extract(Hooks::processHookCalls("main", compact("context"), array("quicklinks"), array("quicklinks")), EXTR_OVERWRITE);

		if (empty($quicklinks)) {
			return "";
		}

		echo "<ul id=\"ft_quicklinks\">";

		$num_quicklinks = count($quicklinks);
		for ($i = 0; $i < $num_quicklinks; $i++) {
			$classes = array();
			if ($i == 0) {
				$classes[] = "ft_quicklinks_first";
			}
			if ($i == $num_quicklinks - 1) {
				$classes[] = "ft_quicklinks_last";
			}

			$class = implode(" ", $classes);

			$quicklink_info = $quicklinks[$i];
			$icon_url = isset($quicklink_info["icon_url"]) ? $quicklink_info["icon_url"] : "";
			$title_text = isset($quicklink_info["title_text"]) ? $quicklink_info["title_text"] : "";
			$onclick = isset($quicklink_info["onclick"]) ? $quicklink_info["onclick"] : "";
			$title_text = htmlspecialchars($title_text);

			if (empty($icon_url)) {
				continue;
			}

			echo "<li class=\"$class\" onclick=\"$onclick\"><img src=\"$icon_url\" title=\"$title_text\" /></li>\n";
		}

		echo "</ul>";
	}


	/**
	 * Gets the << prev, next >> etc. link HTML for the current submission.
	 */
	public static function getPrevNextLinks($form_id, $view_id, $submission_id, $return_page = "index.php")
	{
		$LANG = Core::$L;

		// defaults! As of 2.1.0, the navigation always appears
		$previous_link_html = "<span class=\"light_grey\">{$LANG['word_previous_leftarrow']}</span>";
		$next_link_html = "<span class=\"light_grey\">{$LANG['word_next_rightarrow']}</span>";
		$search_results_link_html = "<a href=\"{$return_page}?form_id=$form_id\">{$LANG['phrase_back_to_search_results']}</a>";

		$session_key = "form_{$form_id}_view_{$view_id}_submissions";
		if (Sessions::exists($session_key) && Sessions::get($session_key) != "") {
			$php_self = General::getCleanPhpSelf();
			$submission_ids = Sessions::get($session_key);
			$current_sub_id_index = array_search($submission_id, $submission_ids);

			if (!empty($current_sub_id_index) || $current_sub_id_index === 0) {
				// PREVIOUS link
				if ($submission_ids[0] != $submission_id && $current_sub_id_index != 0) {
					$previous_submission_id = $submission_ids[$current_sub_id_index - 1];
					$previous_link_html = "<a href=\"$php_self?form_id=$form_id&view_id=$view_id&submission_id=$previous_submission_id\">{$LANG['word_previous_leftarrow']}</a>";
				}
				// NEXT link
				if ($submission_ids[count($submission_ids) - 1] != $submission_id) {
					$next_submission_id = $submission_ids[$current_sub_id_index + 1];
					$next_link_html = "<a href=\"$php_self?form_id=$form_id&view_id=$view_id&submission_id=$next_submission_id\">{$LANG['word_next_rightarrow']}</a>";
				}
			}
		}

		return array($previous_link_html, $search_results_link_html, $next_link_html);
	}


	public static function copySubmissions($form_id, $view_id, $submissions_to_copy, $omit_list, $search_fields)
	{
		$db = Core::$db;
		$L = Core::$L;

		// construct a list of all the actual submission IDs we want to copy
		if ($submissions_to_copy == "all") {
			$searchable_columns = ViewFields::getViewSearchableFields($view_id);
			$submission_ids = Submissions::getSearchSubmissionIds($form_id, $view_id, "submission_id-ASC", $search_fields, $searchable_columns);
			$submission_ids = array_diff($submission_ids, $omit_list);
		} else {
			$submission_ids = $submissions_to_copy;
		}

		$columns = Forms::getFormColumnNames($form_id);
		$columns["is_finalized"] = true;
		unset($columns["submission_id"]);

		$col_names = array_keys($columns);

		$success = true;
		try {

			$submission_id_map = array();
			foreach ($submission_ids as $submission_id) {
				General::copyTableRow("{PREFIX}form_{$form_id}", $col_names, "submission_id", $submission_id);
				$submission_id_map[$submission_id] = $db->getInsertId();
			}

			extract(Hooks::processHookCalls("end", compact("form_id", "submission_id_map"), array()), EXTR_OVERWRITE);

			$num_submissions = count($submission_ids);
			if ($num_submissions === 1) {
				$submission_id = $submission_id_map[$submission_ids[0]];
				$message = "{$L["notify_submission_copied"]} <a href=\"edit_submission.php?submission_id={$submission_id}\">{$L["notify_click_to_edit_new_submission"]}</a>";
			} else {
				$message = General::evalSmartyString($L["notify_submissions_copied"], array(
					"num_submissions" => $num_submissions
				));
			}
		} catch (Exception $e) {
			$success = false;
			$message = $e->getMessage();
		}

		return array($success, $message);
	}


	public static function copySubmission($form_id, $submission_id)
	{
		$db = Core::$db;
		$L = Core::$L;

		$columns = Forms::getFormColumnNames($form_id);
		$columns["is_finalized"] = true;
		unset($columns["submission_id"]);

		$col_names = array_keys($columns);

		$success = true;
		$new_submission_id = "";
		try {
			General::copyTableRow("{PREFIX}form_{$form_id}", $col_names, "submission_id", $submission_id);
			$new_submission_id = $db->getInsertId();

			$submission_id_map = array(
				"$submission_id" => $new_submission_id
			);

			extract(Hooks::processHookCalls("end", compact("form_id", "submission_id_map"), array()), EXTR_OVERWRITE);

			$message = $L["notify_submission_copied_edit"];

		} catch (Exception $e) {
			$success = false;
			$message = $e->getMessage();
		}

		return array($success, $message, $new_submission_id);
	}


	/**
	 * Added in 3.0.13. This is called every time a user edits a field. It tracks the current values they're about to
	 * edit (specific to the View) so we can detect changes that occur to those fields while they are editing and
	 * present them with a UI to reconcile the differences.
	 *
	 * We stash the submission, view & tab so we can do a quick check before updating the data that the info being
	 * updated corresponds to the actual submission. If a user had opened a new tab and edited a different submission
	 * this data would be incorrect if the user then updated the FIRST tab submission. For that scenario we simply
	 * update the submission and don't try to reconcile differences. If and when we actually properly support running
	 * FT in multiple tabs this can be revisited. https://github.com/formtools/core/issues/479
	 *
	 * @param $grouped_fields
	 * @param $submission_id
	 * @param $view_id
	 * @param $tab_number
	 */
	public static function trackCurrentEditSubmissionFields($grouped_fields, $submission_id, $view_id, $tab_number)
	{
		$core_fields = array(
			"core__submission_id",
			"core__submission_date",
			"core__last_modified",
			"core__ip_address"
		);
		$data = array();
		foreach ($grouped_fields as $group) {
			foreach ($group["fields"] as $field) {
				if (!in_array($field["field_name"], $core_fields)) {
					$data[$field["field_name"]] = $field["submission_value"];
				}
			}
		}
		Sessions::set("last_edit_submission_state", array(
			"submission_id" => $submission_id,
			"view_id" => $view_id,
			"tab_number" => $tab_number,
			"data" => $data
		));
	}


	public static function getChangedFieldsSinceLastRender($form_id, $view_id, $submission_id, $tab_number, $post)
	{
		$view_fields = Submissions::getSubmissionByField($form_id, $submission_id, $view_id);
		unset($view_fields["core__submission_id"]);
		unset($view_fields["core__submission_date"]);
		unset($view_fields["core__last_modified"]);
		unset($view_fields["core__ip_address"]);

		$last_edit_submission_state = Sessions::get("last_edit_submission_state");

		// in case the last submission data is NOT the one being updated, just abort. This can happen if the user
		// opened another tab and edited a different submission
		if ($last_edit_submission_state["submission_id"] != $submission_id ||
			$last_edit_submission_state["view_id"] != $view_id ||
			$last_edit_submission_state["tab_number"] != $tab_number) {
			return array();
		}

		// expensive operations, so just load them once if need by
		$form_fields = array();
		$field_types_processing_info = array();
		$field_settings = array();

		$changed = array();
		foreach ($view_fields as $field_name => $value) {

			// ignore any fields that aren't in the POST request
			if (!isset($post[$field_name])) {
				continue;
			}

			if ($last_edit_submission_state["data"][$field_name] !== $value && $value !== $post[$field_name]) {

				if (empty($form_fields)) {
					$form_fields = Fields::getFormFields($form_id);
					$field_types_processing_info = FieldTypes::getFieldTypeProcessingInfo();

					// this gets all settings for the fields, taking into account whatever has been overridden
					$field_ids = (!empty($post["field_ids"])) ? explode(",", $post["field_ids"]) : array();
					$field_settings = FieldTypes::getFormFieldFieldTypeSettings($field_ids, $form_fields);
				}

				$form_field = array();
				foreach ($form_fields as $row) {
					if ($row["field_name"] == $field_name) {
						$form_field = $row;
						break;
					}
				}

				// currently file conflicts are ignored: the latest file is always overridden
				list ($user_value, $file_field) = self::getSaveFieldValueFromUpdateRequest($post, $form_field, $field_settings, $field_types_processing_info);

				$changed[$field_name] = array(
					"db_value" => $value,
					"user_value" => $user_value
				);
			}
		}

		return $changed;
	}


	/**
	 * Used for clients and administrators when updating a submission. This checks the specific submission data hadn't
	 * changed while they were editing (i.e. the actual changed fields, not the general submission itself). If it does
	 * it handles returning the list of changed fields for users to reconcile the conflicts manually.
	 * @param $form_id
	 * @param $submission_id
	 * @param $view_id
	 * @param $editable_field_ids
	 * @param $request
	 * @return array
	 */
	public static function updateSubmissionWithConflictDetection($form_id, $submission_id, $view_id, $editable_field_ids, $request)
	{
		$L = Core::$L;

		$success = true;
		$core_update_success = true;
		$message = "";
		$changed_fields = array();
		$failed_validation = false;

		if (isset($_POST["core__reconcile_changed_fields"])) {
			$state = Sessions::get("last_edit_submission_state");

			// filter out anything with "db value" - we don't care about them any more: the user has indicated they want the
			// value from the DB, so even if it's changed again, just use it
			$keys = array_keys($state["data"]);
			$fields_to_update = array();
			$user_values = Sessions::get("conflicted_user_values");

			$view_fields = Submissions::getSubmissionByField($form_id, $submission_id, $view_id);
			foreach ($keys as $field_name) {
				if ($request[$field_name] == "user_value") {
					$fields_to_update[] = $field_name;
					$request[$field_name] = $user_values[$field_name]["user_value"];
				} else {
					$request[$field_name] = $view_fields[$field_name];
				}
			}

			if (empty($fields_to_update)) {
				$success = true;
				$message = $L["notify_differences_resolved"];
			} else {
				$changed_fields = Submissions::getChangedFieldsSinceLastRender($form_id, $view_id, $submission_id, $request["tab"], $request);

				// now if the old DB values are the same as what they already reviewed, that's fine, we can just update the record.
				// But if it changed again, display the conflict resolution page with the latest info.
				$db_values_same = true;
				foreach ($changed_fields as $field_name => $changes) {
					if ($user_values[$field_name]["db_value"] !== $changes["db_value"]) {
						$db_values_same = false;
					} else {
						$request[$field_name] = $changes["user_value"];
					}
				}

				if ($db_values_same) {
					$changed_fields = array();
					Sessions::set("new_search", "yes");
					$request["view_id"] = $view_id;
					$request["editable_field_ids"] = $editable_field_ids;
					list($success, $message) = Submissions::updateSubmission($form_id, $submission_id, $request, false);
				}
			}

		} else {
			// see if any of the fields have been already updated
			$changed_fields = Submissions::getChangedFieldsSinceLastRender($form_id, $view_id, $submission_id, $_POST["tab"], $_POST);
			Sessions::set("new_search", "yes");
			$request["view_id"] = $view_id;
			$request["editable_field_ids"] = $editable_field_ids;

			// if the DB had more up-to-date values, reset all the conflicting fields to save the last value in the DB before updating
			// the database. We'll then present the conflicts to the user to choose whether to pick their new values or the existing
			// ones
			if (!empty($changed_fields)) {
				foreach ($changed_fields as $field_name => $value) {
					$request[$field_name] = $changed_fields[$field_name]["db_value"];
				}
			}
			list($success, $message, $core_update_success) = Submissions::updateSubmission($form_id, $submission_id, $request);

			if (!empty($changed_fields)) {
				$success = false;
				$message = $L["notify_conflicts_detected"];
			}

			// if there was any problem updating this submission, make a special note of it: we'll use that info to merge the
			// current POST request info with the original field values to ensure the page contains the latest data (i.e. for
			// cases where they fail server-side validation)
			if (!$success) {
				$failed_validation = true;
			}
		}

		return array(
			$success,
			$message,
			$changed_fields,
			$failed_validation,
			$core_update_success
		);
	}


	/**
	 * Used on the edit submission page to figure out what field are changed and return a list of DB + user values
	 * to present to the user.
	 * @param $grouped_fields
	 * @param $changed_fields
	 * @return array
	 */
	public static function getChangedFieldsToReconcile($grouped_fields, $changed_fields)
	{
		$reconcile_changed_fields = array();
		$user_values = array();
		foreach ($changed_fields as $changed_field_name => $changed_info) {
			foreach ($grouped_fields as $group) {
				foreach ($group["fields"] as $field) {
					if ($field["field_name"] !== $changed_field_name) {
						continue;
					}

					$db_value = $field;
					$db_value["is_editable"] = "no";
					$db_value["submission_value"] = $changed_info["db_value"];

					$user_value = $field;
					$user_value["is_editable"] = "no";
					$user_value["submission_value"] = $changed_info["user_value"];

					$reconcile_changed_fields[] = array(
						"field_name" => $changed_field_name,
						"field_id" => $field["field_id"],
						"field_title" => $field["field_title"],
						"db_value" => $db_value,
						"user_value" => $user_value
					);
					$user_values[$changed_field_name] = array(
						"db_value" => $changed_info["db_value"],
						"user_value" => $changed_info["user_value"]
					);
				}
			}
		}
		Sessions::set("conflicted_user_values", $user_values);

		return $reconcile_changed_fields;
	}

	// -----------------------------------------------------------------------------------------------------------------


	/**
	 * Used in the searchSubmissions function to abstract away a few minor details.
	 *
	 * @param $form_id integer
	 * @param $order string
	 * @return string
	 */
	private static function getSearchSubmissionsOrderByClause($form_id, $order)
	{
		$order_by = "submission_id";
		if (empty($order)) {
			return $order_by;
		}

		// sorting by column, format: col_x-desc / col_y-asc
		list($column, $direction) = explode("-", $order);
		$field_info = Fields::getFieldOrderInfoByColname($form_id, $column);

		// no field can be found if the administrator just changed the DB field contents and then went back to the
		// submissions page where they'd already done a sort - and had it cached
		if (!empty($field_info)) {
			if ($field_info["is_date_field"] == "yes") {
				if ($column == "submission_date" || $column == "last_modified_date") {
					$order_by = "$column $direction";
				} else {
					$order_by = "CAST($column as DATETIME) $direction";
				}
			} else {
				if ($field_info["data_type"] == "number") {
					$order_by = "CAST($column as SIGNED) $direction";
				} else {
					$order_by = "$column $direction";
				}
			}

			// important! If the ORDER BY column wasn't the submission_id, we need to add
			// the submission ID as the secondary sorting column
			if ($column != "submission_id") {
				$order_by .= ", submission_id";
			}
		}

		return $order_by;
	}


	/**
	 * This magical function looks at a form field in the database and if the POST request contains a value for that
	 * field, it determines what the actual saved value should be for the field. It takes into account how the field
	 * type is configured - having PHP processing applied to it, or just blankly allowing the POST request to contain
	 * the content & save as-is. It also takes into account how the individual FIELD has been configured - whether
	 * there have been overridden settings via the UI.
	 *
	 * This works for all field types.
	 *
	 * @param $request
	 * @param $form_field
	 * @param $field_settings
	 * @param $field_types_processing_info
	 * @return array
	 */
	private static function getSaveFieldValueFromUpdateRequest($request, $form_field, $field_settings, $field_types_processing_info)
	{
		$value = null;
		$file_field = null;
		$multi_val_delimiter = Core::getMultiFieldValDelimiter();

		// if this is a FILE field that doesn't have any overridden PHP processing code, just store the info
		// about the field. Presumably, the module / field type has registered the appropriate hooks for
		// processing the file. Without it, the module wouldn't work. We pass that field + file info to the hook.
		if ($field_types_processing_info[$form_field["field_type_id"]]["is_file_field"] == "yes") {
			$file_data = array(
				"field_id" => $form_field["field_id"],
				"field_info" => $form_field,
				"data" => $request,
				"code" => $field_types_processing_info[$form_field["field_type_id"]]["php_processing"],
				"settings" => $field_settings[$form_field["field_id"]]
			);

			if (empty($field_types_processing_info[$form_field["field_type_id"]]["php_processing"])) {
				$file_field = $file_data;

				// No actual field types execute this chunk of code here, but it's possible that a user could configure
				// a really lightweight file upload field to upload a file and return the value to store in the DB
			} else {
				$value = Submissions::processFormField($file_data);
			}

		} else if (!empty($field_types_processing_info[$form_field["field_type_id"]]["php_processing"])) {
			$data = array(
				"field_info" => $form_field,
				"data" => $request,
				"code" => $field_types_processing_info[$form_field["field_type_id"]]["php_processing"],
				"settings" => $field_settings[$form_field["field_id"]],
				"account_info" => Sessions::getWithFallback("account", array())
			);
			$value = Submissions::processFormField($data);
		} else {
			if (isset($request[$form_field["field_name"]])) {
				if (is_array($request[$form_field["field_name"]])) {
					$value = implode("$multi_val_delimiter", $request[$form_field["field_name"]]);
				} else {
					$value = $request[$form_field["field_name"]];
				}
			} else {
				$value = "";
			}
		}

		return array($value, $file_field);
	}


	/**
	 * Used in the searchSubmissions() method to abstract away a few minor details.
	 *
	 * @param array $columns
	 * @return string
	 */
	private static function getSearchSubmissionsSelectClause($columns)
	{
		if (!is_array($columns) && $columns == "all") {
			$select_clause = " * ";
		} else {
			$columns = array_unique($columns);

			// if submission_id isn't included, add it - it'll be needed at some point
			if (!in_array("submission_id", $columns)) {
				$columns[] = "submission_id";
			}

			// just in case. This prevents empty column names (which shouldn't get here, but do if something
			// goes wrong) getting into the column list
			$columns = General::arrayRemoveEmptyEls($columns);
			$select_clause = join(", ", $columns);
		}

		return $select_clause;
	}


	/**
	 * Used in the Submissions::searchSubmissions() method to abstract away a few minor details.
	 *
	 * @param integer $view_id
	 * @return string
	 */
	private static function getSearchSubmissionsViewFilterClause($view_id)
	{
		$view_filters = ViewFilters::getViewFilterSql($view_id);
		$filter_clause = "";
		if (!empty($view_filters)) {
			$filter_clause = "AND " . join(" AND ", $view_filters);
		}

		return $filter_clause;
	}


	/**
	 * Used in the Submissions::searchSubmissions() function. This figures out the additional SQL clauses required for
	 * a custom search. Note: as of the Dec 2009 build, this function properly only searches those fields
	 * marked as "is_searchable" in the database.
	 *
	 * @param integer $form_id
	 * @param array $search_fields
	 * @param array $searchable_columns the View columns that have been marked as "is_searchable"
	 * @return string
	 */
	private static function getSearchSubmissionsSearchWhereClause($form_id, $search_fields, $searchable_columns)
	{
		$search_where_clause = "";
		if (empty($search_fields)) {
			return $search_where_clause;
		}

		$search_form_date_field_format = Core::getSearchFormDateFieldFormat();

		$clean_search_fields = $search_fields;
		$search_field = $clean_search_fields["search_field"];
		$search_date = $clean_search_fields["search_date"];
		$search_keyword = str_replace("'", "''", $clean_search_fields["search_keyword"]);

		// search field can either be "all" or a database column name. "submission_date" and "last_modified_date"
		// have special meanings, since they allow for keyword searching within specific date ranges
		if ($search_field == "all") {
			if (!empty($search_keyword)) {

				// if we're searching ALL columns, get all col names. This shouldn't ever get called any more - but
				// I'll leave it in for regression purposes
				$clauses = array();
				if (!is_array($searchable_columns) && $searchable_columns == "all") {
					$col_info = Forms::getFormColumnNames($form_id);
					$col_names = array_keys($col_info);
					unset($col_names["is_finalized"]);
					unset($col_names["submission_date"]);
					unset($col_names["last_modified_date"]);

					foreach ($col_names as $col_name) {
						$clauses[] = "$col_name LIKE '%$search_keyword%'";
					}
				} else if (is_array($searchable_columns)) {

					// Core bug #252: only search on datetime fields if it's a numeric value; there's a MySQL bug that
					// gets thrown about collations if the user enters a utf-8 char
					if (preg_match("/[^0-9\:\-]/", $search_keyword)) {
						$searchable_columns = General::arrayRemoveByValue($searchable_columns, "submission_date");
						$searchable_columns = General::arrayRemoveByValue($searchable_columns, "last_modified_date");
					}

					foreach ($searchable_columns as $col_name) {
						$clauses[] = "$col_name LIKE '%$search_keyword%'";
					}
				}

				if (!empty($clauses)) {
					$search_where_clause = "AND (" . join(" OR ", $clauses) . ") ";
				}
			}
		}

		// date field! Date fields actually take two forms: they're either the Core fields (Submission Date and
		// Last Modified Date), which are real DATETIME fields, or custom date fields which are varchars
		else if (preg_match("/\|date$/", $search_field)) {
			$search_field = preg_replace("/\|date$/", "", $search_field);
			$is_core_date_field = ($search_field == "submission_date" || $search_field == "last_modified_date") ? true : false;
			if (!$is_core_date_field) {
				$search_field = "CAST($search_field as DATETIME) ";
			}

			if (!empty($search_date)) {
				// search by date range
				if (strpos($search_date, "-") !== false) {
					$dates = explode(" - ", $search_date);
					$start = $dates[0];
					$end = $dates[1];
					if ($search_form_date_field_format == "d/m/y") {
						list($start_day, $start_month, $start_year) = explode("/", $start);
						list($end_day, $end_month, $end_year) = explode("/", $end);
					} else {
						list($start_month, $start_day, $start_year) = explode("/", $start);
						list($end_month, $end_day, $end_year) = explode("/", $end);
					}
					$start_day = str_pad($start_day, 2, "0", STR_PAD_LEFT);
					$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
					$end_day = str_pad($end_day, 2, "0", STR_PAD_LEFT);
					$end_month = str_pad($end_month, 2, "0", STR_PAD_LEFT);

					$start_date = "{$start_year}-{$start_month}-{$start_day} 00:00:00";
					$end_date = "{$end_year}-{$end_month}-{$end_day} 23:59:59";
					$search_where_clause = "AND ($search_field >= '$start_date' AND $search_field <= '$end_date') ";

					// otherwise, return a specific day
				} else {
					if ($search_form_date_field_format == "d/m/y") {
						list($day, $month, $year) = explode("/", $search_date);
					} else {
						list($month, $day, $year) = explode("/", $search_date);
					}
					$month = str_pad($month, 2, "0", STR_PAD_LEFT);
					$day = str_pad($day, 2, "0", STR_PAD_LEFT);

					$start = "{$year}-{$month}-{$day} 00:00:00";
					$end = "{$year}-{$month}-{$day} 23:59:59";
					$search_where_clause = "AND ($search_field >= '$start' AND $search_field <= '$end') ";
				}

				if (!empty($search_keyword)) {
					$clauses = array();
					foreach ($searchable_columns as $col_name) {
						$clauses[] = "$col_name LIKE '%$search_keyword%'";
					}
					if (!empty($clauses)) {
						$search_where_clause .= "AND (" . join(" OR ", $clauses) . ") ";
					}
				}
			}

		} else {
			if (!empty($search_keyword) && !empty($search_field)) {
				$search_where_clause = "AND $search_field LIKE '%$search_keyword%'";
			}
		}

		return $search_where_clause;
	}


	/**
	 * Used in the Submissions::searchSubmissions() function to abstract away a few minor details.
	 *
	 * @param array $submission_ids
	 * @return string
	 */
	private static function getSearchSubmissionsSubmissionIdClause($submission_ids)
	{
		$submission_id_clause = "";
		if (!empty($submission_ids)) {
			$rows = array();
			foreach ($submission_ids as $submission_id) {
				$rows[] = "submission_id = $submission_id";
			}
			$submission_id_clause = "AND (" . join(" OR ", $rows) . ") ";
		}

		return $submission_id_clause;
	}

}
