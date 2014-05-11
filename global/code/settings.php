<?php

/**
 * This file defines all functions for managing the global program settings. The location of the code
 * for the various areas of the Settings pages is a little hodge podge; e.g. the menus update code is
 * in menus.php.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Settings
 */


// -------------------------------------------------------------------------------------------------


/**
 * A generic function used for retrieving values from the settings table.
 *
 * - if $settings param empty, it returns only the core settings
 * - if $settings param is a string, returns only that single setting value
 * - if $settings param is an array of setting names, returns only those setting values
 * - if $module param is included, it filters the results to only those settings for that particular
 *   module
 *
 * Tip: to only return the core (non-module) Form Tools settings, pass "core" as the module param
 * value.
 *
 * @param mixed $settings the setting(s) to return
 * @param string $module the name of the module to which these settings belong
 * @return array a hash of all settings.
 */
function ft_get_settings($settings = "", $module = "")
{
  global $g_table_prefix;

  $where_module_clause =  (!empty($module)) ? "WHERE module = '$module'" : "";
  $and_module_clause   =  (!empty($module)) ? "AND module = '$module'" : "";

  $return_val = "";
  if (empty($settings))
  {
    $query = mysql_query("
      SELECT setting_name, setting_value
      FROM   {$g_table_prefix}settings
      $where_module_clause
        ");
    $return_val = array();
    while ($row = mysql_fetch_assoc($query))
      $return_val[$row['setting_name']] = $row['setting_value'];
  }
  else if (is_string($settings))
  {
    $query = mysql_query("
      SELECT setting_value
      FROM   {$g_table_prefix}settings
      WHERE  setting_name = '$settings'
      $and_module_clause
        ");
    $info = mysql_fetch_assoc($query);
    $return_val = $info["setting_value"];
  }
  else if (is_array($settings))
  {
    $return_val = array();
    foreach ($settings as $setting)
    {
      $query = mysql_query("
        SELECT setting_value
        FROM   {$g_table_prefix}settings
        WHERE   setting_name = '$setting'
        $and_module_clause
          ");
      $info = mysql_fetch_assoc($query);
      $return_val[$setting] = $info["setting_value"];
    }
  }

  return $return_val;
}


/**
 * Updates some setting values. If the setting doesn't exist, it creates it. In addition,
 * it updates the value(s) in the current user's sessions.
 *
 * @param array $settings a hash of setting name => setting value
 * @param string $module the module name
 */
function ft_set_settings($settings, $module = "")
{
  global $g_table_prefix;

  $and_module_clause = (!empty($module)) ? "AND module = '$module'" : "";

  while (list($setting_name, $setting_value) = each($settings))
  {
    // find out if it already exists
    $result = mysql_query("
      SELECT count(*) as c
      FROM   {$g_table_prefix}settings
      WHERE  setting_name = '$setting_name'
      $and_module_clause
        ");
    $info = mysql_fetch_assoc($result);

    if ($info["c"] == 0)
    {
      if (!empty($module))
      {
        mysql_query("
          INSERT INTO {$g_table_prefix}settings (setting_name, setting_value, module)
          VALUES ('$setting_name', '$setting_value', '$module')
            ");
      }
      else
      {
        mysql_query("
          INSERT INTO {$g_table_prefix}settings (setting_name, setting_value)
          VALUES ('$setting_name', '$setting_value')
            ");
      }
    }
    else
    {
      mysql_query("
        UPDATE {$g_table_prefix}settings
        SET    setting_value = '$setting_value'
        WHERE  setting_name  = '$setting_name'
        $and_module_clause
          ");
    }

    // hmm... TODO. This looks suspiciously like a bug... [a module could overwrite a core var]
    $_SESSION["ft"]["settings"][$setting_name] = $setting_value;
  }
}


/**
 * Called by administrators; updates the main settings.
 *
 * @param array $infohash this parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the main settings admin page.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_main_settings($infohash)
{
  global $g_table_prefix, $g_root_url, $LANG;

  $success = true;
  $message = $LANG["notify_setup_options_updated"];

  $infohash = ft_sanitize($infohash);

  $rules = array();
  $rules[] = "required,program_name,{$LANG["validation_no_program_name"]}";
  $rules[] = "required,num_clients_per_page,{$LANG["validation_no_num_clients_per_page"]}";
  $rules[] = "digits_only,num_clients_per_page,{$LANG["validation_invalid_num_clients_per_page"]}";
  $rules[] = "required,num_emails_per_page,{$LANG["validation_no_num_emails_per_page"]}";
  $rules[] = "digits_only,num_emails_per_page,{$LANG["validation_invalid_num_emails_per_page"]}";
  $rules[] = "required,num_forms_per_page,{$LANG["validation_no_num_forms_per_page"]}";
  $rules[] = "digits_only,num_forms_per_page,{$LANG["validation_invalid_num_forms_per_page"]}";
  $rules[] = "required,num_option_lists_per_page,{$LANG["validation_no_num_option_lists_per_page"]}";
  $rules[] = "digits_only,num_option_lists_per_page,{$LANG["validation_invalid_num_option_lists_per_page"]}";
  $rules[] = "required,num_menus_per_page,{$LANG["validation_no_num_menus_per_page"]}";
  $rules[] = "digits_only,num_menus_per_page,{$LANG["validation_invalid_num_menus_per_page"]}";
  $rules[] = "required,num_modules_per_page,{$LANG["validation_no_num_modules_per_page"]}";
  $rules[] = "digits_only,num_modules_per_page,{$LANG["validation_invalid_num_modules_per_page"]}";
  $errors = validate_fields($infohash, $rules);

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array ($success, $message, "");
  }

  $settings = array(
    "program_name"              => trim($infohash["program_name"]),
    "logo_link"                 => trim($infohash["logo_link"]),
    "num_clients_per_page"      => trim($infohash["num_clients_per_page"]),
    "num_emails_per_page"       => trim($infohash["num_emails_per_page"]),
    "num_forms_per_page"        => trim($infohash["num_forms_per_page"]),
    "num_option_lists_per_page" => trim($infohash["num_option_lists_per_page"]),
    "num_menus_per_page"        => trim($infohash["num_menus_per_page"]),
    "num_modules_per_page"      => trim($infohash["num_modules_per_page"]),
    "default_date_field_search_value" => $infohash["default_date_field_search_value"]
  );

  ft_set_settings($settings);

  extract(ft_process_hook_calls("end", compact("settings"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Called by administrators; updates the default user account settings.
 *
 * @param array $infohash this parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the main settings admin page.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_account_settings($infohash)
{
  global $g_table_prefix, $g_root_url, $LANG;

  $success = true;
  $message = $LANG["notify_setup_options_updated"];

  $infohash = ft_sanitize($infohash);

  $rules = array();
  $rules[] = "required,default_page_titles,{$LANG["validation_no_page_titles"]}";
  $rules[] = "required,default_client_menu_id,{$LANG["validation_no_menu_id"]}";
  $rules[] = "required,default_theme,{$LANG["validation_no_theme"]}";
  $rules[] = "required,default_login_page,{$LANG["validation_no_login_page"]}";
  $rules[] = "required,default_logout_url,{$LANG["validation_no_logout_url"]}";
  $rules[] = "required,default_language,{$LANG["validation_no_default_language"]}";
  $rules[] = "required,default_sessions_timeout,{$LANG["validation_no_default_sessions_timeout"]}";
  $rules[] = "digits_only,default_sessions_timeout,{$LANG["validation_invalid_default_sessions_timeout"]}";
  $rules[] = "required,default_date_format,{$LANG["validation_no_date_format"]}";
  $errors = validate_fields($infohash, $rules);

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array ($success, $message, "");
  }

  $clients_may_edit_page_titles      = isset($infohash["clients_may_edit_page_titles"]) ? "yes" : "no";
  $clients_may_edit_footer_text      = isset($infohash["clients_may_edit_footer_text"]) ? "yes" : "no";
  $clients_may_edit_theme            = isset($infohash["clients_may_edit_theme"]) ? "yes" : "no";
  $clients_may_edit_logout_url       = isset($infohash["clients_may_edit_logout_url"]) ? "yes" : "no";
  $clients_may_edit_ui_language      = isset($infohash["clients_may_edit_ui_language"]) ? "yes" : "no";
  $clients_may_edit_timezone_offset  = isset($infohash["clients_may_edit_timezone_offset"]) ? "yes" : "no";
  $clients_may_edit_sessions_timeout = isset($infohash["clients_may_edit_sessions_timeout"]) ? "yes" : "no";
  $clients_may_edit_date_format      = isset($infohash["clients_may_edit_date_format"]) ? "yes" : "no";
  $clients_may_edit_max_failed_login_attempts = isset($infohash["clients_may_edit_max_failed_login_attempts"]) ? "yes" : "no";

  $required_password_chars = "";
  if (isset($infohash["required_password_chars"]) && is_array($infohash["required_password_chars"]))
    $required_password_chars = implode(",", $infohash["required_password_chars"]);

  $default_theme = $infohash["default_theme"];
  $default_client_swatch = "";
  if (isset($infohash["{$default_theme}_default_theme_swatches"]))
    $default_client_swatch = $infohash["{$default_theme}_default_theme_swatches"];

  $settings = array(
    "default_page_titles"          => $infohash["default_page_titles"],
    "default_footer_text"          => $infohash["default_footer_text"],
    "default_client_menu_id"       => $infohash["default_client_menu_id"],
    "default_theme"                => $default_theme,
    "default_client_swatch"        => $default_client_swatch,
    "default_login_page"           => $infohash["default_login_page"],
    "default_logout_url"           => $infohash["default_logout_url"],
    "default_language"             => $infohash["default_language"],
    "default_timezone_offset"      => $infohash["default_timezone_offset"],
    "default_sessions_timeout"     => $infohash["default_sessions_timeout"],
    "default_date_format"          => $infohash["default_date_format"],
    "forms_page_default_message"   => $infohash["forms_page_default_message"],
    "clients_may_edit_page_titles" => $clients_may_edit_page_titles,
    "clients_may_edit_footer_text" => $clients_may_edit_footer_text,
    "clients_may_edit_theme"       => $clients_may_edit_theme,
    "clients_may_edit_logout_url"  => $clients_may_edit_logout_url,
    "clients_may_edit_ui_language" => $clients_may_edit_ui_language,
    "clients_may_edit_timezone_offset"  => $clients_may_edit_timezone_offset,
    "clients_may_edit_sessions_timeout" => $clients_may_edit_sessions_timeout,
    "clients_may_edit_date_format"      => $clients_may_edit_date_format,

    // security settings
    "default_max_failed_login_attempts" => $infohash["default_max_failed_login_attempts"],
    "min_password_length"               => $infohash["min_password_length"],
    "required_password_chars"           => $required_password_chars,
    "num_password_history"              => $infohash["num_password_history"],
    "clients_may_edit_max_failed_login_attempts" => $clients_may_edit_max_failed_login_attempts
  );

  ft_set_settings($settings);

  extract(ft_process_hook_calls("end", compact("settings"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Called by administrators; updates the default user account settings.
 *
 * @param array $infohash this parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the main settings admin page.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_file_settings($infohash)
{
  global $g_table_prefix, $g_root_url, $LANG;

  $success = true;
  $message = $LANG["notify_setup_options_updated"];

  $original_file_upload_dir = $infohash["original_file_upload_dir"];
  $file_upload_dir = rtrim(trim($infohash["file_upload_dir"]), "/\\");
  $file_upload_url = rtrim(trim($infohash["file_upload_url"]), "/\\");
  $file_upload_max_size = $infohash["file_upload_max_size"];

  $file_upload_filetypes = (is_array($infohash["file_upload_filetypes"])) ? join(",", $infohash["file_upload_filetypes"]) : "";
  if (!empty($infohash["file_upload_filetypes_other"]))
  {
    if (empty($file_upload_filetypes))
      $file_upload_filetypes = $infohash["file_upload_filetypes_other"];
    else
      $file_upload_filetypes .= ",{$infohash["file_upload_filetypes_other"]}";
  }
  $file_upload_filetypes = mb_strtolower($file_upload_filetypes);

  $settings = array(
    "file_upload_dir" => $file_upload_dir,
    "file_upload_url" => $file_upload_url,
    "file_upload_max_size" => $file_upload_max_size,
    "file_upload_filetypes" => $file_upload_filetypes
  );

  ft_set_settings($settings);

  // check the folder was valid
  list($is_valid_folder, $folder_message) = ft_check_upload_folder($file_upload_dir);
  if (!$is_valid_folder)
    return array($is_valid_folder, $folder_message);

  extract(ft_process_hook_calls("end", compact("infohash"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Called by the administrator from the Themes settings page. It updates the list of enabled
 * themes, and which theme is assigned to the administrator and (default) client accounts. Note:
 * it doesn't disable any themes that are already assigned to a user account. If that happens,
 * it returns a message listing the accounts (each clickable) and an option to bulk assign them
 * to a different theme.
 *
 * @param array $infohash this parameter should be a hash (e.g. $_POST or $_GET) containing the
 *             various fields from the main settings admin page.
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_theme_settings($infohash)
{
  global $g_table_prefix, $g_root_url, $g_root_dir, $LANG;

  // lots to validate! First, check the default admin & client themes have been entered
  $rules = array();
  $rules[] = "required,admin_theme,{$LANG["validation_no_admin_theme"]}";
  $rules[] = "required,default_client_theme,{$LANG["validation_no_default_client_theme"]}";
  $errors = validate_fields($infohash, $rules);

  if (!isset($infohash["is_enabled"]))
    $errors[] = $LANG["validation_no_enabled_themes"];

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array($success, $message);
  }

  $enabled_themes = $infohash["is_enabled"];

  // next, check that both the admin and default client themes are enabled
  $admin_theme          = $infohash["admin_theme"];
  $default_client_theme = $infohash["default_client_theme"];

  if (!in_array($admin_theme, $enabled_themes) || !in_array($default_client_theme, $enabled_themes))
    return array(false, $LANG["validation_default_admin_and_client_themes_not_enabled"]);

  // lastly, if there are already client accounts assigned to disabled themes, we need to sort it out.
  // We handle it the same way as deleting the client menus: if anyone is assigned to this theme,
  // we generate a list of their names, each a link to their account page (in a _blank link). We
  // then inform the user of what's going on, and underneath the name list, give them the option of
  // assigning ALL affected accounts to another (enabled) theme.
  $theme_clauses = array();
  foreach ($enabled_themes as $theme)
    $theme_clauses[] = "theme != '$theme'";
  $theme_clause = join(" AND ", $theme_clauses);

  $query = mysql_query("
    SELECT account_id, first_name, last_name
    FROM   {$g_table_prefix}accounts
    WHERE  $theme_clause
  ");

  $client_info = array();
  while ($row = mysql_fetch_assoc($query))
    $client_info[] = $row;

  if (!empty($client_info))
  {
    $message = $LANG["notify_disabled_theme_already_assigned"];
    $placeholder_str = $LANG["phrase_assign_all_listed_client_accounts_to_theme"];

    $themes = ft_get_themes(true);
    $dd = "<select id=\"mass_update_client_theme\">";

    foreach ($themes as $theme)
      $dd .= "<option value=\"{$theme["theme_id"]}\">{$theme["theme_name"]}</option>";
    $dd .= "</select>";

    // a bit bad (hardcoded HTML!), but organize the account list in 3 columns
    $client_links_table = "<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\n<tr>";
    $num_affected_clients = count($client_info);
    for ($i=0; $i<$num_affected_clients; $i++)
    {
      $account_info = $client_info[$i];
      $client_id  = $account_info["account_id"];
      $first_name = $account_info["first_name"];
      $last_name  = $account_info["last_name"];
      $client_ids[] = $client_id;

      if ($i != 0 && $i % 3 == 0)
        $client_links_table .= "</tr>\n<tr>";

      $client_links_table .= "<td width=\"33%\">&bull;&nbsp;<a href=\"$g_root_url/admin/clients/edit.php?page=settings&client_id=$client_id\" target=\"_blank\">$first_name $last_name</a></td>\n";
    }
    $client_id_str = join(",", $client_ids);

    // close the table
    if ($num_affected_clients % 3 == 1)
      $client_links_table .= "<td colspan=\"2\" width=\"66%\"> </td>";
    else if ($num_affected_clients % 3 == 2)
      $client_links_table .= "<td width=\"33%\"> </td>";

    $client_links_table .= "</tr></table>";

    $submit_button = "<input type=\"button\" value=\"{$LANG["phrase_update_accounts"]}\" onclick=\"window.location='index.php?page=themes&mass_assign=1&accounts=$client_id_str&theme_id=' + $('#mass_update_client_theme').val()\" />";

    $placeholders = array(
      "theme_dropdown" => $dd,
      "submit_button" => $submit_button
    );

    $mass_assign_html = "<div class=\"margin_top_large margin_bottom_large\">" . ft_eval_smarty_string($placeholder_str, $placeholders) . "</div>";
    $html = $message . $mass_assign_html . $client_links_table;

    return array(false, $html);
  }

  // hoorah! Validation complete, let's update the bloomin' database at last

  // update the admin settings
  $admin_id = $_SESSION["ft"]["account"]["account_id"];
  $admin_swatch = "";
  if (isset($infohash["{$admin_theme}_admin_theme_swatches"]))
    $admin_swatch = $infohash["{$admin_theme}_admin_theme_swatches"];

  mysql_query("
    UPDATE {$g_table_prefix}accounts
    SET    theme = '$admin_theme',
           swatch = '$admin_swatch'
    WHERE  account_id = $admin_id
      ");

  $_SESSION["ft"]["account"]["theme"]  = $admin_theme;
  $_SESSION["ft"]["account"]["swatch"] = $admin_swatch;

  $default_client_swatch = "";
  if (isset($infohash["{$default_client_theme}_default_client_theme_swatches"]))
    $default_client_swatch = $infohash["{$default_client_theme}_default_client_theme_swatches"];

  // update the default client theme & swatch
  $new_settings = array(
    "default_theme"         => $default_client_theme,
    "default_client_swatch" => $default_client_swatch
  );
  ft_set_settings($new_settings);


  // finally, update the enabled themes list. Only set the theme as enabled if the
  // cache folder is writable
  mysql_query("UPDATE {$g_table_prefix}themes SET is_enabled = 'no'");
  foreach ($enabled_themes as $theme)
  {
    $cache_folder = "$g_root_dir/themes/$theme/cache";

    // try and set the cache folder as writable
    if (!is_writable($cache_folder))
      @chmod($cache_folder, 0777);

    if (!is_writable($cache_folder))
      continue;

    mysql_query("
      UPDATE {$g_table_prefix}themes
      SET    is_enabled = 'yes'
      WHERE  theme_folder = '$theme'
        ");
  }

  // reset the settings in sessions
  $_SESSION["ft"]["settings"] = ft_get_settings();

  $success = true;
  $message = $LANG["notify_themes_settings_updated"];

  extract(ft_process_hook_calls("end", compact("infohash"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * A helper function to return Form Tool's best guess at the timezone offset. First it checks
 * sessions to see if a person's logged in; if so it uses that. If NOT, it pulls the default
 * timezone offset value from settings.
 *
 * @return string $timezone_offset
 */
function ft_get_current_timezone_offset()
{
  $timezone_offset = "";
  if (isset($_SESSION["ft"]["account"]["timezone_offset"]))
    $timezone_offset = $_SESSION["ft"]["account"]["timezone_offset"];
  else
    $timezone_offset = ft_get_settings("timezone_offset");

  return $timezone_offset;
}
