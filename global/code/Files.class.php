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

}
