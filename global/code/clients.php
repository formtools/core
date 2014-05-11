<?php

/**
 * This file defines all user account functions used by the client accounts. Also see accounts.php (for
 * general functions) and administrator.php for functions used by administrator accounts.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Clients
 */


// -------------------------------------------------------------------------------------------------


/**
 * Updates a client account. Used for whomever is currently logged in.
 *
 * @param array $info This parameter should be a hash (e.g. $_POST or $_GET) containing keys
 *               named the same as the database fields.
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 */
function ft_update_client($account_id, $info)
{
  global $g_table_prefix, $LANG, $g_password_special_chars;

  $success = true;
  $message = $LANG["notify_account_updated"];
  $info = ft_sanitize($info);

  extract(ft_process_hook_calls("start", compact("account_id", "info"), array("info")), EXTR_OVERWRITE);

  $client_info = ft_get_account_info($account_id);

  $page = $info["page"];
  switch ($page)
  {
    case "main":
      $first_name   = $info["first_name"];
      $last_name    = $info["last_name"];
      $email        = $info["email"];
      $username     = $info["username"];

      $password_clause = "";
      $rules           = array();
      if (!empty($info["password"]))
      {
        $required_password_chars = explode(",", $client_info["settings"]["required_password_chars"]);
        if (in_array("uppercase", $required_password_chars))
          $rules[] = "reg_exp,password,[A-Z],{$LANG["validation_client_password_missing_uppercase"]}";
        if (in_array("number", $required_password_chars))
          $rules[] = "reg_exp,password,[0-9],{$LANG["validation_client_password_missing_number"]}";
        if (in_array("special_char", $required_password_chars))
        {
          $error = ft_eval_smarty_string($LANG["validation_client_password_missing_special_char"], array("chars" => $g_password_special_chars));
          $password_special_chars = preg_quote($g_password_special_chars);
          $rules[] = "reg_exp,password,[$password_special_chars],$error";
        }
        if (!empty($client_info["settings"]["min_password_length"]))
        {
          $rule = ft_eval_smarty_string($LANG["validation_client_password_too_short"], array("number" => $client_info["settings"]["min_password_length"]));
          $rules[] = "length>={$client_info["settings"]["min_password_length"]},password,$rule";
        }

        // encrypt the password on the assumption that it passes validation. It'll be used in the update query
        $password = md5(md5($info['password']));
        $password_clause = "password = '$password',";
      }

      $errors = validate_fields($info, $rules);

      // check to see if username is already taken
      list($valid_username, $problem) = _ft_is_valid_username($username, $account_id);
      if (!$valid_username)
        $errors[] = $problem;

      // check the password isn't already in password history (if relevant)
      if (!empty($info["password"]))
      {
        if (!empty($client_info["settings"]["num_password_history"]))
        {
          $encrypted_password = md5(md5($info["password"]));
          if (ft_password_in_password_history($account_id, $encrypted_password, $client_info["settings"]["num_password_history"]))
            $errors[] = ft_eval_smarty_string($LANG["validation_password_in_password_history"], array("history_size" => $client_info["settings"]["num_password_history"]));
          else
            ft_add_password_to_password_history($account_id, $encrypted_password);
        }
      }

      if (!empty($errors))
      {
        $success = false;
        array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
        $message = implode("<br />", $errors);
        return array($success, $message);
      }

      $query = "
          UPDATE  {$g_table_prefix}accounts
          SET     $password_clause
                  first_name = '$first_name',
                  last_name = '$last_name',
                  username = '$username',
                  email = '$email'
          WHERE   account_id = $account_id
               ";
      if (mysql_query($query))
      {
        // if the password wasn't empty, reset the temporary password, in case it was set
        if (!empty($info["password"]))
          mysql_query("UPDATE {$g_table_prefix}accounts SET temp_reset_password = NULL where account_id = $account_id");
      }
      else {
        ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: <i>$query</i>", mysql_error());
      }
      break;

    case "settings":
      $rules = array();
      if ($client_info["settings"]["may_edit_page_titles"] == "yes")
        $rules[] = "required,page_titles,{$LANG["validation_no_titles"]}";
      if ($client_info["settings"]["may_edit_theme"] == "yes")
        $rules[] = "required,theme,{$LANG["validation_no_theme"]}";
      if ($client_info["settings"]["may_edit_logout_url"] == "yes")
        $rules[] = "required,logout_url,{$LANG["validation_no_logout_url"]}";
      if ($client_info["settings"]["may_edit_language"] == "yes")
        $rules[] = "required,ui_language,{$LANG["validation_no_ui_language"]}";
      if ($client_info["settings"]["may_edit_timezone_offset"] == "yes")
        $rules[] = "required,timezone_offset,{$LANG["validation_no_timezone_offset"]}";
      if ($client_info["settings"]["may_edit_sessions_timeout"] == "yes")
      {
        $rules[] = "required,sessions_timeout,{$LANG["validation_no_sessions_timeout"]}";
        $rules[] = "digits_only,sessions_timeout,{$LANG["validation_invalid_sessions_timeout"]}";
      }
      if ($client_info["settings"]["may_edit_date_format"] == "yes")
        $rules[] = "required,date_format,{$LANG["validation_no_date_format"]}";

      $errors = validate_fields($info, $rules);

      if (!empty($errors))
      {
        $success = false;
        array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
        $message = implode("<br />", $errors);
        return array($success, $message);
      }

      // update the main accounts table. Only update those settings they're ALLOWED to
      $settings = array();
      if ($client_info["settings"]["may_edit_language"] == "yes")
        $settings["ui_language"] = $info["ui_language"];
      if ($client_info["settings"]["may_edit_timezone_offset"] == "yes")
        $settings["timezone_offset"] = $info["timezone_offset"];
      if ($client_info["settings"]["may_edit_logout_url"] == "yes")
        $settings["logout_url"] = $info["logout_url"];
      if ($client_info["settings"]["may_edit_sessions_timeout"] == "yes")
        $settings["sessions_timeout"] = $info["sessions_timeout"];
      if ($client_info["settings"]["may_edit_theme"] == "yes")
      {
        $settings["theme"] = $info["theme"];
        $settings["swatch"] = "";
        if (isset($info["{$info["theme"]}_theme_swatches"]))
          $settings["swatch"] = $info["{$info["theme"]}_theme_swatches"];
      }
      if ($client_info["settings"]["may_edit_date_format"] == "yes")
        $settings["date_format"] = $info["date_format"];

      if (!empty($settings))
      {
        $sql_rows = array();
        while (list($column, $value) = each($settings))
          $sql_rows[] = "$column = '$value'";

        $sql = implode(",\n", $sql_rows);
        $query = "
            UPDATE  {$g_table_prefix}accounts
            SET     $sql
            WHERE   account_id = $account_id
                 ";
        mysql_query($query)
          or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: <i>$query</i>", mysql_error());
      }

      $settings = array();
      if (isset($info["page_titles"]))
        $settings["page_titles"] = $info["page_titles"];
      if (isset($info["footer_text"]))
        $settings["footer_text"] = $info["footer_text"];
      if (isset($info["max_failed_login_attempts"]))
        $settings["max_failed_login_attempts"] = $info["max_failed_login_attempts"];

      if (!empty($settings))
      {
        ft_set_account_settings($account_id, $settings);
      }
      break;
  }

  extract(ft_process_hook_calls("end", compact("account_id", "info"), array("success", "message")), EXTR_OVERWRITE);

  // update sessions
  $_SESSION["ft"]["settings"] = ft_get_settings();
  $_SESSION["ft"]["account"]  = ft_get_account_info($account_id);
  $_SESSION["ft"]["account"]["is_logged_in"] = true;

  return array($success, $message);
}


