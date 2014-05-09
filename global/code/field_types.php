<?php

/**
 * This file contains all functions relating to the field types (select, radios etc). Added in 2.1.0
 * with the addition of Custom Fields.
 *
 * @copyright Encore Web Studios 2011
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-1-0
 * @subpackage FieldTypes
 */


// -------------------------------------------------------------------------------------------------


/**
 * An explanation of what's going on here.
 *
 * Web browsers have built-in support various field types - inputs, dropdowns, radios etc. The semantics of
 * the field markup is "hardcoded" - i.e. they require you to enter certain characters to create something that
 * has meaning to the browser - to signify that you want an input field. Form Tools field types are a totally
 * separate layer above this: you can create field types for any old thing - google maps fields, date fields,
 * plain text fields etc. These may or may not map to "actual" form field types understood natively by the
 * browser. But in order for the Add Form process to intelligently map the raw form field types to a Form
 * Tools field type, we need to provide an (optional) mapping.
 *
 * For instance, if you create a Date field type within Form Tools, it's really just a <input type="text" />
 * field in your original form that's enhanced with the jQuery calendar within FT. However, in order to
 * provide the user with the option of *choosing* the Date field type for an input field during the Add
 * External Form process, this mapping is necessary.
 */
$g_raw_field_types = array(
  // HTML 4 field types
  "textbox"       => "word_textbox",
  "textarea"      => "word_textarea",
  "password"      => "word_password",
  "radio-buttons" => "phrase_radio_buttons",
  "checkboxes"    => "word_checkboxes",
  "select"        => "word_dropdown",
  "multi-select"  => "phrase_multi_select_dropdown",
  "file"          => "word_file"
);

// pity this has to be hardcoded... but right now the field setting options don't have their own unique
// identifiers
$g_default_datetime_format = "datetime:yy-mm-dd|h:mm TT|ampm`true";


// ------------------------------------------------------------------------------------------------


/**
 * Return information about the field types in the database. To provide a little re-usability, the two
 * params let you choose whether or not to return the field types AND their settings or just
 * the field types, and whether or not you want to limit the results to specific field type IDs.
 *
 * @param array $return_settings
 * @param array $field_type_ids
 * @return array
 */
function ft_get_field_types($return_settings = false, $field_type_ids = array())
{
  global $g_table_prefix;

  $field_type_id_clause = "";
  if (!empty($field_type_ids))
  {
    $field_type_id_clause = "AND ft.field_type_id IN (" . implode(",", $field_type_ids) . ")";
  }

  $query = mysql_query("
    SELECT *, g.list_order as group_list_order, ft.list_order as field_type_list_order
    FROM   {$g_table_prefix}field_types ft, {$g_table_prefix}list_groups g
    WHERE  g.group_type = 'field_types' AND
           ft.group_id = g.group_id
           $field_type_id_clause
    ORDER BY g.list_order, ft.list_order
  ");

  $field_types = array();
  while ($row = mysql_fetch_assoc($query))
  {
    if ($return_settings) {
      $curr_field_type_id = $row["field_type_id"];
      $row["settings"] = ft_get_field_type_settings($curr_field_type_id, false);
    }

    $field_types[] = $row;
  }

  return $field_types;
}


/**
 * Simple function to return a hash of field_type_id => field type name. Used for display purposes.
 *
 * @return array
 */
function ft_get_field_type_names()
{
  global $g_table_prefix, $LANG;

  $query = mysql_query("
    SELECT field_type_id, field_type_name
    FROM   {$g_table_prefix}field_types
  ");

  $info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $info[$row["field_type_id"]] = ft_eval_smarty_string($row["field_type_name"]);
  }

  return $info;
}


/**
 * Returns an array used for populating Field Type dropdowns. This returns an array with the following
 * structure:
 *
 * [
 *   [
 *     "group":       [ ... ]
 *     "field_types": [ ... ]
 *   ],
 *   ...
 * ]
 */
function ft_get_grouped_field_types()
{
  global $g_table_prefix;

  $group_query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}list_groups
    WHERE  group_type = 'field_types'
    ORDER BY list_order
  ");

  $info = array();
  while ($row = mysql_fetch_assoc($group_query))
  {
    $group_id = $row["group_id"];
    $field_query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_types
      WHERE  group_id = $group_id
      ORDER BY list_order
    ");

    $field_types = array();
    while ($field_type_info = mysql_fetch_assoc($field_query))
    {
      $field_type_id = $field_type_info["field_type_id"];
      $settings_count_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}field_type_settings
        WHERE  field_type_id = $field_type_id
          ");

      $settings = array();
      while ($settings_row = mysql_fetch_assoc($settings_count_query))
        $settings[] = $settings_row;

      $field_type_info["settings"] = $settings;
      $field_types[] = $field_type_info;
    }
    $curr_group = array(
      "group"       => $row,
      "field_types" => $field_types
    );
    $info[] = $curr_group;
  }

  return $info;
}


