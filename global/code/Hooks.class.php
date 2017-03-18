<?php

/**
 * The Account class. Added in 2.3.0; will replace the old accounts.php file.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDOException;


class Hooks {

    /**
     * Called automatically after upgrading the Core, theme or module. This parses the entire Form Tools code base -
     * including any installed modules - and updates the list of available hooks. As of 2.1.0, the available hooks
     * are listed in the ft_hooks table, and the actual hook calls are in the hook_calls table (formerly the hooks
     * table).
     *
     * Before 2.1.0, the list of hooks was stored in /global/misc/hook_info.ini. However it only stored hooks for the
     * Core and habitually got out of date. This is a lot, lot better!
     */
    public static function updateAvailableHooks()
    {
        $ft_root = realpath(__DIR__ . "/../../");
        $hook_locations = array(
            // code hooks
            "process.php"        => "core",
            "global/code"        => "core",
            "global/api/api.php" => "api",
            "modules"            => "module",

            // template hooks
            "themes/default"  => "core"
        );

        $results = array(
            "code_hooks"     => array(),
            "template_hooks" => array()
        );

        while (list($file_or_folder, $component) = each($hook_locations)) {
            _ft_find_hooks("$ft_root/$file_or_folder", $ft_root, $component, $results);
        }


        self::clearHooks();
        self::addCodeHooks($results["code_hooks"]);
        self::addTemplateHooks($results["template_hooks"]);
    }

    private static function clearHooks()
    {
        Core::$db->query("TRUNCATE {PREFIX}hooks");
        Core::$db->execute();
    }

    private static function addCodeHooks($code_hooks)
    {
        Core::$db->beginTransaction();
        $db = Core::$db;

        foreach ($code_hooks as $hook_info)  {
            $db->query("
                INSERT INTO {PREFIX}hooks (hook_type, component, filepath, action_location, function_name, params, overridable)
                VALUES (:hook_type, :component, :file, :action_location, :function_name, :params, :overridable)
            ");
            $db->bindAll(array(
                ":hook_type" => "code",
                ":component" => $hook_info["component"],
                ":file" => $hook_info["file"],
                ":action_location" => $hook_info["action_location"],
                ":function_name" => $hook_info["function_name"],
                ":params" => implode(",", $hook_info["params"]),
                ":overridable" => implode(",", $hook_info["overridable"])
            ));
            $db->execute();
        }

        try {
            $db->processTransaction();
        } catch (PDOException $e) {
            print_r($e);
            $db->rollbackTransaction();
        }
    }

    private static function addTemplateHooks($template_hooks)
    {
        Core::$db->beginTransaction();
        foreach ($template_hooks as $hook_info) {
            Core::$db->query("
                INSERT INTO {PREFIX}hooks (hook_type, component, filepath, action_location, function_name, params, overridable)
                VALUES (:hook_type, :component, :template, :location, '', '', '')
            ");

            Core::$db->bindAll(array(
                ":hook_type" => "template",
                ":component" => $hook_info["component"],
                ":template" => $hook_info["template"],
                ":location" => $hook_info["location"]
            ));
        }
        try {
            Core::$db->processTransaction();
        } catch (PDOException $e) {
            Core::$db->rollbackTransaction();
        }
    }

}
