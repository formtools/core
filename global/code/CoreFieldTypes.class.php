<?php

/**
 * Form Tools - generic form processing, storage and access script
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License included in this zipfile for more details.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @version 3.0.x
 * @package 3-0-x
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use Exception;

// down the road these should all extend an abstract class, with all the content standardized
use FormTools\FieldTypes\Checkbox;
use FormTools\FieldTypes\Code;
use FormTools\FieldTypes\Date;
use FormTools\FieldTypes\Dropdown;
use FormTools\FieldTypes\MultiSelect;
use FormTools\FieldTypes\Password;
use FormTools\FieldTypes\Phone;
use FormTools\FieldTypes\Radio;
use FormTools\FieldTypes\Textarea;
use FormTools\FieldTypes\Textbox;
use FormTools\FieldTypes\Time;


class CoreFieldTypes
{

    /**
     * This installs a single field type and all related data: settings, setting options and validation. If
     * a field type with that identifier already exists, it returns false, with an appropriate error message.
     *
     * @param integer $field_type_identifier "textbox", "textarea", etc... TODO these should be constants.
     */
    public static function installFieldType($field_type_identifier, $group_id)
    {
        $db = Core::$db;

        $field_type_info = FieldTypes::getFieldTypeByIdentifier($field_type_identifier);
        if (!empty($field_type_info)) {
            return array(false, "The database has already been populated with the $field_type_identifier field type.");
        }

        $cft_field_types = CoreFieldTypes::getFieldTypes();
        $data = $cft_field_types[$field_type_identifier];

        // Step 1: install the main field_types record
        $db->query("SELECT count(*) as c FROM {PREFIX}field_types WHERE group_id = :group_id");
        $db->bind("group_id", $group_id);
        $db->execute();
        $result = $db->fetch();

        $next_list_order = $result["c"] + 1;

        $db->query("
            INSERT INTO {PREFIX}field_types (is_editable, non_editable_info, managed_by_module_id, field_type_name,
                field_type_identifier, group_id, is_file_field, is_date_field, raw_field_type_map, raw_field_type_map_multi_select_id,
                list_order, compatible_field_sizes, view_field_rendering_type, view_field_php_function_source, view_field_php_function,
                view_field_smarty_markup, edit_field_smarty_markup, php_processing, resources_css, resources_js)
            VALUES (
                :is_editable, :non_editable_info, :managed_by_module_id, :field_type_name, :field_type_identifier,
                :group_id, :is_file_field, :is_date_field, :raw_field_type_map, :raw_field_type_map_multi_select_id,
                :list_order, :compatible_field_sizes, :view_field_rendering_type, :view_field_php_function_source,
                :view_field_php_function, :view_field_smarty_markup, :edit_field_smarty_markup,
                :php_processing, :resources_css, :resources_js
            )
        ");
        $db->bindAll(array(
            "is_editable" => $data["field_type"]["is_editable"],
            "non_editable_info" => $data["field_type"]["non_editable_info"],
            "managed_by_module_id" => $data["field_type"]["managed_by_module_id"],
            "field_type_name" => $data["field_type"]["field_type_name"],
            "field_type_identifier" => $data["field_type"]["field_type_identifier"],
            "group_id" => $group_id,
            "is_file_field" => $data["field_type"]["is_file_field"],
            "is_date_field" => $data["field_type"]["is_date_field"],
            "raw_field_type_map" => $data["field_type"]["raw_field_type_map"],
            "raw_field_type_map_multi_select_id" => null,
            "list_order" => $next_list_order,
            "compatible_field_sizes" => $data["field_type"]["compatible_field_sizes"],
            "view_field_rendering_type" => $data["field_type"]["view_field_rendering_type"],
            "view_field_php_function_source" => $data["field_type"]["view_field_php_function_source"],
            "view_field_php_function" => $data["field_type"]["view_field_php_function"],
            "view_field_smarty_markup" => $data["field_type"]["view_field_smarty_markup"],
            "edit_field_smarty_markup" => $data["field_type"]["edit_field_smarty_markup"],
            "php_processing" => $data["field_type"]["php_processing"],
            "resources_css" => $data["field_type"]["resources_css"],
            "resources_js" => $data["field_type"]["resources_js"]
        ));

        try {
            $db->execute();
        } catch (Exception $e) {
            CoreFieldTypes::rollbackNewInstallation();
            return array(false, "Failed to insert field type $field_type_identifier: " . $e->getMessage());
        }

        $field_type_id = $db->getInsertId();

        // Step 2: field type settings
        for ($i=1; $i<=count($data["settings"]); $i++) {
            $setting_info             = $data["settings"][$i-1];
            $use_for_option_list_map  = isset($setting_info["use_for_option_list_map"]) ? $setting_info["use_for_option_list_map"] : false;

            $db->query("
                INSERT INTO {PREFIX}field_type_settings (field_type_id, field_label, field_setting_identifier,
                  field_type, field_orientation, default_value_type, default_value, list_order)
                VALUES (:field_type_id, :field_label, :field_setting_identifier, :field_type,
                  :field_orientation, :default_value_type, :default_value, :list_order)
            ");
            $db->bindAll(array(
                "field_type_id" => $field_type_id,
                "field_label" => $setting_info["field_label"],
                "field_setting_identifier" => $setting_info["field_setting_identifier"],
                "field_type" => $setting_info["field_type"],
                "field_orientation" => $setting_info["field_orientation"],
                "default_value_type" => $setting_info["default_value_type"],
                "default_value" => $setting_info["default_value"],
                "list_order" => $i
            ));

            try {
                $db->execute();
            } catch (Exception $e) {
                CoreFieldTypes::rollbackNewInstallation();
                return array(false, "Failed to insert setting {$setting_info["field_setting_identifier"]}: " . $e->getMessage());
            }

            $setting_id = $db->getInsertId();

            // if this setting is being used for the raw field type option list, update the field type record
            if ($use_for_option_list_map) {
                $db->query("
                    UPDATE {PREFIX}field_types
                    SET    raw_field_type_map_multi_select_id = :raw_field_type_map_multi_select_id
                    WHERE  field_type_id = :field_type_id
                ");
                $db->bind("raw_field_type_map_multi_select_id", $setting_id);
                $db->bind("field_type_id", $field_type_id);
                $db->execute();
            }

            for ($j=1; $j<=count($setting_info["options"]); $j++) {
                $option_info = $setting_info["options"][$j-1];

                $db->query("
                    INSERT INTO {PREFIX}field_type_setting_options (setting_id, option_text, option_value, option_order, is_new_sort_group)
                    VALUES (:setting_id, :option_text, :option_value, :option_order, :is_new_sort_group)
                ");
                $db->bindAll(array(
                    "setting_id" => $setting_id,
                    "option_text" => $option_info["option_text"],
                    "option_value" => $option_info["option_value"],
                    "option_order" => $j,
                    "is_new_sort_group" => $option_info["is_new_sort_group"]
                ));

                try {
                    $db->execute();
                } catch (Exception $e) {
                    CoreFieldTypes::rollbackNewInstallation();
                    return array(false, "Failed to insert setting option {$setting_info["field_setting_identifier"]}, {$option_info["option_text"]}: " . $e->getMessage());
                }
            }
        }

        // Step 4: Validation
        for ($i=1; $i<=count($data["validation"]); $i++) {
            $rule_info = $data["validation"][$i-1];

            $db->query("
                INSERT INTO {PREFIX}field_type_validation_rules (field_type_id, rsv_rule, rule_label,
                    rsv_field_name, custom_function, custom_function_required, default_error_message, list_order)
                VALUES (:field_type_id, :rsv_rule, :rule_label, :rsv_field_name, :custom_function,
                    :custom_function_required, :default_error_message, :list_order)
            ");
            $db->bindAll(array(
                "field_type_id" => $field_type_id,
                "rsv_rule" => $rule_info["rsv_rule"],
                "rule_label" => $rule_info["rule_label"],
                "rsv_field_name" => $rule_info["rsv_field_name"],
                "custom_function" => $rule_info["custom_function"],
                "custom_function_required" => $rule_info["custom_function_required"],
                "default_error_message" => $rule_info["default_error_message"],
                "list_order" => $i
            ));

            try {
                $db->execute();
            } catch (Exception $e) {
                CoreFieldTypes::rollbackNewInstallation();
                return array(false, "Failed to insert validation rule {$rule_info["rule_label"]}: " . $e->getMessage());
            }
        }

        return array(true, "");
    }


    /**
     * Used during the installation. If there are ever ANY errors adding the core fields types, it empties the
     * database for a fresh try.
     *
     * This shouldn't ever occur, of course. However, the calling script will always return the explicit error
     * that occurs, so we should be able to plug any problems that occur.
     */
    public static function rollbackNewInstallation()
    {
        $db = Core::$db;

        ListGroups::deleteByGroupType("field_types");

        $db->query("TRUNCATE TABLE {PREFIX}field_types");
        $db->execute();

        $db->query("TRUNCATE TABLE {PREFIX}field_type_settings");
        $db->execute();

        $db->query("TRUNCATE TABLE {PREFIX}field_type_setting_options");
        $db->execute();

        $db->query("TRUNCATE TABLE {PREFIX}field_type_validation_rules");
        $db->execute();
    }

    public static function getFieldTypes()
    {
        return array(
            "checkboxes" => Checkbox::get(),
            "code_markup" => Code::get(),
            "date" => Date::get(),
            "dropdown" => Dropdown::get(),
            "multi_select_dropdown" => MultiSelect::get(),
            "password" => Password::get(),
            "phone" => Phone::get(),
            "radio_buttons" => Radio::get(),
            "textarea" => Textarea::get(),
            "textbox" => Textbox::get(),
            "time" => Time::get()
        );
    }
}
