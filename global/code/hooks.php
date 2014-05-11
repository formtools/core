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
 * @copyright Benjamin Keen 2012
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

  $when          = ft_sanitize($when);
  $function_name = ft_sanitize($function_name);
  $hook_function = ft_sanitize($hook_function);

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
  while ($row = @mysql_fetch_assoc($query))
    $results[] = $row;

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
 * Our main process hooks function. This finds and calls ft_process_hook_call for each hook defined for
 * this event & calling function. It processes each one sequentially in order of priority.
 *
 * I changed the logic of this functionality in 2.1.0 - now I think it will work more intuitively.
 * Precisely what was being allowed to be overridden didn't make sense; this should work better now
 * (but all modules will need to be examined).
 *
 * "Priority" is actually very weird. Although it does allow hooks to define in what order they get
 * called, the priority to overriding the variables actually falls to the hooks with the LOWEST
 * priority. This can and should be adjusted, but right now there's no need for it. The fourth param
 * was added in part to solve this: that lets the calling function concatenate all overridden vars from
 * all calling functions and use all the data to determine what to do. This is used for the quicklinks
 * section on the main Submission Listing page.
 *
 * @param string $event the name of the event in the function calling the hook (e.g. "start", "end",
 *     "manage_files" etc.)
 * @param $vars whatever vars are being passed to the hooks from the context of the calling function
 * @param $overridable_vars whatever variables may be overridden by the hook
 * @param $overridable_vars_to_be_concatenated
 */
