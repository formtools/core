<?php

/**
 * Hooks allow module developers to piggy-back their own functionality on ALL Form Tools functions. Any module can
 * log whatever hooks they require; these are stored in the database in the "hooks" table.
 *
 * Hooks work like so:
 *
 *   1. The module developer writes a function to execute whatever code they want. This function should
 *      be stored in their library.php file (or in a file included by that file). This function can be
 *      called whatever they want, but it should contain a single parameter. e.g. imagine they want to
 *      add code to the ft_update_submission function; the function look like so:
 *         ft_update_submission($form_id, $submission_id, $infohash)
 *
 *      Then their function should look like:
 *         my_update_submission($vars)
 *
 *   $vars will contain ALL defined variables, as available to the functions scope. e.g. $form_id,
 *   $submission_id, $infohash and anything else defined at that point. Accessible via $vars["form_id"],
 *   $vars["submission_id"] and so on.
 *
 *   Return values: your function may return any values you want. These will be registered in the function
 *   scope, overwriting anything already defined there.
 *
 *   2. Register your function. In your library.php file, call the ft_register_hook function.
 *
 *   The first parameter should be "start" or "after". It determines
 *   The function also takes a 3rd, optional parameter: priority. 1-100
 *
 * @copyright Benjamin Keen 2014
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage General
 */


// -------------------------------------------------------------------------------------------------


/**
 * Called by module installation files, and/or whenever needed. This function logs new hooks in the
 * database. This function is called by the module designers WITHIN their own modules.
 *
 * @param string $hook_type "code" or "template"
 * @param string $when when in the functions the hooks should be processed. For code hooks, these are
 *    either "start" or "end"; for template hooks, this is the location attribute of the {template_hook ...}
 *    tag.
 * @param string $function_name the name of the function to which this hook is to be attached
 * @param string $hook_function the name of the hook function, found in the modules library.php file
 * @param integer $priority 1-100 (100 lowest, 1 highest). Optional setting that determines the order
 *    in which this hook gets processed, in relation to OTHER hooks attached to the same event.
 * @param boolean $force_unique if set to true, this will only register hooks that haven't been set
 *    with this module, location, hook and core function.
 */
