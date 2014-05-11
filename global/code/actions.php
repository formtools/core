<?php

/**
 * Actions.php
 *
 * This file handles all server-side responses for Ajax requests. As of 2.0.0, it returns information
 * in JSON format to be handled by JS.
 */

// -------------------------------------------------------------------------------------------------

// this var prevents the default behaviour of auto-logging the user out
$g_check_ft_sessions = false;
require_once("../session_start.php");

// check the permissions
$permission_check = ft_check_permission("user", false);

// check the sessions haven't timeoutted
$sessions_still_valid = ft_check_sessions_timeout(false);
if (!$sessions_still_valid)
{
  @session_destroy();
  $_SESSION["ft"] = array();

  $permission_check["has_permission"] = false;
  $permission_check["message"] = "session_expired";
}

// the action to take and the ID of the page where it will be displayed (allows for
// multiple calls on same page to load content in unique areas)
$request = array_merge($_GET, $_POST);
$action  = $request["action"];

// To be deprecated! This is the pre-jQuery way to return vars back. Change to use return_vars, which passes an object
// ------------
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
    $vals[] = "\"$key\": \"$value\"";
  }
  $return_val_str = ", " . implode(", ", $vals);
}

// new method (see comment above). Doesn't allow double quotes in the key or value [Note: return_vars vs return_vals !]
$return_str = "";
if (isset($request["return_vars"]))
{
  $vals = array();
  while (list($key, $value) = each($request["return_vars"]))
  {
    $vals[] = "\"$key\": \"$value\"";
  }
  $return_str = ", " . implode(", ", $vals);
}

if (!$permission_check["has_permission"])
{
  $message = $permission_check["message"];
  echo "{ \"success\": \"0\", \"ft_logout\": \"1\", \"message\": \"$message\"{$return_val_str} }";
  exit;
}