function ft_process_hook_calls($event, $vars, $overridable_vars, $overridable_vars_to_be_concatenated = array())
{
  $backtrace = debug_backtrace();
  $calling_function = $backtrace[1]["function"];

  // get the hooks associated with this core function and event
  $hooks = ft_get_hook_calls($event, "code", $calling_function);

  // extract the var passed from the calling function into the current scope
  $return_vals = array();
  foreach ($hooks as $hook_info)
  {
    // this clause was added in 2.1 - it should have been included in 2.0.x, but it was missed. This prevents any hooks
    // being processed for modules that are not enabled.
    $module_folder = $hook_info["module_folder"];
    if (!ft_check_module_enabled($module_folder))
      continue;

    // add the hook info to the $template_vars for access by the hooked function. N.B. the "form_tools_"
    // prefix was added to reduce the likelihood of naming conflicts with variables in any Form Tools page
    $vars["form_tools_hook_info"] = $hook_info;
    $updated_vars = ft_process_hook_call($hook_info["module_folder"], $hook_info["hook_function"], $vars, $overridable_vars, $calling_function);

    // now return whatever values have been overwritten by the hooks
    foreach ($overridable_vars as $var_name)
    {
      if (array_key_exists($var_name, $updated_vars))
      {
        if (in_array($var_name, $overridable_vars_to_be_concatenated))
        {
          if (!array_key_exists($var_name, $return_vals))
            $return_vals[$var_name] = array();

          $return_vals[$var_name][] = $updated_vars[$var_name];
        }
        else
        {
          $return_vals[$var_name] = $updated_vars[$var_name];
        }

        // update $vars for any subsequent hook calls
        if (array_key_exists($var_name, $vars))
        {
          $vars[$var_name] = $updated_vars[$var_name];
        }
      }
    }
  }

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
function ft_process_hook_call($module_folder, $hook_function, $vars, $overridable_vars, $calling_function)
{
  // add the overridable variable list and calling function in special hash keys, to provide a little
  // info to the developer with regard to the context in which it's being called and what can be overridden
  $vars["form_tools_overridable_vars"] = $overridable_vars;
  $vars["form_tools_calling_function"] = $calling_function;

  $folder = dirname(__FILE__);
  @include_once(realpath("$folder/../../modules/$module_folder/library.php"));

  if (!function_exists($hook_function))
    return $overridable_vars;

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
  if (function_exists($hook_function))
  {
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


/**
 * Called automatically after upgrading the Core, theme or module. This parses the entire Form Tools code base -
 * including any installed modules - and updates the list of available hooks. As of 2.1.0, the available hooks
 * are listed in the ft_hooks table, and the actual hook calls are in the hook_calls table (formerly the hooks
 * table).
 *
 * Before 2.1.0, the list of hooks was stored in /global/misc/hook_info.ini. However it only stored hooks for the
 * Core and habitually got out of date. This is a lot, lot better!
 */
function ft_update_available_hooks()
{
  global $g_table_prefix;

  $ft_root = realpath(dirname(__FILE__) . "/../../");
  $hook_locations = array(

    // code hooks
    "process.php"        => "core",
    "global/code"        => "core",
    "global/api/api.php" => "api",
    "modules"            => "module",

    // template hooks
    "themes/default"  => "core"
  );

  $results = array(
    "code_hooks"     => array(),
    "template_hooks" => array()
  );
  while (list($file_or_folder, $component) = each($hook_locations))
  {
    _ft_find_hooks("$ft_root/$file_or_folder", $ft_root, $component, $results);
  }

  // now update the database
  mysql_query("TRUNCATE {$g_table_prefix}hooks");
  foreach ($results["code_hooks"] as $hook_info)
  {
    $component       = $hook_info["component"];
    $file            = $hook_info["file"];
    $function_name   = $hook_info["function_name"];
    $action_location = $hook_info["action_location"];
    $params_str      = implode(",", $hook_info["params"]);
    $overridable_str = implode(",", $hook_info["overridable"]);

    mysql_query("
      INSERT INTO {$g_table_prefix}hooks (hook_type, component, filepath, action_location, function_name, params, overridable)
      VALUES ('code', '$component', '$file', '$action_location', '$function_name', '$params_str', '$overridable_str')
    ");
  }

  foreach ($results["template_hooks"] as $hook_info)
  {
    $component = $hook_info["component"];
    $template  = $hook_info["template"];
    $location  = $hook_info["location"];

    mysql_query("
      INSERT INTO {$g_table_prefix}hooks (hook_type, component, filepath, action_location, function_name, params, overridable)
      VALUES ('template', '$component', '$template', '$location', '', '', '')
    ");
  }
}


/**
 * Used by the ft_update_available_hooks function to find all the hooks in the Form Tools script.
 *
 * @param string $curr_folder
 * @param string $root_folder
 * @param string $component "core", "module" or "api"
 * @param array $results
 */
function _ft_find_hooks($curr_folder, $root_folder, $component, &$results)
{
  if (is_file($curr_folder))
  {
    $is_php_file = preg_match("/\.php$/", $curr_folder);
    $is_tpl_file = preg_match("/\.tpl$/", $curr_folder);
    if ($is_php_file)
      $results["code_hooks"] = array_merge($results["code_hooks"], _ft_extract_code_hooks($curr_folder, $root_folder, $component));
    if ($is_tpl_file)
      $results["template_hooks"] = array_merge($results["template_hooks"], _ft_extract_template_hooks($filepath, $root_folder, $component));
  }
  else
  {
    $handle = @opendir($curr_folder);
    if ($handle)
    {
      while (($file = readdir($handle)) !== false)
      {
        if ($file == '.' || $file == '..')
           continue;

        $filepath = $curr_folder . '/' . $file;
        if (is_link($filepath))
          continue;
        if (is_file($filepath))
        {
          $is_php_file = preg_match("/\.php$/", $filepath);
          $is_tpl_file = preg_match("/\.tpl$/", $filepath);
          if ($is_php_file)
            $results["code_hooks"]     = array_merge($results["code_hooks"], _ft_extract_code_hooks($filepath, $root_folder, $component));
          if ($is_tpl_file)
            $results["template_hooks"] = array_merge($results["template_hooks"], _ft_extract_template_hooks($filepath, $root_folder, $component));
        }
        else if (is_dir($filepath))
        {
          _ft_find_hooks($filepath, $root_folder, $component, $results);
        }
      }
      closedir($handle);
    }
  }
}


function _ft_extract_code_hooks($filepath, $root_folder, $component)
{
  $lines = file($filepath);
  $current_function = "";
  $found_hooks = array();
  $root_folder = preg_quote($root_folder);

  foreach ($lines as $line)
  {
    if (preg_match("/^function\s([^(]*)/", $line, $matches))
    {
      $current_function = $matches[1];
      continue;
    }

    // this assumes that the hooks are always on a single line
    if (preg_match("/extract\(\s*ft_process_hook_calls\(\s*[\"']([^\"']*)[\"']\s*,\s*compact\(([^)]*)\)\s*,\s*array\(([^)]*)\)/", $line, $matches))
    {
      $action_location = $matches[1];
      $params          = $matches[2];
      $overridable     = $matches[3];

      // all params should be variables. No whitespace, no double-quotes, no single-quotes
      $params = str_replace("\"", "", $params);
      $params = str_replace(" ", "", $params);
      $params = str_replace("'", "", $params);
      $params = explode(",", $params);

      // same as overridable vars!
      $overridable = str_replace("\"", "", $overridable);
      $overridable = str_replace(" ", "", $overridable);
      $overridable = str_replace("'", "", $overridable);
      $overridable = explode(",", $overridable);
      $file = preg_replace("%" . $root_folder . "%", "", $filepath);

      $found_hooks[] = array(
        "file"            => $file,
        "function_name"   => $current_function,
        "action_location" => $action_location,
        "params"          => $params,
        "overridable"     => $overridable,
        "component"       => $component
      );
    }
  }

  return $found_hooks;
}


function _ft_extract_template_hooks($filepath, $root_folder, $component)
{
  $lines = file($filepath);
  $current_function = "";
  $found_hooks = array();
  $root_folder = preg_quote($root_folder);

  foreach ($lines as $line)
  {
    // this assumes that the hooks are always on a single line
    if (preg_match("/\{template_hook\s+location\s*=\s*[\"']([^}\"]*)/", $line, $matches))
    {
      $template = preg_replace("%" . $root_folder . "%", "", $filepath);
      $found_hooks[] = array(
        "template"  => $template,
        "location"  => $matches[1],
        "component" => $component
      );
    }
  }

  return $found_hooks;
}