/**
 * Returns all field type groups in the database (including ones with no field types in them). It
 * also returns a num_field_types key containing the number of field types in each group.
 *
 * @return hash [group ID] => group name
 */
function ft_get_field_type_groups($return_field_types = true)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}list_groups
    WHERE  group_type = 'field_types'
    ORDER BY list_order
  ");

  // inefficient
  $info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $group_id = $row["group_id"];
    $count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}field_types WHERE group_id = $group_id");
    $result = mysql_fetch_assoc($count_query);
    $row["num_field_types"] = $result["c"];
    $info[] = $row;
  }

  return $info;
}


/**
 * Returns info about a field type.
 *
 * @param integer $field_type_id
 * @param boolean $return_all_info
 */
function ft_get_field_type($field_type_id, $return_all_info = false)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_types
    WHERE  field_type_id = $field_type_id
  ");
  $info = mysql_fetch_assoc($query);

  if ($return_all_info)
    $info["settings"] = ft_get_field_type_settings($field_type_id, true);

  return $info;
}


function ft_get_field_type_by_identifier($identifier)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_types
    WHERE  field_type_identifier = '$identifier'
  ");

  $info = mysql_fetch_assoc($query);

  if (!empty($info))
  {
    $field_type_id = $info["field_type_id"];
    $info["settings"] = ft_get_field_type_settings($field_type_id);
  }

  return $info;
}

function ft_get_field_type_id_by_identifier($identifier)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_types
    WHERE  field_type_identifier = '$identifier'
  ");

  $info = mysql_fetch_assoc($query);

  if (empty($info))
    return "";
  else
    return $info["field_type_id"];
}


/**
 * This finds out the field type ID for a particular field.
 *
 * @param integer $field_id
 * @return integer the field type ID
 */
function ft_get_field_type_id($field_id)
{
  global $g_table_prefix;

  $query = mysql_query("SELECT field_type_id FROM {$g_table_prefix}form_fields WHERE field_id = $field_id");
  $result = mysql_fetch_assoc($query);

  return $result["field_type_id"];
}


/**
 * Returns all info about a field type setting.
 *
 * @param integer $setting_id
 */
function ft_get_field_type_setting($setting_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_type_settings
    WHERE  setting_id = $setting_id
  ");

  $options_query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_type_setting_options
    WHERE  setting_id = $setting_id
    ORDER BY option_order
  ");

  $options = array();
  while ($row = mysql_fetch_assoc($options_query))
  {
    $options[] = array(
      "option_text"       => $row["option_text"],
      "option_value"      => $row["option_value"],
      "option_order"      => $row["option_order"],
      "is_new_sort_group" => $row["is_new_sort_group"]
    );
  }

  $info = mysql_fetch_assoc($query);
  $info["options"] = $options;

  return $info;
}


/**
 * Returns all information about a field type settings for a field type, as identified by its
 * field type identifier string.
 *
 * @param integer $field_type_id
 * @param string $field_type_setting_identifier
 * @return array
 */
function ft_get_field_type_setting_by_identifier($field_type_id, $field_type_setting_identifier)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_type_settings
    WHERE  field_type_id = $field_type_id AND
           field_setting_identifier = '$field_type_setting_identifier'
  ");

  $field_setting_info = mysql_fetch_assoc($query);

  if (!empty($field_setting_info))
  {
  	$setting_id = $field_setting_info["setting_id"];
    $options_query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_type_setting_options
      WHERE  setting_id = $setting_id
      ORDER BY option_order
    ");

    $options = array();
    while ($row = mysql_fetch_assoc($options_query))
    {
      $options[] = array(
        "option_text"       => $row["option_text"],
        "option_value"      => $row["option_value"],
        "option_order"      => $row["option_order"],
        "is_new_sort_group" => $row["is_new_sort_group"]
      );
    }

    $field_setting_info["options"] = $options;
  }

  return $field_setting_info;
}


