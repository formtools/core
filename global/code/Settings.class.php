<?php

/**
 * The installation class. Added in 2.3.0.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;


use PDOException;


class Settings {

    /**
     * Retrieves values from the settings table.
     *
     * - if $settings param empty, it returns only the core settings
     * - if $settings param is a string, returns only that single setting value
     * - if $settings param is an array of setting names, returns only those setting values
     * - if $module param is included, it filters the results to only those settings for that particular
     *   module
     *
     * Tip: to only return the core (non-module) Form Tools settings, pass "core" as the module param
     * value.
     *
     * @param mixed $settings the setting(s) to return
     * @param string $module the name of the module to which these settings belong
     * @return array a hash of all settings.
     */
    public static function getList($settings = "", $module = "")
    {
        $db = Core::$db;

        $where_module_clause = (!empty($module)) ? "WHERE module = '$module'" : "";
        $and_module_clause   = (!empty($module)) ? "AND module = '$module'" : "";

        $result = "";
        if (empty($settings)) {
            $db->query("
                SELECT setting_name, setting_value
                FROM   {PREFIX}settings
                $where_module_clause
            ");
            $db->execute();

            $result = array();
            while ($row = $db->fetchAll()) {
                $result[$row['setting_name']] = $row['setting_value'];
            }

        } else if (is_string($settings)) {
            $db->query("
                SELECT setting_value
                FROM   {PREFIX}settings
                WHERE  setting_name = '$settings'
                $and_module_clause
            ");
            $db->execute();
            $info = $db->fetch();
            $result = $info["setting_value"];

        } else if (is_array($settings)) {
            $result = array();
            foreach ($settings as $setting) {
                $db->query("
                    SELECT setting_value
                    FROM   {PREFIX}settings
                    WHERE  setting_name = '$setting'
                    $and_module_clause
                ");
                $db->execute();
                $info = $db->fetch();
                $return_val[$setting] = $info["setting_value"];
            }
        }

        return $result;
    }

    /**
     * Updates some setting values. If the setting doesn't exist, it creates it. In addition,
     * it updates the value(s) in the current user's sessions.
     *
     * @param array $settings a hash of setting name => setting value
     * @param string $module the module name
     */
    public static function set(array $settings, $module = "")
    {
        $db = Core::$db;

        $and_module_clause = (!empty($module)) ? "AND module = '$module'" : "";

        while (list($setting_name, $setting_value) = each($settings)) {

            $db->query("
                SELECT count(*) as c
                FROM   {PREFIX}settings
                WHERE  setting_name = :setting_name
                $and_module_clause
            ");
            $db->bind(":settings_name", $setting_name);

            try {
                $db->execute();
            } catch (PDOException $e) {
                return array(false, $e->getMessage());
            }

            $info = $db->fetch();

            if ($info["c"] == 0) {
                if (!empty($module)) {
                    $db->query("
                      INSERT INTO {PREFIX}settings (setting_name, setting_value, module)
                      VALUES (:setting_name, :setting_value, :module)
                    ");
                    $db->execute();
                } else {
                    $db->query("
                      INSERT INTO {PREFIX}settings (setting_name, setting_value)
                      VALUES (:setting_name, :setting_value)
                    ");
                    $db->execute();
                }
            } else {
                $db->query("
                    UPDATE {PREFIX}settings
                    SET    setting_value = :setting_value
                    WHERE  setting_name  = :setting_name
                    $and_module_clause
                ");
                $db->execute();
            }

            // hmm... TODO. This looks suspiciously like a bug... [a module could overwrite a core var]
            $_SESSION["ft"]["settings"][$setting_name] = $setting_value;
        }
    }

}
