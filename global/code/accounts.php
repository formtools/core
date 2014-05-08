<?php

/**
 * This file defines all general user-account related functions. For functions specific to administrators
 * or clients, see administrator.php and clients.php.
 *
 * @copyright Encore Web Studios 2010
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-0
 * @subpackage Accounts
 */


// -------------------------------------------------------------------------------------------------


/**
 * Retrieves all information about any user account (administrator or client).
 *
 * @param integer $user_id the unique account ID
 * @return array returns a hash of all pertinent data.
 */
function ft_get_account_info($account_id)
{
  global $g_table_prefix;

  $query = "
    SELECT  *
    FROM    {$g_table_prefix}accounts
    WHERE   account_id = $account_id
           ";
  $result = mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: <i>$query</i>", mysql_error());

  $account_info = mysql_fetch_assoc($result);

  // also extract any account-specific settings from account_settings
  $query = mysql_query("SELECT * FROM {$g_table_prefix}account_settings WHERE account_id = $account_id");

  $settings = array();
  while ($row = mysql_fetch_assoc($query))
    $settings[$row["setting_name"]] = $row["setting_value"];

  $account_info["settings"] = $settings;

  extract(ft_process_hooks("main", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

  return $account_info;
}


/**
 * Returns all custom account settings for a user account.
 *
 * @param integer $account_id
 * @return array
 */
function ft_get_account_settings($account_id)
{
  global $g_table_prefix;

  $query  = mysql_query("
          SELECT setting_name, setting_value
          FROM   {$g_table_prefix}account_settings
          WHERE  account_id = $account_id
          ");
  $hash = array();
  while ($row = mysql_fetch_assoc($query))
    $hash[$row['setting_name']] = $row["setting_value"];

  extract(ft_process_hooks("main", compact("account_id", "hash"), array("hash")), EXTR_OVERWRITE);

  return $hash;
}


/**
 * The login procedure for both administrators and clients in. If successful, redirects them to the
 * appropriate page, otherwise returns an error.
 *
 * @param array   $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing both
 *                "username" and "password" keys, containing that information for the user trying
 *                to log in.
 * @param boolean $login_as_client [optional] This optional parameter is used by administrators
 *                to log in as a particular client, allowing them to view how the account looks,
 *                even if it is disabled.
 * @return string error message string (if error occurs). Otherwise it redirects the user to the
 *                appropriate page, based on account type.
 */
function ft_login($infohash, $login_as_client = false)
{
  global $g_root_url, $g_table_prefix, $LANG;

  $username = strip_tags($infohash["username"]);
  $username = ft_sanitize($username);
  $password = isset($infohash["password"]) ? ft_sanitize($infohash["password"]) : "";
  $password = strip_tags($password);

  // extract info about this user's account
  $query = mysql_query("
    SELECT account_id, account_type, account_status, password, login_page
    FROM   {$g_table_prefix}accounts
    WHERE  username = '$username'
      ");
  $account_info = mysql_fetch_assoc($query);

  // error check user login info
  if (!$login_as_client)
  {
    if (empty($password))                                 return $LANG["validation_no_password"];
    if ($account_info["account_status"] == "disabled")    return $LANG["validation_account_disabled"];
    if ($account_info["account_status"] == "pending")     return $LANG["validation_account_pending"];
    if (empty($password))                                 return $LANG["validation_account_not_recognized"];
    if (md5(md5($password)) != $account_info["password"]) return $LANG["validation_wrong_password"];
  }

  extract(ft_process_hooks("main", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

  // all checks out. Log them in, after populating sessions
  $_SESSION["ft"]["settings"] = ft_get_settings("", "core"); // only load the core settings
  $_SESSION["ft"]["account"]  = ft_get_account_info($account_info["account_id"]);
  $_SESSION["ft"]["account"]["is_logged_in"] = true;
  $_SESSION["ft"]["account"]["password"] = md5(md5($password));

  ft_cache_account_menu($account_info["account_id"]);

  // if this is an administrator, build and cache the upgrade link and ensure the API version is up to date
  if ($account_info["account_type"] == "admin")
  {
    ft_update_api_version();
    ft_build_and_cache_upgrade_info();
  }

  // for clients, store the forms & form Views that they are allowed to access
  if ($account_info["account_type"] == "client")
    $_SESSION["ft"]["permissions"] = ft_get_client_form_views($account_info["account_id"]);

  // redirect the user to whatever login page they specified in their settings
  $login_url = ft_construct_page_url($account_info["login_page"]);
  $login_url = "$g_root_url{$login_url}";

  session_write_close();
  header("Location: $login_url");
  exit;
}


/**
 * Logs a user out programmatically. This was added in 2.0.0 to replace the logout.php page. It has
 * a couple of benefits: (1) it's smart enough to know what page to go when logging out. Formerly, it
 * would always redirect to the account's logout URL, but there are situations where that's not always
 * desirable - e.g. sessions timeout. (2) it has the option of passing a message flag via the query
 * string.
 *
 * Internally, a user can logout by passing a "?logout" query string to any page in Form Tools.
 *
 * @param string $message_flag if this value is set, it ALWAYS redirects to the login page, so that the
 *   message is displayed. If it isn't set, it redirects to the user's custom logout URL (if set).
 */
function ft_logout_user($message_flag = "")
{
  global $g_root_url, $g_session_type;

  extract(ft_process_hooks("main", array(), array()));

  // this ensures sessions are started
  if ($g_session_type == "database")
    $sess = new SessionManager();
  @session_start();

  // first, if $_SESSION["ft"]["admin"] is set, it is an administrator logging out, so just redirect them
  // back to the admin pages
  if (isset($_SESSION["ft"]) && array_key_exists("admin", $_SESSION["ft"]))
    ft_logout_as_client();
  else
  {
    if (!empty($message_flag))
    {
      // empty sessions, but be nice about it. Only delete the Form Tools namespaced sessions - any other
      // PHP scripts the user's running right now should be unaffected
      @session_start();
      @session_destroy();
      $_SESSION["ft"] = array();

      // redirect to the login page, passing along the appropriate message flag so the page knows what to display
      $logout_url = ft_construct_url("$g_root_url/", "message=$message_flag");
      session_write_close();
      header("location: $logout_url");
      exit;
    }
    else
    {
      $logout_url = isset($_SESSION["ft"]["account"]["logout_url"]) ? $_SESSION["ft"]["account"]["logout_url"] : "";

      // empty sessions, but be nice about it. Only delete the Form Tools namespaced sessions - any other
      // PHP scripts the user happens to be running right now should be unaffected
      @session_start();
      @session_destroy();
      $_SESSION["ft"] = array();

      if (empty($logout_url))
        $logout_url = $g_root_url;

      // redirect to login page
      session_write_close();
      header("location: $logout_url");
      exit;
    }
  }
}


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

  $info = ft_sanitize($info);

  extract(ft_process_hooks("start", compact("info"), array("info")), EXTR_OVERWRITE);

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

  // update the database with the new password (encrypted)
  mysql_query("
    UPDATE {$g_table_prefix}accounts
    SET    password = '$encrypted_password'
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


  // if Swift Mailer is enabled, send the emails with that
  if (ft_check_module_enabled("swift_mailer"))
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
			  $message = $sm_message;
    }
  }
  else
	{
    // send email [note: the double quotes around the email recipient and content are intentional:
    // some systems fail without it]
    if (!@mail("$email", $email_subject, $email_content))
    {
      $success = false;
      $message = $LANG["notify_email_not_sent"];
      return array($success, $message);
    }
  }

  extract(ft_process_hooks("end", compact("success", "message", "info"), array("success", "message")), EXTR_OVERWRITE);

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

  extract(ft_process_hooks("start", compact("account_id", "settings"), array("settings")), EXTR_OVERWRITE);

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

  extract(ft_process_hooks("end", compact("account_id", "settings"), array()), EXTR_OVERWRITE);
}


// ------------------------------------------------------------------------------------------------


/**
 * Helper function to determine if a username is valid or not. Checks to see that it only
 * contains alphanumeric chars and that it's not already taken.
 *
 * @param string $username a prospective username
 * @param integer $user_id optional paramter used when editing the username for an account
 * @return array [0]: true/false (success / failure)<br />
 *               [1]: message string
 */
function _ft_is_valid_username($username, $account_id = "")
{
  global $g_table_prefix, $LANG;

  // check the username is alphanumeric
  if (preg_match("/[^\w]/", $username))
    return array(false, $LANG["validation_invalid_client_username2"]);

  $clause = "";
  if (!empty($account_id))
    $clause = "AND account_id != $account_id";

  // now check the username isn't already taken
  $query = mysql_query("
    SELECT count(*)
    FROM   {$g_table_prefix}accounts
    WHERE  username = '$username'
           $clause
           ");
  $info = mysql_fetch_row($query);

  if ($info[0] > 0)
    return array(false, $LANG["validation_username_taken"]);
  else
    return array(true, "");
}
