<?php

/**
 * Submissions.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


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

        $submission_ids = array();
        if ($submissions_to_delete == "all") {
            // get the list of searchable columns for this View. This is needed to ensure that ft_get_search_submission_ids receives
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

}
