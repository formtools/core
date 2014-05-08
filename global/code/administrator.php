<?php

/**
 * This file defines all user account functions for the administrator account. Also see accounts.php (for
 * general functions) and clients.php for functions related to client accounts.
 *
 * @copyright Encore Web Studios 2009
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-0
 * @subpackage Administrator
 */


// -------------------------------------------------------------------------------------------------


/**
 * Returns information about the administrator account.
 *
 * @return array a hash of account information
 */
function ft_get_admin_info()
{
	global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}accounts
    WHERE  account_type = 'admin'
    LIMIT  1
      ");

  $result = mysql_fetch_assoc($query);

  return $result;
}


/**
 * Updates the administrator account. With the addition of the "UI Language" option, this action
 * gets a little more complicated. The problem is that we can't just update the UI language in
 * sessions *within* this function, because by the time this function is called, the appropriate
 * language file is already in memory and being used. So, to get around this problem, the login
 * information form now passes along both the new and old UI languages. If it's different, AFTER
 * this function is called, you need to reset sessions and refresh the page. So be aware that
 * this problem is NOT handled by this function, see:
 *     /admin/accounts/index.php to see how it's solved.
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
 *               following keys: first_name, last_name, user_name, password.
 * @param integer $user_id the administrator's user ID
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 */
function ft_update_admin_account($infohash, $account_id)
{
	global $g_table_prefix, $g_root_url, $LANG;

	$success = true;
	$message = $LANG["notify_account_updated"];
	$infohash = ft_sanitize($infohash);

  $rules = array();
  $rules[] = "required,first_name,{$LANG["validation_no_first_name"]}";
  $rules[] = "required,last_name,{$LANG["validation_no_last_name"]}";
  $rules[] = "required,email,{$LANG["validation_no_email"]}";
  $rules[] = "required,theme,{$LANG["validation_no_theme"]}";
	$rules[] = "required,login_page,{$LANG["validation_no_login_page"]}";
	$rules[] = "required,logout_url,{$LANG["validation_no_account_logout_url"]}";
	$rules[] = "required,ui_language,{$LANG["validation_no_ui_language"]}";
	$rules[] = "required,sessions_timeout,{$LANG["validation_no_sessions_timeout"]}";
  $rules[] = "required,date_format,{$LANG["validation_no_date_format"]}";
  $rules[] = "required,username,{$LANG["validation_no_username"]}";
	$rules[] = "if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed"]}";
	$rules[] = "if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}";
	$errors = validate_fields($infohash, $rules);

	if (!empty($errors))
	{
		$success = false;
		array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
		$message = join("<br />", $errors);
		return array($success, $message);
	}

	$first_name       = $infohash["first_name"];
	$last_name        = $infohash["last_name"];
	$email            = $infohash["email"];
	$theme            = $infohash["theme"];
	$login_page       = $infohash["login_page"];
	$logout_url       = $infohash["logout_url"];
	$ui_language      = $infohash["ui_language"];
	$timezone_offset  = $infohash["timezone_offset"];
	$sessions_timeout = $infohash["sessions_timeout"];
	$date_format      = $infohash["date_format"];
	$username         = $infohash["username"];
	$password         = $infohash["password"];

  // if the password is defined, md5 it
  $password_sql = (!empty($password)) ? "password = '" . md5(md5($password)) . "', " : "";

	// check to see if username is already taken
	list($valid_username, $problem) = _ft_is_valid_username($username, $account_id);
	if (!$valid_username)
		return array(false, $problem);

	$query = "
			UPDATE  {$g_table_prefix}accounts
			SET     $password_sql
			        first_name = '$first_name',
			        last_name = '$last_name',
			        email = '$email',
			        theme = '$theme',
			        login_page = '$login_page',
			        logout_url = '$logout_url',
							ui_language = '$ui_language',
							timezone_offset = '$timezone_offset',
							sessions_timeout = '$sessions_timeout',
							date_format = '$date_format',
			        username = '$username'
			WHERE   account_id = $account_id
					 ";

	mysql_query($query)
		or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: <i>$query</i>", mysql_error());

  // update the settings
  $_SESSION["ft"]["settings"] = ft_get_settings();
  $_SESSION["ft"]["account"] = ft_get_account_info($account_id);
  $_SESSION["ft"]["account"]["is_logged_in"] = true;

  if (!empty($password))
  	$_SESSION["ft"]["account"]["password"] = md5(md5($password));

  // if the password just changed, update sessions
  if (!empty($password))
  {
		$_SESSION["ft"]["account"]  = ft_get_account_info($account_id);
		$_SESSION["ft"]["account"]["is_logged_in"] = true;
		$_SESSION["ft"]["account"]["password"] = md5(md5($password));
  }

	return array($success, $message);
}