/**
 * Returns the setting ID by its identifier.
 *
 * @param integer $field_type_id
 * @param string $field_type_setting_identifier
 * @return integer
 */
function ft_get_field_type_setting_id_by_identifier($field_type_id, $field_type_setting_identifier)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_type_settings
    WHERE  field_type_id = $field_type_id AND
           field_setting_identifier = '$field_type_setting_identifier'
  ");

  $field_setting_info = mysql_fetch_assoc($query);

  return (!empty($field_setting_info)) ? $field_setting_info["setting_id"] : "";
}



/**
 * Returns all settings for a field type, including the options - if requested.
 *
 * @param integer $field_type_id
 * @param boolean $return_options

function ft_get_field_type_settings($field_type_id, $return_options = false)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_type_settings
    WHERE  field_type_id = $field_type_id
    ORDER BY list_order
  ");

  $info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $setting_id = $row["setting_id"];
    if ($return_options)
    {
      $options = array();
      $options_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}field_type_setting_options
        WHERE  setting_id = $setting_id
        ORDER BY option_order
      ");
      while ($option_row = mysql_fetch_assoc($options_query))
      {
        $options[] = array(
          "option_text"       => $option_row["option_text"],
          "option_value"      => $option_row["option_value"],
          "option_order"      => $option_row["option_order"],
          "is_new_sort_group" => $option_row["is_new_sort_group"]
        );
      }
      $row["options"] = $options;
    }
    $info[] = $row;
  }

  return $info;
}
*/


/**
 * Returns all settings for a field type, including the options - if requested.
 *
 * The previous function should be deprecated in favour of this.
 *
 * @param mixed $field_type_id_or_ids the integer or array
 * @param boolean $return_options
 */
function ft_get_field_type_settings($field_type_id_or_ids, $return_options = false)
{
  global $g_table_prefix;

  if (empty($field_type_id_or_ids))
    return array();

  if (is_array($field_type_id_or_ids))
  {
    $field_type_id_str = implode(",", $field_type_id_or_ids);
    $return_one_field_type = false;
  }
  else
  {
    $field_type_id_str = $field_type_id_or_ids;
    $return_one_field_type = true;
  }

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_type_settings
    WHERE  field_type_id IN ($field_type_id_str)
    ORDER BY list_order
  ");

  $info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $field_type_id = $row["field_type_id"];
    $setting_id    = $row["setting_id"];
    if ($return_options)
    {
      $options = array();
      $options_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}field_type_setting_options
        WHERE  setting_id = $setting_id
        ORDER BY option_order
      ");
      while ($option_row = mysql_fetch_assoc($options_query))
      {
        $options[] = array(
          "option_text"       => $option_row["option_text"],
          "option_value"      => $option_row["option_value"],
          "option_order"      => $option_row["option_order"],
          "is_new_sort_group" => $option_row["is_new_sort_group"]
        );
      }
      $row["options"] = $options;
    }

    // for backward compatibility
    if ($return_one_field_type)
    {
      $info[] = $row;
    }
    else
    {
      if (!array_key_exists($field_type_id, $info))
        $info[$field_type_id] = array();

      $info[$field_type_id][] = $row;
    }
  }

  return $info;
}


/**
 * Used on the Edit Fields page to generate the list of settings & setting-options for all field types. This
 * is used to actual create the appropriate markup in the Edit Fields dialog window. Generates the following
 * data structure:
 *
 * page_ns.field_settings["field_type_X"] = [
 *   {
 *     setting_id:  X,
 *     field_label: "",
 *     field_type:  "textbox",
 *     field_orientation: "",
 *     options: [
 *       {
 *         value: "",
 *         text:  ""
 *       },
 *       ...
 *     ]
 *   },
 *   ...
 * ]
 */
