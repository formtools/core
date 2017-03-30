<?php

/**
 * Administrator.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Administrator {

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

        extract(ft_process_hook_calls("main", compact("admin_info"), array("admin_info")), EXTR_OVERWRITE);

        return $admin_info;
    }


    /**
     * Used by administrators to login as a client. This function moves the administrator's sessions to a
     * temporary "admin" session key, and logs the administrator in under the client account. When logging out
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
        $info = array();
        $info["username"] = $client_info["username"];

        // move the session values to separate $_SESSION["ft"]["admin"] values, so that
        // once the administrator logs out we can reset the sessions appropriately
        $current_values = $_SESSION["ft"];
        $_SESSION["ft"] = array();
        $_SESSION["ft"]["admin"] = $current_values;

        // now log in
        ft_login($info, true);
    }

    /**
     * Used by the administrator to logout from a client account. Resets appropriate
     * sessions values and redirects back to admin pages.
     */
    public static function logoutAsClient()
    {
        $root_url = Core::getRootURL();

        // empty old sessions and reload admin settings
        $admin_values = $_SESSION["ft"]["admin"];
        $client_id    = $_SESSION["ft"]["account"]["account_id"];
        $_SESSION["ft"] = array();

        foreach ($admin_values as $key => $value) {
            $_SESSION["ft"][$key] = $value;
        }

        unset($_SESSION["ft"]["admin"]);

        // redirect them back to the edit client page
        session_write_close();
        header("location: $root_url/admin/clients/edit.php?client_id=$client_id");
        exit;
    }

}
