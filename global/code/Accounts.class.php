<?php

/**
 * The Account class. Added in 2.3.0; will replace the old accounts.php file.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-3-x
 * @subpackage Installation
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;


/**
 * Form Tools Accounts class.
 */
class Accounts {

    /**
     * Creates the administrator account. Used within the installation process.
     * @param array $info
     * @return array
     */
    public static function setAdminAccount(Database $db, array $info, $table_prefix)
    {
        global $g_table_prefix, $g_root_url, $LANG;

        $info = ft_install_sanitize_no_db($info);

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
            return array(false, $errors);
        }

        $first_name = $info["first_name"];
        $last_name  = $info["last_name"];
        $email      = $info["email"];
        $username   = $info["username"];
        $password   = md5(md5($info["password"]));

        $db->query("
            UPDATE {$table_prefix}accounts
            SET first_name = :fname,
                last_name = :lname,
                email = :email,
                username = :username
                logout_url = :logout_url
            WHERE account_id = :account_id
        ");

//        $db->bind(":fname", );
//
//        $query = mysql_query("
//            UPDATE {$g_table_prefix}accounts
//            SET    first_name = '$first_name',
//                last_name = '$last_name',
//                email = '$email',
//                username = '$username',
//                password = '$password',
//                logout_url = '$g_root_url'
//            WHERE account_id = 1
//        ");
//
        $success = true;
        $message = "";
        if (!$query) {
            $success = false;
            $message = mysql_error();
        }

        return array($success, $message);
    }


}
