<?php

/**
 * This file contains all functions relating to the field types (select, radios etc). Added in 2.1.0
 * with the addition of Custom Fields.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
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

      $rules_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}field_type_validation_rules
        WHERE  field_type_id = $field_type_id
        ORDER BY list_order
          ");

      $rules = array();
      while ($rules_row = mysql_fetch_assoc($rules_query))
        $rules[] = $rules_row;

      $field_type_info["validation"] = $rules;
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
  {
    $info["settings"]   = ft_get_field_type_settings($field_type_id, true);
    $info["validation"] = ft_get_field_type_validation_rules($field_type_id);
  }

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
function ft_generate_field_type_settings_js($options = array())
{
  global $g_table_prefix, $LANG;

  $namespace = isset($options["page_ns"]) ? $options["page_ns"] : "page_ns";
  $js_key    = isset($options["js_key"]) ? $options["js_key"] : "field_type_id";

  // for dev / prod
  $minimize = true;

  $delimiter = "\n";
  if ($minimize)
    $delimiter = "";

  $query = mysql_query("
    SELECT DISTINCT field_type_id
    FROM   {$g_table_prefix}field_type_settings
  ");

  $field_type_id_to_identifier_map = ft_get_field_type_id_to_identifier();
  $curr_js = array("{$namespace}.field_settings = {};");

  $field_setting_rows = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $field_type_id = $row["field_type_id"];

    $settings_query = mysql_query("
      SELECT setting_id, field_label, field_setting_identifier, field_type, field_orientation, default_value
      FROM   {$g_table_prefix}field_type_settings
      WHERE field_type_id = $field_type_id
      ORDER BY list_order
    ");

    $settings_js = array();
    while ($settings_row = mysql_fetch_assoc($settings_query))
    {
      $setting_id = $settings_row["setting_id"];
      $field_label = ft_eval_smarty_string($settings_row["field_label"]);
      $field_setting_identifier = $settings_row["field_setting_identifier"];
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
        $text  = ft_eval_smarty_string($options_row["option_text"]);
        $options[] = "{ value: \"$value\", text: \"$text\" }";
      }
      $options_js = implode(",$delimiter", $options);
      if (!empty($options_js))
        $options_js = "\n$options_js\n    ";

      $settings_js[] =<<< END
  { setting_id:  $setting_id, field_label: "$field_label", field_setting_identifier: "$field_setting_identifier", field_type:  "$field_type", default_value: "$default_value", field_orientation: "$field_orientation", options: [$options_js] }
END;
    }

    if ($js_key == "field_type_id")
      $curr_js[] = "{$namespace}.field_settings[\"field_type_$field_type_id\"] = [";
    else
    {
      $field_type_identifier = $field_type_id_to_identifier_map[$field_type_id];
      $curr_js[] = "{$namespace}.field_settings[\"$field_type_identifier\"] = [";
    }

    $curr_js[] = implode(",$delimiter", $settings_js);
    $curr_js[] = "];";
  }

  $field_setting_rows[] = implode("$delimiter", $curr_js);

  return implode("$delimiter", $field_setting_rows);
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

    if (!isset($field_id_to_field_type_id_map[$field_id]) || !isset($default_field_type_settings[$field_id_to_field_type_id_map[$field_id]]))
      continue;

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


/**
 * Helper function to return a hash of field type ID => field type identifier.
 *
 * @return array
 */
function ft_get_field_type_id_to_identifier()
{
  $field_types = ft_get_field_types();
  $map = array();
  foreach ($field_types as $field_type_info)
  {
    $map[$field_type_info["field_type_id"]] = $field_type_info["field_type_identifier"];
  }

  return $map;
}


