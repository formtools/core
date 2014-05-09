<?php

/**
 * Actions.php
 *
 * This file handles all server-side responses for Ajax requests. As of 2.0.0, it returns information
 * in JSON format to be handled by JS.
 */

// -------------------------------------------------------------------------------------------------

require_once("../session_start.php");
ft_check_permission("user");


// the action to take and the ID of the page where it will be displayed (allows for
// multiple calls on same page to load content in unique areas)
$request = array_merge($_GET, $_POST);
$action  = $request["action"];

// Find out if we need to return anything back with the response. This mechanism allows us to pass any information
// between the Ajax submit function and the Ajax return function. Usage:
//   "return_vals[]=question1:answer1&return_vals[]=question2:answer2&..."
$return_val_str = "";
if (isset($request["return_vals"]))
{
  $vals = array();
  foreach ($request["return_vals"] as $pair)
  {
    list($key, $value) = split(":", $pair);
    $vals[] = "$key: \"$value\"";
  }
  $return_val_str = ", " . join(", ", $vals);
}


switch ($action)
{
  case "test_folder_permissions":
    list($success, $message) = ft_check_upload_folder($request["file_upload_dir"]);
    $success = ($success) ? 1 : 0;
    echo "{ success: $success, message: \"$message\"{$return_val_str} }";
    break;

  case "test_folder_url_match":
    list($success, $message) = ft_check_folder_url_match($request["file_upload_dir"], $request["file_upload_url"]);
    $success = ($success) ? 1 : 0;
    echo "{ success: $success, message: \"$message\"{$return_val_str} }";
    break;

  case "remember_edit_view_tab":
    $_SESSION["ft"]["edit_view_tab"] = $request["edit_view_tab"];
    break;

  case "remember_edit_email_tab":
    $_SESSION["ft"]["edit_email_tab"] = $request["edit_email_tab"];
    break;

  case "select_submission":
    $form_id = $request["form_id"];
    $submission_id = $request["submission_id"];

    if (empty($_SESSION["ft"]["form_{$form_id}_select_all_submissions"]))
    {
      if (!isset($_SESSION["ft"]["form_{$form_id}_selected_submissions"]))
        $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
      if (!in_array($submission_id, $_SESSION["ft"]["form_{$form_id}_selected_submissions"]))
        $_SESSION["ft"]["form_{$form_id}_selected_submissions"][] = $submission_id;
    }
    else
    {
      // if it's in the omit list, remove it
      if (in_array($submission_id, $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]))
        array_splice($_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"], array_search($submission_id, $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]), 1);
    }
    break;

  // this unselects a submission ID from sessions. If the user had previous selected all submissions in the result
  // set, it adds the submission ID to the form's submission ID omit list; otherwise it just logs the submission ID
  // in the form's selected submission ID array
  case "unselect_submission":
    $form_id = $request["form_id"];
    $submission_id = $request["submission_id"];

    if (empty($_SESSION["ft"]["form_{$form_id}_select_all_submissions"]))
    {
      if (!isset($_SESSION["ft"]["form_{$form_id}_selected_submissions"]))
        $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
      if (in_array($submission_id, $_SESSION["ft"]["form_{$form_id}_selected_submissions"]))
        array_splice($_SESSION["ft"]["form_{$form_id}_selected_submissions"], array_search($submission_id, $_SESSION["ft"]["form_{$form_id}_selected_submissions"]), 1);
    }
    else
    {
      if (!isset($_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]))
        $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();

      if (!in_array($submission_id, $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]))
        $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"][] = $submission_id;
    }
    break;

  case "select_submissions":
    $form_id        = $request["form_id"];
    $submission_ids = split(",", $request["submission_ids"]);

    // user HASN'T selected all submissions
    if (empty($_SESSION["ft"]["form_{$form_id}_select_all_submissions"]))
    {
      if (!isset($_SESSION["ft"]["form_{$form_id}_selected_submissions"]))
        $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();

      foreach ($submission_ids as $submission_id)
      {
        if (!in_array($submission_id, $_SESSION["ft"]["form_{$form_id}_selected_submissions"]))
          $_SESSION["ft"]["form_{$form_id}_selected_submissions"][] = $submission_id;
      }
    }
    // user has already selected all submissions. Here, we actually REMOVE the newly selected submissions from
    // the form submission omit list
    else
    {
      if (!isset($_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]))
        $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();

      foreach ($submission_ids as $submission_id)
      {
        if (in_array($submission_id, $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]))
          array_splice($_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"], array_search($submission_id, $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"]), 1);
      }
    }
    break;

  // this is called when the user has selected all submissions in a result set, regardless of page
  case "select_all_submissions":
    $form_id = $request["form_id"];
    $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "1";
    $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array(); // empty the specific selected submission
    $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();
    break;

  case "unselect_all_submissions":
    $form_id = $request["form_id"];
    $_SESSION["ft"]["form_{$form_id}_select_all_submissions"] = "";
    $_SESSION["ft"]["form_{$form_id}_selected_submissions"] = array();
    $_SESSION["ft"]["form_{$form_id}_all_submissions_selected_omit_list"] = array();
    break;

  case "send_test_email":
    list($success, $message) = ft_send_test_email($request);
    $success = ($success) ? 1 : 0;
    echo "{ success: $success, message: \"$message\" }";
    break;

  case "display_test_email":
    $form_id  = $_SESSION["ft"]["form_id"];
    $email_id = $_SESSION["ft"]["email_id"];
    $info = ft_get_email_components($form_id, "", $email_id, true, $request);
    echo ft_convert_to_json($info);
    break;

  // called by the administrator or client on the Edit Submission page. Note that we pull the submission ID
  // and the form ID from sessions rather than have them explictly passed by the JS. This is a security precaution -
  // it prevents a potential hacker exploiting this function here. Instead they'd have to set the sessions by another
  // route which is trickier
  case "delete_submission_file":
    $form_id       = $_SESSION["ft"]["curr_form_id"];
    $submission_id = $_SESSION["ft"]["last_submission_id"];
    $field_id      = $request["field_id"];
    $force_delete  = ($request["force_delete"] == "true") ? true : false;

    if (empty($form_id) || empty($submission_id))
    {
      echo "{ success: false, message: \"{$LANG["notify_invalid_session_values_re_login"]}\" } ";
      exit;
    }

    list($success, $message) = ft_delete_file_submission($form_id, $submission_id, $field_id, $force_delete);
    $success = ($success) ? 1 : 0;
    $message = ft_sanitize($message);
    echo "{ success: $success, message: \"$message\"{$return_val_str} }";
    break;

  case "edit_submission_send_email":
    $form_id       = $request["form_id"];
    $submission_id = $request["submission_id"];
    $email_id      = $request["email_id"];

    $success = ft_process_email_template($form_id, $submission_id, $email_id);
    if ($success)
    {
      $success = 1;
      $message = $LANG["notify_email_sent"];
    }
    else
    {
      $success = 0;
      $message = $LANG["notify_email_not_sent"];
    }
    echo "{ success: $success, message: \"$message\" }";
    break;

  case "remember_edit_email_advanced_settings":
    $_SESSION["ft"]["edit_email_advanced_settings"] = $request["edit_email_advanced_settings"];
    break;

  case "smart_fill":
    $scrape_method = $request["scrape_method"];
    $url           = $request["url"];
    switch ($scrape_method)
    {
      case "file_get_contents":
        $html = file_get_contents($url);
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        echo $html;
        break;

      case "curl":
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $url);
        $html = curl_exec($c);
        curl_close($c);
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        echo $html;
        break;

      case "redirect":
        header("location: $url");
        exit;
    }
    break;

  case "process_smart_fill_contents":
    $form_id = $_SESSION["ft"]["add_form_form_id"];
    ft_set_form_field_types($form_id, $request);

    // finalize the form and redirect to step 6
    $form_info = ft_get_form($form_id);
    if ($form_info["is_complete"] != 'yes')
      ft_finalize_form($form_id);

    echo "{ success: 1, message: \"\" }";
    break;

  case "get_js_webpage_parse_method":
    $url = $request["url"];
    $method = ft_get_js_webpage_parse_method($url);
    echo "{ scrape_method: \"$method\" }";
    break;

  // used on the Add Form Step 5 page and Edit Field Options pages. It uploads
  // the files to the /upload folder and returns the filenames (renamed & stored in sessions).
  // That information is then used by the JS to load and process the page content
  case "upload_scraped_pages_for_smart_fill":
    $num_pages = $request["num_pages"];
    $settings = ft_get_settings(array("file_upload_dir", "file_upload_url"), "core");
    $file_upload_dir = $settings["file_upload_dir"];
    $file_upload_url = $settings["file_upload_url"];
    $upload_tmp_file_prefix = "ft_sf_tmp_";

    if (!isset($_SESSION["ft"]["smart_fill_tmp_uploaded_files"]))
      $_SESSION["ft"]["smart_fill_tmp_uploaded_files"] = array();

    $uploaded_file_info = array();
    $error = false;
    for ($i=1; $i<=$num_pages; $i++)
    {
      if (!isset($_FILES["form_page_{$i}"]))
        continue;

      $filename        = $upload_tmp_file_prefix . $_FILES["form_page_{$i}"]["name"];
      $tmp_location    = $_FILES["form_page_{$i}"]["tmp_name"];

      list($g_success, $g_message, $final_filename) = ft_upload_file($file_upload_dir, $filename, $tmp_location);
      if ($g_success)
      {
        $uploaded_file_info[] = "$file_upload_url/$final_filename";
        $_SESSION["ft"]["smart_fill_tmp_uploaded_files"][] = "$file_upload_dir/$final_filename";
      }
      else
      {
        $error = true;
        break;
      }
    }

    if ($error)
    {
      echo "{ success: 0, message: '{$LANG["notify_smart_fill_upload_fields_fail"]}' }";
    }
    else
    {
      $params = array("success: 1");
      foreach ($uploaded_file_info as $url)
        $params[] = "url_1: \"$url\"";

      echo "{ " . join(", ", $params) . " }";
    }
    break;

  // used on Edit Field Options pages. It uploads the files to the /upload folder and returns the filenames (renamed
  // & stored in sessions). That information is then used by the JS to load and process the page content
  case "upload_scraped_page_for_smart_fill":
    $settings = ft_get_settings(array("file_upload_dir", "file_upload_url"), "core");
    $file_upload_dir = $settings["file_upload_dir"];
    $file_upload_url = $settings["file_upload_url"];
    $upload_tmp_file_prefix = "ft_sf_tmp_";

    if (!isset($_SESSION["ft"]["smart_fill_tmp_uploaded_files"]))
      $_SESSION["ft"]["smart_fill_tmp_uploaded_files"] = array();

    $uploaded_file_info = array();
    $error = false;

    if (!isset($_FILES["form_page_1"]))
      continue;

    $filename        = $upload_tmp_file_prefix . $_FILES["form_page_1"]["name"];
    $tmp_location    = $_FILES["form_page_1"]["tmp_name"];

    list($g_success, $g_message, $final_filename) = ft_upload_file($file_upload_dir, $filename, $tmp_location);
    if ($g_success)
    {
      $_SESSION["ft"]["smart_fill_tmp_uploaded_files"][] = "$file_upload_dir/$final_filename";
      header("location: $file_upload_url/$final_filename");
      exit;
    }
    else
    {
      echo "{ success: 0, message: '{$LANG["notify_smart_fill_upload_fields_fail"]}' }";
      exit;
    }
    break;

  case "get_upgrade_form_html":
  	echo "<form action=\"http://www.formtools.org/upgrade.php\" id=\"upgrade_form\" method=\"post\" target=\"_blank\">";
  	foreach ($_SESSION["ft"]["upgrade_info"] as $component_info)
    {
    	echo "<input type=\"hidden\" name=\"{$component_info["k"]}\" value=\"{$component_info["v"]}\" />\n";
    }
    echo "</form>";
  	break;
}
