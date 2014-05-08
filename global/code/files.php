<?php

/**
 * This file defines all functions related to files and folders within Form Tools. All direct image-related
 * functionality (uploading, resizing, etc) for the Image Manager module is found in images.php.
 *
 * @copyright Encore Web Studios 2010
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-1
 * @subpackage Files
 */


// -------------------------------------------------------------------------------------------------


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
function ft_get_unique_filename($folder, $filename)
{
  // check the supplied dir is a valid, readable directory
  if (!is_dir($folder) && is_readable($folder))
    return;

  // the filename string to return
  $return_filename = $filename;

  // store all the filenames in the folder into an array
  $filenames = array();
  if ($handle = opendir($folder))
  {
    while (false !== ($file = readdir($handle)))
      $filenames[] = $file;
  }

  // if a file with the same name exists in the directory, find the next free filename of the form:
  // x_$filename, where x is a number starting with 1
  if (in_array($filename, $filenames))
  {
    // if it already starts with x_, strip off the prefix.
    if (preg_match("/^\d+_/", $filename))
      $filename = preg_replace("/^\d+_/", "", $filename);

    // now find the next available filename
    $next_num = 1;
    $return_filename = $next_num . "_" . $filename;
    while (in_array($return_filename, $filenames))
    {
      $return_filename = $next_num . "_" . $filename;
      $next_num++;
    }
  }

  extract(ft_process_hooks("end", compact("return_filename"), array("return_filename")), EXTR_OVERWRITE);

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
function ft_check_folder_url_match($folder, $url)
{
  global $g_debug, $g_default_error_reporting, $LANG;

  $folder = rtrim(trim($folder), "/\\");
  $url    = rtrim(trim($url), "/\\");

  list($success, $message) = ft_check_upload_folder($folder);
  if (!$success)
    return array(false, $LANG["validation_folder_invalid_permissions"]);

  if (ini_get("allow_url_fopen") != "1")
    return array(false, $LANG["notify_allow_url_fopen_not_set"]);

  // create the temp file
  $test_file = "ft_" . date("U") . ".tmp";

  if (($fh = fopen("$folder/$test_file", "w")) === FALSE)
    return array(true, "Problem creating test file.");

  fwrite($fh, "Folder-URL match test");
  fclose($fh);

  // now try and read the file. We activate error reporting for the duration of this test so we
  // can examine any error messages that occur to provide some pointers for the user
  error_reporting(2047);
  ob_start();
  $result = @file("$url/$test_file");
  $errors = ob_get_clean();
  error_reporting($g_default_error_reporting);

  // delete temp file
  @unlink("$folder/$test_file");

  // if $errors is empty, that means there was a match
  if (is_array($result) && $result[0] == "Folder-URL match test")
  {
    return array(true, $LANG["notify_folder_url_match"]);
  }
  else
  {
    $debug = ($g_debug) ? "<br />$errors" : "";

    // let's take a look at the warning.  [Assumption: error messages in English]
    //   "404 Not Found" - Not a match
    if (preg_match("/404 Not Found/", $errors))
      return array(false, $LANG["notify_folder_url_no_match"] . " $debug");

    //   "Authorization Required"    - PHP isn't allowed to look at that URL (URL protected by a .htaccess probably)
    else if (preg_match("/Authorization Required/", $errors))
      return array(false, $LANG["notify_folder_url_no_access"] . " $debug");

    return array(false, $LANG["notify_folder_url_unknown_error"]);
  }
}


/**
 * Examines a folder to check (a) it exists and (b) it has correct permissions.
 *
 * @param string $folder The full path to the folder
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_check_upload_folder($folder)
{
  global $LANG;

  // first, check server's temporary file upload folder
  $upload_tmp_dir = ini_get("upload_tmp_dir");

  if (!empty($upload_tmp_dir))
  {
    if (!is_dir($upload_tmp_dir))
    {
      $replacement_info = array("upload_folder" => $upload_tmp_dir);
      $message = ft_eval_smarty_string($LANG["validation_invalid_upload_folder"], $replacement_info);
      return array(false, );
    }

    if (!is_writable($upload_tmp_dir))
      return array(false, $LANG["validation_upload_folder_not_writable"]);
  }

  // now check the folder is really a folder
  if (!is_dir($folder))
    return array(false, $LANG["validation_invalid_folder"]);

  if (!is_writable($folder))
    return array(false, $LANG["validation_folder_not_writable"]);

  return array(true, $LANG["notify_folder_correct_permissions"]);
}


/**
 * Simple helper function to return the extension of a file.
 *
 * TODO how about using getImageSize?? (GD only?)
 *
 * @param string $filename
 * @return string
 */
function ft_get_filename_extension($filename, $lowercase = false)
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
function ft_move_field_files($field_id, $source_folder, $target_folder, $image_type = "")
{
  global $g_table_prefix, $g_multi_val_delimiter;

  if ($source_folder == $target_folder)
    return;

  $field_info = ft_get_form_field($field_id);
  $col_name   = $field_info["col_name"];
  $field_type = $field_info["field_type"];
  $form_id    = $field_info["form_id"];

  if ($field_type != "file" && $field_type != "image")
    return;

  if ($field_type == "image" && empty($image_type))
    return;


  $query = mysql_query("
    SELECT submission_id, $col_name
    FROM   {$g_table_prefix}form_{$form_id}
    WHERE  $col_name != ''''
      ");

  while ($row = mysql_fetch_assoc($query))
  {
    $submission_id = $row["submission_id"];
    $filename      = $row[$col_name];

    // if this is an image, the field actually contains up to THREE filenames (main image, main thumb, search
    // results thumb). Find the one we want to move and overwrite $filename
    if ($field_type == "image")
    {
      $image_info = ft_get_filenames_from_image_field_string($filename);
      switch ($image_type)
      {
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
          continue;
          break;
      }
    }

    // move the file
    list($success, $new_filename) = ft_move_file($source_folder, $target_folder, $filename);

    // if the file was successfully moved but RENAMED, update the database record
    if ($success && $filename != $new_filename)
    {
      $db_field_str = "";
      if ($image_type = "file")
        $db_field_str = $new_filename;
      else
      {
        switch ($image_type)
        {
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

        $db_field_str = implode($g_multi_val_delimiter, $image_field_string_sections);
      }

      mysql_query("
        UPDATE {$g_table_prefix}form_{$form_id}
        SET    $col_name = '$db_field_str'
        WHERE  submission_id = $submission_id
          ") or die(mysql_error());
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
function ft_move_file($source_folder, $target_folder, $filename)
{
  global $LANG;

  // check the folder is valid and writable
  if (!is_dir($target_folder) || !is_writable($target_folder))
    return array(false, $LANG["notify_invalid_upload_folder"]);

  // ensure the filename is unique
  $unique_filename = ft_get_unique_filename($target_folder, $filename);

  // copy file to the new folder and remove the old one
  if (@rename("$source_folder/$filename", "$target_folder/$unique_filename"))
  {
    @chmod("$target_folder/$unique_filename", 0777);
    @unlink("$source_folder/$filename");

    return array(true, $unique_filename);
  }
  else
    return array(false, $LANG["notify_file_not_uploaded"]);
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
function ft_upload_file($folder, $filename, $tmp_location)
{
  global $g_table_prefix, $LANG;

  // check the folder is valid and writable
  if (!is_dir($folder) || !is_writable($folder))
    return array(false, $LANG["notify_invalid_upload_folder"], "");

  // ensure the filename is unique
  $unique_filename = ft_get_unique_filename($folder, $filename);

  // copy file to uploads folder and remove temporary file
  if (rename($tmp_location, "$folder/$unique_filename"))
  {
    @chmod("$folder/$unique_filename", 0777);
    return array(true, $LANG["notify_file_uploaded"], $unique_filename);
  }
  else
    return array(false, $LANG["notify_file_not_uploaded"], "");
}


/**
 * Uploads a file for a particular form submission field.
 *
 * This is to be called AFTER the submission has already been added to the database so there's an available
 * and valid submission ID. It uploads the file to the specified folder then updates the database record.
 *
 * Since any submission file field can only ever store a single file at once, this file automatically deletes
 * both the file if a new file is successfully uploaded.
 *
 * @param integer $form_id the unique form ID
 * @param integer $submission_id a unique submission ID
 * @param integer $field_id a unique field ID
 * @param array $fileinfo an index from the $_FILES array (containing all data about the file)
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 *               [2]: If success, the filename of the uploaded file
 */
function ft_upload_submission_file($form_id, $submission_id, $field_id, $fileinfo)
{
  global $g_table_prefix, $g_filename_char_whitelist, $LANG;

  extract(ft_process_hooks("start", compact("form_id", "submission_id", "field_id", "fileinfo"),
    array("fileinfo")), EXTR_OVERWRITE);

  // get the column name and upload folder for this field
  $field_info = ft_get_form_field($field_id);
  $extended_field_info = ft_get_extended_field_settings($field_id);

  $col_name             = $field_info["col_name"];
  $file_upload_dir      = $extended_field_info["file_upload_dir"];
  $file_upload_url      = $extended_field_info["file_upload_url"];
  $file_upload_max_size = $extended_field_info["file_upload_max_size"]; // KB
  $file_upload_types    = $extended_field_info["file_upload_filetypes"];

  // if the column name wasn't found, the $field_id passed in was invalid. Return false.
  if (empty($col_name))
    return array(false, $LANG["notify_submission_no_field_id"]);

  // clean up the filename according to the whitelist chars.
  $filename_parts = explode(".", $fileinfo["name"]);
  $extension = $filename_parts[count($filename_parts)-1];
  array_pop($filename_parts);
  $filename_without_extension = implode(".", $filename_parts);
  $valid_chars = preg_quote($g_filename_char_whitelist);
  $filename_without_ext_clean = preg_replace("/[^$valid_chars]/", "", $filename_without_extension);

  // unlikely, but...!
  if (empty($filename_without_ext_clean))
    $filename_without_ext_clean = "file";

  $filename = $filename_without_ext_clean . "." . $extension;

  $tmp_filename = $fileinfo["tmp_name"];
  $filesize     = $fileinfo["size"]; // always in BYTES
  $filesize_kb  = $filesize / 1000;

  // check file size
  if ($filesize_kb > $file_upload_max_size)
    return array(false, $LANG["notify_file_too_large"]);

  // check upload folder is valid and writable
  if (!is_dir($file_upload_dir) || !is_writable($file_upload_dir))
    return array(false, $LANG["notify_invalid_field_upload_folder"]);

  // check file extension is valid. Note: this is "dumb" - it just tests for the file extension string, not
  // the actual file type based on it's header info [this is done because I want to allow users to permit
  // uploading of any file types, and I can't know the headers of all of them]
  $is_valid_extension = true;
  if (!empty($file_upload_types))
  {
    $is_valid_extension = false;
    $raw_extensions = explode(",", $file_upload_types);

    foreach ($raw_extensions as $ext)
    {
      // remove whitespace and periods
      $clean_extension = str_replace(".", "", trim($ext));

      if (preg_match("/$clean_extension$/i", $filename))
        $is_valid_extension = true;
    }
  }

  // all checks out!
  if ($is_valid_extension)
  {
    // find out if there was already a file uploaded in this field. We make a note of this so that
    // in case the new file upload is successful, we automatically delete the old file
    $submission_info = ft_get_submission_info($form_id, $submission_id);
    $old_filename = (!empty($submission_info[$col_name])) ? $submission_info[$col_name] : "";

    // check for duplicate filenames and get a unique name
    $unique_filename = ft_get_unique_filename($file_upload_dir, $filename);

    // copy file to uploads folder and remove temporary file
    if (@rename($tmp_filename, "$file_upload_dir/$unique_filename"))
    {
      @chmod("$file_upload_dir/$unique_filename", 0777);

      // update the database
      $query = "
        UPDATE {$g_table_prefix}form_{$form_id}
        SET    $col_name = '$unique_filename'
        WHERE  submission_id = $submission_id
               ";

      $result = mysql_query($query);

      if ($result)
      {
        // if there was a file previously uploaded in this field, delete it!
        if (!empty($old_filename))
          @unlink("{$extended_field_info["file_upload_dir"]}/$old_filename");

        return array(true, $LANG["notify_file_uploaded"], $unique_filename);
      }
      else
        return array(false, $LANG["notify_file_not_uploaded"]);
    }
    else
      return array(false, $LANG["notify_file_not_uploaded"]);
  }

  // not a valid extension. Inform the user
  else
    return array(false, $LANG["notify_unsupported_file_extension"]);
}


/**
 * This function deletes a folder and all containing files. If it's unable to delete ANYTHING - file or folder,
 * it halts immediately and returns false.
 *
 * @param string $folder
 * @return boolean
 */
function ft_delete_folder($folder)
{
  if (is_dir($folder))
    $handle = opendir($folder);
  else
    return false;

  while (false !== ($file = @readdir($handle)))
  {
    if ($file != "." && $file != "..")
    {
      if (!is_dir($folder . "/" . $file))
      {
        if (!@unlink($folder . "/" . $file))
          return false;
      }
      else
      {
        @closedir($handle);
        ft_delete_folder($folder . '/' . $file);
      }
    }
  }
  @closedir($handle);

  if (!@rmdir($folder))
    return false;

  return true;
}