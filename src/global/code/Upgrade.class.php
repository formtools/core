<?php

namespace FormTools;

use Exception;


class Upgrade
{
    public static function upgrade()
    {
        $current_version_date = Core::getReleaseDate();
        $last_version_in_db = Settings::get("program_version");
        $last_version_date_in_db = Settings::get("release_date");

        $is_upgraded = false;
        $success = true;
        $error_msg = "";

        // any time the version changes, update the list of hooks in the DB
        if ($current_version_date > $last_version_date_in_db) {

            // always re-parse the database to get the latest list of hooks
            Hooks::updateAvailableHooks();

            if (General::isVersionEarlierThan($last_version_in_db, "3.0.1")) {
                list ($success, $error_msg) = self::upgradeTo3_0_1();
                self::patchFieldTypeOptionListSettingMapping();
                self::fixEuropeanDateFormat();
            }

			if (General::isVersionEarlierThan($last_version_in_db, "3.0.4")) {
            	self::addCopySubmissionField();
			}

			if (General::isVersionEarlierThan($last_version_in_db, "3.0.8")) {
				Settings::set(array("installation_complete" => "yes"), "core");
			}

			if (General::isVersionEarlierThan($last_version_in_db, "3.0.10")) {
				self::addViewMappingViewId();
				self::setCoreFieldsAsNotEditable();
			}

			self::removeModulesTableDescCol();
			self::removeModulesTableModuleKeyCol();

			FieldTypes::resetFieldTypes();

			if (General::isVersionEarlierThan($last_version_in_db, "3.0.15")) {
				General::createCacheFolder();
			}

            if ($success) {
                Settings::set(array(
                    "release_date" => $current_version_date,
                    "program_version" => Core::getCoreVersion(),
                    "release_type" => Core::getReleaseType()
                ));

                $is_upgraded = true;
                $success = true;
            }
        }

        return array(
            "upgraded" => $is_upgraded,
            "success" => $success,
            "error_msg" => $error_msg
        );
    }


    /**
     * Handles upgrading from FT2 2.2.5, 2.2.6 or 2.2.7 to 3.0.0/3.0.1.
     *
     * These methods can safely be executed multiple times (but should still only fire once).
     */
    private static function upgradeTo3_0_1()
    {
        $db = Core::$db;

        $success = true;
        $error_msg = "";
        try {
            $db->query("
                ALTER TABLE {PREFIX}forms CHANGE add_submission_button_label add_submission_button_label VARCHAR(255)
            ");
            $db->execute();

            General::deleteColumnIfExists("modules", "is_premium");
            Settings::set(array(
                "edit_submission_onload_resources" => Installation::getEditSubmissionOnloadResources()
            ), "core");

            // reset all core field types to their factory defaults
            FieldTypes::resetFieldTypes();

        } catch (Exception $e) {
            $success = false;
            $error_msg = $e->getMessage();
        }

        return array($success, $error_msg);
    }


    /**
     * FT3 alpha/betas were failing to map the ID of the field type setting ID for select/checkboxes/multi-select/radios
     * that houses the option list info to the original field. This caused a few minor issues in the UI including
     * adding an external form. See: https://github.com/formtools/core/issues/166 (indirect issue)
     */
    private static function patchFieldTypeOptionListSettingMapping ()
    {
        $db = Core::$db;

        $field_types = FieldTypes::get(true);
        foreach ($field_types as $field_type) {
            if (!in_array($field_type["field_type_identifier"], array("dropdown", "multi_select_dropdown", "radio_buttons", "checkboxes"))) {
                continue;
            }

            // In the original data, the setting that's going to store the option list has a "use_for_option_list_map"
            // boolean, but that's not available here. Luckily all 4 of these field types have a field_type value of
            // "option_list_or_form_field" so we use that to locate the DB record here
            $setting_id = null;
            foreach ($field_type["settings"] as $setting_info) {
                if ($setting_info["field_type"] == "option_list_or_form_field") {
                    $setting_id = $setting_info["setting_id"];
                    break;
                }
            }

            $db->query("
                UPDATE {PREFIX}field_types
                SET    raw_field_type_map_multi_select_id = :raw_field_type_map_multi_select_id
                WHERE  field_type_id = :field_type_id
            ");
            $db->bind("raw_field_type_map_multi_select_id", $setting_id);
            $db->bind("field_type_id", $field_type["field_type_id"]);
            $db->execute();
        }
    }

    private static function fixEuropeanDateFormat()
    {
        $db = Core::$db;

        $db->query("
            UPDATE {PREFIX}field_type_setting_options
            SET    option_text = '30. 08. 2011',
                   option_value = 'dd. mm. yy'
            WHERE  option_text = '30. 08. 2011.' AND option_value = 'dd. mm. yy.'
        ");
        $db->execute();
    }

    // fix for missing may_copy_submissions DB field in 3.0.3
    public static function addCopySubmissionField()
	{
		$db = Core::$db;

		if (!General::checkDbTableFieldExists("views", "may_copy_submissions")) {
			try {
				$db->query("
					ALTER TABLE {PREFIX}views ADD may_copy_submissions ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER may_add_submissions
				");
				$db->execute();
			} catch (Exception $e) {

			}
		}
	}

	// fix for https://github.com/formtools/core/issues/371
	public static function addViewMappingViewId()
	{
		$db = Core::$db;

		if (!General::checkDbTableFieldExists("email_templates", "view_mapping_view_id")) {
			try {
				$db->query("
					ALTER TABLE {PREFIX}email_templates
					ADD view_mapping_view_id MEDIUMINT(9) AFTER view_mapping_type
				");
				$db->execute();
			} catch (Exception $e) {
			}
		}
	}

	public static function setCoreFieldsAsNotEditable()
	{
		$db = Core::$db;

		try {
			$db->query("
				UPDATE {PREFIX}field_types
				SET    is_editable = 'no',
					   non_editable_info = '{\$LANG.text_non_deletable_fields}'
				WHERE field_type_identifier IN ('textbox', 'textarea', 'password', 'dropdown', 'multi_select_dropdown',
					'radio_buttons', 'checkboxes', 'date', 'time', 'phone', 'code_markup')
			");
			$db->execute();

			$db->query("
				ALTER TABLE {PREFIX}field_types
				ADD is_enabled ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER is_editable
			");
			$db->execute();

		} catch (Exception $e) {
		}
	}

	public static function removeModulesTableDescCol()
	{
		General::deleteColumnIfExists("modules", "description");
	}

	public static function removeModulesTableModuleKeyCol()
	{
		General::deleteColumnIfExists("modules", "module_key");
	}
}

