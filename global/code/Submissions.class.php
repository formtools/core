<?php

/**
 * This file defines all functions related to managing form submissions.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Submissions
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDOException, PDO;


class Submissions {

    /**
     * Creates a new blank submission in the database and returns the unique submission ID. If the
     * operation fails for whatever reason (e.g. the form doesn't exist), it just returns the empty
     * string.
     *
     * @param integer $form_id
     * @param integer $view_id
     * @param boolean $is_finalized whether the submission is finalized or not.
     */
    public static function createBlankSubmission($form_id, $view_id, $is_finalized = false)
    {
        $db = Core::$db;

        if (!Forms::checkFormExists($form_id)) {
            return "";
        }

        $now = General::getCurrentDatetime();
        $ip  = $_SERVER["REMOTE_ADDR"];

        // if the administrator has specified any default values for submissions created through this View
        $default_insert_pairs = array(
            "submission_date"    => $now,
            "last_modified_date" => $now,
            "ip_address"         => $ip,
            "is_finalized"       => ($is_finalized) ? "yes" : "no"
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

            while (list($field_id, $col_name) = each($field_id_to_column_name_map)) {
                $default_insert_pairs[$col_name] = $field_id_to_value_map[$field_id];
            }
        }

        $col_names  = implode(", ", array_keys($default_insert_pairs));
        $col_values = "'" . implode("', '", array_values($default_insert_pairs)) . "'";

        $db->query("
            INSERT INTO {PREFIX}form_{$form_id} ($col_names)
            VALUES ($col_values)
        ");
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
        if (isset($_SESSION["ft"]["form_{$form_id}_selected_submissions"]) && in_array($submission_id, $_SESSION["ft"]["form_{$form_id}_selected_submissions"])) {
            array_splice($_SESSION["ft"]["form_{$form_id}_selected_submissions"],
            array_search($submission_id, $_SESSION["ft"]["form_{$form_id}_selected_submissions"]), 1);
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
     * @param mixed $delete_ids a single submission ID / an array of submission IDs / "all". This column
     *               determines which submissions will be deleted
     * @param integer $view_id (optional) this is only needed if $delete_ids is set to "all". With the advent
     *               of Views, it needs to know which submissions to delete.
     * @return array returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function deleteSubmissions($form_id, $view_id, $submissions_to_delete, $omit_list, $search_fields, $is_admin)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        if ($submissions_to_delete == "all") {
            // get the list of searchable columns for this View. This is needed to ensure that get_search_submission_ids receives
            // the correct info to determine what submission IDs are appearing in this current search.
            $searchable_columns = Views::getViewSearchableFields($view_id);
            $submission_ids = Submissions::getSearchSubmissionIds($form_id, $view_id, "all", "submission_id-ASC", $search_fields, $searchable_columns);
            $submission_ids = array_diff($submission_ids, $omit_list);
        } else {
            $submission_ids = $submissions_to_delete;
        }

        $submissions_to_delete = $submission_ids;
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
                        "field_id"      => $field_info["field_id"],
                        "field_type_id" => $field_type_id,
                        "filename"      => $filename
                    );
                }
            }

            if (!empty($file_fields_to_delete)) {
                list($success, $file_delete_problems) = Files::deleteSubmissionFiles($form_id, $file_fields_to_delete, "Submissions::deleteSubmissions");
            }
        }


        // now delete the submission

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

        $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";
        $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
        $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();

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
    private static function getSubmission($form_id, $submission_id, $view_id = "")
    {
        $return_arr = array();

        $form_fields = Fields::getFormFields($form_id);
        $submission  = ft_get_submission_info($form_id, $submission_id);

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
     * Retrieves ONLY the submission data itself. If you require "meta" information about the submision
     * such as it's field type, size, database table name etc, use ft_get_submision().
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
     * @param mixed   $results_per_page an integer, or "all"
     * @param string  $order a string of form: "{db column}_{ASC|DESC}"
     * @param array   $search_fields an optional hash with these keys:<br/>
     *                  search_field<br/>
     *                  search_date<br/>
     *                  search_keyword<br/>
     * @param array   $search_columns the columns that are being searched
     * @return string an HTML string
     */
    public static function getSearchSubmissionIds($form_id, $view_id, $results_per_page, $order, $search_fields = array(), $search_columns = array())
    {
        $db = Core::$db;

        // determine the various SQL clauses
        $order_by            = _ft_get_search_submissions_order_by_clause($form_id, $order);
        $filter_clause       = _ft_get_search_submissions_view_filter_clause($view_id);
        $search_where_clause = _ft_get_search_submissions_search_where_clause($form_id, $search_fields, $search_columns);

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
        } catch (PDOException $e) {
            Errors::handleDatabaseError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
            exit;
        }

        return $db->fetchAll();
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
    public static function updateSubmission($form_id, $submission_id, $infohash)
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $multi_val_delimiter = Core::getMultiFieldValDelimiter();

        $success = true;
        $message = $LANG["notify_form_submission_updated"];

        extract(Hooks::processHookCalls("start", compact("form_id", "submission_id", "infohash"), array("infohash")), EXTR_OVERWRITE);

        $field_ids = array();
        if (!empty($infohash["field_ids"])) {
            $field_ids = explode(",", $infohash["field_ids"]);
        }

        // perform any server-side validation
        $errors = FieldValidation::validateSubmission($infohash["editable_field_ids"], $infohash);

        // if there are any problems, return right away
        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors));
        }

        $form_fields = Fields::getFormFields($form_id);
        $field_types_processing_info = FieldTypes::getFieldTypeProcessingInfo();

        // this gets all settings for the fields, taking into account whatever has been overridden
        $field_settings = FieldTypes::getFormFieldFieldTypeSettings($field_ids, $form_fields);

        $now = General::getCurrentDatetime();
        $query = array();
        $query[] = "last_modified_date = '$now'";

        $file_fields = array();
        foreach ($form_fields as $row) {
            $field_id = $row["field_id"];

            // if the field ID isn't in the page's tab, ignore it
            if (!in_array($field_id, $field_ids)) {
                continue;
            }

            // if the field ID isn't editable, the person's being BAD and trying to hack a field value. Ignore it.
            if (!in_array($field_id, $infohash["editable_field_ids"])) {
                continue;
            }

            // if this is a FILE field that doesn't have any overridden PHP processing code, just store the info
            // about the field. Presumably, the module / field type has registered the appropriate hooks for
            // processing the file. Without it, the module wouldn't work. We pass that field + file into to the hook.
            if ($field_types_processing_info[$row["field_type_id"]]["is_file_field"] == "yes") {
                $file_data = array(
                    "field_id"   => $field_id,
                    "field_info" => $row,
                    "data"       => $infohash,
                    "code"       => $field_types_processing_info[$row["field_type_id"]]["php_processing"],
                    "settings"   => $field_settings[$field_id]
                );

                if (empty($field_types_processing_info[$row["field_type_id"]]["php_processing"])) {
                    $file_fields[] = $file_data;
                    continue;
                } else {
                    $value = Submissions::processFormField($file_data);
                    $query[] = $row["col_name"] . " = '$value'";
                }
            }

            if ($row["field_name"] == "core__submission_date" || $row["col_name"] == "core__last_modified") {
                if (!isset($infohash[$row["field_name"]]) || empty($infohash[$row["field_name"]])) {
                    continue;
                }
            }

            // see if this field type has any special PHP processing to do
            if (!empty($field_types_processing_info[$row["field_type_id"]]["php_processing"])) {
                $data = array(
                    "field_info"   => $row,
                    "data"         => $infohash,
                    "code"         => $field_types_processing_info[$row["field_type_id"]]["php_processing"],
                    "settings"     => $field_settings[$field_id],
                    "account_info" => isset($_SESSION["ft"]["account"]) ? $_SESSION["ft"]["account"] : array()
                );
                $value = Submissions::processFormField($data);
                $query[] = $row["col_name"] . " = '$value'";
            } else {
                if (isset($infohash[$row["field_name"]])) {
                    if (is_array($infohash[$row["field_name"]])) {
                        $query[] = $row["col_name"] . " = '" . implode("$multi_val_delimiter", $infohash[$row["field_name"]]) . "'";
                    } else {
                        $query[] = $row["col_name"] . " = '" . $infohash[$row["field_name"]] . "'";
                    }
                } else {
                    $query[] = $row["col_name"] . " = ''";
                }
            }
        }

        $set_query = join(",\n", $query);

        try {
            $db->query("
                UPDATE {PREFIX}form_{$form_id}
                SET    $set_query
                WHERE  submission_id = :submission_id
            ");
            $db->bind("submission_id", $submission_id);
            $db->execute();
        } catch (PDOException $e) {

            // if there was a problem updating the submission, don't even bother calling the file upload hook. Just exit right away
            return array(false, $LANG["notify_submission_not_updated"]);
        }

        // now process any file fields
        extract(Hooks::processHookCalls("manage_files", compact("form_id", "submission_id", "file_fields"), array("success", "message")), EXTR_OVERWRITE);

        // send any emails
        Emails::sendEmails("on_edit", $form_id, $submission_id);

        extract(Hooks::processHookCalls("end", compact("form_id", "submission_id", "infohash"), array("success", "message")), EXTR_OVERWRITE);

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


}
