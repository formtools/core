<?php

/**
 * This file defines all functions related to emails sent by Form Tools.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
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

    // WISHLIST: to have a generic "copy_table_row" function...
    $query = mysql_query("
      INSERT INTO {$g_table_prefix}email_templates (form_id, email_template_name, email_status,
        view_mapping_type, limit_email_content_to_fields_in_view, email_event_trigger,
        include_on_edit_submission_page, subject, email_from, email_from_account_id, custom_from_name,
        custom_from_email, email_reply_to, email_reply_to_account_id, custom_reply_to_name, custom_reply_to_email,
        html_template, text_template)
        (SELECT form_id, email_template_name, email_status,
           view_mapping_type, limit_email_content_to_fields_in_view, email_event_trigger,
           include_on_edit_submission_page, subject, email_from, email_from_account_id, custom_from_name,
           custom_from_email, email_reply_to, email_reply_to_account_id, custom_reply_to_name, custom_reply_to_email,
           html_template, text_template
         FROM {$g_table_prefix}email_templates WHERE email_id = $create_email_from_email_id)
    ");
    $email_id = mysql_insert_id();

    foreach ($email_template_info["recipients"] as $recipient)
    {
      $recipient = ft_sanitize($recipient);

      $recipient_user_type    = $recipient["recipient_user_type"];
      $recipient_type         = $recipient["recipient_type"];
      $account_id             = !empty($recipient["account_id"]) ? $recipient["account_id"] : "NULL";
      $form_email_id          = !empty($recipient["form_email_id"]) ? $recipient["form_email_id"] : "NULL";
      $custom_recipient_name  = $recipient["custom_recipient_name"];
      $custom_recipient_email = $recipient["custom_recipient_email"];

      mysql_query("
        INSERT INTO {$g_table_prefix}email_template_recipients (email_template_id, recipient_user_type,
          recipient_type, account_id, form_email_id, custom_recipient_name, custom_recipient_email)
        VALUES ($email_id, '$recipient_user_type', '$recipient_type', $account_id, $form_email_id,
        '$custom_recipient_name', '$custom_recipient_email')
          ") or die(mysql_error());
    }

    foreach ($email_template_info["edit_submission_page_view_ids"] as $view_id)
    {
      mysql_query("
        INSERT INTO {$g_table_prefix}email_template_edit_submission_views (email_id, view_id)
        VALUES ($email_id, $view_id)
          ");
    }

    foreach ($email_template_info["when_sent_view_ids"] as $view_id)
    {
      mysql_query("
        INSERT INTO {$g_table_prefix}email_template_when_sent_views (email_id, view_id)
        VALUES ($email_id, $view_id)
          ");
    }
  }

  extract(ft_process_hook_calls("end", compact("email_id"), array()), EXTR_OVERWRITE);

  return $email_id;
}


/**
 * Returns a list of all email templates for a form, in no particular order.
 *
 * @param integer $form_id
 * @return array
 */
