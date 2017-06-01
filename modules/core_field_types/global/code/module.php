<?php


use FormTools\Core;
use FormTools\FieldTypes;


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

  $old_version_date = date("Ymd", General::convertDatetimeToTimestamp($old_version_info["module_date"]));

  // fix for MAJOR issue where the raw field types weren't mapped to Option List settings. This was an issue from Oct - Nov 2011, for all
  // 2.1.5 - 2.1.8 versions installed during that time.
  if ($old_version_date < 20111122)
  {
  	// dropdowns
		$select_field_type_id = FieldTypes::getFieldTypeIdByIdentifier("dropdown");
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
			$db->query("
			  UPDATE {PREFIX}field_types
			  SET    raw_field_type_map_multi_select_id = $option_list_setting_id
			  WHERE  field_type_id = $select_field_type_id AND
			         raw_field_type_map_multi_select_id IS NULL
			  LIMIT 1
			") or die(mysql_error());
		}

		$multi_select_field_type_id = FieldTypes::getFieldTypeIdByIdentifier("multi_select_dropdown");
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
			$db->query("
			  UPDATE {PREFIX}field_types
			  SET    raw_field_type_map_multi_select_id = $option_list_setting_id
			  WHERE  field_type_id = $multi_select_field_type_id AND
			         raw_field_type_map_multi_select_id IS NULL
			  LIMIT 1
			") or die(mysql_error());
		}


		$radios_field_type_id = FieldTypes::getFieldTypeIdByIdentifier("radio_buttons");
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
			$db->query("
			  UPDATE {PREFIX}field_types
			  SET    raw_field_type_map_multi_select_id = $option_list_setting_id
			  WHERE  field_type_id = $radios_field_type_id AND
			         raw_field_type_map_multi_select_id IS NULL
			  LIMIT 1
			") or die(mysql_error());
		}

		$checkboxes_field_type_id = FieldTypes::getFieldTypeIdByIdentifier("checkboxes");
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
			$db->query("
			  UPDATE {PREFIX}field_types
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
    $db = Core::$db;

    // Hmph. This ensures that the module contents only get installed once.
	$field_types = FieldTypes::get();
	if (count($field_types) > 0) {
		return array(true, "");
	}

	$group_query = "
        INSERT INTO {PREFIX}list_groups (group_type, group_name, custom_data, list_order)
        VALUES (:group_type, :group_name, :custom_data, :list_order)
	";

	// first, insert the groups for the forthcoming field types
	$db->query($group_query);
	$db->bindAll(array(
	    ":group_type" => "field_types",
        ":group_name" => "{\$LANG.phrase_standard_fields}",
        ":custom_data" => "",
        ":list_order" => 1
    ));
    try {
        $db->execute();
    } catch (PDOException $e) {
        return array(false, "Problem inserting list group item #1: " . $e->getMessage());
    }
    $group1_id = $db->getInsertId();


    $db->query($group_query);
    $db->bindAll(array(
        ":group_type" => "field_types",
        ":group_name" => "{\$LANG.phrase_special_fields}",
        ":custom_data" => "",
        ":list_order" => 2
    ));

    try {
        $db->execute();
    } catch (PDOException $e) {
        cft_rollback_new_installation();
        return array(false, "Problem inserting list group item #2: " . $e->getMessage());
    }
    $group2_id = $db->getInsertId();


    // install each field type one-by-one. If anything fails, return immediately and inform the user. This should
	// NEVER occur, because the only time this code is ever executed is when first installing the module
	list($success, $error) = cft_install_field_type("textbox", $group1_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("textarea", $group1_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("password", $group1_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("dropdown", $group1_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("multi_select_dropdown", $group1_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("radio_buttons", $group1_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("checkboxes", $group1_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("date", $group2_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("time", $group2_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("phone", $group2_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }
    list($success, $error) = cft_install_field_type("code_markup", $group2_id);
    if (!$success) {
        cft_rollback_new_installation();
        return array($success, $error);
    }

    return array(true, "");
}

