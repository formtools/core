<?php

/**
 * This file defines all functions relating to Form Tools modules.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Modules
 */


// -------------------------------------------------------------------------------------------------


/**
 * Finds out if a module is enabled or not. If it's not even installed, just returns false.
 *
 * @param string $module_folder
 * @return boolean
 */
function ft_check_module_enabled($module_folder)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT is_enabled
    FROM   {$g_table_prefix}modules
    WHERE  module_folder = '$module_folder'
      ");

  $result = mysql_fetch_assoc($query);
  return (!empty($result) && $result["is_enabled"] == "yes");
}


/**
 * Finds out if a module is available. By "available", we mean: has the files uploaded to the modules
 * folder and has a corresponding record in the modules table. It may not be installed/enabled.
 *
 * @param string $module_folder
 * @return boolean
 */
function ft_check_module_available($module_folder)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT count(*) as c
    FROM   {$g_table_prefix}modules
    WHERE  module_folder = '$module_folder'
      ");

  $result = mysql_fetch_assoc($query);
  return $result["c"] == 1;
}


/**
 * Uninstalls a module from the database.
 *
 * @param integer $module_id
 */
function ft_uninstall_module($module_id)
{
  global $g_table_prefix, $LANG, $g_root_dir, $g_delete_module_folder_on_uninstallation;

  $module_info = ft_get_module($module_id);
  $module_folder = $module_info["module_folder"];

  if (empty($module_info))
    return false;

  $success = true;

  $has_custom_uninstall_script = false;
  if (is_file("$g_root_dir/modules/$module_folder/library.php"))
  {
    @include_once("$g_root_dir/modules/$module_folder/library.php");
    $uninstall_function_name = "{$module_folder}__uninstall";
    if (function_exists($uninstall_function_name))
    {
      $has_custom_uninstall_script = true;

      // get the module language file contents and store the info in the $LANG global for
      // so it can be accessed by the uninstallation script
      $LANG[$module_folder] = ft_get_module_lang_file_contents($module_folder);
      list($success, $custom_message) = $uninstall_function_name($module_id);

      // if there was a custom message returned (error or notification), overwrite the default
      // message
      if (!empty($custom_message))
        $message = $custom_message;
    }
  }

  // finally, if there wasn't a custom uninstallation script, or there WAS and it was successfully
  // run, remove the module record and any old database references
  if (!$has_custom_uninstall_script || ($has_custom_uninstall_script && $success))
  {
    // delete the module tables
    mysql_query("DELETE FROM {$g_table_prefix}modules WHERE module_id = $module_id");
    mysql_query("DELETE FROM {$g_table_prefix}module_menu_items WHERE module_id = $module_id");

    // if this module was used in any menus, update them
    $query = mysql_query("
      SELECT DISTINCT menu_id
      FROM   {$g_table_prefix}menu_items
      WHERE  page_identifier = 'module_$module_id'
    ");

    $affected_menu_ids = array();
    while ($row = mysql_fetch_assoc($query))
      $affected_menu_ids[] = $row["menu_id"];

    if (!empty($affected_menu_ids))
    {
      mysql_query("
        DELETE FROM {$g_table_prefix}menu_items
        WHERE page_identifier = 'module_$module_id'
          ");

      // now update the orders of all affected menus
      foreach ($affected_menu_ids as $menu_id)
      {
      	ft_update_menu_order($menu_id);
      }

      // if rows were deleted, re-cache the admin menu and update the ordering of the admin account.
      // ASSUMPTION: only administrator accounts can have modules as items (will need to update at some
      // point soon, no doubt).
      ft_cache_account_menu($_SESSION["ft"]["account"]["account_id"]);
      ft_update_menu_order($_SESSION["ft"]["account"]["menu_id"]);
    }

    // delete any hooks registered by this module
    ft_unregister_module_hooks($module_folder);
  }

  // now delete the entire module folder
  $deleted = false;
  if ($g_delete_module_folder_on_uninstallation)
    $deleted = ft_delete_folder("$g_root_dir/modules/$module_folder");

  if ($deleted)
    $message = $LANG["notify_module_uninstalled"];
  else
    $message = $LANG["notify_module_uninstalled_files_not_deleted"];

  extract(ft_process_hook_calls("end", compact("module_id", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Since it's often more convenient to identify modules by its unique folder name, this function is
 * provided to find the module ID. If not found, returns the empty string.
 *
 * @param string $module_folder
 */
function ft_get_module_id_from_module_folder($module_folder)
{
  global $g_table_prefix;

  $result = mysql_query("
    SELECT module_id
    FROM   {$g_table_prefix}modules
    WHERE  module_folder = '$module_folder'
      ");

  $info = mysql_fetch_assoc($result);

  return (isset($info["module_id"])) ? $info["module_id"] : "";
}


/**
 * Since it's often more convenient to identify modules by its unique folder name, this function is
 * provided to find the module ID. If not found, returns the empty string.
 *
 * @param string $module_folder
 */
function ft_get_module_folder_from_module_id($module_id)
{
  global $g_table_prefix;

  $result = mysql_query("
    SELECT module_folder
    FROM   {$g_table_prefix}modules
    WHERE  module_id = $module_id
      ");

  $info = mysql_fetch_assoc($result);

  return (isset($info["module_folder"])) ? $info["module_folder"] : "";
}


/**
 * This is called implicitly by the ft_display_module_page function (only!). That function is used
 * to display any module page; it automatically calls this function to load any custom navigation
 * menu items for a particular module. Then, the theme's modules_header.tpl template uses this
 * information to render the module nav in an appropriate style.
 *
 * Note: to resolve path issues for developers when specifying the paths of the menu items, they may
 * enter the {$module_dir} Smarty placeholder, which is here escaped to the appropriate URL.
 *
 * @param integer $module_id
 */
function ft_get_module_menu_items($module_id, $module_folder)
{
  global $g_table_prefix, $g_root_url;

  $result = mysql_query("
    SELECT *
    FROM {$g_table_prefix}module_menu_items
    WHERE module_id = $module_id
    ORDER BY list_order ASC
      ");

  $menu_items = array();

  $placeholders = array();
  $placeholders["module_dir"] = "$g_root_url/modules/$module_folder";

  while ($row = mysql_fetch_assoc($result))
  {
    $row["url"] = ft_eval_smarty_string($row["url"], $placeholders);
    $menu_items[] = $row;
  }

  extract(ft_process_hook_calls("end", compact("menu_items", "module_id", "module_folder"), array("menu_items")), EXTR_OVERWRITE);

  return $menu_items;
}


/**
 * Retrieves all information about a particular module.
 *
 * @return array
 */
function ft_get_module($module_id)
{
  global $g_table_prefix;

  $query = mysql_query("SELECT * FROM {$g_table_prefix}modules WHERE module_id = $module_id");
  $result = mysql_fetch_assoc($query);

  extract(ft_process_hook_calls("end", compact("module_id", "result"), array("result")), EXTR_OVERWRITE);

  return $result;
}


/**
 * Retrieves one or more settings for a module. This can be used in two ways:
 * 1. It can be called within any module page WITHOUT the second parameter; it then figures out
 *    what module you're currently on, and returns those setting(s) for that module.
 * 2. It's called from outside a module folder (or within a different module, trying to access the
 *    settings of another module). In that case, it needs to specify the second $module_folder param.
 *
 * To return all settings for the current module, just call the function with no parameters.
 *
 * @param mixed $settings a single setting name or an array of setting names
 * @param string $module_folder
 * @return mixed a single setting string value or an array of setting values
 */
function ft_get_module_settings($settings = "", $module_folder = "")
{
  if (empty($module_folder))
    $module_folder = _ft_get_current_module_folder();

  return ft_get_settings($settings, $module_folder);
}


/**
 * This is used on the administrator Modules page. It allows for a simple search/sort mechanism.
 *
 * @param array $search_criteria
 */
function ft_search_modules($search_criteria)
{
  global $g_table_prefix;

  if (!isset($search_criteria["order"]))
    $search_criteria["order"] = "module_name-DESC";

  extract(ft_process_hook_calls("start", compact("search_criteria"), array("search_criteria")), EXTR_OVERWRITE);

  // verbose, but at least it prevents any invalid sorting. We always return modules that aren't installed first
  // so they show up on the first page of results. The calling page then sorts the ones that require upgrading next
  $order_clause = "is_installed DESC";
  switch ($search_criteria["order"])
  {
    case "module_name-DESC":
      $order_clause .= ", module_name DESC";
      break;
    case "module_name-ASC":
      $order_clause .= ", module_name ASC";
      break;
    case "is_enabled-DESC":
      $order_clause .= ", is_enabled DESC";
      break;
    case "is_enabled-ASC":
      $order_clause .= ", is_enabled ASC";
      break;

    default:
      $order_clause .= ", module_name";
      break;
  }
  $order_clause = "ORDER BY $order_clause";

  $keyword_clause = "";
  if (!empty($search_criteria["keyword"]))
  {
    $string = ft_sanitize($search_criteria["keyword"]);
    $fields = array("module_name", "module_folder", "description");

    $clauses = array();
    foreach ($fields as $field)
      $clauses[] = "$field LIKE '%$string%'";

    $keyword_clause = join(" OR ", $clauses);
  }

  // status ("enabled"/"disabled") clause
  $status_clause = "";
  if (count($search_criteria["status"]) < 2)
  {
  	if (in_array("enabled", $search_criteria["status"]))
      $status_clause = "is_enabled = 'yes'";
  	else
      $status_clause = "is_enabled = 'no'";
  }

  // add up the where clauses
  $where_clauses = array();
  if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";
  if (!empty($status_clause))  $where_clauses[] = "($status_clause)";
  if (!empty($where_clauses))
    $where_clause = "WHERE " . join(" AND ", $where_clauses);
  else
    $where_clause = "";


  // get form info
  $module_query_result = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}modules
    $where_clause
    $order_clause
           ");

  // now retrieve the basic info (id, first and last name) about each client assigned to this form
  $module_info = array();
  while ($module = mysql_fetch_assoc($module_query_result))
    $module_info[] = $module;

  return $module_info;
}


/**
 * Retrieves the list of all modules currently in the database.
 *
 * @return array $module_info an ordered array of hashes, each hash being the module info
 */
function ft_get_modules()
{
  global $g_table_prefix;

  $query = mysql_query("SELECT * FROM {$g_table_prefix}modules ORDER BY module_name");

  $modules_info = array();
  while ($module = mysql_fetch_assoc($query))
    $modules_info[] = $module;

  extract(ft_process_hook_calls("start", compact("modules_info"), array("modules_info")), EXTR_OVERWRITE);

  return $modules_info;
}


/**
 * Returns the total number of modules in the database (regardless of whether they're enabled
 * or not).
 *
 * @return integer the number of modules
 */
function ft_get_module_count()
{
  global $g_table_prefix;

  $query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}modules");
  $info = mysql_fetch_assoc($query);

  return $info["c"];
}


/**
 * This function should be called at the top of every module page - or at least every module page that wants to
 * retain the custom module nav. It does the following:
 *
 * 	- start sessions
 *  - checks permission
 *  - loads the module language file into the $LANG[module_folder] variable in the global namespace with
 *    the users chosen language (or if it doesn't exist, the module's default language). It also
 *    loads the language snippets into a $L global, for shorter use. So these are synonymous:
 *        $LANG.image_manager.phrase_hello_world
 *        $L.phrase_hello_world
 *
 * (the longer option is provided simply for consistency: that's how you access the module language variables in
 * regular Form Tools pages after using the ft_include_module() function).
 *
 * @param string $account_type who is allowed to see this module page: "admin", "client"
 */
function ft_init_module_page($account_type = "admin")
{
  global $g_root_dir, $g_session_type, $g_session_save_path, $g_check_ft_sessions, $LANG;

  if ($g_session_type == "database")
    $sess = new SessionManager();

  if (!empty($g_session_save_path))
    session_save_path($g_session_save_path);

  @session_start();
  header("Cache-control: private");
  header("Content-Type: text/html; charset=utf-8");
  ft_check_permission($account_type);

  if ($g_check_ft_sessions && isset($_SESSION["ft"]["account"]))
  	ft_check_sessions_timeout();

  $module_folder = _ft_get_current_module_folder();

  // if there's a library file defined, include it
  if (is_file("$g_root_dir/modules/$module_folder/library.php"))
    include_once("$g_root_dir/modules/$module_folder/library.php");

  // get the language file content
  $content = ft_get_module_lang_file_contents($module_folder);
  $LANG[$module_folder] = $content;
  $GLOBALS["L"] = $content;

  extract(ft_process_hook_calls("end", compact("account_type", "module_folder"), array()), EXTR_OVERWRITE);
}


/**
 * Sets one or more module settings. This basically acts as a wrapper function for ft_set_settings,
 * which ensures that the module field is set appropriately.
 *
 * @param array $setting_name a hash of "setting_name" => "setting_value"
 */
function ft_set_module_settings($settings)
{
  $module_folder = _ft_get_current_module_folder();
  ft_set_settings($settings, $module_folder);
}


/**
 * Updates the list of enabled & disabled modules.
 *
 * There seems to be a bug with the way this function is called or something. Occasionally all modules
 * are no longer enabled...
 *
 * @param array $request
 */
function ft_update_enabled_modules($request)
{
  global $g_table_prefix, $LANG;

  $module_ids_in_page = $request["module_ids_in_page"]; // a comma-delimited string
  $enabled_module_ids = isset($request["is_enabled"]) ? $request["is_enabled"] : array();

  if (!empty($module_ids_in_page))
  {
    mysql_query("
      UPDATE {$g_table_prefix}modules
      SET    is_enabled = 'no'
      WHERE  module_id IN ($module_ids_in_page)
    ");
  }

  foreach ($enabled_module_ids as $module_id)
  {
    mysql_query("
      UPDATE {$g_table_prefix}modules
      SET    is_enabled = 'yes'
      WHERE  module_id = $module_id
    ");
  }

  return array(true, $LANG["notify_enabled_module_list_updated"]);
}


/**
 * Updates the list of modules in the database by examining the contents of the /modules folder.
 */
function ft_update_module_list()
{
  global $g_table_prefix, $g_root_dir, $LANG;

  $modules_folder = "$g_root_dir/modules";

  // loop through all modules in this folder and, if the module contains the appropriate files, add it to the database
  $module_info = array();
  $dh = opendir($modules_folder);

  // if we couldn't open the modules folder, it doesn't exist or something went wrong
  if (!$dh)
    return array(false, $message);

  // get the list of currently installed modules
  $current_modules = ft_get_modules();
  $current_module_folders = array();
  foreach ($current_modules as $module_info)
    $current_module_folders[] = $module_info["module_folder"];

  while (($folder = readdir($dh)) !== false)
  {
    // if this module is already in the database, ignore it
    if (in_array($folder, $current_module_folders))
      continue;

    if (is_dir("$modules_folder/$folder") && $folder != "." && $folder != "..")
    {
      $info = ft_get_module_info_file_contents($folder);

      if (empty($info))
        continue;

      $info = ft_sanitize($info);

      // check the required info file fields
      $required_fields = array("author", "version", "date", "origin_language");
      $all_found = true;
      foreach ($required_fields as $field)
      {
        if (empty($info[$field]))
          $all_found = false;
      }
      if (!$all_found)
        continue;

      // now check the language file contains the two required fields: module_name and module_description
      $lang_file = "$modules_folder/$folder/lang/{$info["origin_language"]}.php";
      $lang_info = _ft_get_module_lang_file_contents($lang_file);
      $lang_info = ft_sanitize($lang_info);

      // check the required language file fields
      if ((!isset($lang_info["module_name"]) || empty($lang_info["module_name"])) ||
          (!isset($lang_info["module_description"]) || empty($lang_info["module_description"])))
        continue;

      $author               = $info["author"];
      $author_email         = $info["author_email"];
      $author_link          = $info["author_link"];
      $module_version       = $info["version"];
      $module_date          = $info["date"];
      $is_premium           = $info["is_premium"];
      $origin_language      = $info["origin_language"];
      $nav                  = $info["nav"];

      $module_name          = $lang_info["module_name"];
      $module_description   = $lang_info["module_description"];

      // convert the date into a MySQL datetime
      list($year, $month, $day) = explode("-", $module_date);
      $timestamp = mktime(null, null, null, $month, $day, $year);
      $module_datetime = ft_get_current_datetime($timestamp);

      mysql_query("
        INSERT INTO {$g_table_prefix}modules (is_installed, is_enabled, is_premium, origin_language, module_name,
          module_folder, version, author, author_email, author_link, description, module_date)
        VALUES ('no','no', '$is_premium', '$origin_language', '$module_name', '$folder', '$module_version',
          '$author', '$author_email', '$author_link', '$module_description', '$module_datetime')
          ") or die(mysql_error());
      $module_id = mysql_insert_id();

      // now add any navigation links for this module
      if ($module_id)
      {
        $order = 1;
        while (list($lang_file_key, $info) = each($nav))
        {
          $url        = $info[0];
          $is_submenu = ($info[1]) ? "yes" : "no";
          if (empty($lang_file_key) || empty($url))
            continue;

          $display_text = isset($lang_info[$lang_file_key]) ? $lang_info[$lang_file_key] : $LANG[$lang_file_key];

          mysql_query("
            INSERT INTO {$g_table_prefix}module_menu_items (module_id, display_text, url, is_submenu, list_order)
            VALUES ($module_id, '$display_text', '$url', '$is_submenu', $order)
            ") or die(mysql_error());

          $order++;
        }
      }
    }
  }
  closedir($dh);

  return array(true, $LANG["notify_module_list_updated"]);
}


// -------------------------------------------------------------------------------------------------


/**
 * A simple helper function to read the module's info file (module.php).
 *
 * @param string $module_folder the module's folder name
 * @return array the module info file contents, or a blank array if there was any problem reading the
 *   file, or it didn't exist or had blank contents.
 */
function ft_get_module_info_file_contents($module_folder)
{
  global $g_root_dir;

  $file = "$g_root_dir/modules/$module_folder/module.php";

  if (!is_file($file))
    return array();

  @include($file);
  $v = get_defined_vars();

  if (!isset($v["MODULE"]))
    return array();

  $values = $v["MODULE"];
  $info["author"] = isset($values["author"]) ? $values["author"] : "";
  $info["author_email"] = isset($values["author_email"]) ? $values["author_email"] : "";
  $info["author_link"] = isset($values["author_link"]) ? $values["author_link"] : "";
  $info["version"] = isset($values["version"]) ? $values["version"] : "";
  $info["date"] = isset($values["date"]) ? $values["date"] : "";
  $info["is_premium"] = (isset($values["is_premium"]) && $values["is_premium"] == "yes") ? "yes" : "no";
  $info["origin_language"] = isset($values["origin_language"]) ? $values["origin_language"] : "";
  $info["nav"] = isset($values["nav"]) ? $values["nav"] : array();

  return $info;
}


/**
 * Returns the contents of a module's language file for the currently logged in user (i.e. in the
 * desired language).
 *
 * @param unknown_typ $module_folder
 * @return unknown
 */
function ft_get_module_lang_file_contents($module_folder)
{
  global $g_root_dir;

  $lang_file_path = "$g_root_dir/modules/$module_folder/lang";
  $desired_lang   = isset($_SESSION["ft"]["account"]["ui_language"]) ? $_SESSION["ft"]["account"]["ui_language"] : "";
  $desired_lang_file = "$lang_file_path/{$desired_lang}.php";

  // if the desired language file exists, use it. Otherwise use the default language file
  $content = array();
  if (!empty($desired_lang) && is_file($desired_lang_file))
  {
    $content = _ft_get_module_lang_file_contents($desired_lang_file);
  }
  else
  {
    $module_id = ft_get_module_id_from_module_folder($module_folder);
    $module_info = ft_get_module($module_id);
    $origin_lang = $module_info["origin_language"];

    // TODO maybe we should throw an warning here if the file isn't found...
    $origin_lang_file = "$lang_file_path/{$origin_lang}.php";
    if (!empty($origin_lang) && is_file($origin_lang_file))
    {
      $content = _ft_get_module_lang_file_contents($origin_lang_file);
    }
  }

  return $content;
}


/**
 * Loads the contents of a module language file.
 *
 * @param string $summary_file the full file path and filename
 */
function _ft_get_module_lang_file_contents($lang_file)
{
  @include($lang_file);
  $vars = get_defined_vars();

  $lang_array = isset($vars["L"]) ? $vars["L"] : array();;

  return $lang_array;
}


/**
 * Expects to be called from within the modules folder; namely, within a particular module. This
 * function returns the name of the current module. Assumption: no module contains a /modules folder.
 *
 * @return string
 */
function _ft_get_current_module_folder()
{
  global $g_root_dir;

  $script_name = $_SERVER["SCRIPT_NAME"];

  $module_folder = "";
  if (preg_match("/\/modules\/([^\/]*)/", $script_name, $matches))
    $module_folder = $matches[1];

  return $module_folder;
}


/**
 * This function is used throughout Form Tools to include all server-side resources from a module.
 * All content in the global namespace is made available to the included files. It relies on the
 * modules having a certain file-folder structure, and imports the following information:
 *
 *   - module PHP code, found in /module_root/library.php
 *   - Smarty plugins, modifiers etc., found in /module_root/smarty/
 *   - the appropriate language file, found in /module_root/lang/
 *
 * Custom module language files are added to the $LANG global for use in any of the templates, using the the
 * module folder as the "namespace". For example, to access the phrase "Hello World!" in a page, you'd use:
 *
 *   $LANG.image_manager.phrase_hello_world
 *
 * Or, just FYI, if you're within the module itself, you can use the shortcut:
 *
 *   $L.phrase_hello_world
 *
 * @param mixed $modules either a string
 */
function ft_include_module($module_folder)
{
  global $g_root_dir, $g_smarty, $LANG;

  foreach ($GLOBALS as $key => $val) { @eval("global \$$key;"); }

  // code file
  if (is_file("$g_root_dir/modules/$module_folder/library.php"))
    include_once("$g_root_dir/modules/$module_folder/library.php");

  // Smarty resources
  if (is_dir("$g_root_dir/modules/$module_folder/smarty"))
    $g_smarty->plugins_dir[] = "$g_root_dir/modules/$module_folder/smarty";

  // load the language file into the $LANG var, under
  $content = ft_get_module_lang_file_contents($module_folder);
  $LANG[$module_folder] = $content;

  extract(ft_process_hook_calls("end", compact("module_folder"), array()), EXTR_OVERWRITE);
}


/**
 * This if the module counterpart function of the ft_load_field function. It works exactly the same
 * way, except that it namespaces the variables in a $_SESSION["ft"][$module_folder] key.
 *
 * It assumes that a variable name can be found in GET, POST or SESSIONS (or all three). What this
 * function does is return the value stored in the most important variable (GET first, POST second,
 * SESSIONS third), and update sessions at the same time. This is extremely helpful in situations
 * where you don't want to keep having to submit the same information from page to page.
 * The fourth parameter is included as a way to set a default value.
 *
 * @param string $module_folder
 * @param string $field_name the field name
 * @param string $session_name the session key for this field name
 * @param string $default_value the default value for the field
 * @return string the field value
 */
function ft_load_module_field($module_folder, $field_name, $session_name, $default_value = "")
{
  $field = $default_value;

  if (!isset($_SESSION["ft"][$module_folder]) || !is_array($_SESSION["ft"][$module_folder]))
    $_SESSION["ft"][$module_folder] = array();

  if (isset($_GET[$field_name]))
  {
    $field = $_GET[$field_name];
    $_SESSION["ft"][$module_folder][$session_name] = $field;
  }
  else if (isset($_POST[$field_name]))
  {
    $field = $_POST[$field_name];
    $_SESSION["ft"][$module_folder][$session_name] = $field;
  }
  else if (isset($_SESSION["ft"][$module_folder][$session_name]))
  {
    $field = $_SESSION["ft"][$module_folder][$session_name];
  }

  return $field;
}


/**
 * Called automatically on installation, or when the administrator clicks on the "Install" link for a module
 * This function runs the module's installation script (if it exists) and returns the appropriate success
 * or error message.
 *
 * @param integer $module_id
 * @return array [0] T/F, [1] error / success message.
 */
function ft_install_module($info)
{
  global $LANG, $g_root_dir, $g_root_url, $g_table_prefix;

  $module_id = $info["install"];

  $module_info = ft_get_module($module_id);
  $module_folder = $module_info["module_folder"];

  $success = true;
  $message = ft_eval_smarty_string($LANG["notify_module_installed"], array("link" => "$g_root_url/modules/$module_folder"));

  $has_custom_install_script = false;

  if (is_file("$g_root_dir/modules/$module_folder/library.php"))
  {
    @include_once("$g_root_dir/modules/$module_folder/library.php");
    $install_function_name = "{$module_folder}__install";
    if (function_exists($install_function_name))
    {
      $has_custom_install_script = true;

      // get the module language file contents and store the info in the $LANG global for
      // so it can be accessed by the installation script
      $LANG[$module_folder] = ft_get_module_lang_file_contents($module_folder);
      list($success, $custom_message) = $install_function_name($module_id);

      // if there was a custom message returned (error or notification), overwrite the default
      // message
      if (!empty($custom_message))
        $message = $custom_message;
    }
  }

  // if there wasn't a custom installation script, or there was and it was successfully run,
  // update the record in the module table to mark it as both is_installed and is_enabled.
  if (!$has_custom_install_script || ($has_custom_install_script && $success))
  {
  	$module_key_clause = "";
  	if (isset($info["k"]))
  	{
      $module_key_clause = ", module_key = '{$info["k"]}'";
  	}
    mysql_query("
      UPDATE {$g_table_prefix}modules
      SET    is_installed = 'yes',
             is_enabled = 'yes'
             $module_key_clause
      WHERE  module_id = $module_id
        ");
  }

  return array($success, $message);
}


/**
 * This is called on the main Modules listing page for each module. It checks to
 * see if the module has a new upgrade available. If it has, it displays an "Upgrade
 * Module" link to allow the administrator to call the upgrade script (ft_upgrade_module)
 *
 * @param integer $module_id
 */
function ft_module_needs_upgrading($module_id)
{
  $module_info = ft_get_module($module_id);
  $module_folder = $module_info["module_folder"];
  $current_db_version = $module_info["version"];

  $latest_module_info = ft_get_module_info_file_contents($module_folder);

  $actual_version = isset($latest_module_info["version"]) ? $latest_module_info["version"] : "";

  return ($current_db_version != $actual_version);
}


/**
 * This function is called from the main Modules page. It upgrades an individual
 * module.
 */
function ft_upgrade_module($module_id)
{
  global $LANG, $g_root_url, $g_root_dir, $g_table_prefix;

  $module_info = ft_get_module($module_id);
  $module_folder = $module_info["module_folder"];
  $module_name = $module_info["module_name"];
  $old_module_version_date = $module_info["module_date"];
  $current_db_version = $module_info["version"];

  $info = ft_get_module_info_file_contents($module_folder);
  $new_version = $info["version"];

  if ($current_db_version == $new_version)
    return array(false, "");

  // if the module has its own upgrade function, call it. In Oct 2011, a BIG problem was identified
  // in the way modules were being updated. For backward compatibility, the new upgrade function
  // must be named [module folder]__update (not ...__upgrade). if the __update function is defined,
  // it will be called instead of the older __upgrade one.
  @include_once("$g_root_dir/modules/$module_folder/library.php");

  // NEW "update" function
  $update_function_name = "{$module_folder}__update";
  if (function_exists($update_function_name))
  {
    list($success, $message) = $update_function_name($module_info, $info);
    if (!$success)
    {
    	return array($success, $message);
    }
  }
  else
  {
	  // OLD "upgrade" function
	  $upgrade_function_name = "{$module_folder}__upgrade";
	  if (function_exists($upgrade_function_name))
	  {
	    $upgrade_function_name($current_db_version, $new_version);
	  }
  }

  // now, update the main module record
  $info = ft_sanitize($info);

  // we're assuming the module developer hasn't removed any of the required fields...

  // now check the language file contains the two required fields: module_name and module_description
  $lang_file = "$g_root_dir/modules/$module_folder/lang/{$info["origin_language"]}.php";
  $lang_info = _ft_get_module_lang_file_contents($lang_file);
  $lang_info = ft_sanitize($lang_info);

  // check the required language file fields
  if ((!isset($lang_info["module_name"]) || empty($lang_info["module_name"])) ||
      (!isset($lang_info["module_description"]) || empty($lang_info["module_description"])))
    return;

  $author               = $info["author"];
  $author_email         = $info["author_email"];
  $author_link          = $info["author_link"];
  $module_version       = $info["version"];
  $module_date          = $info["date"];
  $origin_language      = $info["origin_language"];
  $nav                  = $info["nav"];

  $module_name          = $lang_info["module_name"];
  $module_description   = $lang_info["module_description"];

  // convert the date into a MySQL datetime
  list($year, $month, $day) = explode("-", $module_date);
  $timestamp = mktime(null, null, null, $month, $day, $year);
  $module_datetime = ft_get_current_datetime($timestamp);

  @mysql_query("
    UPDATE {$g_table_prefix}modules
    SET    origin_language = '$origin_language',
           module_name = '$module_name',
           version = '$module_version',
           author = '$author',
           author_email = '$author_email',
           author_link = '$author_link',
           description = '$module_description',
           module_date = '$module_datetime'
    WHERE  module_id = $module_id
      ") or die(mysql_error());

  // remove and update the navigation links for this module
  @mysql_query("DELETE FROM {$g_table_prefix}module_menu_items WHERE module_id = $module_id");
  $order = 1;
  while (list($lang_file_key, $info) = each($nav))
  {
    $url        = $info[0];
    $is_submenu = ($info[1]) ? "yes" : "no";
    if (empty($lang_file_key) || empty($url))
      continue;

    $display_text = isset($lang_info[$lang_file_key]) ? $lang_info[$lang_file_key] : $LANG[$lang_file_key];

    mysql_query("
      INSERT INTO {$g_table_prefix}module_menu_items (module_id, display_text, url, is_submenu, list_order)
      VALUES ($module_id, '$display_text', '$url', '$is_submenu', $order)
        ") or die(mysql_error());

    $order++;
  }

  // And we're done! inform the user that it's been upgraded
  $placeholders = array(
    "module"  => $module_name,
    "version" => $new_version,
    "link"    => "$g_root_url/modules/$module_folder"
  );

  $message = ft_eval_smarty_string($LANG["notify_module_updated"], $placeholders);

  return array(true, $message);
}


/**
 * Added in 2.1.6, to allow for simple "inline" hook overriding from within the PHP pages.
 *
 * @param string $location
 * @param mixed $data
 */
function ft_module_override_data($location, $data)
{
  extract(ft_process_hook_calls("start", compact("location", "data"), array("data")), EXTR_OVERWRITE);
	return $data;
}