function ft_get_email_templates($form_id, $page_num = 1)
{
  global $g_table_prefix;

  $num_emails_per_page = isset($_SESSION["ft"]["settings"]["num_emails_per_page"]) ? $_SESSION["ft"]["settings"]["num_emails_per_page"] : 10;

  // determine the LIMIT clause
  $limit_clause = "";
  if (empty($page_num))
  {
    $page_num = 1;
  }

  $first_item = ($page_num - 1) * $num_emails_per_page;
  $limit_clause = "LIMIT $first_item, $num_emails_per_page";

  $result = mysql_query("
    SELECT *
    FROM 	 {$g_table_prefix}email_templates
    WHERE  form_id = $form_id
    $limit_clause
  ");

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
    $info["recipients"] = ft_get_email_template_recipients($form_id, $row["email_id"]);
    $email_info[] = $info;
  }

  $return_hash["results"] = $email_info;
  $return_hash["num_results"]  = $count_hash["c"];

  extract(ft_process_hook_calls("end", compact("form_id", "return_hash"), array("return_hash")), EXTR_OVERWRITE);

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
    FROM   {$g_table_prefix}email_templates
    WHERE  form_id = $form_id
    ORDER BY email_template_name
      ");

  $info = array();
  while ($row = mysql_fetch_assoc($result))
	{
	  $email_id = $row["email_id"];
	  $when_sent_view_ids = array();
		$query = mysql_query("SELECT view_id FROM {$g_table_prefix}email_template_when_sent_views WHERE email_id = $email_id");
		while ($row2 = mysql_fetch_assoc($query))
		{
		  $when_sent_view_ids[] = $row2["view_id"];
		}
		$row["when_sent_view_ids"] = $when_sent_view_ids;
    $info[] = $row;
  }

  extract(ft_process_hook_calls("end", compact("form_id", "info"), array("info")), EXTR_OVERWRITE);

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
  $form_id = $email_template["form_id"];
  $email_template["recipients"] = ft_get_email_template_recipients($form_id, $email_id);

  // get the list of Views that should show this email template on the edit submission page
  $email_view_query = mysql_query("SELECT view_id FROM {$g_table_prefix}email_template_edit_submission_views WHERE email_id = $email_id");
  $view_ids = array();
  while ($row = mysql_fetch_assoc($email_view_query))
  {
    $view_ids[] = $row["view_id"];
  }
  $email_template["edit_submission_page_view_ids"] = $view_ids;

  // get the list of Views for which this email template is assigned to be sent
  $email_view_query = mysql_query("SELECT view_id FROM {$g_table_prefix}email_template_when_sent_views WHERE email_id = $email_id");
  $view_ids = array();
  while ($row = mysql_fetch_assoc($email_view_query))
  {
    $view_ids[] = $row["view_id"];
  }
  $email_template["when_sent_view_ids"] = $view_ids;

  extract(ft_process_hook_calls("end", compact("email_template"), array("email_template")), EXTR_OVERWRITE);

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

  extract(ft_process_hook_calls("start", compact("info"), array("info")), EXTR_OVERWRITE);

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

      // we deliberately ignore anything other than the specified recipient
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

  // as with Swift Mailer, we deliberately ignore anything other than the specified recipient
  $header_info = array(
    "eol"      => $eol,
    "from"     => $from,
    "reply_to" => $reply_to,
    "cc"       => "",
    "bcc"      => ""
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
 * This handy function figures out the various components of an email and returns them in a hash:
 *      from, reply_to, to, cc, bcc, subject, html_content and text_content
 *
 * This is used both when sending the emails but also for testing. This should be the only place that
 * email content is actually constructed. All other email functions should be using it, regardless of
 * what mechanism actually sends the email.
 *
 * @param integer $form_id
 * @param mixed $submission_id for non-test emails, this is included. For testing, it may be blank.
 * @param integer $email_id
 * @return array
 */
function ft_get_email_components($form_id, $submission_id = "", $email_id, $is_test = false, $test_settings = array())
{
  global $g_table_prefix, $g_root_dir, $LANG, $g_default_theme;

  $email_template = ft_get_email_template($email_id);

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
      $fields_for_email_template[] = ft_get_form_field($view_field_info["field_id"], array("include_field_type_info" => true));
  }
  else
    $fields_for_email_template = ft_get_form_fields($form_id, array("include_field_type_info" => true));


  // this returns a hash with three keys: html_content, text_content and submission_id
  $templates = _ft_get_email_template_content($form_id, $submission_id, $email_template, $is_test, $test_settings);
  $submission_id = $templates["submission_id"];

  // unfortunately we need this, even though it was just called in _ft_get_email_template_content()
  $submission_info = ft_get_submission($form_id, $submission_id);

  // retrieve the placeholders and their substitutes
  $submission_placeholders = ft_get_submission_placeholders($form_id, $submission_id);
  $admin_info = ft_get_admin_info();

  $file_info = array();
  $updated_fields_for_email_template = array();
  foreach ($fields_for_email_template as $field_info)
  {
    if ($field_info["is_file_field"] == "yes")
    {
      $field_id = $field_info["field_id"];
      $field_settings = ft_get_field_settings($field_id);
      $field_info["folder_url"]  = $field_settings["folder_url"];
      $field_info["folder_path"] = $field_settings["folder_path"];
      $filename = $field_info["field_name"];
      $field_info["answer"] = $submission_placeholders["FILENAME_{$filename}"];
    }
    $updated_fields_for_email_template[] = $field_info;
  }

  $fields_for_email_template = $updated_fields_for_email_template;

  $updated_fields_for_email_template = array();
  foreach ($fields_for_email_template as $field_info)
  {
    while (list($placeholder, $value) = each($submission_placeholders))
    {
      if ($placeholder != "ANSWER_{$field_info["field_name"]}")
        continue;

      $field_info["answer"] = $value;
      break;
    }
    reset($submission_placeholders);
    $updated_fields_for_email_template[] = $field_info;
  }
  $fields_for_email_template = $updated_fields_for_email_template;


  $return_info = array();
  $return_info["email_id"] = $email_id;
  $return_info["attachments"] = array();

  $smarty = new Smarty();
  $smarty->template_dir = "$g_root_dir/global/smarty/";
  $smarty->compile_dir  = "$g_root_dir/themes/$g_default_theme/cache/";
  $smarty->assign("LANG", $LANG);
  $smarty->assign("fields", $fields_for_email_template);

  if (!empty($templates["text"]))
  {
    list($templates["text"], $attachments) = _ft_extract_email_attachment_info($templates["text"], $form_id, $submission_placeholders);
    foreach ($attachments as $attachment_info)
    {
      if (!in_array($attachment_info, $return_info["attachments"]))
        $return_info["attachments"][] = $attachment_info;
    }

    $smarty->assign("eval_str", $templates["text"]);
    while (list($key, $value) = each($submission_placeholders))
      $smarty->assign($key, $value);
    reset($submission_placeholders);

    $return_info["text_content"] = $smarty->fetch("eval.tpl");
  }

  if (!empty($templates["html"]))
  {
    list($templates["html"], $attachments) = _ft_extract_email_attachment_info($templates["html"], $form_id, $submission_placeholders);
    foreach ($attachments as $attachment_info)
    {
      if (!in_array($attachment_info, $return_info["attachments"]))
        $return_info["attachments"][] = $attachment_info;
    }

    $smarty->assign("eval_str", $templates["html"]);
    while (list($key, $value) = each($submission_placeholders))
    {
      // convert any newlines chars to page breaks for any answer fields. Hmm...
      if (strpos($key, "ANSWER_") === 0)
        $value = nl2br($value);

      $smarty->assign($key, $value);
    }
    $return_info["html_content"] = $smarty->fetch("eval.tpl");
  }


  // compile the "to" / "from" / "reply-to" recipient list, based on this form submission. Virtually
  // everything is already stored in $email_template["recipients"], but needs to be extracted.
  // The notable exception is the FORM EMAIL FIELD information: that has to be constructed separately
  $return_info["to"]  = array();
  $return_info["cc"]  = array();
  $return_info["bcc"] = array();

  foreach ($email_template["recipients"] as $recipient_info)
  {
    $recipient_type_key = $recipient_info["recipient_type"];
    if ($recipient_info["recipient_type"] == "" || $recipient_info["recipient_type"] == "main")
      $recipient_type_key = "to";

    if ($recipient_info["recipient_user_type"] == "form_email_field")
    {
      $header_info = _ft_get_form_email_field_headers($recipient_info["form_email_id"], $submission_info);
      $user_recipient  = $header_info["recipient_line"];
      $user_first_name = $header_info["first_name"];
      $user_last_name  = $header_info["last_name"];
      $user_email      = $header_info["email"];

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

    case "form_email_field":
      $header_info = _ft_get_form_email_field_headers($email_template["email_from_form_email_id"], $submission_info);
      $user_recipient  = $header_info["recipient_line"];
      $user_first_name = $header_info["first_name"];
      $user_last_name  = $header_info["last_name"];
      $user_email      = $header_info["email"];
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

    case "form_email_field":
      $form_email_id = $email_template["email_reply_to_form_email_id"];
      $header_info = _ft_get_form_email_field_headers($form_email_id, $submission_info);
      $user_recipient  = $header_info["recipient_line"];
      $user_first_name = $header_info["first_name"];
      $user_last_name  = $header_info["last_name"];
      $user_email      = $header_info["email"];
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

  $return_info["subject"] = ft_eval_smarty_string($email_template["subject"], $submission_placeholders);

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
  $curr_folder = dirname(__FILE__);
  $pattern_folder = realpath("$curr_folder/../emails/patterns");
  $email_template_patterns = parse_ini_file("$pattern_folder/patterns.ini", true);

  $placeholders = array();
  $placeholders["fields"] = ft_get_form_fields($form_id, array("include_field_type_info" => true));

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

  extract(ft_process_hook_calls("end", compact("text_patterns", "html_patterns"), array("text_patterns", "html_patterns")), EXTR_OVERWRITE);

  return array("text_patterns" => $text_patterns, "html_patterns" => $html_patterns);
}


/**
 * Returns all email field info that have been registered for this form.
 *
 * @param integer $form_id
 */
function ft_get_email_fields($form_id)
{
  global $g_table_prefix, $LANG;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}form_email_fields
    WHERE  form_id = $form_id
      ");

  $info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    // also retrieve the display names for each of the fields
    $row["email_field_label"]      = ft_get_field_title_by_field_id($row["email_field_id"]);
    $row["first_name_field_label"] = ft_get_field_title_by_field_id($row["first_name_field_id"]);
    $row["last_name_field_label"]  = ft_get_field_title_by_field_id($row["last_name_field_id"]);
    $info[] = $row;
  }

  return $info;
}


/**
 * Called by administrators; this logs the email settings for a form: namely, which form fields
 * correspond to which user information (email, name). This information is used for building the
 * email templates.
 *
 * @param integer $form_id
 * @param array $infohash
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_set_field_as_email_field($form_id, $infohash)
{
  global $g_table_prefix, $LANG;

  $email_field_id      = isset($infohash["email_field_id"]) ? $infohash["email_field_id"] : "";
  $first_name_field_id = isset($infohash["first_name_field_id"]) ? $infohash["first_name_field_id"] : "";
  $last_name_field_id  = isset($infohash["last_name_field_id"]) ? $infohash["last_name_field_id"] : "";

  $result = mysql_query("
    INSERT INTO {$g_table_prefix}form_email_fields (form_id, email_field_id, first_name_field_id, last_name_field_id)
    VALUES ($form_id, '$email_field_id', '$first_name_field_id', '$last_name_field_id')
      ");

  extract(ft_process_hook_calls("end", compact("form_id", "infohash"), array()), EXTR_OVERWRITE);

  if ($result)
    return array(true, $LANG["notify_email_fields_updated"]);
  else
    return array(false, $LANG["notify_email_fields_not_updated"]);
}


/**
 * Called by administrators on the email configuration page. This unregisters a field or group of fields associated
 * with a unique user in the form submission fields.
 *
 * @param integer $form_email_id
 */
function ft_unset_field_as_email_field($form_email_id)
{
  global $g_table_prefix, $LANG;

  @mysql_query("DELETE FROM {$g_table_prefix}form_email_fields WHERE form_email_id = $form_email_id");
  @mysql_query("DELETE FROM {$g_table_prefix}email_template_recipients WHERE form_email_id = $form_email_id");

  // update those email templates that reference this form email ID
  @mysql_query("
    UPDATE {$g_table_prefix}email_templates
    SET    email_from = 'none',
           email_from_form_email_id = ''
    WHERE  email_from_form_email_id = $form_email_id
      ");

  @mysql_query("
    UPDATE {$g_table_prefix}email_templates
    SET    email_reply_to = 'none',
           email_reply_to_form_email_id = ''
    WHERE  email_reply_to_form_email_id = $form_email_id
      ");

  extract(ft_process_hook_calls("end", compact("form_email_id"), array()), EXTR_OVERWRITE);

  return array(true, $LANG["notify_email_field_config_deleted"]);
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

  extract(ft_process_hook_calls("start", compact("email_id", "info"), array("info")), EXTR_OVERWRITE);

  // "Main" tab
  $email_template_name   = $info["email_template_name"];
  $email_status          = $info["email_status"];
  $view_mapping_type     = (isset($info["view_mapping_type"])) ? $info["view_mapping_type"] : "all";
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

  // figure out the email_from field details
  $email_from_account_id    = "";
  $email_from_form_email_id = "";
  if (preg_match("/^client_account_id_(\d+)/", $email_from, $matches))
  {
    $email_from_account_id = $matches[1];
    $email_from = "client";
  }
  else if (preg_match("/^form_email_id_(\d+)/", $email_from, $matches))
  {
    $email_from_form_email_id = $matches[1];
    $email_from = "form_email_field";
  }

  // figure out the email_from field details
  $email_reply_to_account_id = "";
  $email_reply_to_form_email_id = "";
  if (preg_match("/^client_account_id_(\d+)/", $email_reply_to, $matches))
  {
    $email_reply_to_account_id = $matches[1];
    $email_reply_to = "client";
  }
  else if (preg_match("/^form_email_id_(\d+)/", $email_reply_to, $matches))
  {
    $email_reply_to_form_email_id = $matches[1];
    $email_reply_to = "form_email_field";
  }
  $email_from_account_id = (empty($email_from_account_id)) ? "NULL" : "'$email_from_account_id'";
  $email_from_form_email_id = (empty($email_from_form_email_id)) ? "NULL" : "'$email_from_form_email_id'";
  $email_reply_to_account_id = (empty($email_reply_to_account_id)) ? "NULL" : "'$email_reply_to_account_id'";
  $email_reply_to_form_email_id = (empty($email_reply_to_form_email_id)) ? "NULL" : "'$email_reply_to_form_email_id'";
  $email_from = (empty($email_from)) ? "NULL" : "'$email_from'";
  $email_reply_to = (empty($email_reply_to)) ? "NULL" : "'$email_reply_to'";

  // "Email Content" tab
  $html_template = $info["html_template"];
  $text_template = $info["text_template"];

  mysql_query("
    UPDATE {$g_table_prefix}email_templates
    SET    email_template_name = '$email_template_name',
           email_status = '$email_status',
           view_mapping_type = '$view_mapping_type',
           limit_email_content_to_fields_in_view = $limit_email_content_to_fields_in_view,
           email_event_trigger = '$email_event_trigger',
           include_on_edit_submission_page = '$include_on_edit_submission_page',
           subject = '$subject',
           email_from = $email_from,
           email_from_account_id = $email_from_account_id,
           email_from_form_email_id = $email_from_form_email_id,
           custom_from_name = '$custom_from_name',
           custom_from_email = '$custom_from_email',
           email_reply_to = $email_reply_to,
           email_reply_to_account_id = $email_reply_to_account_id,
           email_reply_to_form_email_id = $email_reply_to_form_email_id,
           custom_reply_to_name = '$custom_reply_to_name',
           custom_reply_to_email = '$custom_reply_to_email',
           html_template = '$html_template',
           text_template = '$text_template'
    WHERE  email_id = $email_id
      ") or die(mysql_error());

  // update the email template edit submission page Views
  mysql_query("DELETE FROM {$g_table_prefix}email_template_edit_submission_views WHERE email_id = $email_id");
  $selected_edit_submission_views = isset($info["selected_edit_submission_views"]) ? $info["selected_edit_submission_views"] : array();
  foreach ($selected_edit_submission_views as $view_id)
  {
    mysql_query("
      INSERT INTO {$g_table_prefix}email_template_edit_submission_views (email_id, view_id)
      VALUES ($email_id, $view_id)
        ");
  }

  // update the email template when sent Views
  mysql_query("DELETE FROM {$g_table_prefix}email_template_when_sent_views WHERE email_id = $email_id");
  $selected_when_sent_views = isset($info["selected_when_sent_views"]) ? $info["selected_when_sent_views"] : array();
  foreach ($selected_when_sent_views as $view_id)
  {
    mysql_query("
      INSERT INTO {$g_table_prefix}email_template_when_sent_views (email_id, view_id)
      VALUES ($email_id, $view_id)
        ");
  }

  // update the recipient list
  mysql_query("DELETE FROM {$g_table_prefix}email_template_recipients WHERE email_template_id = $email_id");
  $recipient_ids = $info["recipients"];

  foreach ($recipient_ids as $recipient_id)
  {
    $row = $recipient_id;

    // if there's no recipient user type (admin/form_email_field/client/custom), just ignore the row
    if (!isset($info["recipient_{$row}_user_type"]))
      continue;

    // "" (main), "cc" or "bcc"
    $recipient_type = (empty($info["recipient_{$row}_type"])) ? "main" : $info["recipient_{$row}_type"];
    switch ($info["recipient_{$row}_user_type"])
    {
      case "admin":
        mysql_query("
          INSERT INTO {$g_table_prefix}email_template_recipients (email_template_id, recipient_user_type, recipient_type)
          VALUES ($email_id, 'admin', '$recipient_type')
            ");
        break;

      case "form_email_field":
        $form_email_id = $info["recipient_{$row}_form_email_id"];
        mysql_query("
          INSERT INTO {$g_table_prefix}email_template_recipients
            (email_template_id, recipient_user_type, recipient_type, form_email_id)
          VALUES ($email_id, 'form_email_field', '$recipient_type', $form_email_id)
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

  extract(ft_process_hook_calls("end", compact("email_id", "info"), array("success", "message")), EXTR_OVERWRITE);

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
 * MUST have a first name, last name and email address. For form email fields, the final recipient just contains
 * the title of the email field (the display value).
 *
 * This is obviously used for display purposes only, whereas that value for the other recipient types is used both
 * for display purposes & in the actual email construction. This seemed an adequate approach because this function
 * will never be able to know the individual submission content so it can't construct it properly.
 *
 * The returned results are ordered by (a) recipient type (main, cc then bcc), then (b) recipient user type
 * (admin, client, form_email_field then custom)
 *
 * @param integer $email_id
 * @return array an array of hashes
 */
function ft_get_email_template_recipients($form_id, $email_id)
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

      case "form_email_field":
        $form_email_field_info = ft_get_form_email_field_info($recipient_info["form_email_id"]);
        $email_field_id = $form_email_field_info["email_field_id"];
        $recipient_info["final_recipient"] = ft_get_field_title_by_field_id($email_field_id);
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
 * Returns a record from the form_email_field table.
 *
 * @param integer $form_email_id
 * @return array
 */
function ft_get_form_email_field_info($form_email_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}form_email_fields
    WHERE  form_email_id = $form_email_id
      ");

  $info = mysql_fetch_assoc($query);

  return $info;
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

  extract(ft_process_hook_calls("end", compact("view_id", "email_info"), array("email_info")), EXTR_OVERWRITE);

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
    $events = explode(",", $template_info["email_event_trigger"]);

    if (!in_array($event, $events))
      continue;

    if ($template_info["email_status"] == "disabled")
      continue;

    // if this email template has been mapped to or more particular View, make sure the View ID is
    // valid & that the submission can be seen in at least one of the Views
    if ($template_info["view_mapping_type"] == "specific")
    {
      $view_ids = $template_info["when_sent_view_ids"];

      $found = false;
      foreach ($view_ids as $view_id)
      {
        if (ft_check_view_contains_submission($form_id, $view_id, $submission_id))
        {
        	$found = true;
        	break;
        }
      }

      if (!$found)
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
  list($success, $email_components) = ft_get_email_components($form_id, $submission_id, $email_id);

  if (!$success)
    return array(false, "Email components not returned properly (ft_get_email_components).");

  extract(ft_process_hook_calls("start", compact("form_id", "submission_id", "email_id", "email_components"), array("email_components")), EXTR_OVERWRITE);

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

  // if it was sent (or was attempted to have been sent) by the Swift Mailer module, stop here
  if (!$continue)
    return array($success, $message);

  $eol = _ft_get_email_eol_char();

  $recipient_list = array();
  foreach ($email_components["to"] as $to_info)
    $recipient_list[] = $to_info["recipient_line"];
  $to = join(", ", $recipient_list);
  $to = htmlspecialchars_decode($to);

  if (empty($to)){
    return array(false, "No main recipient specified.");
  }

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
  if (empty($html_content) && empty($text_content))
    return array(false, "No text or HTML email content specified");

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
  $email_sent = @mail("$to", $subject, $message, $headers);
  if ($email_sent)
    return array(true, "");
  else
    return array(false, "The mail() function failed to send the email.");
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
 * form and submission info. This can be used for the "from", "reply-to", or "to" fields.
 *
 * @param array $form_info A hash containing all the form information.
 * @param array $submission_info A hash containing all the submission information.
 * @return array string the email header
 */
function _ft_get_form_email_field_headers($form_email_id, $submission_info)
{
  $form_email_field_info = ft_get_form_email_field_info($form_email_id);

  // retrieve the user's name and email address from the form submission
  $first_name_field_id = $form_email_field_info["first_name_field_id"];
  $last_name_field_id  = $form_email_field_info["last_name_field_id"];
  $email_field_id      = $form_email_field_info["email_field_id"];

  $submission_first_name = "";
  $submission_last_name  = "";
  $submission_email      = "";

  foreach ($submission_info as $row)
  {
    if (!empty($first_name_field_id))
    {
      if ($row["field_id"] == $first_name_field_id)
        $submission_first_name = trim($row["content"]);
    }
    if (!empty($last_name_field_id))
    {
      if ($row["field_id"] == $last_name_field_id)
        $submission_last_name = trim($row["content"]);
    }
    // email
    if ($row["field_id"] == $email_field_id)
      $submission_email = trim($row["content"]);
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
               "first_name" => $submission_first_name,
               "last_name" => $submission_last_name,
               "email" => $submission_email);
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

  mysql_query("DELETE FROM {$g_table_prefix}email_templates WHERE email_id = $email_id");
  mysql_query("DELETE FROM {$g_table_prefix}email_template_recipients WHERE email_template_id = $email_id");
  mysql_query("DELETE FROM {$g_table_prefix}email_template_edit_submission_views WHERE email_id = $email_id");

  return array(true, $LANG["notify_email_template_deleted"]);
}


/**
 * This function is tightly coupled with ft_get_email_components and has gotten increasingly more awful as
 * time passed. It examines the content of an email template and detects any field and file attachments.
 * Field attachments are files that have been uploaded through a form field; file attachments are just files
 * on the server that want to be sent out. It then returns the updated email template (i.e. minus the
 * attachment string) and information about the attachment (file name, location, mimetype) for use by the
 * emailing function (only Swift Mailer module at this time).
 *
 * @param string $template_str the email template (HTML or text)
 * @param integer $form_id
 * @param array $submission_placeholders
 */
function _ft_extract_email_attachment_info($template_str, $form_id, $submission_placeholders)
{
  global $g_root_dir;

  // see if there are any filename placeholders (i.e. uploaded files in this submission)
  $file_field_name_to_filename_hash = array();
  while (list($placeholder, $value) = each($submission_placeholders))
  {
    if (!preg_match("/^FILENAME_/", $placeholder))
      continue;

    $field_name = preg_replace("/^FILENAME_/", "", $placeholder);
    $file_field_name_to_filename_hash[$field_name] = $value;
  }

  $attachment_info = array();
  if (!empty($file_field_name_to_filename_hash))
  {
    // if there are any fields marked as attachments, store them and remove the attachment string
    $field_attachments_regexp = '/\{\$attachment\s+field=("|\')(.+)("|\')\}/';

    if (preg_match_all($field_attachments_regexp, $template_str, $matches))
    {
      foreach ($matches[2] as $field_name)
      {
        $field_id = ft_get_form_field_id_by_field_name($field_name, $form_id);
        if (!empty($field_name) && array_key_exists($field_name, $file_field_name_to_filename_hash))
        {
          $field_settings = ft_get_field_settings($field_id);
          $file_upload_dir = $field_settings["folder_path"];
          $file_and_path = "$file_upload_dir/{$file_field_name_to_filename_hash[$field_name]}";

          if (is_file($file_and_path))
          {
            $info = array(
              "field_name"    => $field_name,
              "file_and_path" => $file_and_path,
              "filename"      => $file_field_name_to_filename_hash[$field_name],
              "mimetype"      => mime_content_type($file_and_path)
            );
            $attachment_info[] = $info;
          }
        }
      }

      $template_str = preg_replace($field_attachments_regexp, "", $template_str);
    }
  }

  $file_attachments_regexp  = '/\{\$attachment\s+file=("|\')(.+)("|\')\}/';
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
          "filename"      => $file_name
        );
        $attachment_info[] = $info;
      }
    }
    $template_str = preg_replace($file_attachments_regexp, "", $template_str);
  }

  $file_attachments_regexp = '/\{\$attachment\s+fieldvalue=("|\')(.+)("|\')\}/';
  if (preg_match_all($file_attachments_regexp, $template_str, $matches))
  {
    foreach ($matches[2] as $file_and_relative_path)
    {
      $file_and_relative_path = ft_eval_smarty_string("{\$" . $file_and_relative_path . "}", $submission_placeholders);
      if (is_file("$g_root_dir/$file_and_relative_path"))
      {
        $pathinfo = pathinfo($file_and_relative_path);
        $file_name = $pathinfo["basename"];

        $info = array(
          "file_and_path" => "$g_root_dir/$file_and_relative_path",
          "filename"      => $file_name
        );
        $attachment_info[] = $info;
      }
    }
    $template_str = preg_replace($file_attachments_regexp, "", $template_str);
  }

  return array($template_str, $attachment_info);
}


/**
 * Strongly coupled with the ft_get_email_components function, this does the legwork to find out exactly what
 * text and HTML (Smarty) content should be in the emails. Returns BOTH, regardless of whether the template is
 * only using one.
 *
 * @param integer $form_id
 * @param integer $submission_id
 * @param array $email_template
 * @param boolean $is_test
 * @param array $test_settings
 */
function _ft_get_email_template_content($form_id, $submission_id, $email_template, $is_test, $test_settings)
{
  global $LANG, $g_table_prefix;

  // if this is a test, find out what information the administrator wants
  $templates = array(
    "text" => "",
    "html" => "",
    "submission_id" => $submission_id
  );

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
        // if this email template has been mapped to only be sent for one or more Views,
        // find a submission that fits into the first in the list
        $where_clause = "";
        if (!empty($email_template["when_sent_view_ids"]))
        {
          $sql_clauses = ft_get_view_filter_sql($email_template["when_sent_view_ids"][0]);
          if (!empty($sql_clauses))
            $where_clause = "WHERE (" . join(" AND ", $sql_clauses) . ") ";
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

    $templates["submission_id"] = $submission_id;

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


  return $templates;
}