/**
 * This should be the one and only place that actually generates the content for a field, for it
 * to be Viewed. This is used on the Submission Listing page, Edit Submission page (for viewable,
 * non-editable fields), in the Export Manager, in the Email Templates, and anywhere else that needs
 * to output the content of a field.
 *
 *    This function is the main source of slowness in 2.1.0. I'll be investigating ways to speed it up
 *    in the Beta.
 *
 * @param array $params a hash with the following:
 *              REQUIRED VALUES:
 *                form_id
 *                submission_id
 *                field_info - a hash containing details of the field:
 *                   REQUIRED:
 *                     field_id
 *                     field_type_id
 *                     col_name
 *                     field_name
 *                     settings - all extended settings defined for the field
 *                   OPTIONAL:
 *                     anything else you want
 *                field_types - all, or any that are relevant. But it should be an array, anyway
 *                value - the actual value stored in the field
 *                settings - (from ft_get_settings())
 * @return string
 */
function ft_generate_viewable_field($params)
{
  global $LANG, $g_root_url, $g_root_dir, $g_multi_val_delimiter, $g_cache;

  // REQUIRED
  $form_id       = $params["form_id"];
  $submission_id = $params["submission_id"];
  $field_info    = $params["field_info"];
  $field_types   = $params["field_types"];
  $value         = $params["value"];
  $settings      = $params["settings"];
  $context       = $params["context"];

  // loop through the field types and store the one we're interested in in $field_type_info
  $field_type_info = array();
  foreach ($field_types as $curr_field_type)
  {
    if ($field_info["field_type_id"] == $curr_field_type["field_type_id"])
    {
      $field_type_info = $curr_field_type;
      break;
    }
  }

  $markup_with_placeholders = trim($field_type_info["view_field_smarty_markup"]);
  $field_settings = $field_info["settings"];

  $output = "";
  if ($field_type_info["view_field_rendering_type"] == "none" || empty($markup_with_placeholders))
  {
    $output = $value;
  }
  else
  {
    $account_info = isset($_SESSION["ft"]["account"]) ? $_SESSION["ft"]["account"] : array();

    // now construct all available placeholders
    $placeholders = array(
      "FORM_ID"       => $form_id,
      "SUBMISSION_ID" => $submission_id,
      "FIELD_ID"      => $field_info["field_id"],
      "NAME"          => $field_info["field_name"],
      "COLNAME"       => $field_info["col_name"],
      "VALUE"         => $value,
      "SETTINGS"      => $settings,
      "CONTEXTPAGE"   => $context,
      "ACCOUNT_INFO"  => $account_info,
      "g_root_url"    => $g_root_url,
      "g_root_dir"    => $g_root_dir,
      "g_multi_val_delimiter" => $g_multi_val_delimiter
    );

    // add in all field type settings and their replacements
    foreach ($field_type_info["settings"] as $setting_info)
    {
      $curr_setting_id         = $setting_info["setting_id"];
      $curr_setting_field_type = $setting_info["field_type"];
      $default_value_type      = $setting_info["default_value_type"];
      $value                   = $setting_info["default_value"];
      $identifier              = $setting_info["field_setting_identifier"];

      if (isset($field_settings) && !empty($field_settings))
      {
  	    while (list($setting_id, $setting_value) = each($field_settings))
        {
          if ($setting_id == $curr_setting_id)
          {
            $value = $setting_value;
            break;
          }
        }
        reset($field_settings);
      }

      // next, if the setting is dynamic, convert the stored value
      if ($default_value_type == "dynamic")
      {
        // dynamic setting values should ALWAYS be of the form "setting_name,module_folder/'core'". If they're not, just ignore it
        $parts = explode(",", $value);
        if (count($parts) == 2)
        {
        	$dynamic_setting_str = $value; // "setting_name,module_folder/'core'"
          if (!array_key_exists("dynamic_settings", $g_cache))
        	  $g_cache["dynamic_settings"] = array();

        	if (array_key_exists($dynamic_setting_str, $g_cache["dynamic_settings"]))
        	  $value = $g_cache["dynamic_settings"][$dynamic_setting_str];
          else
          {
            $value = ft_get_settings($parts[0], $parts[1]);
            $g_cache["dynamic_settings"][$dynamic_setting_str] = $value;
          }
        }
      }

      // if this setting type is a dropdown list and $value is non-empty, get the option list
      if ($curr_setting_field_type == "option_list_or_form_field" && !empty($value))
      {
      	if (preg_match("/form_field:/", $value))
        {
          $value = ft_get_mapped_form_field_data($value);
        }
        else
        {
        	$option_list_id = $value;

        	if (!array_key_exists("option_lists", $g_cache))
        	  $g_cache["option_lists"] = array();

        	if (array_key_exists($option_list_id, $g_cache["option_lists"]))
        	  $value = $g_cache["option_lists"][$option_list_id];
          else
          {
            $value = ft_get_option_list($option_list_id);
            $g_cache["option_lists"][$option_list_id] = $value;
          }
        }
      }

      $placeholders[$identifier] = $value;
    }

    if ($field_type_info["view_field_rendering_type"] == "php")
    {
      $php_function = $field_type_info["view_field_php_function"];

      // if this is a module, include the module's library.php file so we have access to the function
      if ($field_type_info["view_field_php_function_source"] != "core" && is_numeric($field_type_info["view_field_php_function_source"]))
      {
      	$module_folder = ft_get_module_folder_from_module_id($field_type_info["view_field_php_function_source"]);
        @include_once("$g_root_dir/modules/$module_folder/library.php");
      }

      if (function_exists($php_function))
      {
        $output = $php_function($placeholders);
      }
    }
    else
    {
      $output = ft_eval_smarty_string($markup_with_placeholders, $placeholders);
    }
  }

  return $output;
}



