<?php

/**
 * Contains methods for the current logged in user: admin or client. Submission Accounts are handled via that module.
 */

namespace FormTools;


class User
{
    private $lang;

    public function __construct() {

    }

    public function getLang() {
        return $this->lang;
    }

    // --------------------------------------------------------------------------------------------

    /**
     * Simple helper to find out if someone's currently logged in (i.e. sessions exist).
     */
    public static function isLoggedIn() {
        return isset($_SESSION["ft"]["account"]["is_logged_in"]) && $_SESSION["ft"]["account"]["is_logged_in"];
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
}
