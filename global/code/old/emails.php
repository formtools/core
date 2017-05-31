<?php

/**
 * This file defines all functions related to emails sent by Form Tools.
 *
 * @copyright Benjamin Keen 2014
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Emails
 */


// -------------------------------------------------------------------------------------------------

use FormTools\Accounts;
use FormTools\Administrator;



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

	extract(Hooks::processHookCalls("end", compact("form_id", "infohash"), array()), EXTR_OVERWRITE);

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

	extract(Hooks::processHookCalls("end", compact("form_email_id"), array()), EXTR_OVERWRITE);

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

	extract(Hooks::processHookCalls("start", compact("email_id", "info"), array("info")), EXTR_OVERWRITE);

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

	extract(Hooks::processHookCalls("end", compact("email_id", "info"), array("success", "message")), EXTR_OVERWRITE);

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
				$admin_info = Administrator::getAdminInfo();
				$recipient_info["final_name"] = "{$admin_info["first_name"]} {$admin_info["last_name"]}";
				$recipient_info["final_email"] = $admin_info["email"];
				$recipient_info["final_recipient"] = "{$recipient_info["final_name"]} &lt;{$recipient_info["final_email"]}&gt;";
				break;

			case "client":
				$client_info = Accounts::getAccountInfo($recipient_info["account_id"]);
				$recipient_info["final_name"] = "{$client_info["first_name"]} {$client_info["last_name"]}";
				$recipient_info["final_email"] = $client_info["email"];
				$recipient_info["final_recipient"] = "{$recipient_info["final_name"]} &lt;{$recipient_info["final_email"]}&gt;";
				break;

			case "form_email_field":
				$form_email_field_info = self::getFormEmailFieldInfo($recipient_info["form_email_id"]);
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
	while ($row = mysql_fetch_assoc($query)) {
		$email_id = $row["email_id"];
		$email_info[] = self::getEmailTemplate($email_id);
	}

	extract(Hooks::processHookCalls("end", compact("view_id", "email_info"), array("email_info")), EXTR_OVERWRITE);

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
	$all_form_email_templates = self::getEmailTemplateList($form_id);

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
	list($success, $email_components) = self::getEmailComponents($form_id, $submission_id, $email_id);

	if (!$success) {
        return array(false, "Email components not returned properly (Emails::getEmailComponents).");
    }

	extract(Hooks::processHookCalls("start", compact("form_id", "submission_id", "email_id", "email_components"), array("email_components")), EXTR_OVERWRITE);

	// if Swift Mailer is enabled, send the emails with that
	$continue = true;
	if (Modules::checkModuleEnabled("swift_mailer")) {
		$sm_settings = Modules::getModuleSettings("", "swift_mailer");

		if (isset($sm_settings["swiftmailer_enabled"]) && $sm_settings["swiftmailer_enabled"] == "yes") {
			Modules::includeModule("swift_mailer");
			list($success, $message) = swift_send_email($email_components);
			$continue = false;
		}
	}

	// if it was sent (or was attempted to have been sent) by the Swift Mailer module, stop here
	if (!$continue) {
        return array($success, $message);
    }

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

	if (!empty($email_components["from"])) {
		$from = htmlspecialchars_decode($email_components["from"]["recipient_line"]);
		$headers .= "From: {$from}$eol";
	}
	if (!empty($email_components["reply_to"])) {
		$reply_to = htmlspecialchars_decode($email_components["reply_to"]["recipient_line"]);
		$headers .= "Reply-to: {$reply_to}$eol";
	}
	if (!empty($email_components["cc"])) {
		$cc_list = array();
		foreach ($email_components["cc"] as $cc_info) {
            $cc_list[] = $cc_info["recipient_line"];
        }
		$cc = join(", ", $cc_list);
		$cc = htmlspecialchars_decode($cc);
		$headers .= "Cc: {$cc}$eol";
	}
	if (!empty($email_components["bcc"])) {
		$bcc_list = array();
		foreach ($email_components["bcc"] as $bcc_info) {
            $bcc_list[] = $bcc_info["recipient_line"];
        }
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
	if (empty($html_content) && empty($text_content)) {
        return array(false, "No text or HTML email content specified");
    }
	if (!empty($html_content) && !empty($text_content)) {
        $headers .= _ft_get_multipart_message($html_content, $text_content, $eol);
    } else if (!empty($html_content)) {
		$message = $html_content;
		$headers .= "Content-type: text/html; charset=UTF-8";
	} else if (!empty($text_content)) {
		$message = $text_content;
		$headers .= "Content-type: text/plain; charset=UTF-8";
	}

	$subject = $email_components["subject"];

	// send the email
	$email_sent = @mail("$to", $subject, $message, $headers);
	if ($email_sent) {
        return array(true, "");
    } else {
        return array(false, "The mail() function failed to send the email.");
    }
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
	if (strtoupper(substr(PHP_OS, 0, 3) == 'WIN')) {
        $eol = "\r\n";
    } else if (strtoupper(substr(PHP_OS, 0, 3) == 'MAC')) {
        $eol = "\r";
    }
	return $eol;
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
			$file_and_relative_path = General::evalSmartyString("{\$" . $file_and_relative_path . "}", $submission_placeholders);
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
