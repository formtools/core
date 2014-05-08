<?php

/**
 * This file defines all functions related to emails sent by Form Tools.
 *
 * @copyright Encore Web Studios 2009
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-0
 * @subpackage Emails
 */


// -------------------------------------------------------------------------------------------------


/**
 * This function is called whenever the user clicks the "Create Email" button on the main email list page.
 *
 * @param integer $form_id
 * @param integer $create_email_from_email_id this option parameter lets the user create a new email based on
 *      an existing one, saving them the effort of having to re-enter everything.
 */
function ft_create_blank_email_template($form_id, $create_email_from_email_id = "")
{
  global $g_table_prefix;

  if (empty($create_email_from_email_id))
  {
    mysql_query("
      INSERT {$g_table_prefix}email_templates (form_id, email_status, email_event_trigger)
      VALUES ($form_id, 'enabled', 'on_submission')
        ");
    $email_id = mysql_insert_id();
  }
  else
  {
    $email_template_info = ft_get_email_template($create_email_from_email_id);

    // WISHLIST: be nice to have a generic "copy_table_row" function...
    $query = mysql_query("
      INSERT INTO {$g_table_prefix}email_templates (form_id, email_template_name, email_status,
        view_mapping_type, view_mapping_view_id, email_event_trigger, include_on_edit_submission_page,
        subject, email_from, email_from_account_id, custom_from_name, custom_from_email, email_reply_to,
        email_reply_to_account_id, custom_reply_to_name, custom_reply_to_email, html_template, text_template)
        (SELECT form_id, email_template_name, email_status,
           view_mapping_type, view_mapping_view_id, email_event_trigger, include_on_edit_submission_page,
           subject, email_from, email_from_account_id, custom_from_name, custom_from_email, email_reply_to,
           email_reply_to_account_id, custom_reply_to_name, custom_reply_to_email, html_template, text_template
         FROM {$g_table_prefix}email_templates WHERE email_id = $create_email_from_email_id)
    ");
    $email_id = mysql_insert_id();

    foreach ($email_template_info["recipients"] as $recipient)
    {
      $recipient = ft_sanitize($recipient);

      $recipient_user_type    = $recipient["recipient_user_type"];
      $recipient_type         = $recipient["recipient_type"];
      $account_id             = !empty($recipient["account_id"]) ? $recipient["account_id"] : "NULL";
      $custom_recipient_name  = $recipient["custom_recipient_name"];
      $custom_recipient_email = $recipient["custom_recipient_email"];

      mysql_query("
        INSERT INTO {$g_table_prefix}email_template_recipients (email_template_id, recipient_user_type,
          recipient_type, account_id, custom_recipient_name, custom_recipient_email)
        VALUES ($email_id, '$recipient_user_type', '$recipient_type', $account_id, '$custom_recipient_name',
          '$custom_recipient_email')
          ") or die(mysql_error());
    }

    foreach ($email_template_info["edit_submission_page_view_ids"] as $view_id)
    {
      mysql_query("
        INSERT INTO {$g_table_prefix}email_template_edit_submission_views (email_id, view_id)
        VALUES ($email_id, $view_id)
          ");
    }
  }

  extract(ft_process_hooks("end", compact("email_id"), array()), EXTR_OVERWRITE);

  return $email_id;
}


/**
 * Returns a list of all email templates for a form. This returns everything from the email_templates
 * table, plus a "view_name" key->value pair from the Views table (if a View is specified).
 *
 * @param integer $form_id
 * @return array
 */
function ft_get_email_templates($form_id, $page_num = 1)
{
  global $g_table_prefix;

  $num_emails_per_page = $_SESSION["ft"]["settings"]["num_emails_per_page"];

  // determine the LIMIT clause
  $limit_clause = "";
  if (empty($page_num))
    $page_num = 1;
  $first_item = ($page_num - 1) * $num_emails_per_page;
  $limit_clause = "LIMIT $first_item, $num_emails_per_page";

  $result = mysql_query("
    SELECT et.*, v.view_name
    FROM 	 {$g_table_prefix}email_templates et
      LEFT JOIN {$g_table_prefix}views v ON v.view_id = et.view_mapping_view_id
    WHERE  et.form_id = $form_id
     $limit_clause
      ") or die(mysql_error());

  $count_result = mysql_query("
    SELECT count(*) as c
    FROM 	 {$g_table_prefix}email_templates
    WHERE  form_id = $form_id
      ");
  $count_hash = mysql_fetch_assoc($count_result);

  $email_info = array();
  while ($row = mysql_fetch_assoc($result))
  {
    $info = $row;
    $info["recipients"] = ft_get_email_template_recipients($row["email_id"]);
    $email_info[] = $info;
  }

  $return_hash["results"] = $email_info;
  $return_hash["num_results"]  = $count_hash["c"];

  extract(ft_process_hooks("end", compact("form_id", "return_hash"), array("return_hash")), EXTR_OVERWRITE);

  return $return_hash;
}


/**
 * Returns ALL email templates, sorted alphabetically. Currently just used for generating a list
 * of email templates from which to base a new one on.
 *
 * @param integer $form_id
 */
function ft_get_email_template_list($form_id)
{
  global $g_table_prefix;

  $result = mysql_query("
    SELECT *
    FROM 	 {$g_table_prefix}email_templates et
    WHERE  form_id = $form_id
    ORDER BY email_template_name
      ");

  $info = array();
  while ($row = mysql_fetch_assoc($result))
    $info[] = $row;

  extract(ft_process_hooks("end", compact("form_id", "info"), array("info")), EXTR_OVERWRITE);

  return $info;
}


/**
 * Returns all information about a particular email template.
 *
 * @param integer $email_id
 * @return array
 */
function ft_get_email_template($email_id)
{
  global $g_table_prefix;

  $email_template_query = mysql_query("
    SELECT *
    FROM 	 {$g_table_prefix}email_templates
    WHERE  email_id = $email_id
      ");

  $email_template = mysql_fetch_assoc($email_template_query);
  $email_template["recipients"] = ft_get_email_template_recipients($email_id);

  // get the list of Views that should show this email template on the edit submission page
  $email_view_query = mysql_query("SELECT view_id FROM {$g_table_prefix}email_template_edit_submission_views WHERE email_id = $email_id");
  $view_ids = array();
  while ($row = mysql_fetch_assoc($email_view_query))
    $view_ids[] = $row["view_id"];

  $email_template["edit_submission_page_view_ids"] = $view_ids;

  extract(ft_process_hooks("end", compact("email_template"), array("email_template")), EXTR_OVERWRITE);

  return $email_template;
}


/**
 * Sends a test email to administrators as they are building their email templates - or just to
 * confirm the emails are working properly.
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the test email form.
 */
function ft_send_test_email($info)
{
  global $g_table_prefix, $LANG;

  extract(ft_process_hooks("start", compact("info"), array("info")), EXTR_OVERWRITE);

  $form_id        = $_SESSION["ft"]["form_id"];
  $email_id       = $_SESSION["ft"]["email_id"];
  $submission_id  = (isset($info["submission_id"]) && !empty($info["submission_id"])) ? $info["submission_id"] : "";

  list ($success, $email_info) = ft_get_email_components($form_id, $submission_id, $email_id, true, $info);
  if (!$success)
    return array(false, $email_info);

  $recipient = $info["test_email_recipient"];

  // if Swift Mailer is enabled, send the emails with that
  $continue = true;
  if (ft_check_module_enabled("swift_mailer"))
  {
    $sm_settings = ft_get_module_settings("", "swift_mailer");

    if ($sm_settings["swiftmailer_enabled"] == "yes")
    {
      ft_include_module("swift_mailer");

      $email_info["cc"]  = array();
      $email_info["bcc"] = array();
      $email_info["to"]  = array();
      $email_info["to"][] = array("email" => $recipient);

      return swift_send_email($email_info);
      $continue = false;
    }
  }

  if (!$continue)
    return;

  // construct the email headers
  $eol = _ft_get_email_eol_char();

  $from = "";
  if (isset($email_info["from"]) && !empty($email_info["from"]))
  {
    $from = (is_array($email_info["from"])) ? join(", ", $email_info["from"]) : $email_info["from"];
    $from = htmlspecialchars_decode($from);
  }

  $reply_to = "";
  if (isset($email_info["reply_to"]) && !empty($email_info["reply_to"]))
  {
    $reply_to = (is_array($email_info["reply_to"])) ? join(", ", $email_info["reply_to"]) : $email_info["reply_to"];
    $reply_to = htmlspecialchars_decode($reply_to);
  }

  $cc = "";
  if (isset($email_info["cc"]) && !empty($email_info["cc"]))
  {
    $cc = (is_array($email_info["cc"])) ? join(", ", $email_info["cc"]) : $email_info["cc"];
    $cc = htmlspecialchars_decode($cc);
  }

  $bcc = "";
  if (isset($email_info["bcc"]) && !empty($email_info["bcc"]))
  {
    $bcc = (is_array($email_info["bcc"])) ? join(", ", $email_info["bcc"]) : $email_info["bcc"];
    $bcc = htmlspecialchars_decode($bcc);
  }

  $header_info = array(
    "eol" => $eol,
    "from"     => $from,
    "reply_to" => $reply_to,
    "cc"       => $cc,
    "bcc"      => $bcc
  );

  // construct the email headers [move to helper function]
  $headers = _ft_get_email_headers($header_info);


  // stores the content for either text or HTML emails (but not both!)
  $message = "";

  if (!empty($email_info["html_content"]) && !empty($email_info["text_content"]))
    $headers .= _ft_get_multipart_message($email_info["html_content"], $email_info["text_content"], $eol);
  else if (!empty($email_info["text_content"]))
  {
    $headers .= "Content-type: text/plain; charset=UTF-8";
    $message = $email_info["text_content"];
  }
  else if (!empty($email_info["html_content"]))
  {
    $headers .= "Content-type: text/html; charset=UTF-8";
    $message = $email_info["html_content"];
  }

  $subject = $email_info["subject"];

  if (!@mail($recipient, $subject, $message, $headers))
    return array(false, $LANG["notify_test_email_not_sent"]);
  else
    return array(true, $LANG["notify_your_email_sent"]);
}


/**
 * This handy function figures out the various components of an email: from, reply_to, to, cc, bcc,
 * subject, html_content and text_content - and returns them in a hash. This function is used when
 * actually sending the emails and for testing purposes.
 *
 * @param integer $form_id
 * @param mixed $submission_id for non-test emails, this is included. For testing, it may be blank.
 * @param integer $email_id
 * @return array
 */
function ft_get_email_components($form_id, $submission_id = "", $email_id, $is_test = false, $test_settings = array())
{
  global $g_table_prefix, $g_root_dir, $LANG, $g_default_theme;

  $email_template  = ft_get_email_template($email_id);


  // if this is a test, find out what information the administrator wants
  if ($is_test)
  {
    $test_email_format        = $test_settings["test_email_format"];
    $test_email_recipient     = $test_settings["test_email_recipient"];
    $test_email_data_source   = $test_settings["test_email_data_source"];
    $test_email_submission_id = $test_settings["test_email_submission_id"];

    // get the submission ID
    switch ($test_email_data_source)
    {
      case "random_submission":

        // if this email template has been mapped to a View ID, get the filters
        $where_clause = "";
        if (!empty($email_template["view_mapping_view_id"]))
        {
          $sql_clauses = ft_get_view_filter_sql($email_template["view_mapping_view_id"]);
          if (!empty($sql_clauses))
            $where_clause = "AND (" . join(" AND ", $sql_clauses) . ") ";
        }

        $result = mysql_query("
          SELECT submission_id
          FROM   {$g_table_prefix}form_$form_id
          $where_clause
          ORDER BY rand() LIMIT 1
            ");

        $row = mysql_fetch_row($result);
        $submission_id = $row[0];
        break;

      case "submission_id":
        $result = mysql_query("SELECT count(*) FROM {$g_table_prefix}form_$form_id WHERE submission_id=$test_email_submission_id");
        $row = mysql_fetch_row($result);
        if ($row[0] != 1)
          return array(false, $LANG["notify_submission_id_not_found"]);
        else
          $submission_id = $test_email_submission_id;
        break;
    }


    // determine what templates to display
    switch ($test_email_format)
    {
      case "both":
        $templates["html"] = $email_template["html_template"];
        $templates["text"] = $email_template["text_template"];
        break;
      case "text":
        $templates["text"] = $email_template["text_template"];
        break;
      case "html":
        $templates["html"] = $email_template["html_template"];
        break;
    }
  }

  // for non-test submissions, always grab both the HTML and text templates
  else
  {
    $templates["html"] = $email_template["html_template"];
    $templates["text"] = $email_template["text_template"];
  }


  // if the administrator limited the email content to fields in a particular View, pass those fields to the
  // template - NOT all of the form fields (which is the default)
  $fields_for_email_template = array();
  if (!empty($email_template["limit_email_content_to_fields_in_view"]))
  {
    $view_fields = ft_get_view_fields($email_template["limit_email_content_to_fields_in_view"]);

    // here, $view_fields just contains the info from the view_fields table. We need the info from the form_fields
    // table instead - since it contains presentation information likely to be needed in the email templates
    $fields_for_email_template = array();
    foreach ($view_fields as $view_field_info)
    {
      $field_id = $view_field_info["field_id"];
      $fields_for_email_template[] = ft_get_form_field($field_id);
    }
  }
  else
    $fields_for_email_template = ft_get_form_fields($form_id);


  // for file fields, add folder_url and folder_path attributes to the $fields_for_email_template. This provides
  // that information for patterns that use the Smarty Loop
  $updated_fields_for_email_template = array();
  foreach ($fields_for_email_template as $field_info)
  {
    if ($field_info["field_type"] == "file")
    {
      $field_id = $field_info["field_id"];
      $extended_field_info = ft_get_extended_field_settings($field_id);
      $field_info["folder_url"] = $extended_field_info["file_upload_url"];
      $field_info["folder_path"] = $extended_field_info["file_upload_dir"];
    }

    $updated_fields_for_email_template[] = $field_info;
  }

  $fields_for_email_template = $updated_fields_for_email_template;


  // retrieve the placeholders and their substitutes
  $common_placeholders = _ft_get_placeholder_hash($form_id, $submission_id);

  $admin_info = ft_get_admin_info();
  $form_info  = ft_get_form($form_id);
  $submission_info = ft_get_submission($form_id, $submission_id);

  $header_info = _ft_get_submission_email_headers($form_info, $submission_info);
  $user_recipient  = $header_info["recipient_line"];
  $user_first_name = $header_info["user_first_name"];
  $user_last_name  = $header_info["user_last_name"];
  $user_email      = $header_info["user_email"];

  $theme = $g_default_theme;

  $settings = ft_get_settings();
  $default_date_format     = $settings["default_date_format"];
  $default_timezone_offset = $settings["default_timezone_offset"];


  // add the "answer" key to $fields_for_email_template, found in the submission_info content
  $file_info = array();
  $updated_fields_for_email_template = array();
  foreach ($fields_for_email_template as $field_info)
  {
    foreach ($submission_info as $submission_field_info)
    {
      if ($submission_field_info["field_id"] == $field_info["field_id"])
      {
    if ($submission_field_info["field_type"] == "file" || $submission_field_info["field_type"] == "image")
      $file_info[$submission_field_info["field_name"]] = $submission_field_info["content"];

        switch ($submission_field_info["col_name"])
        {
          case "submission_date":
          case "last_modified_date":
            $field_info["answer"] = ft_get_date($default_timezone_offset, $submission_field_info["content"], $default_date_format);
            break;
          default:
            $field_info["answer"] = $submission_field_info["content"];
            break;
        }
        break;
      }
    }
    $updated_fields_for_email_template[] = $field_info;
  }
  $fields_for_email_template = $updated_fields_for_email_template;


  $return_info = array();
  $return_info["email_id"] = $email_id;
  $return_info["attachments"] = array();

  if (isset($templates["text"]) && !empty($templates["text"]))
  {
    $smarty = new Smarty();
    $smarty->template_dir = "$g_root_dir/global/smarty/";
    $smarty->compile_dir  = "$g_root_dir/themes/$theme/cache/";

    list($templates["text"], $attachments) = _ft_extract_email_attachment_info($templates["text"], $form_id, $file_info);
    foreach ($attachments as $attachment_info)
    {
      if (!in_array($attachment_info, $return_info["attachments"]))
        $return_info["attachments"][] = $attachment_info;
    }

    $smarty->assign("eval_str", $templates["text"]);
    while (list($key, $value) = each($common_placeholders))
      $smarty->assign($key, $value);
    reset($common_placeholders);

    $smarty->assign("LANG", $LANG);
    $smarty->assign("fields", $fields_for_email_template);
    $return_info["text_content"] = $smarty->fetch("eval.tpl");
  }

  if (isset($templates["html"]) && !empty($templates["html"]))
  {
    $smarty = new Smarty();
    $smarty->template_dir = "$g_root_dir/global/smarty/";
    $smarty->compile_dir  = "$g_root_dir/themes/$theme/cache/";

    list($templates["html"], $attachments) = _ft_extract_email_attachment_info($templates["html"], $form_id, $file_info);
    foreach ($attachments as $attachment_info)
    {
      if (!in_array($attachment_info, $return_info["attachments"]))
        $return_info["attachments"][] = $attachment_info;
    }

    $smarty->assign("eval_str", $templates["html"]);
    while (list($key, $value) = each($common_placeholders))
      $smarty->assign($key, $value);

    $smarty->assign("LANG", $LANG);
    $smarty->assign("fields", $fields_for_email_template);
    $return_info["html_content"] = $smarty->fetch("eval.tpl");
  }

  // compile the "to" / "from" / "reply-to" recipient list, based on this form submission. Virtually
  // everything is already stored in $email_templates["recipients"], but needs to be extracted.
  // The notable exception is the USER information: that has to be constructed separately
  $return_info["to"]  = array();
  $return_info["cc"]  = array();
  $return_info["bcc"] = array();

  foreach ($email_template["recipients"] as $recipient_info)
  {
    $recipient_type_key = $recipient_info["recipient_type"];
    if ($recipient_info["recipient_type"] == "")
      $recipient_type_key = "to";

    if ($recipient_info["recipient_user_type"] == "user")
    {
      $curr_recipient_info = array(
        "recipient_line" => $user_recipient,
        "name"           => "$user_first_name $user_last_name",
        "email"          => $user_email
          );
    }
    else
    {
      $curr_recipient_info = array(
        "recipient_line" => $recipient_info["final_recipient"],
        "name"           => $recipient_info["final_name"],
        "email"          => $recipient_info["final_email"]
          );
    }

    if (!empty($curr_recipient_info["email"]))
      $return_info[$recipient_type_key][] = $curr_recipient_info;
  }

  $return_info["from"] = array();
  switch ($email_template["email_from"])
  {
    case "admin":
      $return_info["from"] = array(
        "recipient_line" => "{$admin_info["first_name"]} {$admin_info["last_name"]} &lt;{$admin_info["email"]}&gt;",
        "name"           => "{$admin_info["first_name"]} {$admin_info["last_name"]}",
        "email"          => $admin_info["email"]
          );
      break;
    case "client":
      $client_info = ft_get_account_info($email_template["email_from_account_id"]);
      $return_info["from"] = array(
        "recipient_line" => "{$client_info["first_name"]} {$client_info["last_name"]} &lt;{$client_info["email"]}&gt;",
        "name"           => "{$client_info["first_name"]} {$client_info["last_name"]}",
        "email"          => $client_info["email"]
          );
      break;
    case "user":
      $return_info["from"] = array(
        "recipient_line" => $user_recipient,
        "name"           => "$user_first_name $user_last_name",
        "email"          => $user_email
          );
      break;
    case "custom":
      $return_info["from"] = array(
        "recipient_line" => "{$email_template["custom_from_name"]} &lt;{$email_template["custom_from_email"]}&gt;",
        "name"           => $email_template["custom_from_name"],
        "email"          => $email_template["custom_from_email"]
          );
      break;
  }

  $return_info["reply_to"] = array();
  switch ($email_template["email_reply_to"])
  {
    case "admin":
      $return_info["reply_to"] = array(
        "recipient_line" => "{$admin_info["first_name"]} {$admin_info["last_name"]} &lt;{$admin_info["email"]}&gt;",
        "name"           => "{$admin_info["first_name"]} {$admin_info["last_name"]}",
        "email"          => $admin_info["email"]
          );
      break;
    case "client":
      $client_info = ft_get_account_info($email_template["email_reply_to_account_id"]);
      $return_info["reply_to"] = array(
        "recipient_line" => "{$client_info["first_name"]} {$client_info["last_name"]} &lt;{$client_info["email"]}&gt;",
        "name"           => "{$client_info["first_name"]} {$client_info["last_name"]}",
        "email"          => $client_info["email"]
          );
      break;
    case "user":
      $return_info["reply_to"] = array(
        "recipient_line" => $user_recipient,
        "name"           => "$user_first_name $user_last_name",
        "email"          => $user_email
          );
      break;
    case "custom":
      $return_info["reply_to"] = array(
        "recipient_line" => "{$email_template["custom_reply_to_name"]} &lt;{$email_template["custom_reply_to_email"]}&gt;",
        "name"           => $email_template["custom_reply_to_name"],
        "email"          => $email_template["custom_reply_to_email"]
          );
      break;
  }

  $return_info["subject"] = ft_eval_smarty_string($email_template["subject"], $common_placeholders);

  return array(true, $return_info);
}


/**
 * This returns a hash of all email patterns, found in the /global/email_patterns/ folder, sorted
 * alphabetically by pattern name. An email pattern is just an example template used for
 * quickly populating the HTML and text email content in the the email templates. [I *would* have
 * called them "email templates", but that term is already being put to good use, and
 * "email template template" is just plain silly].
 *
 * @param integer $form_id
 */
function ft_get_email_patterns($form_id)
{
  global $g_root_dir;

  $pattern_folder = "$g_root_dir/global/emails/patterns";
  $email_template_patterns = parse_ini_file("$pattern_folder/patterns.ini", true);

  $placeholders = array();
  $placeholders["fields"] = ft_get_form_fields($form_id);

  // get the HTML email patterns
  $html_patterns = array();
  $count = 1;
  while (true)
  {
    if (!isset($email_template_patterns["html_patterns"]["pattern{$count}_name"]))
      break;

    $name     = ft_eval_smarty_string($email_template_patterns["html_patterns"]["pattern{$count}_name"]);
    $optgroup = ft_eval_smarty_string($email_template_patterns["html_patterns"]["pattern{$count}_optgroup"]);
    $filename = $email_template_patterns["html_patterns"]["pattern{$count}_file"];
    $content  = "";

    if (is_readable("$pattern_folder/$filename") && is_file("$pattern_folder/$filename"))
      $content = ft_eval_smarty_string(file_get_contents("$pattern_folder/$filename"), $placeholders);

    // if this has both a name and some email content, log it
    if (!empty($name) && !empty($content))
    {
      $html_patterns[] = array(
        "pattern_name" => $name,
        "optgroup"     => $optgroup,
        "content"      => $content
          );
    }
    $count++;
  }

  // get the text email patterns
  $text_patterns = array();
  $count = 1;
  while (true)
  {
    if (!isset($email_template_patterns["text_patterns"]["pattern{$count}_name"]))
      break;

    $name     = ft_eval_smarty_string($email_template_patterns["text_patterns"]["pattern{$count}_name"]);
    $optgroup = ft_eval_smarty_string($email_template_patterns["text_patterns"]["pattern{$count}_optgroup"]);
    $filename = $email_template_patterns["text_patterns"]["pattern{$count}_file"];
    $content  = "";

    if (is_readable("$pattern_folder/$filename") && is_file("$pattern_folder/$filename"))
      $content = ft_eval_smarty_string(file_get_contents("$pattern_folder/$filename"), $placeholders);

    // if this has both a name and some email content, log it
    if (!empty($name) && !empty($content))
    {
      $text_patterns[] = array(
        "pattern_name" => $name,
        "optgroup"     => $optgroup,
        "content"      => $content
          );
    }
    $count++;
  }

  extract(ft_process_hooks("end", compact("text_patterns", "html_patterns"), array("text_patterns", "html_patterns")), EXTR_OVERWRITE);

  return array("text_patterns" => $text_patterns, "html_patterns" => $html_patterns);
}


/**
 * Called by administrators; updates the email settings for a form: namely, which form fields
 * correspond to which user information (email, name). This information is used for building the
 * email templates.
 *
 * @param integer $form_id
 * @param array $infohash
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_form_email_settings($form_id, $infohash)
{
  global $g_table_prefix, $LANG;

  $user_email_field = isset($infohash["user_email_field"]) ? $infohash["user_email_field"] : "";
  $user_first_name_field = isset($infohash["user_first_name_field"]) ? $infohash["user_first_name_field"] : "";
  $user_last_name_field = isset($infohash["user_last_name_field"]) ? $infohash["user_last_name_field"] : "";

  $result = mysql_query("
    UPDATE {$g_table_prefix}forms
    SET    user_email_field = '$user_email_field',
           user_first_name_field = '$user_first_name_field',
           user_last_name_field = '$user_last_name_field'
    WHERE form_id = $form_id
      ");

  extract(ft_process_hooks("end", compact("form_id", "infohash"), array("form_id", "infohash")), EXTR_OVERWRITE);

  if ($result)
    return array(true, $LANG["notify_email_fields_updated"]);
  else
    return array(false, $LANG["notify_email_fields_not_updated"]);
}


/**
 * Updates all aspects of an email template.
 *
 * @param integer $email_id
 * @param array $info
 */
function ft_update_email_template($email_id, $info)
{
  global $g_table_prefix, $LANG;

  // neat bug. We need to trim out any trailing whitespace from the templates, otherwise they're
  // escaped for DB insertion & can't be trimmed out then
  $info["text_template"] = trim($info["text_template"]);
  $info["html_template"] = trim($info["html_template"]);
  $info = ft_sanitize($info);

  extract(ft_process_hooks("start", compact("email_id", "info"), array("info")), EXTR_OVERWRITE);

  // "Main" tab
  $email_template_name   = $info["email_template_name"];
  $email_status          = $info["email_status"];
  $view_mapping_type     = (isset($info["view_mapping_type"])) ? $info["view_mapping_type"] : "all";
  $view_id               = (isset($info["view_mapping_view_id"]) && !empty($info["view_mapping_view_id"])) ? $info["view_mapping_view_id"] : "NULL";
  $email_event_trigger   = (isset($info["email_event_trigger"]) && !empty($info["email_event_trigger"])) ? join(",", $info["email_event_trigger"]) : "";
  $include_on_edit_submission_page = (isset($info["include_on_edit_submission_page"])) ? $info["include_on_edit_submission_page"] : "no";
  $limit_email_content_to_fields_in_view = (isset($info["limit_email_content_to_fields_in_view"]) && !empty($info["limit_email_content_to_fields_in_view"])) ? $info["limit_email_content_to_fields_in_view"] : "NULL";

  $subject               = $info["subject"];
  $email_from            = $info["email_from"];
  $custom_from_name      = isset($info["custom_from_name"]) ? $info["custom_from_name"] : "";
  $custom_from_email     = isset($info["custom_from_email"]) ? $info["custom_from_email"] : "";
  $email_reply_to        = $info["email_reply_to"];
  $custom_reply_to_name  = isset($info["custom_reply_to_name"]) ? $info["custom_reply_to_name"] : "";
  $custom_reply_to_email = isset($info["custom_reply_to_email"]) ? $info["custom_reply_to_email"] : "";

  // if the From or Reply-to fields are a number, that means it's a client's user account ID
  $email_from_account_id = "";
  if (is_numeric($email_from))
  {
    $email_from_account_id = $email_from;
    $email_from = "client";
  }
  $email_reply_to_account_id = "";
  if (is_numeric($email_reply_to))
  {
    $email_reply_to_account_id = $email_reply_to;
    $email_reply_to = "client";
  }

  // "Email Content" tab
  $html_template = $info["html_template"];
  $text_template = $info["text_template"];

  mysql_query("
    UPDATE {$g_table_prefix}email_templates
    SET    email_template_name = '$email_template_name',
           email_status = '$email_status',
           view_mapping_type = '$view_mapping_type',
           view_mapping_view_id = $view_id,
           limit_email_content_to_fields_in_view = $limit_email_content_to_fields_in_view,
           email_event_trigger = '$email_event_trigger',
           include_on_edit_submission_page = '$include_on_edit_submission_page',
           subject = '$subject',
           email_from = '$email_from',
           email_from_account_id = '$email_from_account_id',
           custom_from_name = '$custom_from_name',
           custom_from_email = '$custom_from_email',
           email_reply_to = '$email_reply_to',
           email_reply_to_account_id = '$email_reply_to_account_id',
           custom_reply_to_name = '$custom_reply_to_name',
           custom_reply_to_email = '$custom_reply_to_email',
           html_template = '$html_template',
           text_template = '$text_template'
    WHERE  email_id = $email_id
      ") or die(mysql_error());

  // update the edit submission page views
  mysql_query("DELETE FROM {$g_table_prefix}email_template_edit_submission_views WHERE email_id = $email_id");
  $selected_edit_submission_views = isset($info["selected_edit_submission_views"]) ? $info["selected_edit_submission_views"] : array();
  foreach ($selected_edit_submission_views as $view_id)
    mysql_query("INSERT INTO {$g_table_prefix}email_template_edit_submission_views (email_id, view_id) VALUES ($email_id, $view_id)");


  // update the recipient list
  mysql_query("DELETE FROM {$g_table_prefix}email_template_recipients WHERE email_template_id = $email_id");
  $recipient_ids = $info["recipients"];

  foreach ($recipient_ids as $recipient_id)
  {
    $row = $recipient_id;

    // if there's no recipient user type (admin/user/client/custom), just ignore the row
    if (!isset($info["recipient_{$row}_user_type"]))
      continue;

    // "", "cc" or "bcc"
    $recipient_type = $info["recipient_{$row}_type"];

    switch ($info["recipient_{$row}_user_type"])
    {
      case "admin":
        mysql_query("
          INSERT INTO {$g_table_prefix}email_template_recipients (email_template_id, recipient_user_type, recipient_type)
          VALUES ($email_id, 'admin', '$recipient_type')
            ");
        break;

      case "user":
        mysql_query("
          INSERT INTO {$g_table_prefix}email_template_recipients (email_template_id, recipient_user_type, recipient_type)
          VALUES ($email_id, 'user', '$recipient_type')
            ");
        break;

      case "client":
        $account_id  = $info["recipient_{$row}_account_id"];
        mysql_query("
          INSERT INTO {$g_table_prefix}email_template_recipients
            (email_template_id, recipient_user_type, recipient_type, account_id)
          VALUES ($email_id, 'client', '$recipient_type', '$account_id')
            ");
        break;

      case "custom":
        $name  = isset($info["recipient_{$row}_name"]) ? $info["recipient_{$row}_name"] : "";
        $email = isset($info["recipient_{$row}_email"]) ? $info["recipient_{$row}_email"] : "";
        mysql_query("
          INSERT INTO {$g_table_prefix}email_template_recipients
            (email_template_id, recipient_user_type, recipient_type, custom_recipient_name, custom_recipient_email)
          VALUES ($email_id, 'custom', '$recipient_type', '$name', '$email')
            ");
        break;
    }
  }

  $success = true;
  $message = $LANG["notify_email_template_updated"];

  extract(ft_process_hooks("end", compact("email_id", "info"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Returns an array of hashes. Each hash contains the contents of the email_template_recipients table; i.e.
 * the raw information about a particular recipient. For convenience, this function also determines the
 * actual name and email of the recipients, returned in "final_name" and "final_email" keys. Also, it returns a
 * "final_recipient" containing the complete recipient string, like:
 *
 *   Tom Jones <babe@magnet.com>
 *
 * If the name doesn't exist, that key just returns the email address. ASSUMPTION: All clients and administrator
 * MUST have a first name, last name and email address.
 *
 * Returns results ordered by (a) recipient type (main, cc then bcc), then (b) recipient user type (admin, client,
 * user then custom)
 *
 * @param integer $email_id
 * @return array an array of hashes
 */
function ft_get_email_template_recipients($email_id)
{
  global $g_table_prefix, $LANG;

  // now add any recipients for this email template
  $recipient_query = mysql_query("
    SELECT etr.*, a.first_name, a.last_name, a.email
    FROM   {$g_table_prefix}email_template_recipients etr
      LEFT JOIN {$g_table_prefix}accounts a ON a.account_id = etr.account_id
    WHERE  etr.email_template_id = $email_id
    ORDER BY etr.recipient_type, etr.recipient_user_type
      ");

  $recipients = array();
  while ($recipient_info = mysql_fetch_assoc($recipient_query))
  {
    // construct and append the extra keys (final_name, final_email and final_recipient)
    switch ($recipient_info["recipient_user_type"])
    {
      case "admin":
        $admin_info = ft_get_admin_info();
        $recipient_info["final_name"] = "{$admin_info["first_name"]} {$admin_info["last_name"]}";
        $recipient_info["final_email"] = $admin_info["email"];
        $recipient_info["final_recipient"] = "{$recipient_info["final_name"]} &lt;{$recipient_info["final_email"]}&gt;";
        break;
      case "client":
        $client_info = ft_get_account_info($recipient_info["account_id"]);
        $recipient_info["final_name"] = "{$client_info["first_name"]} {$client_info["last_name"]}";
        $recipient_info["final_email"] = $client_info["email"];
        $recipient_info["final_recipient"] = "{$recipient_info["final_name"]} &lt;{$recipient_info["final_email"]}&gt;";
        break;
      case "user":
        $recipient_info["final_recipient"] = $LANG["phrase_from_user_form_submission_b"];
        break;
      case "custom":
        $recipient_info["final_name"] = $recipient_info["custom_recipient_name"];
        $recipient_info["final_email"] = $recipient_info["custom_recipient_email"];

        if (!empty($recipient_info["final_name"]))
          $recipient_info["final_recipient"] = "{$recipient_info["final_name"]} &lt;{$recipient_info["final_email"]}&gt;";
        else
          $recipient_info["final_recipient"] = $recipient_info["final_email"];
        break;
    }

    $recipients[] = $recipient_info;
  }

  return $recipients;
}


/**
 * This is used on the Edit Submission pages to show the list of email templates which can be emailed
 * for the submission-View.
 *
 * @param integer $form_id
 * @param integer $view_id
 * @return array
 */
function ft_get_edit_submission_email_templates($form_id, $view_id)
{
  global $g_table_prefix;

  // a bit complicated, but all this query does is return those templates that are specified to be
  // displayed for ALL views, or for those that have been mapped to this specific View
  $query = mysql_query("
    SELECT et.email_id
    FROM   {$g_table_prefix}email_templates et
    WHERE  et.email_status = 'enabled' AND
           et.form_id = $form_id AND
           (et.include_on_edit_submission_page = 'all_views' OR
             (et.include_on_edit_submission_page = 'specific_views'
               AND EXISTS
                 (SELECT *
                  FROM   {$g_table_prefix}email_template_edit_submission_views etesv
                  WHERE  et.email_id = etesv.email_id AND
                         etesv.view_id = $view_id)
             )
           )
      ") or die(mysql_error());


  $email_info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $email_id = $row["email_id"];
    $email_info[] = ft_get_email_template($email_id);
  }

  extract(ft_process_hooks("end", compact("view_id", "email_info"), array("email_info")), EXTR_OVERWRITE);

  return $email_info;
}


/**
 * The main email function for Form Tools. This gets executed on particular events in a submissions life: when
 * first placed ("on_submission"), when edited ("on_edit") and when deleted ("on_delete"). This function does
 * the horrible job of figuring out precisely what the administrator wants, and sends the email(s) to the
 * appropriate recipients.
 *
 * @param string $event "on_submission", "on_edit", "on_delete"
 * @param integer $form_id
 * @param integer $submission_id
 */
function ft_send_emails($event, $form_id, $submission_id)
{
  $all_form_email_templates = ft_get_email_template_list($form_id);

  // filter out those templates that aren't for this event
  $email_templates = array();
  foreach ($all_form_email_templates as $template_info)
  {
    $events = split(",", $template_info["email_event_trigger"]);

    if (!in_array($event, $events))
      continue;

    if ($template_info["email_status"] == "disabled")
      continue;

    // if this email template has been mapped to a particular View, make sure the View ID is valid & that the
    // submission can be seen in the View
    if ($template_info["view_mapping_type"] == "specific")
    {
      $view_id = $template_info["view_mapping_view_id"];

      // if there's no View ID specified, there's been a problem with the input - or a View has been deleted
      if (empty($view_id))
        continue;

      if (!ft_check_view_contains_submission($form_id, $view_id, $submission_id))
        continue;
    }

    $email_templates[] = $template_info;
  }

  // now process each template individually
  foreach ($email_templates as $template_info)
  {
    $email_id = $template_info["email_id"];
    ft_process_email_template($form_id, $submission_id, $email_id);
  }
}


/**
 * This constructs and sends the email from an individual email template for a single form
 * submission.
 *
 * @param integer $form_id
 * @param integer $submission_id
 * @param integer $email_id
 */
function ft_process_email_template($form_id, $submission_id, $email_id)
{
  list($success, $email_components) =	ft_get_email_components($form_id, $submission_id, $email_id);

  if (!$success)
    return false;

  extract(ft_process_hooks("start", compact("form_id", "submission_id", "email_id", "email_components"),
    array("email_components")), EXTR_OVERWRITE);

  // if Swift Mailer is enabled, send the emails with that
  $continue = true;
  if (ft_check_module_enabled("swift_mailer"))
  {
    $sm_settings = ft_get_module_settings("", "swift_mailer");

    if (isset($sm_settings["swiftmailer_enabled"]) && $sm_settings["swiftmailer_enabled"] == "yes")
    {
      ft_include_module("swift_mailer");
      list($success, $message) = swift_send_email($email_components);
      $continue = false;
    }
  }

  if (!$continue)
    return $success;

  $eol = _ft_get_email_eol_char();

  $recipient_list = array();
  foreach ($email_components["to"] as $to_info)
    $recipient_list[] = $to_info["recipient_line"];
  $to = join(", ", $recipient_list);
  $to = htmlspecialchars_decode($to);

  $headers = "MIME-Version: 1.0$eol";

  if (!empty($email_components["from"]))
  {
    $from = htmlspecialchars_decode($email_components["from"]["recipient_line"]);
    $headers .= "From: {$from}$eol";
  }
  if (!empty($email_components["reply_to"]))
  {
    $reply_to = htmlspecialchars_decode($email_components["reply_to"]["recipient_line"]);
    $headers .= "Reply-to: {$reply_to}$eol";
  }
  if (!empty($email_components["cc"]))
  {
    $cc_list = array();
    foreach ($email_components["cc"] as $cc_info)
      $cc_list[] = $cc_info["recipient_line"];
    $cc = join(", ", $cc_list);
    $cc = htmlspecialchars_decode($cc);
    $headers .= "Cc: {$cc}$eol";
  }
  if (!empty($email_components["bcc"]))
  {
    $bcc_list = array();
    foreach ($email_components["bcc"] as $bcc_info)
      $bcc_list[] = $bcc_info["recipient_line"];
    $bcc = join(", ", $bcc_list);
    $bcc = htmlspecialchars_decode($bcc);
    $headers .= "Bcc: {$bcc}$eol";
  }

  $message = "";
  $html_content = isset($email_components["html_content"]) ? $email_components["html_content"] : "";
  $text_content = isset($email_components["text_content"]) ? $email_components["text_content"] : "";
  $html_content = trim($html_content);
  $text_content = trim($text_content);

  // if there's no TO line or there's no email content for either types, we can't send the email
  if (empty($to) || (empty($html_content) && empty($text_content)))
    return false;

  if (!empty($html_content) && !empty($text_content))
    $headers .= _ft_get_multipart_message($html_content, $text_content, $eol);
  else if (!empty($html_content))
  {
    $message = $html_content;
    $headers .= "Content-type: text/html; charset=UTF-8";
  }
  else if (!empty($text_content))
  {
    $message = $text_content;
    $headers .= "Content-type: text/plain; charset=UTF-8";
  }

  $subject = $email_components["subject"];

  // send the email
  return @mail("$to", $subject, $message, $headers);
}


//------------------------------------ helper functions -------------------------------------------


/**
 * Pieces together the main email content from the text and HTML content, generated separately by
 * {@link http://www.formtools.org/developerdoc/1-4-6/Emails/_code---emails.php.html#function_parse_template
 * _parse_template}.
 *
 * @param string $HTML_content The HTML section of the email.
 * @param string $text_content The text section of the email.
 * @param string $eol_content The end-of-line character for this system.
 */
function _ft_get_multipart_message($HTML_content, $text_content, $eol)
{
  $boundary = md5(time());

  $content = "Content-Type: multipart/alternative; boundary = $boundary$eol"
           . "\n--$boundary$eol"
           . "Content-type: text/plain; charset=UTF-8$eol"
           . $text_content
           . "\n--$boundary$eol"
           . "Content-type: text/html; charset=UTF-8$eol"
           . $HTML_content;

  return $content;
}


/**
 * Generates the email content for a particular template - either HTML or text.
 *
 * TODO: Presumably this function should format the dates according to the own user's timezone offset?
 *
 * @param integer $form_id The unique form ID
 * @param integer $submission_id The unique submission ID
 * @param array $client_info a hash of information about the user to whom this email is being sent
 * @return array a hash of placeholders and their replacement values (e.g. $arr["FORMURL"] => 17)
 */
function _ft_get_placeholder_hash($form_id, $submission_id, $client_info = "")
{
  global $g_root_url;

  $placeholders = array();

  $settings        = ft_get_settings();
  $form_info       = ft_get_form($form_id);
  $submission_info = ft_get_submission($form_id, $submission_id);
  $admin_info      = ft_get_admin_info();

  // now loop through the info stored for this particular submission and for this particular field,
  // add the custom submission responses to the placeholder hash
  foreach ($submission_info as $field)
  {
    $field_id   = $field["field_id"];
    $field_name = $field["field_name"];
    $field_type = $field["field_type"];

    if ($field_type != "system")
      $placeholders["QUESTION_$field_name"] = $field["field_title"];

    if ($field_type == "file")
    {
      $extended_settings = ft_get_extended_field_settings($field_id, "core");
      $placeholders["FILENAME_$field_name"] = $field["content"];
      $placeholders["FILEURL_$field_name"]  = "{$extended_settings["file_upload_url"]}/{$field["content"]}";
    }
    else
    {
      if ($field_type != "system")
        $placeholders["ANSWER_$field_name"] = $field["content"];
    }

    if ($field['col_name'] == "submission_date")
      $placeholders["SUBMISSIONDATE"] = ft_get_date($settings['default_timezone_offset'], $field["content"], $settings["default_date_format"]);

    if ($field['col_name'] == "last_modified_date")
      $placeholders["LASTMODIFIEDDATE"] = ft_get_date($settings['default_timezone_offset'], $field["content"], $settings['default_date_format']);

    if ($field['col_name'] == "ip_address")
      $placeholders["IPADDRESS"] = $field["content"];
  }

  $placeholders["ADMINEMAIL"]   = $admin_info["email"];
  $placeholders["FORMNAME"]     = $form_info["form_name"];
  $placeholders["FORMURL"]      = $form_info["form_url"];
  $placeholders["SUBMISSIONID"] = $submission_id;
  $placeholders["LOGINURL"]     = $g_root_url . "/index.php";

  if (!empty($client_info))
  {
    $placeholders["EMAIL"]       = $client_info["email"];
    $placeholders["FIRSTNAME"]   = $client_info["first_name"];
    $placeholders["LASTNAME"]    = $client_info["last_name"];
    $placeholders["COMPANYNAME"] = $client_info["company_name"];
  }

  extract(ft_process_hooks("end", compact("placeholders"), array("placeholders")), EXTR_OVERWRITE);

  return $placeholders;
}


/**
 * Helper function to return the newline character for emails, appropriate for this user's system.
 *
 * @return string A string of the appropriate end-of-line character.
 */
function _ft_get_email_eol_char()
{
  $eol = "\n";
  if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN'))
    $eol = "\r\n";
  else if (strtoupper(substr(PHP_OS, 0, 3) == 'MAC'))
    $eol = "\r";

  return $eol;
}


/**
 * Determines the first name, last name and email address used the email headers based on the
 * form and submission info.
 *
 * @param array $form_info A hash containing all the form information.
 * @param array $submission_info A hash containing all the submission information.
 * @return array string the email header
 */
function _ft_get_submission_email_headers($form_info, $submission_info)
{
  // retrieve the user's name and email address from the form submission
  $user_first_name_field = $form_info["user_first_name_field"];
  $user_last_name_field  = $form_info["user_last_name_field"];
  $user_email_field      = $form_info["user_email_field"];

  $submission_first_name = "";
  $submission_last_name  = "";
  $submission_email      = "";

  foreach ($submission_info as $row)
  {
    if (!empty($user_first_name_field))
    {
      if ($row["col_name"] == $user_first_name_field)
        $submission_first_name = trim($row['content']);
    }
    if (!empty($user_last_name_field))
    {
      if ($row['col_name'] == $user_last_name_field)
        $submission_last_name = trim($row['content']);
    }
    // email
    if ($row['col_name'] == $user_email_field)
      $submission_email = trim($row['content']);
  }

  // now build the header string
  $name = array();
  if (!empty($submission_first_name))
    $name[] = $submission_first_name;
  if (!empty($submission_last_name))
    $name[] = $submission_last_name;

  $recipient_line = join(" ", $name);

  if (empty($recipient_line) && !empty($submission_email))
    $recipient_line = $submission_email;
  else if (!empty($submission_email))
    $recipient_line .= " &lt;$submission_email&gt;";

  // return EVERYTHING
  return array("recipient_line" => $recipient_line,
               "user_first_name" => $submission_first_name,
               "user_last_name" => $submission_last_name,
               "user_email" => $submission_email);
}


/**
 * Returns the email headers, constructed from the contents of the $info parameter. Note: this just
 * handles the From, Reply-to, cc and bcc headers: the encoding type (multipart, etc) is handled elsewhere.
 *
 * @param array $info a hash of values:
 *                    eol - the end of line character (required)
 *                    from, reply_to, cc, bcc
 */
function _ft_get_email_headers($info)
{
  $eol = $info["eol"];

  $headers = "MIME-Version: 1.0";

  if (isset($info["from"]) && !empty($info["from"]))
    $headers .= "{$eol}From: {$info["from"]}";
  if (isset($info["reply_to"]) && !empty($info["reply_to"]))
    $headers .= "{$eol}Reply-to: {$info["reply_to"]}";
  if (isset($info["cc"]) && !empty($info["cc"]))
    $headers .= "{$eol}Cc: {$info["cc"]}";
  if (isset($info["bcc"]) && !empty($info["bcc"]))
    $headers .= "{$eol}Bcc: {$info["bcc"]}";

  $headers .= $eol;

  return $headers;
}


/**
 * Completely deletes an email template from the system.
 *
 * @param integer $email_id
 * @return array [0] T/F
 *               [1] success / error message
 */
function ft_delete_email_template($email_id)
{
  global $LANG, $g_table_prefix;

  if (empty($email_id) || !is_numeric($email_id))
    return array(false, $LANG["validation_invalid_email_id"]);

  mysql_query("
    DELETE FROM {$g_table_prefix}email_templates
    WHERE email_id = $email_id
      ");

  mysql_query("
    DELETE FROM {$g_table_prefix}email_template_recipients
    WHERE email_template_id = $email_id
      ");

  return array(true, $LANG["notify_email_template_deleted"]);
}


/**
 * This function is tightly coupled with ft_get_email_components. I know, I know. It examines the content
 * of an email template and detects any field and file attachments. Field attachments are files that have
 * been uploaded through a form field; file attachments are just files on the server that want to be sent
 * out. It then returns the updated email template (i.e. minus the attachment string) and information about
 * the attachment (file name, location, mimetype) for use by the emailing function (only Swift Mailer module
 * at this time).
 *
 * @param string $template_str the email template (HTML or text)
 * @param integer $form_id
 * @param array $file_info information about all files
 */
function _ft_extract_email_attachment_info($template_str, $form_id, $file_info)
{
  global $g_root_dir;

  // if there are any fields marked as attachments, store them and remove the attachment string
  $field_attachments_regexp = '/\{\$attachment\s+field=("|\')(.+)("|\')\}/';
  $file_attachments_regexp  = '/\{\$attachment\s+file=("|\')(.+)("|\')\}/';

  $attachment_info = array();
  if (preg_match_all($field_attachments_regexp, $template_str, $matches))
  {
    foreach ($matches[2] as $field_name)
    {
      $field_id = ft_get_form_field_id_by_field_name($field_name, $form_id);
      if (!empty($field_name) && array_key_exists($field_name, $file_info))
      {
        $field_settings = ft_get_extended_field_settings($field_id, "core", "file_upload_dir");
        $file_upload_dir = $field_settings["file_upload_dir"];
        $file_and_path = "$file_upload_dir/{$file_info[$field_name]}";

        if (is_file($file_and_path))
        {
          $info = array(
            "field_name" => $field_name,
            "file_and_path" => $file_and_path,
            "filename" => $file_info[$field_name],
            "mimetype" => mime_content_type($file_and_path)
          );
          $attachment_info[] = $info;
        }
      }
    }

    $template_str = preg_replace($field_attachments_regexp, "", $template_str);
  }

  if (preg_match_all($file_attachments_regexp, $template_str, $matches))
  {
    foreach ($matches[2] as $file_and_relative_path)
    {
      if (is_file("$g_root_dir/$file_and_relative_path"))
      {
        $pathinfo = pathinfo($file_and_relative_path);
        $file_name = $pathinfo["basename"];

        $info = array(
          "file_and_path" => "$g_root_dir/$file_and_relative_path",
          "filename" => $file_name
        );
        $attachment_info[] = $info;
      }
    }
    $template_str = preg_replace($file_attachments_regexp, "", $template_str);
  }

  return array($template_str, $attachment_info);
}