// The following code contains the functions to generate the field type markup for the Core fields. The Core field
// types may be rendered by Smarty or these function. These are much faster, so they're enabled by default.


function ft_display_field_type_date($placeholders)
{
  if (empty($placeholders["VALUE"]))
    return;

  $tzo = "";
  if ($placeholders["apply_timezone_offset"] == "yes" && isset($placeholders["ACCOUNT_INFO"]["timezone_offset"]))
    $tzo = $placeholders["ACCOUNT_INFO"]["timezone_offset"];

  switch ($placeholders["display_format"])
  {
    case "yy-mm-dd":
      $php_format = "Y-m-d";
      break;
    case "dd/mm/yy":
      $php_format = "d/m/Y";
      break;
    case "mm/dd/yy":
      $php_format = "m/d/Y";
      break;
    case "M d, yy":
      $php_format = "M j, Y";
      break;
    case "MM d, yy":
      $php_format = "F j, Y";
      break;
    case "D M d, yy":
      $php_format = "D M j, Y";
      break;
    case "DD, MM d, yy":
      $php_format = "l M j, Y";
      break;
    case "dd. mm. yy.":
      $php_format = "d. m. Y.";
      break;

    case "datetime:dd/mm/yy|h:mm TT|ampm`true":
      $php_format = "d/m/Y g:i A";
      break;
    case "datetime:mm/dd/yy|h:mm TT|ampm`true":
      $php_format = "m/d/Y g:i A";
      break;
    case "datetime:yy-mm-dd|h:mm TT|ampm`true":
      $php_format = "Y-m-d g:i A";
      break;
    case "datetime:yy-mm-dd|hh:mm":
      $php_format = "Y-m-d H:i";
      break;
    case "datetime:yy-mm-dd|hh:mm:ss|showSecond`true":
      $php_format = "Y-m-d H:i:s";
      break;
    case "datetime:dd. mm. yy.|hh:mm":
      $php_format = "d. m. Y. H:i";
      break;

    default:
      break;
  }

  return ft_get_date($tzo, $placeholders["VALUE"], $php_format);
}


function ft_display_field_type_radios($placeholders)
{
  // if this isn't assigned to an Option List / form field, ignore the sucker
  if (empty($placeholders["contents"]))
    return;

  $output = "";
  foreach ($placeholders["contents"]["options"] as $curr_group_info)
  {
    $group_info = $curr_group_info["group_info"];
    $options    = $curr_group_info["options"];

    foreach ($options as $option_info)
    {
      if ($placeholders["VALUE"] == $option_info["option_value"])
      {
        $output = $option_info["option_name"];
        break;
      }
    }
  }

  return $output;
}