/**
 * Used by administrators to login as a client. This function moves the administrator's sessions to a
 * temporary "admin" session key, and logs the administrator in under the client account. When logging out
 * as a client, the logout function detects if it's really an administrator and erases the old client
 * sessions, replacing them with the old administrator sessions, to enable a smooth transition
 * from one account to the next.
 *
 * @param integer $client_id the client ID
 */
function ft_login_as_client($client_id)
{
	// extract the user's login info
	$client_info = ft_get_account_info($client_id);
	$info = array();
	$info["username"] = $client_info["username"];

	// move the session values to separate $_SESSION['ft']['admin'] values, so that
	// once the administrator logs out we can reset the sessions appropriately
	$current_values = $_SESSION["ft"];
	$_SESSION["ft"] = array();
	$_SESSION["ft"]["admin"] = $current_values;

	// now log in
	ft_login($info, true);
}


/**
 * Used by the administrator to logout from a client account. Resets appropriate
 * sessions values and redirects back to admin pages.
 */
function ft_logout_as_client()
{
	global $g_root_url;

	// empty old sessions and reload admin settings
	$admin_values = $_SESSION["ft"]["admin"];
	$client_id    = $_SESSION["ft"]["account"]["account_id"];
	$_SESSION["ft"] = array();

	foreach ($admin_values as $key => $value)
		$_SESSION["ft"][$key] = $value;

	unset($_SESSION["ft"]["admin"]);

	// redirect them back to the edit client page
	session_write_close();
	header("location: $g_root_url/admin/clients/edit.php?client_id=$client_id");
	exit;
}

/**
 * Creates a new client based on first and last name, and returns the new account id.
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
 *               following required keys: first_name, last_name, user_name, password.
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 *               [2]: the new user ID (if successful)
 */