switch ($action)
{
  case "test_folder_permissions":
    list($success, $message) = ft_check_upload_folder($request["file_upload_dir"]);
    $success = ($success) ? 1 : 0;
    echo "{ \"success\": \"$success\", \"message\": \"$message\"{$return_val_str} }";
    break;

  case "test_folder_url_match":
    list($success, $message) = ft_check_folder_url_match($request["file_upload_dir"], $request["file_upload_url"]);
    $success = ($success) ? 1 : 0;
    echo "{ \"success\": \"$success\", \"message\": \"$message\"{$return_val_str} }";
    break;

  // expects the tabset name and inner_tab to contain an alphanumeric string only
  case "remember_inner_tab":
    $tabset = strip_tags($request["tabset"]);
    $tab    = strip_tags($request["tab"]);

    if (!array_key_exists("inner_tabs", $_SESSION["ft"]))
      $_SESSION["ft"]["inner_tabs"] = array();

    $_SESSION["ft"]["inner_tabs"][$tabset] = $tab;
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
    echo "{ \"success\": \"$success\", \"message\": \"$message\" }";
    break;

  case "display_test_email":
    $form_id  = $_SESSION["ft"]["form_id"];
    $email_id = $_SESSION["ft"]["email_id"];
    $info = ft_get_email_components($form_id, "", $email_id, true, $request);
    echo ft_convert_to_json($info);
    break;

  case "edit_submission_send_email":
    $form_id       = $request["form_id"];
    $submission_id = $request["submission_id"];
    $email_id      = $request["email_id"];

    list($success, $message) = ft_process_email_template($form_id, $submission_id, $email_id);
    if ($success)
    {
      $success = 1;
      $message = $LANG["notify_email_sent"];
    }
    else
    {
      $edit_email_template_link = "[<a href=\"{$g_root_url}/admin/forms/edit.php?form_id=$form_id&email_id=$email_id&page=edit_email\">edit email template</a>]";
      $success = 0;
      $message = $LANG["notify_email_not_sent_c"] . mb_strtolower($message) . " " . $edit_email_template_link;
    }
    $message = addslashes($message);
    echo "{ \"success\": \"$success\", \"message\": \"$message\" }";
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
        $url = ft_construct_url($url, "ft_sessions_url_override=1");
      	$html = file_get_contents($url);
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        echo $html;
        break;

      case "curl":
        $url = ft_construct_url($url, "ft_sessions_url_override=1");
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
    {
      $response = ft_finalize_form($form_id);
      echo ft_convert_to_json($response);
    }
    else
    {
    	echo "{ \"success\": \"1\", \"message\": \"\" }";
    }
    break;

  case "get_js_webpage_parse_method":
    $url = $request["url"];
    $method = ft_get_js_webpage_parse_method($url);
    echo "{ \"scrape_method\": \"$method\" }";
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

      $filename     = $upload_tmp_file_prefix . $_FILES["form_page_{$i}"]["name"];
      $tmp_location = $_FILES["form_page_{$i}"]["tmp_name"];

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
      echo "{ \"success\": \"0\", \"message\": \"{$LANG["notify_smart_fill_upload_fields_fail"]}\" }";
    }
    else
    {
      $params = array("\"success\": \"1\"");
      $count = 1;
      foreach ($uploaded_file_info as $url)
      {
        $params[] = "\"url_{$count}\": \"$url\"";
        $count++;
      }

      echo "{ " . implode(", ", $params) . " }";
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
      echo "{ \"success\": \"0\", \"message\": \"{$LANG["notify_smart_fill_upload_fields_fail"]}\" }";
      exit;
    }
    break;

  case "get_upgrade_form_html":
    $components = ft_get_formtools_installed_components();
    echo "<form action=\"http://www.formtools.org/upgrade.php\" id=\"upgrade_form\" method=\"post\" target=\"_blank\">";
    while (list($key, $value) = each($components))
    {
      echo "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";
    }
    echo "</form>";
    break;

  case "get_extended_field_settings":
    $field_id      = $request["field_id"];
    $field_type_id = $request["field_type_id"];
    $settings      = ft_get_extended_field_settings($field_id, "", true);
    $validation    = ft_get_field_validation($field_id);
    $info = array(
      "field_id"      => $field_id,
      "field_type_id" => $field_type_id,
      "settings"      => $settings,
      "validation"    => $validation
    );
    echo ft_convert_to_json($info);
    break;

  case "get_option_lists":
    $option_lists = ft_get_option_lists("all");
    $option_list_info = array();
    foreach ($option_lists["results"] as $option_list) {
      $option_list_info[$option_list["list_id"]] = $option_list["option_list_name"];
    }
    echo ft_convert_to_json($option_list_info);
    break;

  // used on the Edit Form -> Fields tab
  case "get_form_list":
    $form_list = ft_get_form_list();
    $forms = array();
    foreach ($form_list as $form_info) {
      $forms[$form_info["form_id"]] = $form_info["form_name"];
    }
    echo ft_convert_to_json($forms);
    break;

  // used for the Edit Form -> fields tab. Note that any dynamic settings ARE evaluated.
  case "get_form_fields":
    $form_id     = $request["form_id"];
    $field_id    = $request["field_id"];
    $field_order = $request["field_order"];
    $form_fields = ft_get_form_fields($form_id); // array("evaluate_dynamic_settings" => true)
    $fields = array();
    foreach ($form_fields as $field_info)
    {
      $fields[$field_info["field_id"]] = $field_info["field_title"];
    }
    $return_info = array(
      "form_id"     => $form_id,
      "field_id"    => $field_id,
      "field_order" => $field_order,
      "fields"      => $fields
    );
    echo ft_convert_to_json($return_info);
    break;

  case "create_new_view":
    $form_id   = $request["form_id"];
    $group_id  = $request["group_id"];
    $view_name = $request["view_name"];
    $duplicate_view_id = "";

    // here, create_view_from_view_id either contains the ID of the View that the user wants to copy,
    // or "blank_view_no_fields", meaning a totally blank View or "blank_view_all_fields" meaning
    // they want all View fields added by default
    if (isset($request["create_view_from_view_id"]) && !empty($request["create_view_from_view_id"]))
    {
      $duplicate_view_id = $request["create_view_from_view_id"];
    }

    $view_id = ft_create_new_view($form_id, $group_id, $view_name, $duplicate_view_id);

    // always set the default Edit View tab to the first one
    $_SESSION["ft"]["edit_view_tab"] = 1;
    echo "{ \"success\": \"1\", \"view_id\": \"$view_id\" }";
    break;

  case "create_new_view_group":
    $form_id    = $_SESSION["ft"]["form_id"];
    $group_type = "form_{$form_id}_view_group";
    $group_name = $request["group_name"];
    $info = ft_add_list_group($group_type, $group_name);
    echo ft_convert_to_json($info);
    break;

  case "delete_view":
    $view_id = $request["view_id"];
    ft_delete_view($view_id);
    echo "{ \"success\": \"1\", \"view_id\": \"$view_id\" }";
    break;

  // this is called when the user clicks on the "Save Changes" button on the Edit Field dialog on the
  // Fields tab
  case "update_form_fields":
    $form_id = $request["form_id"];
    $changed_field_ids = $request["data"]["changed_field_ids"];

    // update whatever information has been included in the request
    $problems = array();
    $count = 1;
    $new_field_map = array();
    foreach ($changed_field_ids as $field_id)
    {
      if (!isset($request["data"]["field_$field_id"]))
        continue;

      // if this is a NEW field, we just ignore it here. New fields are only added by updating the main page, not
      // via the Edit Field dialog
      if (preg_match("/^NEW/", $field_id))
        continue;

      list($success, $message) = ft_update_field($form_id, $field_id, $request["data"]["field_$field_id"]);
      if (!$success)
      {
        $problems[] = array("field_id" => $field_id, "error" => $message);
      }
    }
    if (!empty($problems))
    {
      $problems_json = ft_convert_to_json($problems);
      echo "{ \"success\": \"0\", \"problems\": $problems_json{$return_str} }";
    }
    else
    {
      echo "{ \"success\": \"1\"{$return_str} }";
    }
    break;

  // used to return a page outlining all the form field placeholders available
  case "get_form_field_placeholders":
    $form_id = $request["form_id"];

    $text_reference_tab_info = ft_eval_smarty_string($LANG["text_reference_tab_info"], array("g_root_url" => $g_root_url));

    $page_vars = array();
    $page_vars["form_id"] = $form_id;
    $page_vars["form_fields"] = ft_get_form_fields($form_id, array("include_field_type_info" => true));
    $page_vars["text_reference_tab_info"] = $text_reference_tab_info;

    ft_display_page("admin/forms/form_placeholders.tpl", $page_vars);
    break;
}