/**
 * Completely removes a client account from the database, including any email-related stuff that requires
 * their user account.
 *
 * @param integer $account_id the unique account ID
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 */
function ft_delete_client($account_id)
{
  global $g_table_prefix, $LANG;

  mysql_query("DELETE FROM {$g_table_prefix}accounts WHERE account_id = $account_id");
  mysql_query("DELETE FROM {$g_table_prefix}account_settings WHERE account_id = $account_id");
  mysql_query("DELETE FROM {$g_table_prefix}client_forms WHERE account_id = $account_id");
  mysql_query("DELETE FROM {$g_table_prefix}email_template_recipients WHERE account_id = $account_id");
  mysql_query("DELETE FROM {$g_table_prefix}email_templates WHERE email_from account_id = $account_id OR email_to_account_id = $account_id");
  mysql_query("DELETE FROM {$g_table_prefix}public_form_omit_list WHERE account_id = $account_id");
  mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE account_id = $account_id");

  $success = true;
  $message = $LANG["notify_account_deleted"];
  extract(ft_process_hook_calls("end", compact("account_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Simple helper function to disable a client account.
 *
 * @param integer $account_id
 */
function ft_disable_client($account_id)
{
  global $g_table_prefix;

  $account_id = ft_sanitize($account_id);

  if (empty($account_id) || !is_numeric($account_id))
    return;

  mysql_query("UPDATE {$g_table_prefix}accounts SET account_status = 'disabled' WHERE account_id = $account_id");

  extract(ft_process_hook_calls("end", compact("account_id"), array()), EXTR_OVERWRITE);
}


/**
 * Retrieves a list of all clients in the database ordered by last name. N.B. As of 2.0.0, this function
 * no longer returns a MySQL resource.
 *
 * @return array $clients an array of hashes. Each hash is the client info.
 */
function ft_get_client_list()
{
  global $g_table_prefix;

  $query = "SELECT * FROM {$g_table_prefix}accounts WHERE account_type = 'client' ORDER BY last_name";

  $result = mysql_query($query)
    or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>: <i>$query</i>", mysql_error());

  $clients = array();
  while ($client = mysql_fetch_assoc($result))
    $clients[] = $client;

  return $clients;
}


/**
 * Returns the total number of clients in the database.
 *
 * @return int the number of clients
 */
function ft_get_client_count()
{
  global $g_table_prefix;

  $query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}accounts WHERE account_type = 'client'");
  $result = mysql_fetch_assoc($query);

  return $result["c"];
}


/**
 * Performs a simple search of the client list, returning ALL results (not in pages).
 *
 * @param array $search_criteria optional search / sort criteria. Keys are:
 *                               "order" - (string) client_id-ASC, client_id-DESC, last_name-DESC,
 *                                         last_name-ASC, email-ASC, email-DESC
 *                               "keyword" - (string) searches the client name and email fields.
 *                               "status" - (string) "account_status", "disabled", or empty (all)
 */
function ft_search_clients($search_criteria = array())
{
  global $g_table_prefix;

  extract(ft_process_hook_calls("start", compact("search_criteria"), array("search_criteria")), EXTR_OVERWRITE);

  if (!isset($search_criteria["order"]))
    $search_criteria["order"] = "client_id-DESC";

  $order_clause = _ft_get_client_order_clause($search_criteria["order"]);

  $status_clause = "";
  if (isset($search_criteria["status"]))
  {
    switch ($search_criteria["status"])
    {
      case "active":
        $status_clause = "account_status = 'active' ";
        break;
      case "disabled":
        $status_clause = "account_status = 'disabled'";
        break;
      case "pending":
        $status_clause = "account_status = 'pending'";
        break;
      default:
        $status_clause = "";
        break;
    }
  }

  $keyword_clause = "";
  if (isset($search_criteria["keyword"]) && !empty($search_criteria["keyword"]))
  {
    $string = ft_sanitize($search_criteria["keyword"]);
    $fields = array("last_name", "first_name", "email", "account_id");

    $clauses = array();
    foreach ($fields as $field)
      $clauses[] = "$field LIKE '%$string%'";

    $keyword_clause = implode(" OR ", $clauses);
  }

  // add up the where clauses
  $where_clauses = array("account_type = 'client'");
  if (!empty($status_clause)) $where_clauses[] = "($status_clause)";
  if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";

  $where_clause = "WHERE " . implode(" AND ", $where_clauses);

  // get the clients
  $client_query_result = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}accounts
    $where_clause
    $order_clause
           ");

  $clients = array();
  while ($client = mysql_fetch_assoc($client_query_result))
    $clients[] = $client;

  extract(ft_process_hook_calls("end", compact("search_criteria", "clients"), array("clients")), EXTR_OVERWRITE);

  return $clients;
}


/**
 * This returns the IDs of the previous and next client accounts, as determined by the administrators current
 * search and sort.
 *
 * Not happy with this function! Getting this info is surprisingly tricky, once you throw in the sort clause.
 * Still, the number of client accounts are liable to be quite small, so it's not such a sin.
 *
 * @param integer $account_id
 * @param array $search_criteria
 * @return hash prev_account_id => the previous account ID (or empty string)
 *              next_account_id => the next account ID (or empty string)
 */
function ft_get_client_prev_next_links($account_id, $search_criteria = array())
{
  global $g_table_prefix;

  $keyword_clause = "";
  if (isset($search_criteria["keyword"]) && !empty($search_criteria["keyword"]))
  {
    $string = ft_sanitize($search_criteria["keyword"]);
    $fields = array("last_name", "first_name", "email", "account_id");

    $clauses = array();
    foreach ($fields as $field)
      $clauses[] = "$field LIKE '%$string%'";

    $keyword_clause = implode(" OR ", $clauses);
  }

  // add up the where clauses
  $where_clauses = array("account_type = 'client'");
  if (!empty($status_clause)) $where_clauses[] = "($status_clause)";
  if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";

  $where_clause = "WHERE " . implode(" AND ", $where_clauses);

  $order_clause = _ft_get_client_order_clause($search_criteria["order"]);

  // get the clients
  $client_query_result = mysql_query("
    SELECT account_id
    FROM   {$g_table_prefix}accounts
    $where_clause
    $order_clause
           ");

  $sorted_account_ids = array();
  while ($row = mysql_fetch_assoc($client_query_result))
  {
    $sorted_account_ids[] = $row["account_id"];
  }
  $current_index = array_search($account_id, $sorted_account_ids);

  $return_info = array("prev_account_id" => "", "next_account_id" => "");
  if ($current_index === 0)
  {
    if (count($sorted_account_ids) > 1)
      $return_info["next_account_id"] = $sorted_account_ids[$current_index+1];
  }
  else if ($current_index === count($sorted_account_ids)-1)
  {
    if (count($sorted_account_ids) > 1)
      $return_info["prev_account_id"] = $sorted_account_ids[$current_index-1];
  }
  else
  {
    $return_info["prev_account_id"] = $sorted_account_ids[$current_index-1];
    $return_info["next_account_id"] = $sorted_account_ids[$current_index+1];
  }

  return $return_info;
}


/**
 * Basically a wrapper function for ft_search_forms.
 *
 * @return array
 */
function ft_get_client_forms($account_id)
{
  return ft_search_forms($account_id, true);
}


/**
 * This returns all forms and form Views that a client account may access.
 *
 * @param array $account_id
 */
function ft_get_client_form_views($account_id)
{
  $client_forms = ft_search_forms($account_id);

  $info = array();
  foreach ($client_forms as $form_info)
  {
    $form_id = $form_info["form_id"];
    $views = ft_get_form_views($form_id, $account_id);

    $view_ids = array();
    foreach ($views as $view_info)
      $view_ids[] = $view_info["view_id"];

    $info[$form_id] = $view_ids;
  }

  extract(ft_process_hook_calls("end", compact("account_id", "info"), array("info")), EXTR_OVERWRITE);

  return $info;
}


/**
 * This function updates the default theme for multiple accounts simultaneously. It's called when
 * an administrator disables a theme that's current used by some client accounts. They're presented with
 * the option of setting the theme ID for all the clients.
 *
 * There's very little error checking done here...
 *
 * @param string $account_id_str a comma delimited list of account IDs
 * @param integer $theme_id the theme ID
 */
function ft_update_client_themes($account_ids, $theme_id)
{
  global $LANG, $g_table_prefix;

  if (empty($account_ids) || empty($theme_id))
    return;

  $client_ids = explode(",", $account_ids);

  $theme_info = ft_get_theme($theme_id);
  $theme_name = $theme_info["theme_name"];
  $theme_folder = $theme_info["theme_folder"];

  foreach ($client_ids as $client_id)
    mysql_query("UPDATE {$g_table_prefix}accounts SET theme='$theme_folder' WHERE account_id = $client_id");

  $placeholders = array("theme" => $theme_name);
  $message = ft_eval_smarty_string($LANG["notify_client_account_themes_updated"], $placeholders);
  $success = true;

  return array($success, $message);
}


/**
 * Used in a couple of places, so I stuck it here. (Refactor this hideousness!)
 *
 * @param string $order
 * @return string the ORDER BY clause
 */
function _ft_get_client_order_clause($order = "")
{
  $order_clause = "";
  switch ($order)
  {
    case "client_id-DESC":
      $order_clause = "account_id DESC";
      break;
    case "client_id-ASC":
      $order_clause = "account_id ASC";
      break;
    case "first_name-DESC":
      $order_clause = "first_name DESC";
      break;
    case "first_name-ASC":
      $order_clause = "first_name ASC";
      break;
    case "last_name-DESC":
      $order_clause = "last_name DESC";
      break;
    case "last_name-ASC":
      $order_clause = "last_name ASC";
      break;
    case "email-DESC":
      $order_clause = "email DESC";
      break;
    case "email-ASC":
      $order_clause = "email ASC";
      break;
    case "status-DESC":
      $order_clause = "account_status DESC";
      break;
    case "status-ASC":
      $order_clause = "account_status ASC";
      break;
    case "last_logged_in-DESC":
      $order_clause = "last_logged_in DESC";
      break;
    case "last_logged_in-ASC":
      $order_clause = "last_logged_in ASC";
      break;

    default:
      $order_clause = "account_id DESC";
      break;
  }

  if (!empty($order_clause))
    $order_clause = "ORDER BY $order_clause";

  return $order_clause;
}
