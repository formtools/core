<?php

/**
 * The Account class. Added in 2.3.0; will replace the old accounts.php file.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;


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
        $table_prefix = Core::getDbTablePrefix();
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

        // now update the database
        mysql_query("TRUNCATE {$table_prefix}hooks");
        foreach ($results["code_hooks"] as $hook_info)  {
            $component       = $hook_info["component"];
            $file            = $hook_info["file"];
            $function_name   = $hook_info["function_name"];
            $action_location = $hook_info["action_location"];
            $params_str      = implode(",", $hook_info["params"]);
            $overridable_str = implode(",", $hook_info["overridable"]);

            mysql_query("
                INSERT INTO {$table_prefix}hooks (hook_type, component, filepath, action_location, function_name, params, overridable)
                VALUES ('code', '$component', '$file', '$action_location', '$function_name', '$params_str', '$overridable_str')
            ");
        }

        foreach ($results["template_hooks"] as $hook_info) {
            $component = $hook_info["component"];
            $template  = $hook_info["template"];
            $location  = $hook_info["location"];

            mysql_query("
                INSERT INTO {$table_prefix}hooks (hook_type, component, filepath, action_location, function_name, params, overridable)
                VALUES ('template', '$component', '$template', '$location', '', '', '')
            ");
        }
    }

}
