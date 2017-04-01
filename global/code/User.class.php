<?php

/**
 * Contains methods for the current logged in user: admin or client. Submission Accounts are handled via that module.
 */

namespace FormTools;


class User
{
    private $lang;
    private $isLoggedIn = false;
    private $accountType;
    private $accountId;
    private $username;
    private $email;
    private $theme;
    private $swatch;


    /**
     * The singleton user object is instantiated automatically in Core::init() and available via Core::$user. A user
     * object is always instantiated, even if the user isn't logged in.
     */
    public function __construct() {
        if (empty($account_id)) {

        } else {

        }
    }


    /**
     * The login procedure for both administrators and clients in. If successful, redirects them to the
     * appropriate page, otherwise returns an error.
     *
     * @param array   $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing both
     *                "username" and "password" keys, containing that information for the user trying
     *                to log in.
     * @param boolean $login_as_client [optional] This optional parameter is used by administrators
     *                to log in as a particular client, allowing them to view how the account looks,
     *                even if it is disabled.
     * @return string error message string (if error occurs). Otherwise it redirects the user to the
     *                appropriate page, based on account type.
     */
    public function login($info, $login_as_client = false)
    {
        $LANG = Core::$db;

        $settings = Settings::get("", "core");

        $username = $info["username"];

        // administrators can log into client accounts to see what they see. They don't require the client's password
        $password = isset($info["password"]) ? $info["password"] : "";

        // extract info about this user's account
        $account_info = Accounts::getAccountByUsername($username);

        // error check user login info
        if (!$login_as_client) {
            if (empty($password)) {
                return $LANG["validation_no_password"];
            }
            if ($account_info["account_status"] == "disabled") {
                return $LANG["validation_account_disabled"];
            }
            if ($account_info["account_status"] == "pending") {
                return $LANG["validation_account_pending"];
            }
            if (empty($username)) {
                return $LANG["validation_account_not_recognized"];
            }

            $password_correct      = (md5(md5($password)) == $account_info["password"]);
            $temp_password_correct = (md5(md5($password)) == $account_info["temp_reset_password"]);


            if (!$password_correct && !$temp_password_correct) {

                // if this is a client account and the administrator has enabled the maximum failed login attempts feature,
                // keep track of the count
                $account_settings = Accounts::getAccountSettings($account_info["account_id"]);

                // stores the MAXIMUM number of failed attempts permitted, before the account gets disabled. If the value
                // is empty in either the user account or for the default value, that means the administrator doesn't want
                // to track the failed login attempts
                $max_failed_login_attempts = (isset($account_settings["max_failed_login_attempts"])) ?
                $account_settings["max_failed_login_attempts"] : $settings["default_max_failed_login_attempts"];

                if ($account_info["account_type"] == "client" && !empty($max_failed_login_attempts)) {
                    $num_failed_login_attempts = (isset($account_settings["num_failed_login_attempts"]) && !empty($account_settings["num_failed_login_attempts"])) ?
                    $account_settings["num_failed_login_attempts"] : 0;

                    $num_failed_login_attempts++;

                    if ($num_failed_login_attempts >= $max_failed_login_attempts) {
                        Clients::disableClient($account_info["account_id"]);
                        Accounts::setAccountSettings($account_info["account_id"], array("num_failed_login_attempts" => 0));
                        return $LANG["validation_account_disabled"];
                    } else {
                        Accounts::setAccountSettings($account_info["account_id"], array("num_failed_login_attempts" => $num_failed_login_attempts));
                    }
                }
                return $LANG["validation_wrong_password"];
            }
        }

        extract(ft_process_hook_calls("main", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

        // all checks out. Log them in, after populating sessions
        $_SESSION["ft"]["settings"] = $settings;
        $_SESSION["ft"]["account"]  = Accounts::getAccountInfo($account_info["account_id"]);
        $_SESSION["ft"]["account"]["is_logged_in"] = true;

        // this is deliberate
        $_SESSION["ft"]["account"]["password"] = md5(md5($password));

        ft_cache_account_menu($account_info["account_id"]);

        // if this is an administrator, ensure the API version is up to date
        if ($account_info["account_type"] == "admin") {
            ft_update_api_version();
        } else {
            Accounts::setAccountSettings($account_info["account_id"], array("num_failed_login_attempts" => 0));
        }

        // for clients, store the forms & form Views that they are allowed to access
        if ($account_info["account_type"] == "client")
            $_SESSION["ft"]["permissions"] = ft_get_client_form_views($account_info["account_id"]);


        // if the user just logged in with a temporary password, append some args to pass to the login page
        // so that they will be prompted to changing it upon login
        $reset_password_args = array();
        if ((md5(md5($password)) == $account_info["temp_reset_password"])) {
            $reset_password_args["message"] = "change_temp_password";
        }

        // redirect the user to whatever login page they specified in their settings
        $login_url = Pages::constructPageURL($account_info["login_page"], "", $reset_password_args);
        $login_url = "$g_root_url{$login_url}";

        if (!$login_as_client) {
            $this->updateLastLoggedIn();
        }

        session_write_close();
        header("Location: $login_url");
        exit;
    }

    public function getLang() {
        return $this->lang;
    }

    public function getTheme() {
        return $this->theme;
    }

    /**
     * Helper to find out if someone's currently logged in (i.e. sessions exist).
     */
    public function isLoggedIn() {
        return $this->isLoggedIn;
    }


    /**
     * Returns the account ID of the currently logged in user - or returns the empty string if there's no user account.
     *
     * @return integer the account ID
     */
    function getAccountId() // ft_get_current_account_id
    {
//        $account_id = isset($_SESSION["ft"]["account"]["account_id"]) ? $_SESSION["ft"]["account"]["account_id"] : "";
//        return $account_id;
    }

    /**
     * Helper function to determine if the user currently logged in is an administrator or not.
     */
    function isAdmin()
    {
//        $account_id = isset($_SESSION["ft"]["account"]["account_id"]) ? $_SESSION["ft"]["account"]["account_id"] : "";
//        if (empty($account_id))
//            return false;
//
//        $account_info = Accounts::getAccountInfo($account_id);
//        if (empty($account_info) || $account_info["account_type"] != "admin")
//            return false;
//
//        return true;
    }


    /**
     * Updates the last logged in date for the currently logged in user.
     */
    private function updateLastLoggedIn()
    {
        $db = Core::$db;
        $db-query("
            UPDATE {PREFIX}accounts
            SET    last_logged_in = :now
            WHERE  account_id = :account_id
        ");
        $db->bindAll(array(
            ":now" => General::getCurrentDatetime(),
            ":account_id" => $this->accountId
        ));
        $db->execute();
    }


    /**
     * Redirects a logged in user to their login page. Make this non-static.
     */
    public static function redirectToLoginPage() {
        $rootURL = Core::getRootURL();
        $loginPage = $_SESSION["ft"]["account"]["login_page"];
        $page = Pages::constructPageURL($loginPage);
        header("location: {$rootURL}$page");
    }



    /**
     * Logs a user out programmatically. This was added in 2.0.0 to replace the logout.php page. It has
     * a couple of benefits: (1) it's smart enough to know what page to go when logging out. Formerly, it
     * would always redirect to the account's logout URL, but there are situations where that's not always
     * desirable - e.g. sessions timeout. (2) it has the option of passing a message flag via the query
     * string.
     *
     * Internally, a user can logout by passing a "?logout" query string to any page in Form Tools.
     *
     * @param string $message_flag if this value is set, it ALWAYS redirects to the login page, so that the
     *   message is displayed. If it isn't set, it redirects to the user's custom logout URL (if defined).
     */
    public function logout($message_flag = "")
    {
        global $g_root_url, $g_session_type;

        extract(ft_process_hook_calls("main", array(), array()));

        // this ensures sessions are started
        if ($g_session_type == "database")
            $sess = new SessionManager();
        @session_start();

        // first, if $_SESSION["ft"]["admin"] is set, it is an administrator logging out, so just redirect them
        // back to the admin pages
        if (isset($_SESSION["ft"]) && array_key_exists("admin", $_SESSION["ft"])) {
            Administrator::logoutAsClient();
        } else {
            if (!empty($message_flag))
            {
                // empty sessions, but be nice about it. Only delete the Form Tools namespaced sessions - any other
                // PHP scripts the user's running right now should be unaffected
                @session_start();
                @session_destroy();
                $_SESSION["ft"] = array();

                // redirect to the login page, passing along the appropriate message flag so the page knows what to display
                $logout_url = ft_construct_url("$g_root_url/", "message=$message_flag");
                session_write_close();
                header("location: $logout_url");
                exit;
            }
            else
            {
                $logout_url = isset($_SESSION["ft"]["account"]["logout_url"]) ? $_SESSION["ft"]["account"]["logout_url"] : "";

                // empty sessions, but be nice about it. Only delete the Form Tools namespaced sessions - any other
                // PHP scripts the user happens to be running right now should be unaffected
                @session_start();
                @session_destroy();
                $_SESSION["ft"] = array();

                if (empty($logout_url))
                    $logout_url = $g_root_url;

                // redirect to login page
                session_write_close();
                header("location: $logout_url");
                exit;
            }
        }
    }

}