function ft_generate_field_type_settings_js($namespace = "page_ns")
{
  global $g_table_prefix, $LANG;

  $query = mysql_query("
    SELECT DISTINCT field_type_id
    FROM   {$g_table_prefix}field_type_settings
  ");

  $curr_js = array("{$namespace}.field_settings = {};");

  $field_setting_rows = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $field_type_id = $row["field_type_id"];

    $settings_query = mysql_query("
      SELECT setting_id, field_label, field_type, field_orientation, default_value
      FROM   {$g_table_prefix}field_type_settings
      WHERE field_type_id = $field_type_id
      ORDER BY list_order
    ");

    $settings_js = array();
    while ($settings_row = mysql_fetch_assoc($settings_query))
    {
      $setting_id = $settings_row["setting_id"];
      $field_label = $settings_row["field_label"];
      $field_type = $settings_row["field_type"];
      $default_value = $settings_row["default_value"];
      $field_orientation = $settings_row["field_orientation"];

      // now one more nested query (!!) to get all the options for this field type setting
      $options_query = mysql_query("
        SELECT option_text, option_value
        FROM   {$g_table_prefix}field_type_setting_options
        WHERE  setting_id = $setting_id
        ORDER BY option_order
      ");
      $options = array();
      while ($options_row = mysql_fetch_assoc($options_query))
      {
        $value = $options_row["option_value"];
        $text  = $options_row["option_text"];
        $options[] =<<< END
      {
        value: "$value",
        text:  "$text"
      }
END;
      }
      $options_js = implode(",\n", $options);
      if (!empty($options_js))
        $options_js = "\n$options_js\n    ";

      $settings_js[] =<<< END
  {
    setting_id:  $setting_id,
    field_label: "$field_label",
    field_type:  "$field_type",
    default_value: "$default_value",
    field_orientation:  "$field_orientation",
    options: [$options_js]
  }
END;
    }

    $curr_js[] = "{$namespace}.field_settings[\"field_type_$field_type_id\"] = [";
    $curr_js[] = implode(",\n", $settings_js);
    $curr_js[] = "];";
  }

  $field_setting_rows[] = implode("\n", $curr_js);

  return implode("\n", $field_setting_rows);
}


/**
 * This function returns a hash containing usage information about a field type. The hash is
 * structured like so:
 *
 *   "total_num_fields" => X (the number
 *   "usage_by_form" => array (
 *     array("form_name" => "...", form_id => X, "num_fields" => Y),
 *     array("form_name" => "...", form_id => X, "num_fields" => Z)
 *   )
 *
 * @param integer $field_type
 * @return array
 */
function ft_get_field_type_usage($field_type_id)
{
  global $g_table_prefix;

  // grr! This should be a single query as the next
  $query = mysql_query("SELECT DISTINCT form_id FROM {$g_table_prefix}form_fields WHERE field_type_id = $field_type_id");

  $info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $form_id = $row["form_id"];

    $field_type_query = mysql_query("
      SELECT count(*) as c
      FROM {$g_table_prefix}form_fields
      WHERE form_id = $form_id AND
            field_type_id = $field_type_id
    ");
    $result = mysql_fetch_assoc($field_type_query);

    $info[] = array(
      "form_id"    => $form_id,
      "form_name"  => ft_get_form_name($form_id),
      "num_fields" => $result["c"]
    );
  }

  return $info;
}


/**
 * Used in the Add External Form process. This generates a JS object that maps "raw" field types to those
 * field types specified in the Custom Field module. This allows the script to provide a list of appropriate
 * field types for each form field, from which the user can choose.
 *
 * Any fields that aren't mapped to a "raw" field won't get listed here. They can be used when editing forms,
 * but not when initially adding them. Also, for Option List fields (checkboxes, radios, dropdowns, multi-selects),
 * this function ONLY returns those custom fields that specify an Option List. Without it, the user wouldn't be
 * able to map the options in their form to an Option List associated with a field type.
 *
 * @return string a JS object
 */
function ft_get_raw_field_types_js($namespace = "page_ns")
{
  global $g_table_prefix, $g_raw_field_types;

  $field_types = ft_get_field_types();

  $mapped = array();
  while (list($raw_field_type, $field_type_label) = each($g_raw_field_types))
  {
    $curr_mapped_field_types = array();
    foreach ($field_types as $field_type_info)
    {
      if ($field_type_info["raw_field_type_map"] != $raw_field_type)
        continue;

      if (in_array($raw_field_type, array("radio-buttons", "checkboxes", "select", "multi-select")))
      {
        if (empty($field_type_info["raw_field_type_map_multi_select_id"]))
          continue;
      }

      $curr_mapped_field_types[] = array(
        "field_type_id"   => $field_type_info["field_type_id"],
        "field_type_name" => ft_eval_smarty_string($field_type_info["field_type_name"]),
        "compatible_field_sizes" => $field_type_info["compatible_field_sizes"],
        "raw_field_type_map_multi_select_id" => $field_type_info["raw_field_type_map_multi_select_id"]
      );
    }

    $mapped[$raw_field_type] = $curr_mapped_field_types;
  }
  reset($g_raw_field_types);

  $js = $namespace . ".raw_field_types = " . ft_convert_to_json($mapped);
  return $js;
}


/**
 * Returns all CSS or javascript defined for all fields types. Note: this does NOT include external files uploaded
 * through the Resources section of the Custom Fields module. That's
 *
 * @param $resource_type
 */
function ft_get_field_type_resources($resource_type)
{
  global $g_table_prefix;

  $db_column    = "";
  $setting_name = "";
  if ($resource_type == "css")
  {
    $setting_name = "edit_submission_shared_resources_css";
    $db_column    = "resources_css";
  }
  else
  {
    $setting_name = "edit_submission_shared_resources_js";
    $db_column    = "resources_js";
  }

  $str = ft_get_settings($setting_name);
  $query = mysql_query("
    SELECT $db_column
    FROM   {$g_table_prefix}field_types
  ");
  while ($row = mysql_fetch_assoc($query))
    $str .= $row[$db_column] . "\n";

  return $str;
}


/**
 * A simple function to return a hash of field_type_id => hash of information that's needed
 * for processing the field type and storing it in the database. Namely: the PHP processing
 * code for the field type and whether it's a date or file field.
 *
 * @return array
 */
function ft_get_field_type_processing_info()
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT field_type_id, php_processing, is_date_field, is_file_field
    FROM   {$g_table_prefix}field_types
  ") or die(mysql_error());

  $result = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $result[$row["field_type_id"]] = array(
      "php_processing" => trim($row["php_processing"]),
      "is_date_field"  => $row["is_date_field"],
      "is_file_field"  => $row["is_file_field"]
    );
  }

  return $result;
}


/**
 * Used in the ft_update_submission function. This retrieves all setting information for a
 * field - including the field type settings that weren't overridden.
 *
 * @param $field_ids
 * @return array a hash of [field_id][identifier] = values
 */
function ft_get_form_field_field_type_settings($field_ids = array(), $form_fields)
{
  global $g_table_prefix;

  if (empty($field_ids))
    return array();

  $field_id_str = implode(",", $field_ids);

  // get the overridden settings
  $query = mysql_query("
    SELECT fts.field_type_id, fs.field_id, fts.field_setting_identifier, fs.setting_value
    FROM   {$g_table_prefix}field_type_settings fts, {$g_table_prefix}field_settings fs
    WHERE  fts.setting_id = fs.setting_id AND
           fs.field_id IN ($field_id_str)
    ORDER BY fs.field_id
  ");

  $overridden_settings = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $overridden_settings[$row["field_id"]][$row["field_setting_identifier"]] = $row["setting_value"];
  }

  // now figure out what field_type_ids we're concerned about
  $relevant_field_type_ids = array();
  $field_id_to_field_type_id_map = array();
  foreach ($form_fields as $field_info)
  {
    if (!in_array($field_info["field_id"], $field_ids))
      continue;

    if (!in_array($field_info["field_type_id"], $relevant_field_type_ids))
      $relevant_field_type_ids[] = $field_info["field_type_id"];

    $field_id_to_field_type_id_map[$field_info["field_id"]] = $field_info["field_type_id"];
  }

  // this returns ALL the default field type settings. The function is "dumb": it doesn't evaluate
  // any of the dynamic default values - that's done below
  $default_field_type_settings = ft_get_field_type_settings($relevant_field_type_ids);

  // now overlay the two and return all field settings for all fields
  $results = array();
  foreach ($field_ids as $field_id)
  {
    $results[$field_id] = array();
    $field_type_settings = $default_field_type_settings[$field_id_to_field_type_id_map[$field_id]];
    foreach ($field_type_settings as $setting_info)
    {
      $identifier         = $setting_info["field_setting_identifier"];
      $default_value_type = $setting_info["default_value_type"];
      if ($default_value_type == "static")
        $value = $setting_info["default_value"];
      else
      {
        $parts = explode(",", $setting_info["default_value"]);

        // dynamic setting values should ALWAYS be of the form "setting_name,module_folder/'core'". If they're
        // not, just ignore it
        if (count($parts) != 2)
          $value = "";
        else
        {
          $value = ft_get_settings($parts[0], $parts[1]);
        }
      }

      if (isset($overridden_settings[$field_id]) && isset($overridden_settings[$field_id][$identifier]))
        $value = $overridden_settings[$field_id][$identifier];

      $results[$field_id][$identifier] = $value;
    }
  }

  return $results;
}


/**
 * This is used on the Submission Listing page to provide the default value for the date range field, which
 * appears when a user chooses a date to search on.
 *
 * @param string $choice
 * @return array a hash with the two keys:
 *                 "default_date_field_search_value": the default value to show. This depends on what they
 *                       selected on the Settings -> Main tab field.
 *                 "date_field_search_js_format": the format to pass to the jQuery date range picker
 */
function ft_get_default_date_field_search_value($choice)
{
  global $LANG, $g_search_form_date_field_format;

  $php_date_format = "";
  $date_field_search_js_format = "";
  if ($g_search_form_date_field_format == "d/m/y") {
  	$php_date_format = "j/n/Y";
  	$date_field_search_js_format = "d/m/yy";
  } else {
  	$php_date_format = "n/j/Y";
  	$date_field_search_js_format = "m/d/yy";
  }

  $value = "";
  switch ($choice)
  {
  	case "none":
      $value = $LANG["phrase_select_date"];
      break;
  	case "today":
  	  $value = date($php_date_format);
      break;
  	case "last_7_days":
  	  $now  = date("U");
  	  $then = $now - (60 * 60 * 24 * 7);
  	  $value = date($php_date_format, $then) . " - " . date($php_date_format, $now);
      break;
  	case "month_to_date":
      $current_month = date("n");
      $current_year  = date("Y");
      if ($g_search_form_date_field_format == "d/m/y") {
        $value = "1/$current_month/$current_year - " . date($php_date_format);
      } else {
      	$value = "$current_month/1/$current_year - " . date($php_date_format);
      }
      break;
  	case "year_to_date":
      $current_year  = date("Y");
      $value = "1/1/$current_year - " . date($php_date_format);
      break;
  	case "previous_month":
  	  $current_month = date("n");
  	  $previous_month = ($current_month == 1) ? 12 : $current_month-1;
  	  $current_year  = date("Y");
  	  $mid_previous_month = mktime(0, 0, 0, $previous_month, 15, $current_year);
  	  $num_days_in_last_month = date("t", $mid_previous_month);
  	  if ($g_search_form_date_field_format == "d/m/y") {
  	  	$value = "1/$previous_month/$current_year - $num_days_in_last_month/$previous_month/$current_year";
  	  } else {
  	  	$value = "$previous_month/1/$current_year - $previous_month/$num_days_in_last_month/$current_year";
  	  }
      break;
  }

  return array(
    "default_date_field_search_value" => $value,
    "date_field_search_js_format"     => $date_field_search_js_format
  );
}


/**
 * Helper function to return a list of field type IDs for file fields. Note, this was only added very late in the Alpha
 * so it's not widely used. Use it!
 *
 * @return array $field_type_ids
 */
function ft_get_file_field_type_ids()
{
  global $g_table_prefix;

  $query = mysql_query("SELECT field_type_id FROM {$g_table_prefix}field_types WHERE is_file_field = 'yes'");
  $field_type_ids = array();
  while ($row = mysql_fetch_assoc($query))
  {
  	$field_type_ids[] = $row["field_type_id"];
  }

  return $field_type_ids;
}

/**
 * Returns a list of field type IDs for date field types
 *
 * @return array $field_type_ids
 */
function ft_get_date_field_type_ids()
{
  global $g_table_prefix;

  $query = mysql_query("SELECT field_type_id FROM {$g_table_prefix}field_types WHERE is_date_field = 'yes'");
  $field_type_ids = array();
  while ($row = mysql_fetch_assoc($query))
  {
  	$field_type_ids[] = $row["field_type_id"];
  }

  return $field_type_ids;
}
