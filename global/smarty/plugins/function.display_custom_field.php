<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_custom_field
 * Type:     function
 * Name:     display_custom_field
 * Purpose:  This is used on the main submission listing page.
 * -------------------------------------------------------------
 */
function smarty_function_display_custom_field($params, &$smarty)
{
  global $LANG, $g_root_url, $g_root_dir, $g_multi_val_delimiter;

  if (empty($params["form_id"]))
  {
    $smarty->trigger_error("assign: missing 'form_id' parameter.");
    return;
  }
  if (empty($params["view_id"]))
  {
    $smarty->trigger_error("assign: missing 'view_id' parameter.");
    return;
  }
  if (empty($params["submission_id"]))
  {
    $smarty->trigger_error("assign: missing 'submission_id' parameter.");
    return;
  }
  if (empty($params["field_info"]))
  {
    $smarty->trigger_error("assign: missing 'field_info' parameter.");
    return;
  }
  if (empty($params["field_types"]))
  {
    $smarty->trigger_error("assign: missing 'field_types' parameter.");
    return;
  }
  if (empty($params["settings"]))
  {
    $smarty->trigger_error("assign: missing 'settings' parameter.");
    return;
  }

  $form_id       = $params["form_id"];
  $view_id       = $params["view_id"];
  $submission_id = $params["submission_id"];
  $field_info    = $params["field_info"];
  $field_types   = $params["field_types"];
  $value         = $params["value"];
  $settings      = $params["settings"];

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

  if (empty($markup_with_placeholders))
  {
    if ($field_info["col_name"] == "submission_id")
      echo $submission_id;
    else
      echo $value;
  }
  else
  {
    // now construct all available placeholders
    $placeholders = array(
      "FORM_ID"       => $form_id,
      "VIEW_ID"       => $view_id,
      "SUBMISSION_ID" => $submission_id,
      "FIELD_ID"      => $field_info["field_id"],
      "NAME"          => $field_info["field_name"],
      "COLNAME"       => $field_info["col_name"],
      "VALUE"         => $value,
      "SETTINGS"      => $settings,
      "CONTEXTPAGE"   => "submission_listing",
      "ACCOUNT_INFO"  => $_SESSION["ft"]["account"],
      "g_root_url"    => $g_root_url,
      "g_root_dir"    => $g_root_dir,
      "g_multi_val_delimiter" => $g_multi_val_delimiter
    );

    // add in all field type settings and their replacements
    foreach ($field_type_info["settings"] as $setting_info)
    {
      $curr_setting_id         = $setting_info["setting_id"];
      $curr_setting_field_type = $setting_info["field_type"];
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

      // if this setting type is a dropdown list and $value is non-empty, get the option list
      if ($curr_setting_field_type == "option_list_or_form_field" && !empty($value))
      {
        if (preg_match("/form_field:/", $value))
        {
          $value = ft_get_mapped_form_field_data($value);
        }
        else
        {
          $value = ft_get_option_list($value);
        }
      }
      $placeholders[$identifier] = $value;
    }

    echo ft_eval_smarty_string($markup_with_placeholders, $placeholders);
  }
}
