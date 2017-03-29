<?php

/**
 * This file defines all general user-account related functions. For functions specific to administrators
 * or clients, see administrator.php and clients.php.
 *
 * @copyright Benjamin Keen 2014
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Accounts
 */


// -------------------------------------------------------------------------------------------------


/**
 * Used by the "forget password?" page to have a client's login information sent to them.
 *
 * @param array $info the $_POST containing a "username" key. That value is used to find the user
 *      account information to email them.
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 */
function ft_send_password($info)
{
	global $g_root_url, $g_root_dir, $g_table_prefix, $LANG;

	extract(ft_process_hook_calls("start", compact("info"), array("info")), EXTR_OVERWRITE);

	$success = true;
	$message = $LANG["notify_login_info_emailed"];

	if (!isset($info["username"]) || empty($info["username"]))
	{
		$success = false;
		$message = $LANG["validation_no_username_or_js"];
		return array($success, $message);
	}
	$username = $info["username"];

	$query = mysql_query("
     SELECT *
     FROM   {$g_table_prefix}accounts
     WHERE  username = '$username'
          ");

	// not found
	if (!mysql_num_rows($query))
	{
		$success = false;
		$message = $LANG["validation_account_not_recognized_info"];
		return array($success, $message);
	}

	$account_info = mysql_fetch_assoc($query);
	$email        = $account_info["email"];

	// one final check: confirm the email is defined & valid
	if (empty($email) || !ft_is_valid_email($email))
	{
		$success = false;
		$message = $LANG["validation_email_not_found_or_invalid"];
		return array($success, $message);
	}

	$account_id   = $account_info["account_id"];
	$username     = $account_info["username"];
	$new_password = ft_generate_password();
	$encrypted_password = md5(md5($new_password));

	// update the database with the new password (encrypted). As of 2.1.0 there's a second field to store the
	// temporary generated password, leaving the original password intact. This prevents a situation arising when
	// someone other than the admin / client uses the "Forget Password" feature and invalidates a valid, known password.
	// Any time the user successfully logs in,
	mysql_query("
    UPDATE {$g_table_prefix}accounts
    SET    temp_reset_password = '$encrypted_password'
    WHERE  account_id = $account_id
      ");

	// now build and sent the email

	// 1. build the email content
	$placeholders = array(
		"login_url" => "$g_root_url/?id=$account_id",
		"email"     => $email,
		"username"  => $username,
		"new_password" => $new_password
	);
	$smarty_template_email_content = file_get_contents("$g_root_dir/global/emails/forget_password.tpl");
	$email_content = ft_eval_smarty_string($smarty_template_email_content, $placeholders);

	// 2. build the email subject line
	$placeholders = array(
		"program_name" => ft_get_settings("program_name")
	);
	$smarty_template_email_subject = file_get_contents("$g_root_dir/global/emails/forget_password_subject.tpl");
	$email_subject = trim(ft_eval_smarty_string($smarty_template_email_subject, $placeholders));

	// if Swift Mailer is enabled, send the emails with that. In case there's a problem sending the message with
	// Swift, it falls back the default mail() function.
	$swift_mail_error = false;
	$swift_mail_enabled = ft_check_module_enabled("swift_mailer");
	if ($swift_mail_enabled)
	{
		$sm_settings = ft_get_module_settings("", "swift_mailer");
		if ($sm_settings["swiftmailer_enabled"] == "yes")
		{
			ft_include_module("swift_mailer");

			// get the admin info. We'll use that info for the "from" and "reply-to" values. Note
			// that we DON'T use that info for the regular mail() function. This is because retrieving
			// the password is important functionality and we don't want to cause problems that could
			// prevent the email being sent. Many servers don't all the 4th headers parameter of the mail()
			// function
			$admin_info = ft_get_admin_info();
			$admin_email = $admin_info["email"];

			$email_info  = array();
			$email_info["to"]  = array();
			$email_info["to"][] = array("email" => $email);
			$email_info["from"] = array();
			$email_info["from"]["email"] = $admin_email;
			$email_info["subject"] = $email_subject;
			$email_info["text_content"] = $email_content;
			list($success, $sm_message) = swift_send_email($email_info);

			// if the email couldn't be sent, display the appropriate error message. Otherwise
			// the default success message is used
			if (!$success)
			{
				$swift_mail_error = true;
				$message = $sm_message;
			}
		}
	}

	// if there was an error sending with Swift, or if it wasn't installed, send it by mail()
	if (!$swift_mail_enabled || $swift_mail_error)
	{
		// send email [note: the double quotes around the email recipient and content are intentional: some systems fail without it]
		if (!@mail("$email", $email_subject, $email_content))
		{
			$success = false;
			$message = $LANG["notify_email_not_sent"];
			return array($success, $message);
		}
	}

	extract(ft_process_hook_calls("end", compact("success", "message", "info"), array("success", "message")), EXTR_OVERWRITE);

	return array($success, $message);
}


/**
 * Updates any number of settings for a particular user account. As with the similar ft_set_settings
 * function, it creates the record if it doesn't already exist.
 *
 * @param integer $account_id
 * @param array $settings a hash of setting name => setting value.
 */
function ft_set_account_settings($account_id, $settings)
{
	global $g_table_prefix;

	extract(ft_process_hook_calls("start", compact("account_id", "settings"), array("settings")), EXTR_OVERWRITE);

	while (list($setting_name, $setting_value) = each($settings))
	{
		// find out if it already exists
		$result = mysql_query("
      SELECT count(*) as c
      FROM   {$g_table_prefix}account_settings
      WHERE  setting_name = '$setting_name' AND
             account_id = $account_id
        ");
		$info = mysql_fetch_assoc($result);

		if ($info["c"] == 0)
		{
			mysql_query("
        INSERT INTO {$g_table_prefix}account_settings (account_id, setting_name, setting_value)
        VALUES ($account_id, '$setting_name', '$setting_value')
          ");
		}
		else
		{
			mysql_query("
        UPDATE {$g_table_prefix}account_settings
        SET    setting_value = '$setting_value'
        WHERE  setting_name  = '$setting_name' AND
               account_id = $account_id
          ");
		}
	}

	extract(ft_process_hook_calls("end", compact("account_id", "settings"), array()), EXTR_OVERWRITE);
}

