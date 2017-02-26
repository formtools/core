<?php



/**
 * This installs a single field type and all related data: settings, setting options and validation. If
 * a field type with that identifier already exists, it returns false, with an appropriate error message.
 * The field type data is all stored in the field_types.php file in this folder.
 *
 * @param integer $field_type_identifier "textbox", "textarea", etc.
 */
function cft_install_field_type($field_type_identifier, $group_id)
{
  global $g_table_prefix, $LANG;

  $field_type_info = ft_get_field_type_by_identifier($field_type_identifier);
  if (!empty($field_type_info))
  {
    return array(false, "The database has already been populated with the $field_type_identifier field type.");
  }

  $cft_field_types = cft_get_field_types();
  $data = $cft_field_types[$field_type_identifier];

  // Step 1: install the main field_types record
  $count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}field_types WHERE group_id = $group_id");
  $count_result = mysql_fetch_assoc($count_query);
  $next_list_order = $count_result["c"] + 1;

  $query = "
    INSERT INTO {$g_table_prefix}field_types (is_editable, non_editable_info, managed_by_module_id, field_type_name,
      field_type_identifier, group_id, is_file_field, is_date_field, raw_field_type_map, raw_field_type_map_multi_select_id,
      list_order, compatible_field_sizes, view_field_rendering_type, view_field_php_function_source, view_field_php_function,
      view_field_smarty_markup, edit_field_smarty_markup, php_processing, resources_css, resources_js)
    VALUES (
      '{$data["field_type"]["is_editable"]}',
      {$data["field_type"]["non_editable_info"]},
      {$data["field_type"]["managed_by_module_id"]},
      '{$data["field_type"]["field_type_name"]}',
      '{$data["field_type"]["field_type_identifier"]}',
      $group_id,
      '{$data["field_type"]["is_file_field"]}',
      '{$data["field_type"]["is_date_field"]}',
      '{$data["field_type"]["raw_field_type_map"]}',
      NULL,
      $next_list_order,
      '{$data["field_type"]["compatible_field_sizes"]}',
      '{$data["field_type"]["view_field_rendering_type"]}',
      '{$data["field_type"]["view_field_php_function_source"]}',
      '{$data["field_type"]["view_field_php_function"]}',
      '{$data["field_type"]["view_field_smarty_markup"]}',
      '{$data["field_type"]["edit_field_smarty_markup"]}',
      '{$data["field_type"]["php_processing"]}',
      '{$data["field_type"]["resources_css"]}',
      '{$data["field_type"]["resources_js"]}'
    )
  ";

  $result = mysql_query($query);
  if (!$result)
  {
    $error = mysql_error();
    cft_rollback_new_installation();
    return array(false, "Failed to insert field type $field_type_identifier: $error");
  }
  $field_type_id = mysql_insert_id();

  // Step 2: field type settings
  $setting_id_used_for_raw_field_map = "";

  for ($i=1; $i<=count($data["settings"]); $i++)
  {
    $setting_info             = $data["settings"][$i-1];
    $field_label              = $setting_info["field_label"];
    $field_setting_identifier = $setting_info["field_setting_identifier"];
    $field_type               = $setting_info["field_type"];
    $field_orientation        = $setting_info["field_orientation"];
    $default_value_type       = $setting_info["default_value_type"];
    $default_value            = $setting_info["default_value"];
    $use_for_option_list_map  = isset($setting_info["use_for_option_list_map"]) ? $setting_info["use_for_option_list_map"] : false;

    $query = "
      INSERT INTO {$g_table_prefix}field_type_settings (field_type_id, field_label, field_setting_identifier,
        field_type, field_orientation, default_value_type, default_value, list_order)
      VALUES ($field_type_id, '$field_label', '$field_setting_identifier', '$field_type',
        '$field_orientation', '$default_value_type', '$default_value', $i)
    ";
    $result = mysql_query($query);
    if (!$result)
    {
      $error = mysql_error();
      cft_rollback_new_installation();
      return array(false, "Failed to insert setting $field_setting_identifier: $error");
    }
    $setting_id = mysql_insert_id();

    // if this setting is being used for the raw field type option list, update the field type record
    if ($use_for_option_list_map)
    {
      mysql_query("
        UPDATE {$g_table_prefix}field_types
        SET    raw_field_type_map_multi_select_id = $setting_id
        WHERE  field_type_id = $field_type_id
      ");
    }

    for ($j=1; $j<=count($setting_info["options"]); $j++)
    {
      $option_info = $setting_info["options"][$j-1];
      $option_text       = $option_info["option_text"];
      $option_value      = $option_info["option_value"];
      $is_new_sort_group = $option_info["is_new_sort_group"];

      $query = "
        INSERT INTO {$g_table_prefix}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group)
        VALUES ($setting_id, '$option_text', '$option_value', $j, '$is_new_sort_group')
      ";
      $result = mysql_query($query);
      if (!$result)
      {
        $error = mysql_error();
        cft_rollback_new_installation();
        return array(false, "Failed to insert setting option $field_setting_identifier, $option_text: $error");
      }
    }
  }


  // Step 4: Validation
  for ($i=1; $i<=count($data["validation"]); $i++)
  {
    $rule_info = $data["validation"][$i-1];
    $rsv_rule                 = $rule_info["rsv_rule"];
    $rule_label               = $rule_info["rule_label"];
    $rsv_field_name           = $rule_info["rsv_field_name"];
    $custom_function          = $rule_info["custom_function"];
    $custom_function_required = $rule_info["custom_function_required"];
    $default_error_message    = $rule_info["default_error_message"];

    $query = "
      INSERT INTO {$g_table_prefix}field_type_validation_rules (field_type_id, rsv_rule, rule_label,
        rsv_field_name, custom_function, custom_function_required, default_error_message, list_order)
      VALUES ($field_type_id, '$rsv_rule', '$rule_label', '$rsv_field_name', '$custom_function',
        '$custom_function_required', '$default_error_message', $i)
    ";
    $result = mysql_query($query);
    if (!$result)
    {
      $error = mysql_error();
      cft_rollback_new_installation();
      return array(false, "Failed to insert validation rule $rule_label: $error");
    }
  }

  return array(true, "");
}


/**
 * Called during the installation. If there are ever ANY errors, it empties the contents of the field
 * type tables.
 *
 * This shouldn't ever occur, of course. However, the calling script will always return the explicit error
 * that occurs, so we should be able to plug any problems that occur, when they occur.
 */
function cft_rollback_new_installation()
{
  global $g_table_prefix;

  @mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_type = 'field_types'");
  @mysql_query("TRUNCATE TABLE {$g_table_prefix}field_types");
  @mysql_query("TRUNCATE TABLE {$g_table_prefix}field_type_settings");
  @mysql_query("TRUNCATE TABLE {$g_table_prefix}field_type_setting_options");
  @mysql_query("TRUNCATE TABLE {$g_table_prefix}field_type_validation_rules");
}
