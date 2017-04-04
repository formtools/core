<?php

/**
 * This file defines all functions relating to Form Tools modules.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Modules
 */


use FormTools\Core;
use FormTools\Settings;


// -------------------------------------------------------------------------------------------------


/**
 * Finds out if a module is enabled or not. If it's not even installed, just returns false.
 *
 * @param string $module_folder
 * @return boolean
 */
function ft_check_module_enabled($module_folder)
{
    $db = Core::$db;
	$db->query("
        SELECT is_enabled
        FROM   {PREFIX}modules
        WHERE  module_folder = :module_folder
    ");
	$db->bind(":module_folder", $module_folder);
	$db->execute();
	$result = $db->fetch();

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
    $db = Core::$db;
	$db->query("
        SELECT count(*) as c
        FROM   {PREFIX}modules
        WHERE  module_folder = :module_folder
    ");
	$db->bind(":module_folder", $module_folder);
	$db->execute();
	$result = $db->fetch();

	return $result["c"] == 1;
}


/**
 * Uninstalls a module from the database.
 *
 * @param integer $module_id
 */
function ft_uninstall_module($module_id)
{
	$LANG = Core::$L;
	$delete_module_folder_on_uninstallation = Core::shouldDeleteFolderOnUninstallation();
	$rootDir = Core::getRootDir();

	$module_info = self::getModule($module_id);
	$module_folder = $module_info["module_folder"];

	if (empty($module_info)) {
        return false;
    }

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
			$LANG[$module_folder] = self::getModuleLangFile($module_folder, Core::$user->getLang());
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

	extract(Hooks::processHookCalls("end", compact("module_id", "success", "message"), array("success", "message")), EXTR_OVERWRITE);

	return array($success, $message);
}


/**
 * Since it's often more convenient to identify modules by its unique folder name, this function is
 * provided to find the module ID. If not found, returns the empty string.
 *
 * @param string $module_folder
 */
function ft_get_module_folder_from_module_id($module_id)
{
    $db = Core::$db;

    $db->query("
        SELECT module_folder
        FROM   {PREFIX}modules
        WHERE  module_id = :module_id
    ");
    $db->bind(":module_id", $module_id);
    $db->execute();
    $info = $db->fetch();

	return (isset($info["module_folder"])) ? $info["module_folder"] : "";
}


/**
 * This is called implicitly by the Themes::displayModulePage function (only!). That function is used
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
    $db = Core::$db;

    $db->query("
        SELECT *
        FROM {PREFIX}module_menu_items
        WHERE module_id = $module_id
        ORDER BY list_order ASC
    ");

    $rootURL = Core::getRootUrl();
	$placeholders = array(
	    "module_dir" => "$rootURL/modules/$module_folder"
    );

    $menu_items = array();
	while ($row = mysql_fetch_assoc($result)) {
		$row["url"] = General::evalSmartyString($row["url"], $placeholders);
		$menu_items[] = $row;
	}

	extract(Hooks::processHookCalls("end", compact("menu_items", "module_id", "module_folder"), array("menu_items")), EXTR_OVERWRITE);

	return $menu_items;
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
	if (empty($module_folder)) {
        $module_folder = _ft_get_current_module_folder();
    }
	return Settings::get($settings, $module_folder);
}


/**
 * This is used on the administrator Modules page. It allows for a simple search/sort mechanism.
 *
 * @param array $search_criteria
 */
function ft_search_modules($search_criteria)
{
	if (!isset($search_criteria["order"])) {
        $search_criteria["order"] = "module_name-DESC";
    }

	extract(Hooks::processHookCalls("start", compact("search_criteria"), array("search_criteria")), EXTR_OVERWRITE);

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
	if (!empty($search_criteria["keyword"])) {
		$string = $search_criteria["keyword"];
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
	$content = self::getModuleLangFile($module_folder, Core::$user-getLang());
	$LANG[$module_folder] = $content;
	$GLOBALS["L"] = $content;

	extract(Hooks::processHookCalls("end", compact("account_type", "module_folder"), array()), EXTR_OVERWRITE);
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


// -------------------------------------------------------------------------------------------------


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
	$content = self::getModuleLangFile($module_folder, Core::$user-getLang());
	$LANG[$module_folder] = $content;

	extract(Hooks::processHookCalls("end", compact("module_folder"), array()), EXTR_OVERWRITE);
}


/**
 * This if the module counterpart function of the General::loadField function. It works exactly the same
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

