<?php

/**
 * This file defines all general user-account related functions. For functions specific to administrators
 * or clients, see administrator.php and clients.php.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Accounts
 */


// -------------------------------------------------------------------------------------------------

/**
 * Figure out if an account exists or not.
 *
 * @param $account_id
 */
function ft_account_exists($account_id)
{
  global $g_table_prefix;

  if (empty($account_id) || !is_numeric($account_id))
    return false;

  $query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}accounts WHERE account_id = $account_id");
  $result = mysql_fetch_assoc($query);

  return ($result["c"] == 1);
}


/**
 * Helper function to determine if the user currently logged in is an administrator or not.
 *
 * return boolean. False if client or not logged in.
 */
function ft_is_admin()
{
  $account_id = isset($_SESSION["ft"]["account"]["account_id"]) ? $_SESSION["ft"]["account"]["account_id"] : "";
	if (empty($account_id))
	  return false;

	$account_info = ft_get_account_info($account_id);
	if (empty($account_info) || $account_info["account_type"] != "admin")
	  return false;

  return true;
}


/**
 * Returns the account ID of the currently logged in user - or returns the empty string if there's no user account.
 *
 * @return integer the account ID
 */
function ft_get_current_account_id()
{
	$account_id = isset($_SESSION["ft"]["account"]["account_id"]) ? $_SESSION["ft"]["account"]["account_id"] : "";
	return $account_id;
}


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

  if (empty($account_info))
    return array();

  // also extract any account-specific settings from account_settings
  $query = mysql_query("SELECT * FROM {$g_table_prefix}account_settings WHERE account_id = $account_id");
  $settings = array();
  while ($row = mysql_fetch_assoc($query))
    $settings[$row["setting_name"]] = $row["setting_value"];

  $account_info["settings"] = $settings;

  extract(ft_process_hook_calls("main", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

  return $account_info;
}


/**
 * Returns all custom account settings for a user account. This merely queries the
 * account_settings table, nothing more; it doesn't trickle up the inheritance
 * chain to figure out the default settings.
 *
 * @param integer $account_id
 * @return array
 */
function ft_get_account_settings($account_id)
{
  global $g_table_prefix;

  if (empty($account_id))
    return array();

  $query = mysql_query("
    SELECT setting_name, setting_value
    FROM   {$g_table_prefix}account_settings
    WHERE  account_id = $account_id
  ");

  $hash = array();
  while ($row = mysql_fetch_assoc($query))
    $hash[$row['setting_name']] = $row["setting_value"];

  extract(ft_process_hook_calls("main", compact("account_id", "hash"), array("hash")), EXTR_OVERWRITE);

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

  $settings = ft_get_settings("", "core");

  $username = strip_tags($infohash["username"]);
  $username = ft_sanitize($username);
  $password = isset($infohash["password"]) ? ft_sanitize($infohash["password"]) : "";
  $password = strip_tags($password);

  // extract info about this user's account
  $query = mysql_query("
    SELECT account_id, account_type, account_status, password, temp_reset_password, login_page
    FROM   {$g_table_prefix}accounts
    WHERE  username = '$username'
      ");
  $account_info = mysql_fetch_assoc($query);

  $has_temp_reset_password = (empty($account_info["temp_reset_password"])) ? false : true;

  // error check user login info
  if (!$login_as_client)
  {
    if (empty($password))                                 return $LANG["validation_no_password"];
    if ($account_info["account_status"] == "disabled")    return $LANG["validation_account_disabled"];
    if ($account_info["account_status"] == "pending")     return $LANG["validation_account_pending"];
    if (empty($username))                                 return $LANG["validation_account_not_recognized"];

    $password_correct      = (md5(md5($password)) == $account_info["password"]);
    $temp_password_correct = (md5(md5($password)) == $account_info["temp_reset_password"]);

    if (!$password_correct && !$temp_password_correct)
    {
      // if this is a client account and the administrator has enabled the maximum failed login attempts feature,
      // keep track of the count
      $account_settings = ft_get_account_settings($account_info["account_id"]);

      // stores the MAXIMUM number of failed attempts permitted, before the account gets disabled. If the value
      // is empty in either the user account or for the default value, that means the administrator doesn't want
      // to track the failed login attempts
      $max_failed_login_attempts = (isset($account_settings["max_failed_login_attempts"])) ?
        $account_settings["max_failed_login_attempts"] : $settings["default_max_failed_login_attempts"];

      if ($account_info["account_type"] == "client" && !empty($max_failed_login_attempts))
      {
        $num_failed_login_attempts = (isset($account_settings["num_failed_login_attempts"]) && !empty($account_settings["num_failed_login_attempts"])) ?
          $account_settings["num_failed_login_attempts"] : 0;

        $num_failed_login_attempts++;

        if ($num_failed_login_attempts >= $max_failed_login_attempts)
        {
          ft_disable_client($account_info["account_id"]);
          ft_set_account_settings($account_info["account_id"], array("num_failed_login_attempts" => 0));
          return $LANG["validation_account_disabled"];
        }
        else
        {
          ft_set_account_settings($account_info["account_id"], array("num_failed_login_attempts" => $num_failed_login_attempts));
        }
      }
      return $LANG["validation_wrong_password"];
    }
  }

  extract(ft_process_hook_calls("main", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

  // all checks out. Log them in, after populating sessions
  $_SESSION["ft"]["settings"] = $settings;
  $_SESSION["ft"]["account"]  = ft_get_account_info($account_info["account_id"]);
  $_SESSION["ft"]["account"]["is_logged_in"] = true;

  // this is deliberate.
  $_SESSION["ft"]["account"]["password"] = md5(md5($password));

  ft_cache_account_menu($account_info["account_id"]);

  // if this is an administrator, ensure the API version is up to date
  if ($account_info["account_type"] == "admin")
  {
    ft_update_api_version();
  }
  else
  {
    ft_set_account_settings($account_info["account_id"], array("num_failed_login_attempts" => 0));
  }

  // for clients, store the forms & form Views that they are allowed to access
  if ($account_info["account_type"] == "client")
    $_SESSION["ft"]["permissions"] = ft_get_client_form_views($account_info["account_id"]);


  // if the user just logged in with a temporary password, append some args to pass to the login page
  // so that they will be prompted to changing it upon login
  $reset_password_args = array();
  if ((md5(md5($password)) == $account_info["temp_reset_password"]))
  {
    $reset_password_args["message"] = "change_temp_password";
  }

  // redirect the user to whatever login page they specified in their settings
  $login_url = ft_construct_page_url($account_info["login_page"], "", $reset_password_args);
  $login_url = "$g_root_url{$login_url}";

  if (!$login_as_client)
    ft_update_last_logged_in($account_info["account_id"]);

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

  extract(ft_process_hook_calls("main", array(), array()));

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


/**
 * Updates the last logged in date for an account.
 *
 * @param $account_id
 */
function ft_update_last_logged_in($account_id)
{
  global $g_table_prefix;

  $account_id = ft_sanitize($account_id);
  if (!is_numeric($account_id))
    return;

  $now = ft_get_current_datetime();

  @mysql_query("
    UPDATE {$g_table_prefix}accounts
    SET    last_logged_in = '$now'
    WHERE  account_id = $account_id
  ");
}


/**
 * This function is called when updating a client account and the administrator has chosen
 * to prevent them from choosing any password they entered in the last N times (up to 10).
 *
 * The password_history setting in the users' account_settings table always stores the last 10
 * encrypted passwords, comma-delimited, and ordered newest to oldest. This function just checks
 * that log against an incoming password to check it's validity.
 *
 * @param $account_id
 * @param string $password (encrypted)
 * @param integer the number of items to check in the history. e.g. 5 would only check the last
 *                5 passwords.
 */
function ft_password_in_password_history($account_id, $password, $num_password_history)
{
  $account_settings = ft_get_account_settings($account_id);
  $last_passwords = (isset($account_settings["password_history"]) && !empty($account_settings["password_history"])) ?
    explode(",", $account_settings["password_history"]) : array();

  $is_found = false;
  for ($i=0; $i<$num_password_history; $i++)
  {
    if ($password == $last_passwords[$i])
    {
      $is_found = true;
      break;
    }
  }
  return $is_found;
}


/**
 * Updates the password history queue for a client account. The assumption is that ft_password_in_password_history()
 * has already been called to determine whether or not the password should be added to the list.
 *
 * @param integer $account_id
 * @param string $password
 */
function ft_add_password_to_password_history($account_id, $password)
{
  global $g_password_history_size;

  $account_settings = ft_get_account_settings($account_id);
  $last_passwords = (isset($account_settings["password_history"]) && !empty($account_settings["password_history"])) ?
    explode(",", $account_settings["password_history"]) : array();
  array_unshift($last_passwords, $password);
  $trimmed_list = array_splice($last_passwords, 0, $g_password_history_size);
  $new_password_history = implode(",", $trimmed_list);
  ft_set_account_settings($account_id, array("password_history" => $new_password_history));
}


// ------------------------------------------------------------------------------------------------


/**
 * Helper function to determine if a username is valid or not. Checks to see that it only
 * contains a-Z, 0-9, ., _ and @ chars and that it's not already taken.
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
  if (preg_match("/[^\.a-zA-Z0-9_@]/", $username))
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