function ft_add_client($infohash)
{
	global $g_table_prefix, $LANG;

	$form_vals = ft_sanitize($infohash);

	$success = true;
	$message = "";

	// validate POST fields
	$rules = array();
	$rules[] = "required,first_name,{$LANG["validation_no_client_first_name"]}";
	$rules[] = "required,last_name,{$LANG["validation_no_client_last_name"]}";
	$rules[] = "required,email,{$LANG["validation_no_client_email"]}";
	$rules[] = "required,username,{$LANG["validation_no_client_username"]}";
	$rules[] = "is_alpha,username,{$LANG["validation_invalid_client_username"]}";
	$rules[] = "required,password,{$LANG["validation_no_client_password"]}";
	$rules[] = "is_alpha,password,{$LANG["validation_invalid_client_password"]}";
	$rules[] = "same_as,password,password_2,{$LANG["validation_passwords_different"]}";
	$errors = validate_fields($form_vals, $rules);

	if (!empty($errors))
	{
		$success = false;
		array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
		$message = join("<br />", $errors);
		return array($success, $message, "");
	}

	$first_name = $form_vals["first_name"];
	$last_name  = $form_vals["last_name"];
	$email      = $form_vals["email"];
	$username   = $form_vals["username"];
	$password   = md5(md5($form_vals["password"]));

	$settings = ft_get_settings();
	$language         = $settings["default_language"];
	$timezone_offset  = $settings["default_timezone_offset"];
	$sessions_timeout = $settings["default_sessions_timeout"];
	$date_format      = $settings["default_date_format"];
	$login_page       = $settings["default_login_page"];
	$logout_url       = $settings["default_logout_url"];
	$theme            = $settings["default_theme"];
	$menu_id          = $settings["default_client_menu_id"];


	// first, insert the record into the accounts table. This contains all the settings common to ALL
	// accounts (including the administrator and any other future account types)
	$query = "
		 INSERT INTO {$g_table_prefix}accounts (account_type, account_status, ui_language, timezone_offset, sessions_timeout,
		   date_format, login_page, logout_url, theme, menu_id, first_name, last_name, email, username, password)
		 VALUES ('client', 'active', '$language', '$timezone_offset', '$sessions_timeout',
		   '$date_format', '$login_page', '$logout_url', '$theme',
		   $menu_id, '$first_name', '$last_name', '$email', '$username', '$password')";
	mysql_query($query)
		or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: <i>$query</i>", mysql_error());

	$new_user_id = mysql_insert_id();


	// now create all the custom client account settings, most of which are based on the default values
	// in the settings table
	$account_settings = array(
	  "client_notes" => "",
	  "company_name" => "",
    "page_titles"          => $settings["default_page_titles"],
	  "footer_text"          => $settings["default_footer_text"],
	  "may_edit_page_titles" => $settings["clients_may_edit_page_titles"],
	  "may_edit_footer_text" => $settings["clients_may_edit_footer_text"],
	  "may_edit_theme"       => $settings["clients_may_edit_theme"],
	  "may_edit_logout_url"  => $settings["clients_may_edit_logout_url"],
	  "may_edit_language"    => $settings["clients_may_edit_ui_language"],
	  "may_edit_timezone_offset"  => $settings["clients_may_edit_timezone_offset"],
	  "may_edit_sessions_timeout" => $settings["clients_may_edit_sessions_timeout"],
	  "may_edit_date_format"      => $settings["clients_may_edit_date_format"]
  );
  ft_set_account_settings($new_user_id, $account_settings);

	return array($success, $message, $new_user_id);
}

