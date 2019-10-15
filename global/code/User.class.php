<?php

/**
 * Contains methods for the current logged in user: admin or client. Submission Accounts are handled entirely
 * via that module.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage User
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class User
{
	private $lang;
	private $isLoggedIn;
	private $accountType;
	private $accountId;
	private $username;
	private $email;
	private $firstName;
	private $lastName;
	private $theme; // default from DB
	private $swatch; // default from DB


	/**
	 * This class is instantiated on every page load through Core::init() and available via Core::$user. A user
	 * object is always instantiated, even if the user isn't logged in. This provides a consistent interface to
	 * find out things like what theme, language etc. should be used.
	 *
	 * How should this work? We need to store details about the user in sessions (e.g. their ID) but we don't
	 * want to query the database for the user on each and every page load. So the constructor here relies on checking
	 * sessions to instantiate the user.
	 */
	public function __construct()
	{
		$account_id = Sessions::get("account.account_id");

		// if the user isn't logged in, set the defaults
		if (empty($account_id)) {
			$this->isLoggedIn = false;

			$settings = Settings::get(array(
				"default_language",
				"default_theme",
				"default_client_swatch"
			));
			$this->lang = $settings["default_language"];
			$this->theme = $settings["default_theme"];
			$this->swatch = $settings["default_client_swatch"];
		} else {
			$this->isLoggedIn = true;
			$this->accountId = $account_id;
			$this->theme = Sessions::get("account.theme");
			$this->swatch = Sessions::get("account.swatch");
			$this->lang = Sessions::get("account.ui_language");
			$this->email = Sessions::get("account.email");
			$this->username = Sessions::get("account.username");
			$this->firstName = Sessions::get("account.first_name");
			$this->lastName = Sessions::get("account.last_name");
			$this->accountType = Sessions::get("account.account_type");
		}
	}

	/**
	 * Logs in administrators and clients. If successful, redirects them to the appropriate page, otherwise returns an
	 * error.
	 *
	 * @param array $info $_POST or $_GET containing both "username" and "password" keys, containing that information
	 *                for the user trying to log in.
	 * @param boolean $login_as_client [optional] This optional parameter is used by administrators
	 *                to log in as a particular client, allowing them to view how the account looks,
	 *                even if it is disabled.
	 * @return string error message string (if error occurs). Otherwise it redirects the user to the
	 *                appropriate page, based on account type.
	 */
	public function login($info, $login_as_client = false)
	{
		$LANG = Core::$L;
		$root_url = Core::getRootUrl();

		$settings = Settings::get("", "core");
		$username = $info["username"];

		// administrators can log into client accounts to see what they see. They don't require the client's password
		$password = isset($info["password"]) ? $info["password"] : "";

		// extract info about this user's account
		$account_info = Accounts::getAccountByUsername($username);
		$account_settings = Accounts::getAccountSettings($account_info["account_id"]);

		if (!$login_as_client) {
			if (General::isEmpty($password)) {
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

			$password_correct = General::encode($password) == $account_info["password"];
			$temp_password_correct = General::encode($password) == $account_info["temp_reset_password"];

			// if this is a client account and the administrator has enabled the maximum failed login attempts feature,
			// keep track of the count
			if (!$password_correct && !$temp_password_correct) {

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

		extract(Hooks::processHookCalls("main", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

		$account_info["settings"] = $account_settings;

		// all checks out. Log them in, after populating sessions
		Sessions::set("settings", $settings);
		Sessions::set("account", $account_info);
		Sessions::set("account.is_logged_in", true);

		if ($password) {
			Sessions::set("account.password", General::encode($password));
		}

		Menus::cacheAccountMenu($account_info["account_id"]);

		// if this is an administrator, ensure the API version is up to date
		if ($account_info["account_type"] == "admin") {
			General::updateApiVersion();
		} else {
			Accounts::setAccountSettings($account_info["account_id"], array("num_failed_login_attempts" => 0));
		}

		// for clients, store the forms & form Views that they are allowed to access
		if ($account_info["account_type"] == "client") {
			Sessions::set("permissions", Clients::getClientFormViews($account_info["account_id"]));
		}

		// if the user just logged in with a temporary password, append some args to pass to the login page
		// so that they will be prompted to changing it upon login
		$reset_password_args = array();
		if (General::encode($password) == $account_info["temp_reset_password"]) {
			$reset_password_args["message"] = "change_temp_password";
		}

		// redirect the user to whatever login page they specified in their settings
		$login_url = Pages::constructPageURL($account_info["login_page"], "", $reset_password_args);
		$login_url = "$root_url{$login_url}";

		if (!$login_as_client) {
			$this->updateLastLoggedIn($account_info["account_id"]);
		}

		General::redirect($login_url);
	}

	public function getLang()
	{
		return $this->lang;
	}

	public function setLang($lang)
	{
		$this->lang = $lang;
	}

	public function setTheme($theme)
	{
		$this->theme = $theme;
	}

	public function getTheme()
	{
		return $this->theme;
	}

	public function setSwatch($swatch)
	{
		$this->swatch = $swatch;
	}

	public function getSwatch()
	{
		return $this->swatch;
	}

	public function isLoggedIn()
	{
		return $this->isLoggedIn;
	}

	public function getAccountId()
	{
		return $this->accountId;
	}

	public function getAccountType()
	{
		return $this->accountType;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function getFirstName()
	{
		return $this->firstName;
	}

	public function getLastName()
	{
		return $this->lastName;
	}

	/**
	 * Updates the last logged in date for the currently logged in user.
	 * @param $account_id
	 */
	private function updateLastLoggedIn($account_id)
	{
		$db = Core::$db;

		$db->query("
            UPDATE {PREFIX}accounts
            SET    last_logged_in = :now
            WHERE  account_id = :account_id
        ");
		$db->bindAll(array(
			"now" => General::getCurrentDatetime(),
			"account_id" => $account_id
		));
		$db->execute();
	}

	/**
	 * Redirects a logged in user to their login page.
	 */
	public function redirectToLoginPage()
	{
		$root_url = Core::getRootUrl();
		$login_page = Sessions::get("account.login_page");
		$page = Pages::constructPageURL($login_page);
		General::redirect("{$root_url}$page");
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
		$root_url = Core::getRootUrl();

		extract(Hooks::processHookCalls("main", array(), array()));

		// first, if $_SESSION["ft"]["admin"] is set, it is an administrator logging out, so just redirect them
		// back to the admin pages
		if (Sessions::exists("admin")) {
			Administrator::logoutAsClient();
		} else {
			if (!empty($message_flag)) {
				// empty sessions, but be nice about it. Only delete the Form Tools namespaced sessions - any other
				// PHP scripts the user's running right now should be unaffected
				@session_start();
				@session_destroy();
				Sessions::clearAll();

				// redirect to the login page, passing along the appropriate message flag so the page knows what to display
				$logout_url = General::constructUrl($root_url, "message=$message_flag");
				General::redirect($logout_url);
			} else {
				$logout_url = Sessions::getWithFallback("account.logout_url", "");

				// empty sessions, but be nice about it. Only delete the Form Tools namespaced sessions - any other
				// PHP scripts the user happens to be running right now should be unaffected
				@session_start();
				@session_destroy();
				Sessions::clearAll();

				if (empty($logout_url)) {
					$logout_url = $root_url;
				}

				// redirect to login page
				General::redirect($logout_url);
			}
		}
	}

	public function isAdmin()
	{
		return $this->accountType == "admin";
	}

	/**
	 * Verifies the user has permission to view the current page. It is used by feeding the minimum account type to
	 * view the page - "client", will let administrators and clients view it, but "admin" will only let administrators.
	 * If the person doesn't have permission to view the page they are logged out.
	 *
	 * Should be called on ALL Form Tools pages - including modules.
	 *
	 * @param string $account_type The account type - "admin" / "client" / "user" (for Submission Accounts module)
	 * @param boolean $auto_logout either automatically log the user out if they don't have permission to view the page (or
	 *     sessions have expired), or - if set to false, just return the result as a boolean (true = has permission,
	 *     false = doesn't have permission)
	 * @return array (if $auto_logout is set to false)
	 */
	public function checkAuth($required_account_type, $auto_logout = true)
	{
		$db = Core::$db;
		$root_url = Core::getRootUrl();

		$boot_out_user = false;
		$message_flag = "";

		extract(Hooks::processHookCalls("start", compact("account_type"), array("boot_out_user", "message_flag")), EXTR_OVERWRITE);

		$account_id = $this->accountId;
		$account_type = $this->accountType;

		// This will all be refactored when we introduce role-based auth
		// ----------------
		// some VERY complex logic here. The "user" account permission type is included so that people logged in
		// via the Submission Accounts can still view certain pages, e.g. pages with the Pages module. This checks that
		// IF the minimum account type of the page is a "user", it EITHER has the user account info set (i.e. the submission ID)
		// or it's a regular client or admin account with the account_id set. Crumby, but it'll have to suffice for now.
		if ($this->accountType == "user") {

			if ((!Sessions::exists("account.submission_id") || General::isEmpty(Sessions::get("account.submission_id"))) &&
				General::isEmpty(Sessions::get("account.account_id"))) {
				if ($auto_logout) {
					General::redirect("$root_url/modules/submission_accounts/logout.php");
				} else {
					$boot_out_user = true;
					$message_flag = "notify_no_account_id_in_sessions";
				}
			}
		} // check the user ID is in sessions
		else if (!$account_id || !$account_type) {
			$boot_out_user = true;
			$message_flag = "notify_no_account_id_in_sessions";
		} else if ($account_type == "client" && $required_account_type == "admin") {
			$boot_out_user = true;
			$message_flag = "notify_invalid_permissions";
		} else {
			$db->query("
                SELECT count(*) as c
                FROM {PREFIX}accounts
                WHERE account_id = :account_id AND (password = :password OR temp_reset_password = :temp_reset_password)
            ");
			$db->bindAll(array(
				"account_id" => $account_id,
				"password" => Sessions::get("account.password"),
				"temp_reset_password" => Sessions::get("account.password")
			));
			$db->execute();
			$info = $db->fetch();

			if ($info["c"] != 1) {
				$boot_out_user = true;
				$message_flag = "notify_invalid_account_information_in_sessions";
			}
		}

		if ($boot_out_user && $auto_logout) {
			$this->logout($message_flag);
		} else {
			return array(
				"has_permission" => !$boot_out_user, // we invert it because we want to return TRUE if they have permission
				"message" => $message_flag
			);
		}
	}

	public function getAccountPlaceholders()
	{
		$account_id = $this->getAccountId();

		$placeholders = array(
			"ACCOUNT_ID" => $account_id,
			"USERNAME" => $this->getUsername(),
			"EMAIL" => $this->getEmail(),
			"FIRST_NAME" => $this->getFirstName(),
			"LAST_NAME" => $this->getLastName()
		);

		extract(Hooks::processHookCalls("main", compact("placeholders", "account_id"), array("placeholders")), EXTR_OVERWRITE);

		return $placeholders;
	}

}
