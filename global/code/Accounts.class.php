<?php

/**
 * The Account class. Added in 2.3.0; will replace the old accounts.php file.
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDOException;


/**
 * Form Tools Accounts class.
 */
class Accounts {

    /**
     * Creates the administrator account. Used within the installation process.
     * @param array $info
     * @return array
     */
    public static function setAdminAccount(array $info)
    {
        global $LANG;

        $rules = array();
        $rules[] = "required,first_name,{$LANG["validation_no_first_name"]}";
        $rules[] = "required,last_name,{$LANG["validation_no_last_name"]}";
        $rules[] = "required,email,{$LANG["validation_no_admin_email"]}";
        $rules[] = "valid_email,email,Please enter a valid administrator email address.";
        $rules[] = "required,username,{$LANG["validation_no_username"]}";
        $rules[] = "required,password,{$LANG["validation_no_password"]}";
        $rules[] = "required,password_2,{$LANG["validation_no_second_password"]}";
        $rules[] = "same_as,password,password_2,{$LANG["validation_passwords_different"]}";
        $errors = validate_fields($info, $rules);

        if (!empty($errors)) {
            return array(false, General::getErrorListHTML($errors));
        }

        $db = Core::$db;
        $db->query("
            UPDATE {PREFIX}accounts
            SET first_name = :first_name,
                last_name = :last_name,
                email = :email,
                username = :username,
                password = :password,
                logout_url = :logout_url
            WHERE account_id = :account_id
        ");

        $db->bindAll(array(
            ":first_name" => $info["first_name"],
            ":last_name" => $info["last_name"],
            ":email" => $info["email"],
            ":username" => $info["username"],
            ":password" => md5(md5($info["password"])),
            ":logout_url" => Core::getRootURL(),
            ":account_id" => 1 // the admin account is always ID 1
        ));

        try {
            $db->execute();
        } catch (PDOException $e) {
            return array(false, $e->getMessage());
        }

        return array(true, "");
    }


    public static function getAccountByUsername($username) {
        $db = Core::$db;
        $db->query("
            SELECT account_id, account_type, account_status, password, temp_reset_password, login_page
            FROM   {PREFIX}accounts
            WHERE  username = :username
        ");
        $db->bind(":username", $username);
        $db->execute();
        return $db->fetch();
    }

    /**
     * Returns all custom account settings for a user account. This merely queries the
     * account_settings table, nothing more; it doesn't trickle up the inheritance
     * chain to figure out the default settings.
     *
     * @param integer $account_id
     * @return array
     */
    public static function getAccountSettings($account_id)
    {
        $db = Core::$db;

        if (empty($account_id)) {
            return array();
        }

        $db->query("
            SELECT setting_name, setting_value
            FROM   {PREFIX}account_settings
            WHERE  account_id = :account_id
        ");
        $db->bind(":account_id", $account_id);
        $db->execute();

        $hash = array();
        foreach ($db->fetchAll() as $row) {
            $hash[$row['setting_name']] = $row["setting_value"];
        }

        extract(ft_process_hook_calls("main", compact("account_id", "hash"), array("hash")), EXTR_OVERWRITE);

        return $hash;
    }


    /**
     * Figure out if an account exists or not.
     */
    public static function accountExists($account_id)
    {
        $db = Core::$db;

        if (empty($account_id) || !is_numeric($account_id)) {
            return false;
        }

        $db->query("
            SELECT count(*) as c
            FROM {PREFIX}accounts
            WHERE account_id = :account_id
        ");
        $db->bind(":account_id", $account_id);
        $db->execute();

        $result = $db->fetch();

        return ($result["c"] == 1);
    }


    /**
     * Retrieves all information about any user account (administrator or client).
     *
     * @param integer $user_id the unique account ID
     * @return array returns a hash of all pertinent data.
     */
    public static function getAccountInfo($account_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM {PREFIX}accounts
            WHERE account_id = :account_id
        ");
        $db->bind(":account_id", $account_id);
        $db->execute();

        $account_info = $db->fetch();

        if (empty($account_info)) {
            return array();
        }

        // also extract any account-specific settings from account_settings
        $db->query("
            SELECT * 
            FROM {PREFIX}account_settings 
            WHERE account_id = :account_id
        ");
        $db->bind(":account_id", $account_id);
        $db->execute();

        $settings = array();
        foreach ($db->fetchAll() as $row) {
            $settings[$row["setting_name"]] = $row["setting_value"];
        }
        $account_info["settings"] = $settings;

        extract(ft_process_hook_calls("main", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

        return $account_info;
    }


    /**
     * This function is called when updating a client account and the administrator has chosen to prevent them from
     * choosing any password they entered in the last N times (up to 10).
     *
     * The password_history setting in the users' account_settings table always stores the last 10 encrypted passwords,
     * comma-delimited, and ordered newest to oldest. This function just checks that log against an incoming password
     * to check its validity.
     *
     * @param $account_id
     * @param string $password (encrypted)
     * @param integer the number of items to check in the history. e.g. 5 would only check the last 5 passwords.
     */
    public static function passwordInPasswordHistory($account_id, $password, $num_password_history)
    {
        $account_settings = self::getAccountSettings($account_id);
        $last_passwords = (isset($account_settings["password_history"]) && !empty($account_settings["password_history"])) ?
        explode(",", $account_settings["password_history"]) : array();

        $is_found = false;
        for ($i=0; $i<$num_password_history; $i++) {
            if ($password == $last_passwords[$i]) {
                $is_found = true;
                break;
            }
        }
        return $is_found;
    }


    /**
     * Updates the password history queue for a client account. The assumption is that ft_password_in_password_history()
     * has already been called to determine whether or not the password should be added to the list.
     *
     * @param integer $account_id
     * @param string $password
     */
    public static function addPasswordToPasswordHistory($account_id, $password)
    {
        $passwordHistorySize = Core::getPasswordHistorySize();

        $account_settings = ft_get_account_settings($account_id);
        $last_passwords = (isset($account_settings["password_history"]) && !empty($account_settings["password_history"])) ?
            explode(",", $account_settings["password_history"]) : array();
        array_unshift($last_passwords, $password);
        $trimmed_list = array_splice($last_passwords, 0, $passwordHistorySize);
        $new_password_history = implode(",", $trimmed_list);
        ft_set_account_settings($account_id, array("password_history" => $new_password_history));
    }


    /**
     * Helper function to determine if a username is valid or not. Checks to see that it only contains a-Z, 0-9, ., _
     * and @ chars and that it's not already taken.
     *
     * @param string $username a prospective username
     * @param integer $user_id optional paramter used when editing the username for an account
     * @return array [0]: true/false (success / failure)<br />
     *               [1]: message string
     */
    public static function isValidUsername($username, $account_id = "")
    {
        $LANG = Core::$L;
        $db = Core::$db;

        // check the username is alphanumeric
        if (preg_match("/[^\.a-zA-Z0-9_@]/", $username)) {
            return array(false, $LANG["validation_invalid_client_username2"]);
        }

        $clause = (!empty($account_id)) ? "AND account_id != :account_id" : "";

        // now check the username isn't already taken
        $db->query("
            SELECT count(*)
            FROM   {PREFIX}accounts
            WHERE  username = :username
            $clause
        ");
        $db->bind(":username", $username);
        if (!empty($account_id)) {
            $db->bind(":account_id", $account_id);
        }
        $db->execute();
        $info = $db->fetch();

        if ($info[0] > 0) {
            return array(false, $LANG["validation_username_taken"]);
        } else {
            return array(true, "");
        }
    }

}