function ft_display_field_type_checkboxes($placeholders)
{
  // if this isn't assigned to an Option List / form field, ignore it!
  if (empty($placeholders["contents"]))
    return;

  $g_multi_val_delimiter = $placeholders["g_multi_val_delimiter"];
  $vals = explode($g_multi_val_delimiter, $placeholders["VALUE"]);

  $output = "";
  $is_first = true;
  foreach ($placeholders["contents"]["options"] as $curr_group_info)
  {
    $options = $curr_group_info["options"];

    foreach ($options as $option_info)
    {
      if (in_array($option_info["option_value"], $vals))
      {
        if (!$is_first)
          $output .= $g_multi_val_delimiter;

        $output .= $option_info["option_name"];
        $is_first = false;
      }
    }
  }

  return $output;
}


function ft_display_field_type_dropdown($placeholders)
{
  if (empty($placeholders["contents"]))
    return;

  $output = "";
  foreach ($placeholders["contents"]["options"] as $curr_group_info)
  {
    $options = $curr_group_info["options"];
    foreach ($options as $option_info)
    {
      if ($placeholders["VALUE"] == $option_info["option_value"])
      {
        $output = $option_info["option_name"];
        break;
      }
    }
  }

  return $output;
}


function ft_display_field_type_multi_select_dropdown($placeholders)
{
  // if this isn't assigned to an Option List / form field, ignore it!
  if (empty($placeholders["contents"]))
    return;

  $g_multi_val_delimiter = $placeholders["g_multi_val_delimiter"];
  $vals = explode($g_multi_val_delimiter, $placeholders["VALUE"]);

  $output = "";
  $is_first = true;
  foreach ($placeholders["contents"]["options"] as $curr_group_info)
  {
    $options = $curr_group_info["options"];

    foreach ($options as $option_info)
    {
      if (in_array($option_info["option_value"], $vals))
      {
        if (!$is_first)
          $output .= $g_multi_val_delimiter;

        $output .= $option_info["option_name"];
        $is_first = false;
      }
    }
  }

  return $output;
}


function ft_display_field_type_phone_number($placeholders)
{
  $phone_number_format = $placeholders["phone_number_format"];
  $values = explode("|", $placeholders["VALUE"]);

  $pieces = preg_split("/(x+)/", $phone_number_format, 0, PREG_SPLIT_DELIM_CAPTURE);
  $counter = 1;
  $output = "";
  $has_content = false;
  foreach ($pieces as $piece)
  {
    if (empty($piece))
      continue;

    if ($piece[0] == "x")
    {
      $value = (isset($values[$counter-1])) ? $values[$counter-1] : "";
      $output .= $value;
      if (!empty($value))
      {
        $has_content = true;
      }
      $counter++;
    } else {
      $output .= $piece;
    }
  }

  if (!empty($output) && $has_content)
    return $output;
  else
    return "";
}


function ft_display_field_type_code_markup($placeholders)
{
  $output = "";
  if ($placeholders["CONTEXTPAGE"] == "edit_submission")
  {
    $code_markup = $placeholders["code_markup"];
    $field_name  = $placeholders["NAME"];
    $value       = $placeholders["VALUE"];
    $height      = $placeholders["height"];
    $g_root_url  = $placeholders["g_root_url"];
    $output =<<< END
  <textarea id="{$name}_id" name="{$name}">{$value}</textarea>
  <script>
  var code_mirror_{$name} = new CodeMirror.fromTextArea("{$name}_id", {
    height:   "{$height}px",
    path:     "{$g_root_url}/global/codemirror/js/",
    readOnly: true,
    {if $code_markup == "HTML" || $code_markup == "XML"}
      parserfile: ["parsexml.js"],
      stylesheet: "{$g_root_url}/global/codemirror/css/xmlcolors.css"
    {elseif $code_markup == "CSS"}
      parserfile: ["parsecss.js"],
      stylesheet: "{$g_root_url}/global/codemirror/css/csscolors.css"
    {elseif $code_markup == "JavaScript"}
      parserfile: ["tokenizejavascript.js", "parsejavascript.js"],
      stylesheet: "{$g_root_url}/global/codemirror/css/jscolors.css"
    {/if}
  });
  </script>
END;
  }
  else
  {
    $output = strip_tags($placeholders["VALUE"]);
  }

  return $output;
}


