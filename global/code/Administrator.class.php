<?php

/**
 * Administrator-related functionality.
 *
 * This class will be changed. I'm going to make this class a singleton that inherits the methods of User &
 * instantiated instead of User in Core. It opens up the possibility of other user types (Submission Accounts...?).
 */

// ---------------------------------------------------------------------------------------------------------------------

namespace FormTools;

use Exception, PDO;


class Administrator
{
    /**
     * Returns information about the administrator account.
     *
     * @return array a hash of account information
     */
    public static function getAdminInfo()
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}accounts
            WHERE  account_type = 'admin'
            LIMIT  1
        ");
        $db->execute();

        $admin_info = $db->fetch();

        extract(Hooks::processHookCalls("main", compact("admin_info"), array("admin_info")), EXTR_OVERWRITE);

        return $admin_info;
    }


    /**
     * Creates a new client based on first and last name, and returns the new account id.
     *
     * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
     *               following required keys: first_name, last_name, user_name, password.
     * @return array [0]: true/false (success / failure)
     *               [1]: message string
     *               [2]: the new user ID (if successful)
     */
    public static function addClient($form_vals)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        extract(Hooks::processHookCalls("start", compact("form_vals"), array("form_vals")), EXTR_OVERWRITE);

        $success = true;
        $message = "";

        // validate POST fields
        $rules = array();
        $rules[] = "required,first_name,{$LANG["validation_no_client_first_name"]}";
        $rules[] = "required,last_name,{$LANG["validation_no_client_last_name"]}";
        $rules[] = "required,email,{$LANG["validation_no_client_email"]}";
        $rules[] = "valid_email,email,{$LANG["validation_invalid_email"]}";
        $rules[] = "required,username,{$LANG["validation_no_client_username"]}";
        $rules[] = "is_alpha,username,{$LANG["validation_invalid_client_username"]}";
        $rules[] = "required,password,{$LANG["validation_no_client_password"]}";
        $rules[] = "same_as,password,password_2,{$LANG["validation_passwords_different"]}";

        $settings = Settings::get();

        if (!empty($form_vals["password"])) {
            $password_special_chars = Core::getRequiredPasswordSpecialChars();

            $required_password_chars = explode(",", $settings["required_password_chars"]);
            if (in_array("uppercase", $required_password_chars)) {
                $rules[] = "reg_exp,password,[A-Z],{$LANG["validation_client_password_missing_uppercase"]}";
            }
            if (in_array("number", $required_password_chars)) {
                $rules[] = "reg_exp,password,[0-9],{$LANG["validation_client_password_missing_number"]}";
            }
            if (in_array("special_char", $required_password_chars)) {
                $error = General::evalSmartyString($LANG["validation_client_password_missing_special_char"], array("chars" => $password_special_chars));
                $password_special_chars = preg_quote($password_special_chars);
                $rules[] = "reg_exp,password,[$password_special_chars],$error";
            }
            if (!empty($settings["min_password_length"])) {
                $rule = General::evalSmartyString($LANG["validation_client_password_too_short"], array("number" => $settings["min_password_length"]));
                $rules[] = "length>={$settings["min_password_length"]},password,$rule";
            }
        }

        $errors = validate_fields($form_vals, $rules);
        list($valid_username, $problem) = Accounts::isValidUsername($form_vals["username"]);
        if (!$valid_username) {
            $errors[] = $problem;
        }

        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors), "");
        }

        $password = General::encode($form_vals["password"]);

        // first, insert the record into the accounts table. This contains all the settings common to ALL
        // accounts (including the administrator and any other future account types)
        $db->query("
            INSERT INTO {PREFIX}accounts (account_type, account_status, ui_language, timezone_offset, sessions_timeout,
              date_format, login_page, logout_url, theme, swatch, menu_id, first_name, last_name, email, username, password)
            VALUES (:account_type, :account_status, :ui_language, :timezone_offset, :sessions_timeout, :date_format,
              :login_page, :logout_url, :theme, :swatch, :menu_id, :first_name, :last_name, :email, :username, :password)
        ");
        $db->bindAll(array(
            "account_type" => "client",
            "account_status" => "active",
            "ui_language" => $settings["default_language"],
            "timezone_offset" => $settings["default_timezone_offset"],
            "sessions_timeout" => $settings["default_sessions_timeout"],
            "date_format" => $settings["default_date_format"],
            "login_page" => $settings["default_login_page"],
            "logout_url" => $settings["default_logout_url"],
            "theme" => $settings["default_theme"],
            "swatch" => $settings["default_client_swatch"],
            "menu_id" => $settings["default_client_menu_id"],
            "first_name" => $form_vals["first_name"],
            "last_name" => $form_vals["last_name"],
            "email" => $form_vals["email"],
            "username" => $form_vals["username"],
            "password" => $password
        ));
        $db->execute();

        $new_user_id = $db->getInsertId();

        // now create all the custom client account settings, most of which are based on the default values
        // in the settings table
        $account_settings = array(
            "client_notes" => "",
            "company_name" => "",
            "page_titles"          => $settings["default_page_titles"],
            "footer_text"          => $settings["default_footer_text"],
            "may_edit_page_titles" => $settings["clients_may_edit_page_titles"],
            "may_edit_footer_text" => $settings["clients_may_edit_footer_text"],
            "may_edit_theme"       => $settings["clients_may_edit_theme"],
            "may_edit_logout_url"  => $settings["clients_may_edit_logout_url"],
            "may_edit_language"    => $settings["clients_may_edit_ui_language"],
            "may_edit_timezone_offset"  => $settings["clients_may_edit_timezone_offset"],
            "may_edit_sessions_timeout" => $settings["clients_may_edit_sessions_timeout"],
            "may_edit_date_format"      => $settings["clients_may_edit_date_format"],
            "max_failed_login_attempts" => $settings["default_max_failed_login_attempts"],
            "num_failed_login_attempts" => 0,
            "password_history"          => "",
            "min_password_length"       => $settings["min_password_length"],
            "num_password_history"      => $settings["num_password_history"],
            "required_password_chars"   => $settings["required_password_chars"],
            "may_edit_max_failed_login_attempts" => $settings["clients_may_edit_max_failed_login_attempts"],
            "forms_page_default_message" => $settings["forms_page_default_message"]
        );

        Accounts::setAccountSettings($new_user_id, $account_settings);

        // store this password in the password history queue
        Accounts::addPasswordToPasswordHistory($new_user_id, $password);

        extract(Hooks::processHookCalls("end", compact("new_user_id", "account_settings"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message, $new_user_id);
    }


    /**
     * Used by administrators to login as a client. This function moves the administrator's session to a temporary
     * "admin" session key, and logs the administrator in under the client account. When logging out
     * as a client, the logout function detects if it's really an administrator and erases the old client
     * sessions, replacing them with the old administrator sessions, to enable a smooth transition
     * from one account to the next.
     *
     * @param integer $client_id the client ID
     */
    public static function loginAsClient($client_id)
    {
        // extract the user's login info
        $client_info = Accounts::getAccountInfo($client_id);
        $info = array(
            "username" => $client_info["username"]
        );

        // move the session values to a separate "admin" location, so that once the administrator logs out we can
        // reset the sessions
        $current_values = Sessions::get();
        Sessions::clearAll();
        Sessions::set("admin", $current_values);

        Core::$user->login($info, true);
    }

    /**
     * Used by the administrator to logout from a client account. Resets appropriate
     * sessions values and redirects back to admin pages.
     */
    public static function logoutAsClient()
    {
        $root_url = Core::getRootUrl();

        // empty old sessions and reload admin settings
        $admin_values = Sessions::get("admin");
        $client_id    = Sessions::get("account.account_id");

        Sessions::clearAll();
        foreach ($admin_values as $key => $value) {
            Sessions::set($key, $value);
        }

        Sessions::clear("admin");

        // redirect them back to the edit client page
        General::redirect("$root_url/admin/clients/edit.php?client_id=$client_id");
        exit;
    }


    /**
     * TODO this belongs in User.
     *
     * Updates the administrator account. With the addition of the "UI Language" option, this action
     * gets a little more complicated. The problem is that we can't just update the UI language in
     * sessions *within* this function, because by the time this function is called, the appropriate
     * language file is already in memory and being used. So, to get around this problem, the login
     * information form now passes along both the new and old UI languages. If it's different, AFTER
     * this function is called, you need to reset sessions and refresh the page. So be aware that
     * this problem is NOT handled by this function, see:
     *     /admin/accounts/index.php to see how it's solved.
     *
     * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
     *               following keys: first_name, last_name, user_name, password.
     * @param integer $user_id the administrator's user ID
     * @return array [0]: true/false (success / failure)
     *               [1]: message string
     */
    public static function updateAdminAccount($infohash, $account_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $success = true;
        $message = $LANG["notify_account_updated"];

        extract(Hooks::processHookCalls("start", compact("infohash", "account_id"), array("infohash")), EXTR_OVERWRITE);

        $rules = array();
        $rules[] = "required,first_name,{$LANG["validation_no_first_name"]}";
        $rules[] = "required,last_name,{$LANG["validation_no_last_name"]}";
        $rules[] = "required,email,{$LANG["validation_no_email"]}";
        $rules[] = "required,theme,{$LANG["validation_no_theme"]}";
        $rules[] = "required,login_page,{$LANG["validation_no_login_page"]}";
        $rules[] = "required,logout_url,{$LANG["validation_no_account_logout_url"]}";
        $rules[] = "required,ui_language,{$LANG["validation_no_ui_language"]}";
        $rules[] = "required,sessions_timeout,{$LANG["validation_no_sessions_timeout"]}";
        $rules[] = "required,date_format,{$LANG["validation_no_date_format"]}";
        $rules[] = "required,username,{$LANG["validation_no_username"]}";
        $rules[] = "if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed"]}";
        $rules[] = "if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}";
        $errors = validate_fields($infohash, $rules);

        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors));
        }

        $theme    = $infohash["theme"];
        $username = $infohash["username"];
        $password = $infohash["password"];

        // check to see if username is already taken
        list($valid_username, $problem) = Accounts::isValidUsername($username, $account_id);
        if (!$valid_username) {
            return array(false, $problem);
        }

        $swatch = "";
        if (isset($infohash["{$theme}_theme_swatches"])) {
            $swatch = $infohash["{$theme}_theme_swatches"];
        }

        // if the password is defined, encode it
        $password_clause = !empty($password) ? "password = :password, " : "";
        $enc_password = General::encode($password);

        try {
            $db->query("
                UPDATE  {PREFIX}accounts
                SET     $password_clause
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        theme = :theme,
                        swatch = :swatch,
                        login_page = :login_page,
                        logout_url = :logout_url,
                        ui_language = :ui_language,
                        timezone_offset = :timezone_offset,
                        sessions_timeout = :sessions_timeout,
                        date_format = :date_format,
                        username = :username
                WHERE   account_id = :account_id
            ");
            $db->bindAll(array(
                "first_name" => $infohash["first_name"],
                "last_name" => $infohash["last_name"],
                "email" => $infohash["email"],
                "theme" => $theme,
                "swatch" => $swatch,
                "login_page" => $infohash["login_page"],
                "logout_url" => $infohash["logout_url"],
                "ui_language" => $infohash["ui_language"],
                "timezone_offset" => $infohash["timezone_offset"],
                "sessions_timeout" => $infohash["sessions_timeout"],
                "date_format" => $infohash["date_format"],
                "username" => $username,
                "account_id" => $account_id
            ));
            if (!empty($password)) {
                $db->bind("password", $enc_password);
            }
            $db->execute();
        } catch (Exception $e) {
            return array(false, "Error: " . $e->getMessage());
        }

        // update the settings
        Sessions::set("settings", Settings::get());
        Sessions::set("account", Accounts::getAccountInfo($account_id));
        Sessions::set("account.is_logged_in", true);

        Core::$user->setTheme($theme);
        Core::$user->setSwatch($swatch);

        // if the password just changed, update sessions and empty any temporary password that happens to have been
        // stored
        if (!empty($password)) {
            Sessions::set("account.password", $enc_password);
            Accounts::clearResetPassword($account_id);
        }

        extract(Hooks::processHookCalls("end", compact("infohash", "account_id"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Administrator function used to update a client account. It updates one tab at a time - determined by the
     * second $tab_num parameter.
     *
     * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing keys
     *               named the same as the database fields.
     * @param integer $tab_num the tab number (1-3: 1=main, 2=styles, 3=permissions)
     * @return array [0]: true/false (success / failure)
     *               [1]: message string
     */
    public static function adminUpdateClient($infohash, $tab_num)
    {
        $LANG = Core::$L;

        extract(Hooks::processHookCalls("start", compact("infohash", "tab_num"), array("infohash", "tab_num")), EXTR_OVERWRITE);

        if ($tab_num === 1) {
            list($success, $message) = Administrator::adminUpdateClientAccountMainTab($infohash);
        } else if ($tab_num === 2) {
            list($success, $message) = Administrator::adminUpdateClientAccountSettingsTab($infohash);
        } else if ($tab_num === 3) {
            list($success, $message) = Administrator::adminUpdateClientAccountFormsTab($infohash);
        }

        if ($success) {
            $message = $LANG["notify_client_account_updated"];
        }

        extract(Hooks::processHookCalls("end", compact("infohash", "tab_num"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    // ----------------------------------------------------------------------------------------------------------------


    private static function adminUpdateClientAccountMainTab($form_vals)
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $req_password_special_chars = Core::getRequiredPasswordSpecialChars();
        $debug_enabled = Core::isDebugEnabled();
        $account_id = $form_vals["client_id"];

        $rules = array();
        $rules[] = "required,first_name,{$LANG["validation_no_client_first_name"]}";
        $rules[] = "required,last_name,{$LANG["validation_no_client_last_name"]}";
        $rules[] = "required,email,{$LANG["validation_no_client_email"]}";
        $rules[] = "valid_email,email,{$LANG["validation_invalid_email"]}";
        $rules[] = "required,username,{$LANG["validation_no_client_username"]}";
        $rules[] = "if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed"]}";
        $rules[] = "if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}";

        $account_settings = Accounts::getAccountSettings($account_id);
        if ($account_settings["min_password_length"] != "" && !General::isEmpty($form_vals["password"])) {
            $rule = General::evalSmartyString($LANG["validation_client_password_too_short"], array("number" => $account_settings["min_password_length"]));
            $rules[] = "length>={$account_settings["min_password_length"]},password,$rule";
        }

        if (!General::isEmpty($form_vals["password"])) {
            $required_password_chars = explode(",", $account_settings["required_password_chars"]);
            if (in_array("uppercase", $required_password_chars)) {
                $rules[] = "reg_exp,password,[A-Z],{$LANG["validation_client_password_missing_uppercase"]}";
            }
            if (in_array("number", $required_password_chars)) {
                $rules[] = "reg_exp,password,[0-9],{$LANG["validation_client_password_missing_number"]}";
            }
            if (in_array("special_char", $required_password_chars)) {
                $error = General::evalSmartyString($LANG["validation_client_password_missing_special_char"], array("chars" => $req_password_special_chars));
                $password_special_chars = preg_quote($req_password_special_chars);
                $rules[] = "reg_exp,password,[$password_special_chars],$error";
            }
        }

        $errors = validate_fields($form_vals, $rules);

        // check the username isn't already taken
        $username = $form_vals["username"];
        list($valid_username, $problem) = Accounts::isValidUsername($username, $account_id);
        if (!$valid_username) {
            $errors[] = $problem;
        }

        if (!General::isEmpty($form_vals["password"])) {
            // check the password isn't already in password history (if relevant)
            if (!empty($account_settings["num_password_history"])) {
                $encrypted_password = General::encode($form_vals["password"]);
                if (Accounts::passwordInPasswordHistory($account_id, $encrypted_password, $account_settings["num_password_history"])) {
                    $errors[] = General::evalSmartyString($LANG["validation_password_in_password_history"],
                        array("history_size" => $account_settings["num_password_history"]));
                } else {
                    Accounts::addPasswordToPasswordHistory($account_id, $encrypted_password);
                }
            }
        }

        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors));
        }


        // if the password is defined, md5 it
        $password = $form_vals['password'];
        $password_sql = (!General::isEmpty($password)) ? "password = '" . General::encode($password) . "', " : "";

        // execute the query
        $db->query("
            UPDATE  {PREFIX}accounts
            SET     $password_sql
                    account_status = :account_status,
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    username = :username
            WHERE   account_id = :account_id
        ");
        $db->bindAll(array(
            "account_status" => $form_vals['account_status'],
            "first_name" => $form_vals['first_name'],
            "last_name" => $form_vals['last_name'],
            "email" => $form_vals['email'],
            "username" => $username,
            "account_id" => $account_id
        ));
        try {
            $db->execute();
        } catch (Exception $e) {
            $message = $LANG["notify_client_account_not_updated"];
            if ($debug_enabled) {
                $message .= "<br/>Error: " . $e->getMessage();
            }
            return array(false, $message);
        }

        $new_account_settings = array(
            "client_notes" => $form_vals["client_notes"],
            "company_name" => $form_vals["company_name"]
        );
        Accounts::setAccountSettings($account_id, $new_account_settings);

        return array(true, "");
    }


    private static function adminUpdateClientAccountSettingsTab($form_vals)
    {
        $db = Core::$db;
        $LANG = Core::$L;
        $debug_enabled = Core::isDebugEnabled();

        $rules = array();
        $rules[] = "required,page_titles,{$LANG["validation_no_titles"]}";
        $rules[] = "required,menu_id,{$LANG["validation_no_menu"]}";
        $rules[] = "required,theme,{$LANG["validation_no_theme"]}";
        $rules[] = "required,login_page,{$LANG["validation_no_client_login_page"]}";
        $rules[] = "required,logout_url,{$LANG["validation_no_logout_url"]}";
        $rules[] = "required,ui_language,{$LANG["validation_no_ui_language"]}";
        $rules[] = "required,sessions_timeout,{$LANG["validation_no_sessions_timeout"]}";
        $rules[] = "digits_only,sessions_timeout,{$LANG["validation_invalid_sessions_timeout"]}";
        $rules[] = "required,date_format,{$LANG["validation_no_date_format"]}";
        $errors = validate_fields($form_vals, $rules);

        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors));
        }

        // update the main accounts table
        $account_id = $form_vals["client_id"];
        $theme      = $form_vals['theme'];
        $swatch = (isset($form_vals["{$theme}_theme_swatches"])) ? $swatch = $form_vals["{$theme}_theme_swatches"] : "";

        $db->query("
            UPDATE  {PREFIX}accounts
            SET     ui_language = :ui_language,
                    timezone_offset = :timezone_offset,
                    login_page = :login_page,
                    logout_url = :logout_url,
                    menu_id = :menu_id,
                    theme = :theme,
                    swatch = :swatch,
                    sessions_timeout = :sessions_timeout,
                    date_format = :date_format
            WHERE   account_id = :account_id
        ");
        $db->bindAll(array(
            "ui_language" => $form_vals['ui_language'],
            "timezone_offset" => $form_vals['timezone_offset'],
            "login_page" => $form_vals['login_page'],
            "logout_url" => $form_vals['logout_url'],
            "menu_id" => $form_vals['menu_id'],
            "theme" => $theme,
            "swatch" => $swatch,
            "sessions_timeout" => $form_vals['sessions_timeout'],
            "date_format" => $form_vals['date_format'],
            "account_id" => $account_id
        ));

        try {
            $db->execute();
        } catch (Exception $e) {
            $message = $LANG["notify_client_account_not_updated"];
            if ($debug_enabled) {
                $message .= "<br/>Error: " . $e->getMessage();
            }
            return array(false, $message);
        }

        $may_edit_page_titles      = isset($form_vals["may_edit_page_titles"]) ? "yes" : "no";
        $may_edit_footer_text      = isset($form_vals["may_edit_footer_text"]) ? "yes" : "no";
        $may_edit_theme            = isset($form_vals["may_edit_theme"]) ? "yes" : "no";
        $may_edit_logout_url       = isset($form_vals["may_edit_logout_url"]) ? "yes" : "no";
        $may_edit_language         = isset($form_vals["may_edit_language"]) ? "yes" : "no";
        $may_edit_timezone_offset  = isset($form_vals["may_edit_timezone_offset"]) ? "yes" : "no";
        $may_edit_sessions_timeout = isset($form_vals["may_edit_sessions_timeout"]) ? "yes" : "no";
        $may_edit_date_format      = isset($form_vals["may_edit_date_format"]) ? "yes" : "no";
        $may_edit_max_failed_login_attempts = isset($form_vals["may_edit_max_failed_login_attempts"]) ? "yes" : "no";
        $max_failed_login_attempts = $form_vals["max_failed_login_attempts"];
        $min_password_length       = $form_vals["min_password_length"];
        $num_password_history      = $form_vals["num_password_history"];
        $required_password_chars   = (isset($form_vals["required_password_chars"]) && is_array($form_vals["required_password_chars"])) ? implode(",", $form_vals["required_password_chars"]) : "";
        $forms_page_default_message = $form_vals["forms_page_default_message"];

        // update the client custom account settings table
        $settings = array(
            "page_titles" => $form_vals["page_titles"],
            "footer_text" => $form_vals["footer_text"],
            "may_edit_page_titles" => $may_edit_page_titles,
            "may_edit_footer_text" => $may_edit_footer_text,
            "may_edit_theme"       => $may_edit_theme,
            "may_edit_logout_url"  => $may_edit_logout_url,
            "may_edit_language"    => $may_edit_language,
            "may_edit_timezone_offset"  => $may_edit_timezone_offset,
            "may_edit_sessions_timeout" => $may_edit_sessions_timeout,
            "may_edit_max_failed_login_attempts" => $may_edit_max_failed_login_attempts,
            "max_failed_login_attempts" => $max_failed_login_attempts,
            "may_edit_date_format" => $may_edit_date_format,
            "required_password_chars" => $required_password_chars,
            "min_password_length" => $min_password_length,
            "num_password_history" => $num_password_history,
            "forms_page_default_message" => $forms_page_default_message
        );
        Accounts::setAccountSettings($account_id, $settings);

        return array(true, "");
    }


    private static function adminUpdateClientAccountFormsTab($form_vals)
    {
        $db = Core::$db;

        $account_id = $form_vals["client_id"];

        // clear out the old mappings for the client-forms and client-Views. This section re-inserts everything
        Forms::deleteClientFormsByAccountId($account_id);
        Views::deleteClientViewsByAccountId($account_id);
        OmitLists::deleteFormOmitListByAccountId($account_id);
        OmitLists::deleteViewOmitListByAccountId($account_id);

        $num_form_rows = $form_vals["num_forms"];
        $client_forms      = array(); // stores the form IDs of all forms this client has been added to
        $client_form_views = array(); // stores the view IDs of each form this client is associated with

        for ($i=1; $i<=$num_form_rows; $i++) {

            // ignore blank and empty form rows
            if (!isset($form_vals["form_row_{$i}"]) || empty($form_vals["form_row_{$i}"])) {
                continue;
            }

            $form_id = $form_vals["form_row_{$i}"];
            $client_forms[] = $form_id;
            $client_form_views[$form_id] = array();

            // find out a little info about this form. If it's a public form, the user is already (implicitly) assigned
            // to it, so don't bother inserting a redundant record into the client_forms table
            $form_info = Forms::getFormRow($form_id);

            if ($form_info["access_type"] != "public") {
                $db->query("INSERT INTO {PREFIX}client_forms (account_id, form_id) VALUES (:account_id, :form_id)");
                $db->bindAll(array(
                    "account_id" => $account_id,
                    "form_id" => $form_id
                ));
                $db->execute();
            }

            // if this form was previously an "admin" type, it no longer is! By adding this client to the form, it's now
            // changed to a "private" access type
            if ($form_info["access_type"] == "admin") {
                $db->query("UPDATE {PREFIX}forms SET access_type = 'private' WHERE form_id = :form_id");
                $db->bind("form_id", $form_id);
                $db->execute();
            }

            // now loop through selected Views. Get View info
            if (!isset($form_vals["row_{$i}_selected_views"])) {
                continue;
            }

            $client_form_views[$form_id] = $form_vals["row_{$i}_selected_views"];
            foreach ($form_vals["row_{$i}_selected_views"] as $view_id) {
                $db->query("SELECT access_type FROM {PREFIX}views WHERE view_id = :view_id");
                $db->bind("view_id", $view_id);
                $db->execute();

                $access_type = $db->fetch(PDO::FETCH_COLUMN);

                if ($access_type != "public") {
                    $db->query("INSERT INTO {PREFIX}client_views (account_id, view_id) VALUES (:account_id, :view_id)");
                    $db->bindAll(array(
                        "account_id" => $account_id,
                        "view_id" => $view_id
                    ));
                    $db->execute();
                }

                // if this View was previously an "admin" type, it no longer is! By adding this client to the View, it's now
                // changed to a "private" access type
                if ($access_type == "admin") {
                    $db->query("UPDATE {PREFIX}views SET access_type = 'private' WHERE view_id = :view_id");
                    $db->bind("view_id", $view_id);
                    $db->execute();
                }
            }
        }

        // now all the ADDING the forms/Views is done, we look at all other public forms in the database and if this
        // update request didn't include that form, add this client to its omit list. Same goes for the form Views
        $db->query("SELECT form_id, access_type FROM {PREFIX}forms");
        $db->execute();

        foreach ($db->fetchAll() as $form_info) {
            $form_id        = $form_info["form_id"];
            $form_is_public = ($form_info["access_type"] == "public") ? true : false;

            if ($form_is_public && !in_array($form_id, $client_forms)) {
                $db->query("INSERT INTO {PREFIX}public_form_omit_list (account_id, form_id) VALUES (:account_id, :form_id)");
                $db->bindAll(array(
                    "account_id" => $account_id,
                    "form_id" => $form_id
                ));
                $db->execute();
            }

            if (in_array($form_id, $client_forms)) {
                $db->query("SELECT view_id, access_type FROM {PREFIX}views WHERE form_id = :form_id");
                $db->bind("form_id", $form_id);
                $db->execute();

                foreach ($db->fetchAll() as $view_info) {
                    $view_id        = $view_info["view_id"];
                    $view_is_public = ($view_info["access_type"] == "public") ? true : false;

                    if ($view_is_public && !in_array($view_id, $client_form_views[$form_id])) {
                        $db->query("INSERT INTO {PREFIX}public_view_omit_list (account_id, view_id) VALUES (:account_id, :view_id)");
                        $db->bindAll(array(
                            "account_id" => $account_id,
                            "view_id" => $view_id
                        ));
                        $db->execute();
                    }
                }
            }
        }

        return array(true, "");
    }

}
