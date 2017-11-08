<?php

namespace FormTools;


class Upgrade
{
    public static function upgrade()
    {
        $current_version_date = Core::getReleaseDate();
        $last_version_date_in_db = Settings::get("release_date");

        $is_upgraded = false;
        $success = false;

        // if the files have been updated but the DB is older, the user is upgrading
        if ($current_version_date > $last_version_date_in_db) {
            Settings::set(array("release_date" => $current_version_date));

            $is_upgraded = true;
            $success = true;
        }

        return array(
            "upgraded" => $is_upgraded,
            "success" => $success
        );
    }

}
