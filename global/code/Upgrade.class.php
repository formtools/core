<?php

namespace FormTools;

use Exception;


class Upgrade
{
    public static function upgrade()
    {
        $current_version_date = Core::getReleaseDate();
        $last_version_date_in_db = Settings::get("release_date");

        $is_upgraded = false;
        $success = true;
        $error_msg = "";

        // any time the version changes, update the list of hooks in the DB
        if ($current_version_date > $last_version_date_in_db) {
            Hooks::updateAvailableHooks();
        }

        // if the files have been updated but the DB is older, the user is upgrading
        if ($current_version_date > $last_version_date_in_db) {
            if ($current_version_date <= 20180204) {
                list ($success, $error_msg) = self::upgradeTo3_0_0();
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
     * Handles upgrading from FT2 2.2.5, 2.2.6 or 2.2.7 to 3.0.0.
     *
     * These methods can safely be executed multiple times (but should still only fire once).
     */
    private static function upgradeTo3_0_0()
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
}