function ft_register_hook($hook_type, $module_folder, $when, $function_name, $hook_function, $priority = 50, $force_unique = false)
{
	global $g_table_prefix;

	$may_proceed = true;
	if ($force_unique)
	{
		$query = mysql_query("
      SELECT count(*) as c
      FROM   {$g_table_prefix}hook_calls
      WHERE  hook_type = '$hook_type' AND
             action_location = '$when' AND
             module_folder = '$module_folder' AND
             function_name = '$function_name' AND
             hook_function = '$hook_function'
        ");

		$result = mysql_fetch_assoc($query);
		if ($result["c"] > 0)
			$may_proceed = false;
	}

	$result = mysql_query("
    INSERT INTO {$g_table_prefix}hook_calls (hook_type, action_location, module_folder, function_name, hook_function, priority)
    VALUES ('$hook_type', '$when', '$module_folder', '$function_name', '$hook_function', $priority)
      ");

	if ($result)
	{
		$hook_id = mysql_insert_id();
		return array(true, $hook_id);
	}
	else
		return array(false, "");
}


/**
 * Called internally. This is called when a user uninstalls a module; it removes all hooks relating to
 * that module from the database.
 *
 * @param integer $module_id
 */
function ft_unregister_module_hooks($module_folder)
{
	global $g_table_prefix;
	mysql_query("DELETE FROM {$g_table_prefix}hook_calls WHERE module_folder = '$module_folder'");
}


/**
 * Returns all hooks associated with a particular function event, ordered by priority.
 *
 * @param string $event
 * @param string $function_name
 * @return array a hash of hook information
 */
function ft_get_hook_calls($event, $hook_type, $function_name)
{
	global $g_table_prefix;

	$query = @mysql_query("
		SELECT *
		FROM   {$g_table_prefix}hook_calls
		WHERE  hook_type = '$hook_type' AND
			action_location = '$event' AND
			function_name = '$function_name'
		ORDER BY priority ASC
      ");

	$results = array();
	while ($row = @mysql_fetch_assoc($query)) {
		$results[] = $row;
	}

	return $results;
}


/**
 * Returns all modules associated with a particular module ordered by priority.
 *
 * @param string $module_folder
 * @return array
 */
function ft_get_module_hook_calls($module_folder)
{
	global $g_table_prefix;

	$query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}hook_calls
    WHERE  module_folder = '$module_folder'
    ORDER BY priority ASC
      ");

	$results = array();
	while ($row = mysql_fetch_assoc($query))
		$results[] = $row;

	return $results;
}


/**
 * Processes an actual hook and returns the value. This requires all hook functions to return either NOTHING,
 * or a hash of values to be overridden.
 *
 * @param string $module_folder
 * @param string $hook_function
 * @param array $vars
 */
function ft_process_hook_call($module_folder, $hook_function, $vars, $overridable_vars, $calling_function)
{
	// add the overridable variable list and calling function in special hash keys, to provide a little
	// info to the developer with regard to the context in which it's being called and what can be overridden
	$vars["form_tools_overridable_vars"] = $overridable_vars;
	$vars["form_tools_calling_function"] = $calling_function;

	@include_once(realpath(__DIR__ . "/../../modules/$module_folder/library.php"));

	if (!function_exists($hook_function))
		return $overridable_vars;

	$result = @$hook_function($vars);

	$updated_values = array();
	if (!empty($result)) {
		while (list($key, $value) = each($result)) {
			if (in_array($key, $overridable_vars)) {
                $updated_values[$key] = $value;
            }
		}
	}

	return $updated_values;
}


/**
 * This processes all template hooks for a particular template location (e.g. edit client page, at the top).
 * It works similarly to the ft_process_hooks function, except there are no options to override values in the
 * template. This is used purely to insert content into the templates.
 *
 * @param string $location
 * @param array an array of all variables currently in the template
 * @param array in most cases, template hooks just contain the single "location" parameter which identifies
 *     where the hook is from. But hooks may also contain any additional rbitrary attribute names. This
 *     param contains all of them.
 */
function ft_process_template_hook_calls($location, $template_vars, $all_params = array())
{
	$hooks = ft_get_hook_calls($location, "template", "");

	// extract the var passed from the calling function into the current scope
	foreach ($hooks as $hook_info)
	{
		$module_folder = $hook_info["module_folder"];
		if (!ft_check_module_enabled($module_folder))
			continue;

		// add the hook info to the $template_vars for access by the hooked function. N.B. the "form_tools_"
		// prefix was added to reduce the likelihood of naming conflicts with variables in any Form Tools page
		$template_vars["form_tools_hook_info"] = $hook_info;

		ft_process_template_hook_call($hook_info["module_folder"], $hook_info["hook_function"], $location, $template_vars, $all_params);
	}
}


/**
 * This function called the template hooks and returns the generated HTML.
 *
 * @param string $module_folder
 * @param string $hook_function
 * @param string $hook_function
 * @return string
 */
function ft_process_template_hook_call($module_folder, $hook_function, $location, $template_vars, $all_template_hook_params = array())
{
	global $g_root_dir;

	@include_once("$g_root_dir/modules/$module_folder/library.php");

	// this is very unfortunate, but has to be done for backward compatibility. Up until July 2011, template hooks only ever
	// needed the single "location" attribute + the template var information. But with the Data Visualization module, it needs to be more
	// flexible. The generated hooks for each visualization can be used in pages generated in the Pages module, and we need to add a
	// "height" and "width" attributes to the hook to permit the user to tinker around with the size (hardcoding the size of the
	// visualization makes no sense, because it can be used in different contexts). But... to get that information to the template hook
	// calls functions we CAN'T pass in an additional param, because it would break all hook call functions. So instead, we add the
	// information into the $template_vars info for use by the hook call function. Boo!
	$template_vars["form_tools_all_template_hook_params"] = $all_template_hook_params;

	$html = "";
	if (function_exists($hook_function)) {
		$html = @$hook_function($location, $template_vars);
	}

	return $html;
}


/**
 * Deletes a hook by hook ID.
 *
 * @param integer $hook_id
 */
function ft_delete_hook_call($hook_id)
{
	global $g_table_prefix;
	mysql_query("DELETE FROM {$g_table_prefix}hook_calls WHERE hook_id = $hook_id");
}

