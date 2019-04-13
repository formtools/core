<?php

/**
 * The installation class. Added in 2.3.0.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Templates
 */

// ---------------------------------------------------------------------------------------------------------------------

namespace FormTools;


use PDO, Exception;


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
    public static function get($settings = "", $module = "")
    {
        $db = Core::$db;

        $where_module_clause = (!empty($module)) ? "WHERE module = :module" : "";
        $and_module_clause   = (!empty($module)) ? "AND module = :module" : "";

        $result = "";
        if (empty($settings)) {
            $db->query("
                SELECT setting_name, setting_value
                FROM   {PREFIX}settings
                $where_module_clause
            ");
            if (!empty($module)) {
                $db->bind("module", $module);
            }
            $db->execute();

            $result = array();
            foreach ($db->fetchAll() as $row) {
                $result[$row['setting_name']] = $row['setting_value'];
            }

        } else if (is_string($settings)) {
            $db->query("
                SELECT setting_value
                FROM   {PREFIX}settings
                WHERE  setting_name = '$settings'
                $and_module_clause
            ");
            if (!empty($module)) {
                $db->bind("module", $module);
            }
            $db->execute();
            $info = $db->fetch();
            $result = $info["setting_value"];

        } else if (is_array($settings)) {
            $result = array();

            foreach ($settings as $setting_name) {
                $db->query("
                    SELECT setting_value
                    FROM   {PREFIX}settings
                    WHERE  setting_name = :setting_name
                    $and_module_clause
                ");
                $db->bind("setting_name", $setting_name);
                if (!empty($module)) {
                    $db->bind("module", $module);
                }
                $db->execute();

                $result[$setting_name] = $db->fetch(PDO::FETCH_COLUMN);
            }
        }

        return $result;
    }

    /**
     * Updates some setting values. If the setting doesn't exist, it creates it. In addition,
     * it updates the value(s) in the current user's sessions.
     *
     * TODO this interface is pretty poor. Change this to Settings::set("key", "value") and add a Settings::setAll(array()) version.
     *
     * @param array $settings a hash of setting name => setting value
     * @param string $module the module name
     */
    public static function set(array $settings, $module = "")
    {
        $db = Core::$db;
        $and_module_clause = (!empty($module)) ? "AND module = :module" : "";

        if (!is_array($settings)) {
            return;
        }

		foreach ($settings as $setting_name => $setting_value) {
            $db->query("
                SELECT count(*)
                FROM   {PREFIX}settings
                WHERE  setting_name = :setting_name
                $and_module_clause
            ");
            $db->bind("setting_name", $setting_name);
            if (!empty($module)) {
                $db->bind("module", $module);
            }

            try {
                $db->execute();
            } catch (Exception $e) {
                return array(false, $e->getMessage());
            }

            if ($db->fetch(PDO::FETCH_COLUMN) == 0) {
                if (!empty($module)) {
                    $query = "INSERT INTO {PREFIX}settings (setting_name, setting_value, module) VALUES (:setting_name, :setting_value, :module)";
                } else {
                    $query = "INSERT INTO {PREFIX}settings (setting_name, setting_value) VALUES (:setting_name, :setting_value)";
                }
            } else {
                $query = "UPDATE {PREFIX}settings SET setting_value = :setting_value WHERE setting_name = :setting_name $and_module_clause";
            }

            $db->query($query);
            if (!empty($module)) {
                $db->bind("module", $module);
            }
            $db->bindAll(array(
                "setting_value" => $setting_value,
                "setting_name" => $setting_name
            ));
            $db->execute();

            // TODO. This looks suspiciously like a bug... [a module could overwrite a core var]
            Sessions::set("settings.{$setting_name}", $setting_value);
        }
    }


    /**
     * Called by administrators; updates the main settings.
     *
     * @param array $infohash this parameter should be a hash (e.g. $_POST or $_GET) containing the
     *             various fields from the main settings admin page.
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function updateMainSettings($infohash)
    {
        $LANG = Core::$L;

        $success = true;
        $message = $LANG["notify_setup_options_updated"];

        $rules = array();
        $rules[] = "required,program_name,{$LANG["validation_no_program_name"]}";
        $rules[] = "required,num_clients_per_page,{$LANG["validation_no_num_clients_per_page"]}";
        $rules[] = "digits_only,num_clients_per_page,{$LANG["validation_invalid_num_clients_per_page"]}";
        $rules[] = "required,num_emails_per_page,{$LANG["validation_no_num_emails_per_page"]}";
        $rules[] = "digits_only,num_emails_per_page,{$LANG["validation_invalid_num_emails_per_page"]}";
        $rules[] = "required,num_forms_per_page,{$LANG["validation_no_num_forms_per_page"]}";
        $rules[] = "digits_only,num_forms_per_page,{$LANG["validation_invalid_num_forms_per_page"]}";
        $rules[] = "required,num_option_lists_per_page,{$LANG["validation_no_num_option_lists_per_page"]}";
        $rules[] = "digits_only,num_option_lists_per_page,{$LANG["validation_invalid_num_option_lists_per_page"]}";
        $rules[] = "required,num_menus_per_page,{$LANG["validation_no_num_menus_per_page"]}";
        $rules[] = "digits_only,num_menus_per_page,{$LANG["validation_invalid_num_menus_per_page"]}";
        $rules[] = "required,num_modules_per_page,{$LANG["validation_no_num_modules_per_page"]}";
        $rules[] = "digits_only,num_modules_per_page,{$LANG["validation_invalid_num_modules_per_page"]}";
        $errors = validate_fields($infohash, $rules);

        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors), "");
        }

        $settings = array(
            "program_name"              => trim($infohash["program_name"]),
            "logo_link"                 => trim($infohash["logo_link"]),
            "num_clients_per_page"      => trim($infohash["num_clients_per_page"]),
            "num_emails_per_page"       => trim($infohash["num_emails_per_page"]),
            "num_forms_per_page"        => trim($infohash["num_forms_per_page"]),
            "num_option_lists_per_page" => trim($infohash["num_option_lists_per_page"]),
            "num_menus_per_page"        => trim($infohash["num_menus_per_page"]),
            "num_modules_per_page"      => trim($infohash["num_modules_per_page"]),
            "default_date_field_search_value" => $infohash["default_date_field_search_value"]
        );

        Settings::set($settings);

        extract(Hooks::processHookCalls("end", compact("settings"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Called by administrators; updates the default user account settings.
     *
     * @param array $infohash this parameter should be a hash (e.g. $_POST or $_GET) containing the
     *             various fields from the main settings admin page.
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function updateAccountSettings($infohash)
    {
        $LANG = Core::$L;

        $success = true;
        $message = $LANG["notify_setup_options_updated"];

        $rules = array();
        $rules[] = "required,default_page_titles,{$LANG["validation_no_page_titles"]}";
        $rules[] = "required,default_client_menu_id,{$LANG["validation_no_menu_id"]}";
        $rules[] = "required,default_theme,{$LANG["validation_no_theme"]}";
        $rules[] = "required,default_login_page,{$LANG["validation_no_login_page"]}";
        $rules[] = "required,default_logout_url,{$LANG["validation_no_logout_url"]}";
        $rules[] = "required,default_language,{$LANG["validation_no_default_language"]}";
        $rules[] = "required,default_sessions_timeout,{$LANG["validation_no_default_sessions_timeout"]}";
        $rules[] = "digits_only,default_sessions_timeout,{$LANG["validation_invalid_default_sessions_timeout"]}";
        $rules[] = "required,default_date_format,{$LANG["validation_no_date_format"]}";
        $errors = validate_fields($infohash, $rules);

        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors), "");
        }

        $clients_may_edit_page_titles      = isset($infohash["clients_may_edit_page_titles"]) ? "yes" : "no";
        $clients_may_edit_footer_text      = isset($infohash["clients_may_edit_footer_text"]) ? "yes" : "no";
        $clients_may_edit_theme            = isset($infohash["clients_may_edit_theme"]) ? "yes" : "no";
        $clients_may_edit_logout_url       = isset($infohash["clients_may_edit_logout_url"]) ? "yes" : "no";
        $clients_may_edit_ui_language      = isset($infohash["clients_may_edit_ui_language"]) ? "yes" : "no";
        $clients_may_edit_timezone_offset  = isset($infohash["clients_may_edit_timezone_offset"]) ? "yes" : "no";
        $clients_may_edit_sessions_timeout = isset($infohash["clients_may_edit_sessions_timeout"]) ? "yes" : "no";
        $clients_may_edit_date_format      = isset($infohash["clients_may_edit_date_format"]) ? "yes" : "no";
        $clients_may_edit_max_failed_login_attempts = isset($infohash["clients_may_edit_max_failed_login_attempts"]) ? "yes" : "no";

        $required_password_chars = "";
        if (isset($infohash["required_password_chars"]) && is_array($infohash["required_password_chars"])) {
            $required_password_chars = implode(",", $infohash["required_password_chars"]);
        }

        $default_theme = $infohash["default_theme"];
        $default_client_swatch = "";
        if (isset($infohash["{$default_theme}_default_theme_swatches"])) {
            $default_client_swatch = $infohash["{$default_theme}_default_theme_swatches"];
        }

        $settings = array(
            "default_page_titles"          => $infohash["default_page_titles"],
            "default_footer_text"          => $infohash["default_footer_text"],
            "default_client_menu_id"       => $infohash["default_client_menu_id"],
            "default_theme"                => $default_theme,
            "default_client_swatch"        => $default_client_swatch,
            "default_login_page"           => $infohash["default_login_page"],
            "default_logout_url"           => $infohash["default_logout_url"],
            "default_language"             => $infohash["default_language"],
            "default_timezone_offset"      => $infohash["default_timezone_offset"],
            "default_sessions_timeout"     => $infohash["default_sessions_timeout"],
            "default_date_format"          => $infohash["default_date_format"],
            "forms_page_default_message"   => $infohash["forms_page_default_message"],
            "clients_may_edit_page_titles" => $clients_may_edit_page_titles,
            "clients_may_edit_footer_text" => $clients_may_edit_footer_text,
            "clients_may_edit_theme"       => $clients_may_edit_theme,
            "clients_may_edit_logout_url"  => $clients_may_edit_logout_url,
            "clients_may_edit_ui_language" => $clients_may_edit_ui_language,
            "clients_may_edit_timezone_offset"  => $clients_may_edit_timezone_offset,
            "clients_may_edit_sessions_timeout" => $clients_may_edit_sessions_timeout,
            "clients_may_edit_date_format"      => $clients_may_edit_date_format,

            // security settings
            "default_max_failed_login_attempts" => $infohash["default_max_failed_login_attempts"],
            "min_password_length"               => $infohash["min_password_length"],
            "required_password_chars"           => $required_password_chars,
            "num_password_history"              => $infohash["num_password_history"],
            "clients_may_edit_max_failed_login_attempts" => $clients_may_edit_max_failed_login_attempts
        );

        Settings::set($settings);

        extract(Hooks::processHookCalls("end", compact("settings"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Called by administrators; updates the default user account settings.
     *
     * @param array $infohash this parameter should be a hash (e.g. $_POST or $_GET) containing the
     *             various fields from the main settings admin page.
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function updateFileSettings($infohash)
    {
        $LANG = Core::$L;

        $success = true;
        $message = $LANG["notify_setup_options_updated"];

        $file_upload_dir = rtrim(trim($infohash["file_upload_dir"]), "/\\");
        $file_upload_url = rtrim(trim($infohash["file_upload_url"]), "/\\");
        $file_upload_max_size = $infohash["file_upload_max_size"];

        $file_upload_filetypes = (is_array($infohash["file_upload_filetypes"])) ? join(",", $infohash["file_upload_filetypes"]) : "";
        if (!empty($infohash["file_upload_filetypes_other"])) {
            if (empty($file_upload_filetypes)) {
                $file_upload_filetypes = $infohash["file_upload_filetypes_other"];
            } else {
                $file_upload_filetypes .= ",{$infohash["file_upload_filetypes_other"]}";
            }
        }
        $file_upload_filetypes = mb_strtolower($file_upload_filetypes);

        $settings = array(
            "file_upload_dir" => $file_upload_dir,
            "file_upload_url" => $file_upload_url,
            "file_upload_max_size" => $file_upload_max_size,
            "file_upload_filetypes" => $file_upload_filetypes
        );

        Settings::set($settings);

        // check the folder was valid
        list ($is_valid_folder, $folder_message) = Files::checkUploadFolder($file_upload_dir);
        if (!$is_valid_folder) {
            return array($is_valid_folder, $folder_message);
        }

        extract(Hooks::processHookCalls("end", compact("infohash"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Called by the administrator from the Themes settings page. It updates the list of enabled
     * themes, and which theme is assigned to the administrator and (default) client accounts. Note:
     * it doesn't disable any themes that are already assigned to a user account. If that happens,
     * it returns a message listing the accounts (each clickable) and an option to bulk assign them
     * to a different theme.
     *
     * @param array $infohash this parameter should be a hash (e.g. $_POST or $_GET) containing the
     *             various fields from the main settings admin page.
     * @return array Returns array with indexes:<br/>
     *               [0]: true/false (success / failure)<br/>
     *               [1]: message string<br/>
     */
    public static function updateThemeSettings($infohash)
    {
        $LANG = Core::$L;
        $db = Core::$db;
        $root_url = Core::getRootUrl();
        $root_dir = Core::getRootDir();

        // lots to validate! First, check the default admin & client themes have been entered
        $rules = array();
        $rules[] = "required,admin_theme,{$LANG["validation_no_admin_theme"]}";
        $rules[] = "required,default_client_theme,{$LANG["validation_no_default_client_theme"]}";
        $errors = validate_fields($infohash, $rules);

        if (!isset($infohash["is_enabled"])) {
            $errors[] = $LANG["validation_no_enabled_themes"];
        }

        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors));
        }

        $enabled_themes = $infohash["is_enabled"];

        // next, check that both the admin and default client themes are enabled
        $admin_theme          = $infohash["admin_theme"];
        $default_client_theme = $infohash["default_client_theme"];

        if (!in_array($admin_theme, $enabled_themes) || !in_array($default_client_theme, $enabled_themes)) {
            return array(false, $LANG["validation_default_admin_and_client_themes_not_enabled"]);
        }

        // lastly, if there are already client accounts assigned to disabled themes, we need to sort it out.
        // We handle it the same way as deleting the client menus: if anyone is assigned to this theme,
        // we generate a list of their names, each a link to their account page (in a _blank link). We
        // then inform the user of what's going on, and underneath the name list, give them the option of
        // assigning ALL affected accounts to another (enabled) theme.
        $theme_clauses = array();
        foreach ($enabled_themes as $theme) {
            $theme_clauses[] = "theme != '$theme'";
        }
        $theme_clause = join(" AND ", $theme_clauses);

        $db->query("
            SELECT account_id, first_name, last_name
            FROM   {PREFIX}accounts
            WHERE  $theme_clause
        ");
        $db->execute();
        $client_info = $db->fetch();

        // TODO MOVE! Single responsibility, Ben!
        if (!empty($client_info))
        {
            $message = $LANG["notify_disabled_theme_already_assigned"];
            $placeholder_str = $LANG["phrase_assign_all_listed_client_accounts_to_theme"];

            $themes = Themes::getList(true);
            $dd = "<select id=\"mass_update_client_theme\">";

            foreach ($themes as $theme) {
                $dd .= "<option value=\"{$theme["theme_id"]}\">{$theme["theme_name"]}</option>";
            }
            $dd .= "</select>";

            // a bit bad (hardcoded HTML!), but organize the account list in 3 columns
            $client_links_table = "<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">\n<tr>";
            $num_affected_clients = count($client_info);
            for ($i=0; $i<$num_affected_clients; $i++) {
                $account_info = $client_info[$i];
                $client_id  = $account_info["account_id"];
                $first_name = $account_info["first_name"];
                $last_name  = $account_info["last_name"];
                $client_ids[] = $client_id;

                if ($i != 0 && $i % 3 == 0) {
                    $client_links_table .= "</tr>\n<tr>";
                }

                $client_links_table .= "<td width=\"33%\">&bull;&nbsp;<a href=\"$root_url/admin/clients/edit.php?page=settings&client_id=$client_id\" target=\"_blank\">$first_name $last_name</a></td>\n";
            }
            $client_id_str = join(",", $client_ids);

            // close the table
            if ($num_affected_clients % 3 == 1) {
                $client_links_table .= "<td colspan=\"2\" width=\"66%\"> </td>";
            } else if ($num_affected_clients % 3 == 2) {
                $client_links_table .= "<td width=\"33%\"> </td>";
            }

            $client_links_table .= "</tr></table>";

            $submit_button = "<input type=\"button\" value=\"{$LANG["phrase_update_accounts"]}\" onclick=\"window.location='index.php?page=themes&mass_assign=1&accounts=$client_id_str&theme_id=' + $('#mass_update_client_theme').val()\" />";

            $placeholders = array(
                "theme_dropdown" => $dd,
                "submit_button" => $submit_button
            );

            $mass_assign_html = "<div class=\"margin_top_large margin_bottom_large\">" . General::evalSmartyString($placeholder_str, $placeholders) . "</div>";
            $html = $message . $mass_assign_html . $client_links_table;

            return array(false, $html);
        }

        // hoorah! Validation complete, let's update the bloomin' database at last

        // update the admin settings
        $admin_id = Sessions::get("account.account_id");
        $admin_swatch = "";
        if (isset($infohash["{$admin_theme}_admin_theme_swatches"])) {
            $admin_swatch = $infohash["{$admin_theme}_admin_theme_swatches"];
        }

        $db->query("
            UPDATE {PREFIX}accounts
            SET    theme = :theme,
                   swatch = :swatch
            WHERE  account_id = :account_id
        ");
        $db->bindAll(array(
            "theme" => $admin_theme,
            "swatch" => $admin_swatch,
            "account_id" => $admin_id
        ));
        $db->execute();

        Sessions::set("account.theme", $admin_theme);
        Sessions::set("account.swatch", $admin_swatch);
        Core::$user->setTheme($admin_theme);
        Core::$user->setSwatch($admin_swatch);

        $default_client_swatch = "";
        if (isset($infohash["{$default_client_theme}_default_client_theme_swatches"])) {
            $default_client_swatch = $infohash["{$default_client_theme}_default_client_theme_swatches"];
        }

        // update the default client theme & swatch
        $new_settings = array(
            "default_theme"         => $default_client_theme,
            "default_client_swatch" => $default_client_swatch
        );
        Settings::set($new_settings);


        // finally, update the enabled themes list
        $db->query("
            UPDATE {PREFIX}themes
            SET is_enabled = 'no'
        ");
        $db->execute();

        foreach ($enabled_themes as $theme) {
            $db->query("
                UPDATE {PREFIX}themes
                SET    is_enabled = 'yes'
                WHERE  theme_folder = :theme
            ");
            $db->bind("theme", $theme);
            $db->execute();
        }

        // reset the settings in sessions
        Sessions::set("settings", Settings::get());

        $success = true;
        $message = $LANG["notify_themes_settings_updated"];

        extract(Hooks::processHookCalls("end", compact("infohash"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }
}