/**
 * Used when updating a field. This is passed those field that have just had their field type changed. It figures
 * out what values
 *
 * @param array $field_type_map
 * @param string $field_type_settings_shared_characteristics
 * @param integer $field_id
 * @param integer $new_field_type_id
 * @param integer $old_field_type_id
 */
function ft_get_shared_field_setting_info($field_type_map, $field_type_settings_shared_characteristics, $field_id, $new_field_type_id, $old_field_type_id)
{
  $new_field_type_identifier = $field_type_map[$new_field_type_id];
  $old_field_type_identifier = $field_type_map[$old_field_type_id];

  $groups = explode("|", $field_type_settings_shared_characteristics);
  $return_info = array();
  foreach ($groups as $group_info)
  {
  	list($group_name, $vals) = explode(":", $group_info);

    $pairs = explode("`", $vals);
    $settings = array();
    foreach ($pairs as $str)
    {
      list($field_type_identifier, $setting_identifier) = explode(",", $str);
      $settings[$field_type_identifier] = $setting_identifier;
    }

    $shared_field_types = array_keys($settings);
    if (!in_array($new_field_type_identifier, $shared_field_types) || !in_array($old_field_type_identifier, $shared_field_types))
      continue;

    $old_setting_id = ft_get_field_type_setting_id_by_identifier($old_field_type_id, $settings[$new_field_type_identifier]);
    $new_setting_id = ft_get_field_type_setting_id_by_identifier($new_field_type_id, $settings[$old_field_type_identifier]);

    $old_setting_value = ft_get_field_setting($field_id, $old_setting_id);
    $return_info[] = array(
      "field_id"       => $field_id,
      "old_setting_id" => $old_setting_id,
      "new_setting_id" => $new_setting_id,
      "setting_value"  => $old_setting_value
    );
  }

  return $return_info;
}


/**
 * This is used exclusively on the Edit Forms -> fields tab. It returns a JS version of the shared characteristics
 * information for use by the page. The JS it returns in an anonymous JS object of the following form:
 *   {
 *     s(setting ID): array(characteristic IDs),
 *     ...
 *   }
 *
 * "Characteristic ID" is a made-up number for the sake of the use-case. We just need a way to recognize the shared
 * characteristics - that's what it does.
 *
 * @return string
 */
