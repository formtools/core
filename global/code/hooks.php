<?php

/**
 * This page contains all code relating to Form Tools hooks. Hooks allow module developers to piggy-back
 * their own functionality on ALL Form Tools functions. Any module can log whatever hooks they require;
 * these are stored in the database in the "hooks" table.
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
 * @copyright Encore Web Studios 2009
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-0
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
 * @param string $core_function the name of the core function to which this hook is to be attached
 * @param string $hook_function the name of the hook function, found in the modules library.php file
 * @param integer $priority 1-100 (100 lowest, 1 highest). Optional setting that determines the order
 *    in which this hook gets processed, in relation to OTHER hooks attached to the same event.
 * @param boolean $force_unique if set to true, this will only register hooks that haven't been set
 *    with this module, location, hook and core functino.
 */
function ft_register_hook($hook_type, $module_folder, $when, $core_function, $hook_function, $priority = 50, $force_unique = false)
{
  global $g_table_prefix;

  $when          = ft_sanitize($when);
  $core_function = ft_sanitize($core_function);
  $hook_function = ft_sanitize($hook_function);

  $may_proceed = true;
  if ($force_unique)
  {
  	$query = mysql_query("
  	  SELECT count(*) as c
  	  FROM   {$g_table_prefix}hooks
  	  WHERE  hook_type = '$hook_type' AND
			       action_location = '$when' AND
  	         module_folder = '$module_folder' AND
  	         core_function = '$core_function' AND
  	         hook_function = '$hook_function'
      	");

  	$result = mysql_fetch_assoc($query);
  	if ($result["c"] > 0)
  	  $may_proceed = false;
  }

  $result = mysql_query("
    INSERT INTO {$g_table_prefix}hooks (hook_type, action_location, module_folder, core_function, hook_function, priority)
    VALUES ('$hook_type', '$when', '$module_folder', '$core_function', '$hook_function', $priority)
      ");

  if ($result)
    return true;
  else
    return false;
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
  mysql_query("DELETE FROM {$g_table_prefix}hooks WHERE module_folder = '$module_folder'");
}


/**
 * Returns all hooks associated with a particular function event, ordered by priority.
 *
 * @param string $event
 * @param string $core_function
 * @return array a hash of hook information
 */
function ft_get_hooks($event, $hook_type, $core_function)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}hooks
    WHERE  hook_type = '$hook_type' AND
           action_location = '$event' AND
           core_function = '$core_function'
    ORDER BY priority ASC
      ");

  $results = array();
  while ($row = mysql_fetch_assoc($query))
    $results[] = $row;

  return $results;
}


/**
 * Returns all modules associated with a particular module ordered by priority.
 *
 * @param string $module_folder
 * @return array
 */
function ft_get_module_hooks($module_folder)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}hooks
    WHERE  module_folder = '$module_folder'
    ORDER BY priority ASC
      ");

  $results = array();
  while ($row = mysql_fetch_assoc($query))
    $results[] = $row;

  return $results;
}


/**
 * Our main process hooks function. This finds and calls ft_process_hook for each hook defined
 * for this event & calling function. It processes each one sequentially in order of priority.
 */
function ft_process_hooks($event, $vars, $overridable_vars)
{
  $backtrace = debug_backtrace();
  $calling_function = $backtrace[1]["function"];

  // get the hooks associated with this core function and event
  $hooks = ft_get_hooks($event, "code", $calling_function);

  // extract the var passed from the calling function into the current scope
  foreach ($hooks as $hook_info)
  {
    $updated_vars = ft_process_hook($hook_info["module_folder"], $hook_info["hook_function"], $vars, $overridable_vars, $calling_function);

    // update $vars with any values that have been updated by the hook
    while (list($key, $value) = each($vars))
    {
      if (array_key_exists($key, $updated_vars))
        $vars[$key] = $updated_vars[$key];
    }
    reset($vars);
  }

  // now return whatever values have been overwritten by the hooks
  $return_vals = array();
  foreach ($overridable_vars as $var_name)
  {
    if (array_key_exists($var_name, $vars))
      $return_vals[$var_name] = $vars[$var_name];
  }

  // return the variables defined in the current scope
  return $return_vals;
}


/**
 * Processes an actual hook and returns the value. This requires all hook functions to return either NOTHING,
 * or a hash of values to be overridden.
 *
 * @param string $module_folder
 * @param string $hook_function
 * @param array $vars
 */
function ft_process_hook($module_folder, $hook_function, $vars, $overridable_vars, $calling_function)
{
  global $g_root_dir;

  // add the overridable variable list and calling function in special hash keys, to provide a little
  // info to the developer with regard to the context in which it's being called and what can be overridden
  $vars["form_tools_overridable_vars"] = $overridable_vars;
  $vars["form_tools_calling_function"] = $calling_function;

  @include_once("$g_root_dir/modules/$module_folder/library.php");
  $result = @$hook_function($vars);

  $updated_values = array();
  if (!empty($result))
  {
    while (list($key, $value) = each($result))
    {
      if (in_array($key, $overridable_vars))
        $updated_values[$key] = $value;
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
 */
function ft_process_template_hooks($location, $template_vars)
{
  $hooks = ft_get_hooks($location, "template", "");
	
  // extract the var passed from the calling function into the current scope
  foreach ($hooks as $hook_info)
  {
    ft_process_template_hook($hook_info["module_folder"], $hook_info["hook_function"], $location, $template_vars);
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
function ft_process_template_hook($module_folder, $hook_function, $location, $template_vars)
{
  global $g_root_dir;

  @include_once("$g_root_dir/modules/$module_folder/library.php");
	$html = @$hook_function($location, $template_vars);
	
	return $html;
}