/**
 * Administrator function used to update a client account. It updates one tab at a time - determined by the
 * second $tab_num parameter.
 *
 * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing keys
 *               named the same as the database fields.
 * @param integer $tab_num the tab number (1-3: 1=main, 2=styles, 3=permissions)
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 */
function ft_admin_update_client($infohash, $tab_num)
{
	global $g_table_prefix, $g_debug, $LANG;

	$success = true;
	$message = $LANG["notify_client_account_updated"];

	$form_vals = ft_sanitize($infohash);
	$account_id = $form_vals['client_id'];

	switch ($tab_num)
	{
		// MAIN tab
		case "1":
			$rules = array();
			$rules[] = "required,first_name,{$LANG["validation_no_client_first_name"]}";
			$rules[] = "required,last_name,{$LANG["validation_no_client_last_name"]}";
			$rules[] = "required,email,{$LANG["validation_no_client_email"]}";
			$rules[] = "valid_email,email,{$LANG["validation_invalid_email"]}";
			$rules[] = "required,username,{$LANG["validation_no_client_username"]}";
	    $rules[] = "if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed"]}";
	    $rules[] = "if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}";
			$errors = validate_fields($form_vals, $rules);

			// check the username isn't already taken
			$username   = $form_vals['username'];
			list($valid_username, $problem) = _ft_is_valid_username($username, $account_id);
			if (!$valid_username)
				$errors[] = $problem;

			if (!empty($errors))
			{
				$success = false;
				array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
				$message = join("<br />", $errors);
				return array($success, $message);
			}

			$account_status  = $form_vals['account_status'];
			$first_name      = $form_vals['first_name'];
			$last_name       = $form_vals['last_name'];
			$email           = $form_vals['email'];
			$password        = $form_vals['password'];

	    // if the password is defined, md5 it
      $password_sql = (!empty($password)) ? "password = '" . md5(md5($password)) . "', " : "";

			$query = "
					UPDATE  {$g_table_prefix}accounts
					SET     $password_sql
					        account_status = '$account_status',
					        first_name = '$first_name',
									last_name = '$last_name',
									email = '$email',
									username = '$username'
					WHERE   account_id = $account_id
							 ";

			// execute the query
			$result = @mysql_query($query);
			if (!$result)
			{
				$success = false;
				$message = $LANG["notify_client_account_not_updated"];
				if ($g_debug) $message .= "<br/>Query: $query<br />Error: " . mysql_error();
			}

			$account_settings["client_notes"] = $form_vals["client_notes"];
			$account_settings["company_name"] = $form_vals["company_name"];
			ft_set_account_settings($account_id, $account_settings);
			break;

		// SETTINGS tab
		case "2":
			$rules = array();
			$rules[] = "required,page_titles,{$LANG["validation_no_titles"]}";
			$rules[] = "required,menu_id,{$LANG["validation_no_menu"]}";
			$rules[] = "required,theme,{$LANG["validation_no_theme"]}";
			$rules[] = "required,login_page,{$LANG["validation_no_client_login_page"]}";
			$rules[] = "required,logout_url,{$LANG["validation_no_logout_url"]}";
			$rules[] = "required,ui_language,{$LANG["validation_no_ui_language"]}";
			$rules[] = "required,sessions_timeout,{$LANG["validation_no_sessions_timeout"]}";
			$rules[] = "digits_only,sessions_timeout,{$LANG["validation_invalid_sessions_timeout"]}";
			$rules[] = "required,date_format,{$LANG["validation_no_date_format"]}";
			$errors = validate_fields($form_vals, $rules);

			if (!empty($errors))
			{
				$success = false;
				array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
				$message = join("<br />", $errors);
				return array($success, $message);
			}

			// update the main accounts table
			$ui_language      = $form_vals['ui_language'];
			$timezone_offset  = $form_vals['timezone_offset'];
			$login_page       = $form_vals['login_page'];
			$logout_url       = $form_vals['logout_url'];
			$menu_id          = $form_vals['menu_id'];
			$theme            = $form_vals['theme'];
			$sessions_timeout = $form_vals['sessions_timeout'];
			$date_format      = $form_vals['date_format'];

			$query = "
					UPDATE  {$g_table_prefix}accounts
					SET     ui_language = '$ui_language',
						      timezone_offset = '$timezone_offset',
									login_page = '$login_page',
									logout_url = '$logout_url',
									menu_id = $menu_id,
									theme = '$theme',
									sessions_timeout = '$sessions_timeout',
									date_format = '$date_format'
					WHERE   account_id = $account_id
							 ";

			// execute the query
			$result = @mysql_query($query);
			if (!$result)
			{
				$success = false;
				$message = $LANG["notify_client_account_not_updated"];
				if ($g_debug) $message .= "<br/>Query: $query<br />Error: " . mysql_error();
				return array($success, $message);
			}

		  $may_edit_page_titles      = isset($infohash["may_edit_page_titles"]) ? "yes" : "no";
		  $may_edit_footer_text      = isset($infohash["may_edit_footer_text"]) ? "yes" : "no";
		  $may_edit_theme            = isset($infohash["may_edit_theme"]) ? "yes" : "no";
		  $may_edit_logout_url       = isset($infohash["may_edit_logout_url"]) ? "yes" : "no";
		  $may_edit_language         = isset($infohash["may_edit_language"]) ? "yes" : "no";
		  $may_edit_timezone_offset  = isset($infohash["may_edit_timezone_offset"]) ? "yes" : "no";
		  $may_edit_sessions_timeout = isset($infohash["may_edit_sessions_timeout"]) ? "yes" : "no";
		  $may_edit_date_format      = isset($infohash["may_edit_date_format"]) ? "yes" : "no";

			// update the client custom account settings table
			$settings = array(
			  "page_titles" => $form_vals["page_titles"],
			  "footer_text" => $form_vals["footer_text"],
				"may_edit_page_titles" => $may_edit_page_titles,
				"may_edit_footer_text" => $may_edit_footer_text,
				"may_edit_theme"       => $may_edit_theme,
				"may_edit_logout_url"  => $may_edit_logout_url,
				"may_edit_language"    => $may_edit_language,
				"may_edit_timezone_offset"  => $may_edit_timezone_offset,
				"may_edit_sessions_timeout" => $may_edit_sessions_timeout
			);
			ft_set_account_settings($account_id, $settings);
			break;

		// FORMS tab
		case "3":

			// clear out the old mappings for the client-forms and client-Views. This section re-inserts everything
			mysql_query("DELETE FROM {$g_table_prefix}client_forms WHERE account_id = $account_id");
			mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE account_id = $account_id");
      mysql_query("DELETE FROM {$g_table_prefix}public_form_omit_list WHERE account_id = $account_id");
      mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE account_id = $account_id");

      $num_form_rows = $infohash["num_forms"];
      $client_forms = array(); // stores the form IDs of all forms this client has been added to
      $client_form_views = array(); // stores the view IDs of each form this client is associated with
      for ($i=1; $i<=$num_form_rows; $i++)
      {
      	// ignore blank and empty form rows
      	if (!isset($infohash["form_row_{$i}"]) || empty($infohash["form_row_{$i}"]))
      	  continue;

      	$form_id = $infohash["form_row_{$i}"];
      	$client_forms[] = $form_id;
      	$client_form_views[$form_id] = array();

      	// find out a little info about this form. If it's a public form, the user is automatically assigned
      	// to it, so don't bother inserting a record into the client_forms table
      	$form_info_query = mysql_query("SELECT access_type FROM {$g_table_prefix}forms WHERE form_id = $form_id");
      	$form_info = mysql_fetch_assoc($form_info_query);

      	if ($form_info["access_type"] != "public")
      		mysql_query("INSERT INTO {$g_table_prefix}client_forms (account_id, form_id) VALUES ($account_id, $form_id)");

      	// now loop through selected Views. Get View info
      	if (!isset($infohash["row_{$i}_selected_views"]))
      	  continue;

      	$client_form_views[$form_id] = $infohash["row_{$i}_selected_views"];

      	foreach ($infohash["row_{$i}_selected_views"] as $view_id)
      	{
          $view_info_query = mysql_query("SELECT access_type FROM {$g_table_prefix}views WHERE view_id = $view_id");
      	  $view_info = mysql_fetch_assoc($view_info_query);

      	  if ($view_info["access_type"] != "public")
	      		mysql_query("INSERT INTO {$g_table_prefix}client_views (account_id, view_id) VALUES ($account_id, $view_id)");

	        // if this View was previously an "admin" type, it no longer is! By adding this client to the View, it's now
	        // changed to a "private" access type
	        if ($view_info["access_type"] == "admin")
            mysql_query("UPDATE {$g_table_prefix}views SET access_type = 'private' WHERE view_id = $view_id");
      	}
      }

      // now all the ADDING the forms/Views is done, we look at all other public forms in the database and if this
      // update request didn't include that form, add this client to its omit list. Same goes for the form Views
      $public_form_query = mysql_query("SELECT form_id, access_type FROM {$g_table_prefix}forms");
      while ($form_info = mysql_fetch_assoc($public_form_query))
      {
      	$form_id        = $form_info["form_id"];
      	$form_is_public = ($form_info["access_type"] == "public") ? true : false;

      	if ($form_is_public && !in_array($form_id, $client_forms))
      		mysql_query("INSERT INTO {$g_table_prefix}public_form_omit_list (account_id, form_id) VALUES ($account_id, $form_id)");

      	if (in_array($form_id, $client_forms))
      	{
      		$public_view_query = mysql_query("SELECT view_id, access_type FROM {$g_table_prefix}views WHERE form_id = $form_id");

					while ($view_info = mysql_fetch_assoc($public_view_query))
		      {
		      	$view_id        = $view_info["view_id"];
		      	$view_is_public = ($view_info["access_type"] == "public") ? true : false;

		      	if ($view_is_public && !in_array($view_id, $client_form_views[$form_id]))
		      		mysql_query("INSERT INTO {$g_table_prefix}public_view_omit_list (account_id, view_id) VALUES ($account_id, $view_id)");
		      }
      	}
      }
			break;
	}

	return array($success, $message);
}