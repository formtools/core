<?php

/**
 * Files.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Files
{

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
     * This is passed all necessary information about the submission & file fields that need to be deleted. Modules
     * then tie into this method via hooks to handle deletion of the actual files.
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
    public static function deleteSubmissionFiles($form_id, $file_field_info)
    {
        $success = true;
        $problems = array();

        extract(Hooks::processHookCalls("start", compact("form_id", "file_field_info"), array("success", "problems")), EXTR_OVERWRITE);

        return array($success, $problems);
    }


    /**
     * This function should be called every time you try to create a file in a folder. It examines the contents of
     * the folder to see if a filename already exists with that name. If so, it returns a new, "free" filename.
     *
     * The new filenames are of the form "x_$filename", where x is an integer starting at 1.
     *
     * @param string $folder The folder to examine
     * @param string $filename The name of the filename to check for
     * @return string The name of a free filename
     */
    public static function getUniqueFilename($folder, $filename)
    {
        // check the supplied dir is a valid, readable directory
        if (!is_dir($folder) && is_readable($folder)) {
            return;
        }

        // the filename string to return
        $return_filename = $filename;

        // store all the filenames in the folder into an array
        $filenames = array();
        if ($handle = opendir($folder)) {
            while (false !== ($file = readdir($handle))) {
                $filenames[] = $file;
            }
        }

        // if a file with the same name exists in the directory, find the next free filename of the form:
        // x_$filename, where x is a number starting with 1
        if (in_array($filename, $filenames)) {

            // if it already starts with x_, strip off the prefix.
            if (preg_match("/^\d+_/", $filename)) {
                $filename = preg_replace("/^\d+_/", "", $filename);
            }

            // now find the next available filename
            $next_num = 1;
            $return_filename = $next_num . "_" . $filename;
            while (in_array($return_filename, $filenames)) {
                $return_filename = $next_num . "_" . $filename;
                $next_num++;
            }
        }

        extract(Hooks::processHookCalls("end", compact("return_filename"), array("return_filename")), EXTR_OVERWRITE);

        // return the appropriate filename
        return $return_filename;
    }


    /**
     * Confirms that a folder dir and a URL purportedly linking to that folder do, in fact, match.
     *
     * If the URL does point to the folder it returns true; otherwise returns false. The function works
     * by creating a temporary file in $folder, then try and scrape it via file(). If it exists, the
     * folder is a match for URL and it returns true.
     *
     * Assumption: the "allow_url_fopen" setting in php.ini is set to "1" (Checks for this). If it's
     * not set it always returns false.
     *
     * @param string $folder a folder on this server.
     * @param string $url The URL that claims to point to <b>$folder</b>
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function checkFolderUrlMatch($folder, $url)
    {
        $LANG = Core::$L;
        $default_error_reporting = Core::getDefaultErrorReporting();
        $debug_enabled = Core::isDebugEnabled();

        $folder = rtrim(trim($folder), "/\\");
        $url = rtrim(trim($url), "/\\");

        list($success) = Files::checkUploadFolder($folder);
        if (!$success) {
            return array(false, $LANG["validation_folder_invalid_permissions"]);
        }
        if (ini_get("allow_url_fopen") != "1") {
            return array(false, $LANG["notify_allow_url_fopen_not_set"]);
        }

        // create the temp file
        $test_file = "ft_" . date("U") . ".tmp";

        if (($fh = fopen("$folder/$test_file", "w")) === false) {
            return array(true, "Problem creating test file.");
        }

        fwrite($fh, "Folder-URL match test");
        fclose($fh);

        // now try and read the file. We activate error reporting for the duration of this test so we
        // can examine any error messages that occur to provide some pointers for the user
        error_reporting(2047);
        ob_start();
        $result = @file("$url/$test_file");
        $errors = ob_get_clean();
        error_reporting($default_error_reporting);

        // delete temp file
        @unlink("$folder/$test_file");

        // if $errors is empty, that means there was a match
        if (is_array($result) && $result[0] == "Folder-URL match test") {
            return array(true, $LANG["notify_folder_url_match"]);
        } else {
            $debug = ($debug_enabled) ? "<br />$errors" : "";

            // let's take a look at the warning.  [Assumption: error messages in English]
            //   "404 Not Found" - Not a match
            if (preg_match("/404 Not Found/", $errors)) {
                return array(false, $LANG["notify_folder_url_no_match"] . " $debug");
            } //   "Authorization Required"    - PHP isn't allowed to look at that URL (URL protected by a .htaccess probably)
            else {
                if (preg_match("/Authorization Required/", $errors)) {
                    return array(false, $LANG["notify_folder_url_no_access"] . " $debug");
                }
            }

            return array(false, $LANG["notify_folder_url_unknown_error"]);
        }
    }


    /**
     * Return the extension of a file. Assumes it has one.
     * @param string $filename
     * @return string
     */
    public static function getFilenameExtension($filename, $lowercase = false)
    {
        $sections = explode(".", $filename);
        $extension = ($lowercase) ? mb_strtolower($sections[count($sections) - 1]) : $sections[count($sections) - 1];
        return $extension;
    }


    /**
     * Moves all files that are associated with a particular form field. This is used whenever the path for a particular
     * form field is changed. It it called automatically by the script to move all files to the correct location. This can
     * be used for standard file fields or for images uploaded through the Image Manager module.
     *
     * @param integer $field_id
     * @param string $source_folder
     * @param string $target_folder
     * @param string $image_type if the files being moved are images from the Image Manager, the script need to know
     *   what image type it is (main_image, main_thumb, search_results_thumb) to know which ones to move.
     */
    public static function moveFieldFiles($field_id, $source_folder, $target_folder, $image_type = "")
    {
        $db = Core::$db;
        $multi_val_delimiter = Core::getMultiFieldValDelimiter();

        if ($source_folder == $target_folder) {
            return;
        }

        $field_info = Fields::getFormField($field_id);
        $col_name = $field_info["col_name"];
        $field_type = $field_info["field_type"];
        $form_id = $field_info["form_id"];

        if ($field_type != "file" && $field_type != "image") {
            return;
        }
        if ($field_type == "image" && empty($image_type)) {
            return;
        }

        // TODO '''' correct?
        $db->query("
            SELECT submission_id, $col_name
            FROM   {PREFIX}form_{$form_id}
            WHERE  $col_name != ''''
        ");
        $db->execute();

        foreach ($db->fetchAll() as $row) {
            $submission_id = $row["submission_id"];
            $filename = $row[$col_name];

            // if this is an image, the field actually contains up to THREE filenames (main image, main thumb, search
            // results thumb). Find the one we want to move and overwrite $filename
            if ($field_type == "image") {
                $image_info = ft_get_filenames_from_image_field_string($filename);

                switch ($image_type) {
                    case "main_image":
                        $filename = $image_info["main_image"];
                        break;
                    case "main_thumb":
                        $filename = $image_info["main_thumb"];
                        break;
                    case "search_results_thumb":
                        $filename = $image_info["search_results_thumb"];
                        break;
                    default:
                        break;
                }
            }

            // move the file
            list($success, $new_filename) = Files::moveFile($source_folder, $target_folder, $filename);

            // if the file was successfully moved but RENAMED, update the database record
            if ($success && $filename != $new_filename) {
                if ($image_type = "file") {
                    $db_field_str = $new_filename;
                } else {
                    switch ($image_type) {
                        case "main_image":
                            $image_field_string_sections = array(
                                "main_image:$new_filename",
                                "main_thumb:{$image_info["main_thumb"]}",
                                "search_results_thumb:{$image_info["search_results_thumb"]}"
                            );
                            break;
                        case "main_thumb":
                            $image_field_string_sections = array(
                                "main_image:{$image_info["main_image"]}",
                                "main_thumb:$new_filename",
                                "search_results_thumb:{$image_info["search_results_thumb"]}"
                            );
                            break;
                        case "search_results_thumb":
                            $image_field_string_sections = array(
                                "main_image:{$image_info["main_image"]}",
                                "main_thumb:{$image_info["main_thumb"]}",
                                "search_results_thumb:$new_filename"
                            );
                            break;
                    }

                    $db_field_str = implode($multi_val_delimiter, $image_field_string_sections);
                }

                $db->query("
                    UPDATE {PREFIX}form_{$form_id}
                    SET    $col_name = :col_name_value
                    WHERE  submission_id = :submission_id
                ");
                $db->bindAll(array(
                    "col_name_value" => $db_field_str,
                    "submission_id" => $submission_id
                ));
            }
        }
    }


    /**
     * Moves a file from one folder to another. If the filename is not unique, it goes ahead and gets one that
     * is. In either case it returns the filename - new or old.
     *
     * @param string $source
     * @param string $target
     * @return array [0] T/F [1] the filename (if successful), the error message if not
     */
    public static function moveFile($source_folder, $target_folder, $filename)
    {
        $LANG = Core::$L;

        // check the folder is valid and writable
        if (!is_dir($target_folder) || !is_writable($target_folder)) {
            return array(false, $LANG["notify_invalid_upload_folder"]);
        }

        // ensure the filename is unique
        $unique_filename = Files::getUniqueFilename($target_folder, $filename);

        // copy file to the new folder and remove the old one
        if (@rename("$source_folder/$filename", "$target_folder/$unique_filename")) {
            @chmod("$target_folder/$unique_filename", 0777);
            @unlink("$source_folder/$filename");
            return array(true, $unique_filename);
        } else {
            return array(false, $LANG["notify_file_not_uploaded"]);
        }
    }


    /**
     * A simple, no-frills file upload function. It:
     *   - checks the folder exists & has write permissions
     *   - ensures the file has a unique name & doesn't overwrite anything else
     *
     * @param string the folder to upload to
     * @param $filename the (desired) name to call the file (will be renamed if a file with the same
     *     name exists)
     * @param $tmp_location the location of the temporary file
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     *               [2]: the unique filename (or empty, if not successful)
     */
    public static function uploadFile($folder, $filename, $tmp_location)
    {
        $LANG = Core::$L;

        // check the folder is valid and writable
        if (!is_dir($folder) || !is_writable($folder)) {
            return array(false, $LANG["notify_invalid_upload_folder"], "");
        }

        // ensure the filename is unique
        $unique_filename = Files::getUniqueFilename($folder, $filename);

        // copy file to uploads folder and remove temporary file
        if (rename($tmp_location, "$folder/$unique_filename")) {
            @chmod("$folder/$unique_filename", 0777);
            return array(true, $LANG["notify_file_uploaded"], $unique_filename);
        } else {
            return array(false, $LANG["notify_file_not_uploaded"], "");
        }
    }


    /**
     * This function deletes a folder and all containing files. If it's unable to delete ANYTHING - file or folder,
     * it halts immediately and returns false.
     *
     * This also sucks. This has failed to properly delete module folders since 2.0.0.
     *
     * @param string $folder
     * @return boolean
     */
    public static function deleteFolder($directory)
    {
        if (substr($directory, -1) == '/') {
            $directory = substr($directory, 0, -1);
        }
        if (!file_exists($directory) || !is_dir($directory)) {
            return false;
        } elseif (is_readable($directory)) {
            $handle = opendir($directory);
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    $path = $directory . '/' . $item;
                    if (is_dir($path)) {
                        Files::deleteFolder($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            closedir($handle);
            if (!rmdir($directory)) {
                return false;
            }
        }
        return true;
    }


    /**
     * Deletes all files associated with any submission in the form.
     */
    public static function removeFormFiles($form_id, $form_fields)
    {
        $db = Core::$db;

        try {
            $db->query("SELECT submission_id FROM {PREFIX}form_{$form_id}");
            $db->execute();
        } catch (Exception $e) {
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
            exit;
        }

        $file_fields_to_delete = array();

        foreach ($db->fetchAll() as $row) {
            $submission_id = $row["submission_id"];

            foreach ($form_fields as $form_field_info) {
                if ($form_field_info["is_file_field"] == "no") {
                    continue;
                }

                // I really don't like this... what should be done is do a SINGLE query after this loop is complete
                // to return a map of field_id to values. That would then update $file_fields_to_delete
                // with a fraction of the cost
                $submission_info = Submissions::getSubmissionInfo($form_id, $submission_id);
                $filename = $submission_info[$form_field_info["col_name"]];

                // if no filename was stored, it was empty - just continue
                if (empty($filename)) {
                    continue;
                }

                $file_fields_to_delete[] = array(
                    "submission_id" => $submission_id,
                    "field_id" => $form_field_info["field_id"],
                    "field_type_id" => $form_field_info["field_type_id"],
                    "filename" => $filename
                );
            }
        }

        $file_delete_problems = array();
        if (!empty($file_fields_to_delete)) {
            list($success, $file_delete_problems) = Files::deleteSubmissionFiles($form_id, $file_fields_to_delete);
        }

        return $file_delete_problems;
    }

}