function ft_get_field_type_setting_shared_characteristics_js()
{
  $field_type_settings_shared_characteristics = ft_get_settings("field_type_settings_shared_characteristics");
  $info = ft_get_field_type_and_setting_info();
  $field_type_id_to_identifier = $info["field_type_id_to_identifier"];
  $field_identifier_to_id = array_flip($field_type_id_to_identifier);

  $groups = explode("|", $field_type_settings_shared_characteristics);
  $return_info = array();

  // this is what we're trying to generate: a hash of setting id => array( characteristic IDs )
  // The Òcharacteristic IDÓ is a new (temporary) number for characteristic. In every situation that I can
  // think of, the value array will contain a single entry (why would a setting be mapped to multiple
  // characteristics?). However, the interface doesn't limit it. To be safe, IÕll stash it in an array.
  $setting_ids_to_characteristic_ids = array();

  $characteristic_id = 1;
  foreach ($groups as $group_info)
  {
  	list($group_name, $vals) = explode(":", $group_info);

    $pairs = explode("`", $vals);
    $settings = array();
    foreach ($pairs as $str)
    {
      // we need to do a little legwork here to actually find the setting ID. The problem is that many
      // field types reference fields with the same setting identifier (it's only required to be unique within the
      // field type - not ALL field types).
      list($field_type_identifier, $setting_identifier) = explode(",", $str);

      // the shared characteristic settings may reference uninstalled modules
      if (!array_key_exists($field_type_identifier, $field_identifier_to_id))
        continue;

      $field_type_id = $field_identifier_to_id[$field_type_identifier];
      $all_field_type_setting_ids = $info["field_type_ids_to_setting_ids"][$field_type_id];

      // loop through all the settings for this field type and locate the one we're interested in
      foreach ($all_field_type_setting_ids as $setting_id)
      {
      	if ($info["setting_id_to_identifier"][$setting_id] != $setting_identifier)
      	  continue;

      	if (!array_key_exists($setting_id, $setting_ids_to_characteristic_ids))
      	  $setting_ids_to_characteristic_ids[$setting_id] = array();

      	$setting_ids_to_characteristic_ids[$setting_id][] = $characteristic_id;
      }
    }

    $characteristic_id++;
  }

  // now convert the info into a simple JS object. We could have done it above, but this keeps it simple.
  $js_lines = array();
  while (list($setting_id, $characteristic_ids) = each($setting_ids_to_characteristic_ids))
  {
    $js_lines[] = "s{$setting_id}:[" . implode(",", $characteristic_ids) . "]";
  }
  $js = "{" . implode(",", $js_lines) . "}";

  return $js;
}


/**
 * A little tricky to name. We often need the key info about the field type and their settings (i.e. IDs and names)
 * in different ways. This function returns the info in different data structures. The top level structure returned
 * is a hash. You can pick and choose what info you want. Since it's all generated with a single SQL query, it's much
 * faster to use this than separate functions.
 *
 * Note: this function returns a superset of ft_get_field_type_id_to_identifier(). If you need to access the settings
 * as well as the field type info, chances are this will be a better candidate.
 *
 * @return array
 */
function ft_get_field_type_and_setting_info()
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT ft.field_type_id, ft.field_type_name, ft.field_type_identifier, fts.*
    FROM {$g_table_prefix}field_types ft
    LEFT JOIN {$g_table_prefix}field_type_settings fts ON (ft.field_type_id = fts.field_type_id)
  ");

  $field_type_id_to_identifier   = array();
  $field_type_ids_to_setting_ids = array();
  $setting_id_to_identifier      = array();
  while ($row = mysql_fetch_assoc($query))
  {
  	$field_type_id = $row["field_type_id"];
  	$setting_id    = $row["setting_id"];

  	if (!array_key_exists($field_type_id, $field_type_id_to_identifier))
      $field_type_id_to_identifier[$field_type_id] = $row["field_type_identifier"];

  	if (!array_key_exists($field_type_id, $field_type_ids_to_setting_ids))
      $field_type_ids_to_setting_ids[$field_type_id] = array();

    $field_type_ids_to_setting_ids[$field_type_id][] = $setting_id;

  	if (!array_key_exists($setting_id, $setting_id_to_identifier))
      $setting_id_to_identifier[$setting_id] = $row["field_setting_identifier"];
  }

  $return_info = array(
    "field_type_id_to_identifier"   => $field_type_id_to_identifier,
    "field_type_ids_to_setting_ids" => $field_type_ids_to_setting_ids,
    "setting_id_to_identifier"      => $setting_id_to_identifier
  );

  return $return_info;
}


/**
 * Returns all validation rules for a field type.
 *
 * @param $field_type_id
 */
function ft_get_field_type_validation_rules($field_type_id)
{
	global $g_table_prefix;

	$query = mysql_query("
	  SELECT *
	  FROM   {$g_table_prefix}field_type_validation_rules
	  WHERE  field_type_id = $field_type_id
	  ORDER BY list_order
	");

	$rules = array();
	while ($row = mysql_fetch_assoc($query))
	{
		$rules[] = $row;
	}

	return $rules;
}
