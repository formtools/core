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

        // if the files have been updated but the DB is older, the user is upgrading
        if ($current_version_date > $last_version_date_in_db) {
            if ($current_version_date <= 20180128) {
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
     * Handles upgrading from FT2 2.2.5, 2.2.6 or 2.2.7 to 3.0.0. FT3 didn't introduce many database changes, just
     * fixed a couple of bugs, which is why this is so short.
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

        } catch (Exception $e) {
            $success = false;
            $error_msg = $e->getMessage();
        }

        return array($success, $error_msg);
    }
}
