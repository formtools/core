<?php

/**
 * Files.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Files {

    /**
     * Examines a folder to check (a) it exists and (b) it has correct permissions.
     *
     * @param string $folder The full path to the folder
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function checkUploadFolder($folder)
    {
        $LANG = Core::$L;

        // first, check server's temporary file upload folder
        $upload_tmp_dir = ini_get("upload_tmp_dir");

        if (!empty($upload_tmp_dir)) {
            if (!is_dir($upload_tmp_dir)) {
                $replacement_info = array("upload_folder" => $upload_tmp_dir);
                $message = General::evalSmartyString($LANG["validation_invalid_upload_folder"], $replacement_info);
                return array(false, $message);
            }
            if (!is_writable($upload_tmp_dir)) {
                return array(false, $LANG["validation_upload_folder_not_writable"]);
            }
        }

        // now check the folder is really a folder
        if (!is_dir($folder)) {
            return array(false, $LANG["validation_invalid_folder"]);
        }
        if (!is_writable($folder)) {
            return array(false, $LANG["validation_folder_not_writable"]);
        }
        return array(true, $LANG["notify_folder_correct_permissions"]);
    }

    /**
     * This is called by the ft_delete_submission and ft_delete_submissions function. It's passed all relevant
     * information about the submission & file fields that need to be deleted. The function is just a stub to
     * allow file upload modules to add their hooks to.
     *
     * Modules that extend this function should return $problems. That should be an array of hashes. Each hash
     * having keys "filename" and "error". Since the calling functions will blithely delete the submissions even
     * if the file deletion fails, no other info is worth returning.
     *
     * @param integer $form_id
     * @param array $file_info an array of hashes. Each hash has the following keys (all self-explanatory):
     *                    submission_id -
     *                    field_id -
     *                    filename -
     * @param string $context. Just used to pass a little more info to the hook. This is the context in which this
     *                    function is being called; i.e. the function name / action.
     */
    public static function deleteSubmissionFiles($form_id, $file_field_info, $context = "")
    {
        $success = true;
        $problems = array();

        extract(Hooks::processHookCalls("start", compact("form_id", "file_field_info"), array("success", "problems")), EXTR_OVERWRITE);

        return array($success, $problems);
    }



}
