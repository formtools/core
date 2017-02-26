<?php


/**
 * Our "mock" installation function. This doesn't actually do anything: it just pretends to install
 * the module. For people installing running 2.1.4 or earlier, they already have all the field type
 * data in the database.
 *
 * @param integer $module_id
 */
function core_field_types__install($module_id)
{
  return array(true, "");
}


function core_field_types__update($old_version_info, $new_version_info)
{
	global $g_table_prefix;

  $old_version_date = date("Ymd", ft_convert_datetime_to_timestamp($old_version_info["module_date"]));

  // fix for MAJOR issue where the raw field types weren't mapped to Option List settings. This was an issue from Oct - Nov 2011, for all
  // 2.1.5 - 2.1.8 versions installed during that time.
  if ($old_version_date < 20111122)
  {
  	// dropdowns
		$select_field_type_id = ft_get_field_type_id_by_identifier("dropdown");
		$field_info = ft_get_field_type($select_field_type_id, true);
		$option_list_setting_id = "";
		foreach ($field_info["settings"] as $setting_info)
		{
			if ($setting_info["field_type"] == "option_list_or_form_field")
			{
				$option_list_setting_id = $setting_info["setting_id"];
				break;
			}
		}
		if (!empty($option_list_setting_id))
		{
			mysql_query("
			  UPDATE {$g_table_prefix}field_types
			  SET    raw_field_type_map_multi_select_id = $option_list_setting_id
			  WHERE  field_type_id = $select_field_type_id AND
			         raw_field_type_map_multi_select_id IS NULL
			  LIMIT 1
			") or die(mysql_error());
		}

		$multi_select_field_type_id = ft_get_field_type_id_by_identifier("multi_select_dropdown");
		$field_info = ft_get_field_type($multi_select_field_type_id, true);
		$option_list_setting_id = "";
		foreach ($field_info["settings"] as $setting_info)
		{
			if ($setting_info["field_type"] == "option_list_or_form_field")
			{
				$option_list_setting_id = $setting_info["setting_id"];
				break;
			}
		}
		if (!empty($option_list_setting_id))
		{
			mysql_query("
			  UPDATE {$g_table_prefix}field_types
			  SET    raw_field_type_map_multi_select_id = $option_list_setting_id
			  WHERE  field_type_id = $multi_select_field_type_id AND
			         raw_field_type_map_multi_select_id IS NULL
			  LIMIT 1
			") or die(mysql_error());
		}


		$radios_field_type_id = ft_get_field_type_id_by_identifier("radio_buttons");
		$field_info = ft_get_field_type($radios_field_type_id, true);
		$option_list_setting_id = "";
		foreach ($field_info["settings"] as $setting_info)
		{
			if ($setting_info["field_type"] == "option_list_or_form_field")
			{
				$option_list_setting_id = $setting_info["setting_id"];
				break;
			}
		}
		if (!empty($option_list_setting_id))
		{
			mysql_query("
			  UPDATE {$g_table_prefix}field_types
			  SET    raw_field_type_map_multi_select_id = $option_list_setting_id
			  WHERE  field_type_id = $radios_field_type_id AND
			         raw_field_type_map_multi_select_id IS NULL
			  LIMIT 1
			") or die(mysql_error());
		}


		$checkboxes_field_type_id = ft_get_field_type_id_by_identifier("checkboxes");
		$field_info = ft_get_field_type($checkboxes_field_type_id, true);
		$option_list_setting_id = "";
		foreach ($field_info["settings"] as $setting_info)
		{
			if ($setting_info["field_type"] == "option_list_or_form_field")
			{
				$option_list_setting_id = $setting_info["setting_id"];
				break;
			}
		}
		if (!empty($option_list_setting_id))
		{
			mysql_query("
			  UPDATE {$g_table_prefix}field_types
			  SET    raw_field_type_map_multi_select_id = $option_list_setting_id
			  WHERE  field_type_id = $checkboxes_field_type_id AND
			         raw_field_type_map_multi_select_id IS NULL
			  LIMIT 1
			") or die(mysql_error());
		}
  }

  return array(true, "");
}


/**
 * This installation function + module is unique. For Core 2.1.5 and later, it's bundled with all Core scripts
 * and installs the field types. It can't be removed. This function is called explicitly in the main Form
 * Tools installation function.
 *
 * @param integer $module_id
 */
function cft_install_module()
{
  global $g_table_prefix, $LANG, $cft_field_types;

	$field_types = ft_get_field_types();
	if (!empty($field_types))
	{
		return array(true, "");
	}

	// first, insert the groups for the forthcoming field types
	$query = mysql_query("
	  INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
	  VALUES ('field_types', '{\$LANG.phrase_standard_fields}', '', 1)
	");
  if (!$query)
  {
    return array(false, "Problem inserting list group item #1: " .mysql_error());
  }
  $group1_id = mysql_insert_id();

  $query = mysql_query("
    INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
    VALUES ('field_types', '{\$LANG.phrase_special_fields}', '', 2)
  ");
  if (!$query)
  {
  	$error = mysql_error();
  	cft_rollback_new_installation();
    return array(false, "Problem inserting list group item #2: " . $error);
  }
  $group2_id = mysql_insert_id();


	// install each field type one-by-one. If ANYTHING fails, return immediately and inform the user. This should
	// NEVER occur, because the only time this code is ever executed is when first installing the module

	list($success, $error) = cft_install_field_type("textbox", $group1_id);
  if (!$success)
  {
  	cft_rollback_new_installation();
  	return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("textarea", $group1_id);
  if (!$success)
  {
  	cft_rollback_new_installation();
  	return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("password", $group1_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("dropdown", $group1_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("multi_select_dropdown", $group1_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("radio_buttons", $group1_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("checkboxes", $group1_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("date", $group2_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("time", $group2_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("phone", $group2_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }
  list($success, $error) = cft_install_field_type("code_markup", $group2_id);
  if (!$success)
  {
    cft_rollback_new_installation();
    return array($success, $error);
  }

  return array(true, "");
}

