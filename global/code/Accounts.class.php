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


}
