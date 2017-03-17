<?php

/**
 * The installation class. Added in 2.3.0.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;


use PDOException;


class Settings {

    /**
     * Updates some setting values. If the setting doesn't exist, it creates it. In addition,
     * it updates the value(s) in the current user's sessions.
     *
     * @param array $settings a hash of setting name => setting value
     * @param string $module the module name
     */
    public static function set(array $settings, $module = "")
    {
        $table_prefix = Core::getDbTablePrefix();
        $db = Core::$db;

        $and_module_clause = (!empty($module)) ? "AND module = '$module'" : "";

        while (list($setting_name, $setting_value) = each($settings)) {

            $db->query("
                SELECT count(*) as c
                FROM   {$table_prefix}settings
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
                      INSERT INTO {$table_prefix}settings (setting_name, setting_value, module)
                      VALUES (:setting_name, :setting_value, :module)
                    ");
                    $db->execute();
                } else {
                    $db->query("
                      INSERT INTO {$table_prefix}settings (setting_name, setting_value)
                      VALUES (:setting_name, :setting_value)
                    ");
                    $db->execute();
                }
            } else {
                $db->query("
                    UPDATE {$table_prefix}settings
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
