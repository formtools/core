<?php

/**
 * Form Tools - generic form processing, storage and access script
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License included in this zipfile for more details.
 *
 * The Core class added in 3.0.0. This replaces the old /global/library.php file. It's a singleton that's
 * instantiated for all page loads and contains all the core functionality / objects / data etc. used
 * throughout the script. Basically it's a convenience static object that contains most of the stuff you need, e.g.:
 *          - Core::$db (database connection)
 *          - Core::$user (current user)
 *          - Core::$L (language strings for current user)
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @version 3.0.x
 * @package 3-0-x
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use Smarty, SmartyBC;


class Core
{

	// SECTION 1: settings you can override in your global/config.php file

	/**
	 * This is the base URL of the Form Tools installation on your server. e.g.
	 * http://www.yoursite.com/formtools. You can either supply an absolute or relative URL. Note: if
	 * you include the full URL, make sure that the "www." part is either included or removed
	 * consistently; if you try to log in at http://www.yoursite.com/admin but your $rootURL is set to
	 * http://yoursite.com/admin it will not work! (and vice versa).
	 */
	private static $rootURL = "";

	/**
	 * The server directory path to your Form Tools folder.
	 */
	private static $rootDir = "";

	/**
	 * The database hostname (most often 'localhost').
	 */
	private static $dbHostname = "";

	/**
	 * The name of the database. Most often, hosting providers provide you with some sort of user
	 * interface for creating databases and assigning user accounts to them.
	 */
	private static $dbName = "";

	/**
	 * The DB port.
	 */
	private static $dbPort = "";

	/**
	 * The MySQL username. Note: this user account must have privileges for adding and deleting tables, and
	 * adding and deleting records.
	 */
	private static $dbUsername = "";

	/**
	 * The MySQL password.
	 */
	private static $dbPassword = "";

	/**
	 * This value lets you define a custom database prefix for your Form Tools tables. This is handy if
	 * Form Tools will be added to an existing database and you want to avoid table naming conflicts.
	 */
	private static $dbTablePrefix = "ft_";

	/**
	 * Added in 3.0.15, lets users override the default cache folder location.
	 */
	private static $cacheFolder;

	/**
	 * This controls the maximum number of pagination links that appear in the Form Tools UI (e.g. for
	 * viewing the submission listings page).
	 */
	private static $maxNavPages;

	/**
	 * This offers support for unicode. All form submissions will be sent as UTF-8. This is enabled for all
	 * new installations.
	 */
	private static $unicode = true;

	/**
	 * Used for local development mode. Added in 3.0.20.
	 * @var bool
	 */
	private static $devMode = false;

	/**
	 * This is set to 1 by default (genuine errors only). Crank it up to 2047 to list every
	 * last error/warning/notice that occurs.
	 */
	private static $errorReporting;

	/**
	 * Various debug settings. As of 2.3.0 these are of varying degrees of being supported.
	 */
	private static $debugEnabled;
	private static $jsDebugEnabled;
	private static $smartyDebug = false;

	/**
	 * This tells Smarty to create the compiled templates in subdirectories, which is slightly more efficient.
	 * Not compatible on some systems, so it's set to false by default.
	 */
	private static $smartyUseSubDirs = false;

	/**
	 * This determines the value used to separate the content of array form submissions (e.g. checkboxes
	 * in your form that have the same name, or multi-select dropdowns) when submitted via a query
	 * string for "direct" form submissions (added in version 1.4.2).
	 */
	private static $queryStrMultiValSeparator;

	/**
	 * This setting lets you control the type of sessions the application uses. The default value is "database",
	 * but you can change it to "php" if you'd prefer to use PHP sessions. This applies to all users of the program.
	 */
	private static $sessionType = "php"; // "php" or "database"

	/**
	 * This lets you specify the session save path, used by PHP sessions. By default this isn't set, relying
	 * on the default value. But on some systems this value needs to be set.
	 */
	private static $sessionSavePath = "";

	/**
	 * Used for the database charset. For rare cases, the utf8 character set isn't available, so this allows
	 * them to change it and install the script.
	 */
	private static $dbTableCharset = "utf8";

	/**
	 * Permissible characters in a filename. All other characters are stripped out. *** including a hyphen here
	 * leads to problems. ***
	 */
	private static $filenameCharWhitelist = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_0123456789";

	/**
	 * Special chars, required in password (optional setting through interface).
	 */
	private static $requiredPasswordSpecialChars = "~!@#$%^&";

	/**
	 * The size of the password_history setting in the settings table. Form Tools keeps track of the last 10
	 * passwords, to (optionally) prevent users from re-using a password that they used before.
	 */
	private static $passwordHistorySize = 10;

	/**
	 * Determines the format of the date range string when searching a date field. Note: this only accepts two
	 * values: d/m/y or m/d/y. This is because this value is used by both the daterangepicker element and
	 * on the server. I don't want to fuss around with too many formats; it's confusing enough!
	 */
	private static $searchFormDateFieldFormat;

	/**
	 * Added in 2.1.0 and enabled by default. This overrides the default SQL mode for any query, to prevent
	 * problems that may arise due to MySQL strict mode being on. This was deprecated in 3.0.2; use $g_sql_strict_mode
	 * instead
	 */
	private static $setSqlMode;

	/**
	 * Defines the SQL strict mode for queries. Added in 3.0.2; replaces the older $g_set_sql_mode setting.
	 * @var string "on", "off", "default"
	 */
	private static $sqlStrictMode;

	/**
	 * This hides the upgrade link in the administrator's UI.
	 */
	private static $hideUpgradeLink;

	/**
	 * Limits the number of forms that can be stored in the database. If left blank there are no limits.
	 */
	private static $maxForms = "";

	/**
	 * Limits the number of fields that can be stored for a form.
	 */
	private static $maxFormFields = "";


	// -------------------------------------------------------------------------------------------------

	// API settings.

	private static $apiRecaptchaSiteKey = "";
	private static $apiRecaptchaSecretKey = "";
	private static $apiRecaptchaLang = "";
	private static $apiDebug = false;

	/**
	 * The default sessions timeout for the API. Default is 1 hour (3600 seconds)
	 */
	private static $apiSessionsTimeout;

	/**
	 * This is used by the API::initFormPage() function when setting up the environment for the webpage;
	 * headers are sent with this charset.
	 */
	private static $apiHeaderCharset = "utf-8";

	/**
	 * Added in 3.0.1, the $g_use_smarty_bc setting in the /global/config.php file makes Form Tools use the
	 * SmartyBC (Backward-Compatibility) class rather than Smarty. Handy if you need {php} tags within your
	 * Smarty content.
	 * @var bool
	 */
	private static $useSmartyBC = false;

	// -------------------------------------------------------------------------------------------------

	// Internal settings. These can't be overridden.

	/**
	 * The database instance automatically instantiated by Core::init(). This allows any code to just
	 * reference Core::$db for any database interaction.
	 * @var Database
	 */
	public static $db;

	/**
	 * @var Smarty
	 */
	public static $smarty;

	/**
	 * The translations object. Used to get the current UI language and translation strings (Core::$translations->getList())
	 * @var Translations
	 */
	public static $translations;

	public static $L;
	private static $currLang;

	/**
	 * User-related settings.
	 * @var User
	 */
	public static $user;
	private static $userInitialized = false;

	/**
	 * The current version of the Form Tools Core.
	 */
	private static $version = "3.0.20";

	/**
	 * The release type: alpha, beta or main
	 */
	private static $releaseType = "main";

	/**
	 * The release date: YYYYMMDD
	 */
	private static $releaseDate = "20191019";

	/**
	 * The minimum required PHP version needed to run Form Tools.
	 */
	protected static $requiredPhpVersion = "5.3";

	/**
	 * Default values. These are use during installation when we have no idea what the user wants. For non-authenticated
	 * people visiting the login/forget password pages, they'll get whatever theme & lang has been configured in the
	 * database (I figure that's a bit more flexible putting it there than hardcoded in config file).
	 */
	private static $defaultTheme = "default";
	private static $defaultLang = "en_us";

	/**
	 * This determines the value used in the database to separate multiple field values (checkboxes and
	 * multi-select boxes) and image filenames (main image, main thumb, search results thumb). It's strongly
	 * recommended to leave this value alone.
	 */
	private static $multiFieldValDelimiter;

	/**
	 * Simple benchmarking code. When enabled, this outputs a page load time in the footer.
	 */
	private static $enableBenchmarking;
	private static $benchmarkStart = "";

	/**
	 * Tracks whether sessions have been started or not.
	 * @var bool
	 */
	private static $sessionsStarted = false;

	/**
	 * Added in 2.3.0 to prevent hooks being executed during in the installation process, prior to the database being
	 * ready. This was always an issue but the errors were swallowed up with earlier versions of PHP.
	 */
	private static $hooksEnabled = true;

	/**
	 * Added in 2.1.0 to provide better error checking on the login page. This is used to confirm that all the Core
	 * tables do in fact exist before letting the user log in.
	 */
	private static $coreTables = array(
		"account_settings",
		"accounts",
		"client_forms",
		"client_views",
		"email_template_edit_submission_views",
		"email_template_recipients",
		"email_template_when_sent_views",
		"email_templates",
		"field_options",
		"field_settings",
		"field_type_setting_options",
		"field_type_settings",
		"field_types",
		"field_type_validation_rules",
		"field_validation",
		"form_email_fields",
		"form_fields",
		"forms",
		"hook_calls",
		"hooks",
		"list_groups",
		"menu_items",
		"menus",
		"modules",
		"module_menu_items",
		"multi_page_form_urls",
		"new_view_submission_defaults",
		"option_lists",
		"public_form_omit_list",
		"public_view_omit_list",
		"sessions",
		"settings",
		"themes",
		"views",
		"view_columns",
		"view_fields",
		"view_filters",
		"view_tabs"
	);

	private static $upgradeUrl = "https://formtools.org/upgrade.php";

	/**
	 * Simple mechanism to provide a global storage hash for pages, added for performance reasons.
	 */
	public static $tempCache = array();

	/**
	 * Initializes the Core singleton for use throughout Form Tools.
	 *   - sets up PDO database connection available through Core::$db
	 *   - starts sessions
	 *   - if a user is logged in, instantiates the User object and makes it available via Core::$user
	 *   - The language is user-specific, but lang strings available as a convenience here: Core::$L
	 */
	public static function init($params = array())
	{
		$options = array_merge(array(
			"start_sessions" => true,
			"init_user" => true,

			// if the users session has expired, or if ?logout=1 is set in the query string, this logs the user out
			"auto_logout" => true
		), $params);

		self::loadConfigFile();

		// explicitly set the error reporting value
		error_reporting(self::$errorReporting);

		if (self::checkConfigFileExists()) {
			self::initDatabase();
		}

		self::initSmarty();
		if ($options["start_sessions"]) {
			self::startSessions();
		}

		if ($options["init_user"]) {
			self::initUser();
		}

		if (self::$debugEnabled) {
			self::enableDebugging();
		}

		// optionally enable benchmarking. Dev-only feature to confirm pages aren't taking too long to load
		if (self::$enableBenchmarking) {
			self::$benchmarkStart = General::getMicrotimeFloat();
		}

		// not thrilled with this, but it needs to be handled on all pages and this is a convenient spot
		if ($options["auto_logout"]) {
			if (Core::checkConfigFileExists() && isset($_GET["logout"])) {
				Core::$user->logout();
			}
			if (self::$user->isLoggedIn()) {
				General::checkSessionsTimeout();
			}
		}
	}

	public static function startSessions($context = "")
	{
		if (self::$sessionsStarted) {
			return;
		}
		if (self::$sessionType == "database") {
			new DatabaseSessions(self::$db, self::$sessionSavePath);
		}
		if (!empty(self::$sessionSavePath)) {
			session_save_path(self::$sessionSavePath);
		}

		// Form Tools uses utf-8 for all headers; if the user is using this method in an API page they can choose to
		// customize the header charset via $apiHeaderCharset
		$header_charset = "utf-8";
		if ($context == "api_form") {
			$header_charset = self::getAPIHeaderCharset();
		}

		session_start();
		header("Cache-control: private");
		header("Content-Type: text/html; charset=$header_charset");

		self::$sessionsStarted = true;
	}

	/**
	 * This can be called independently of anything else. It simply checks for the existence of the config file.
	 */
	public static function checkConfigFileExists()
	{
		return file_exists(self::getConfigFilePath());
	}

	public static function getConfigFilePath()
	{
		return realpath(__DIR__ . "/../config.php");
	}

	/**
	 * Loads the user's config file. If successful, it updates the various private member vars
	 * with whatever's been defined.
	 * @access private
	 */
	public static function loadConfigFile()
	{
		if (self::checkConfigFileExists()) {
			require(self::getConfigFilePath());
		}

		self::$rootURL = (isset($g_root_url)) ? $g_root_url : null;
		self::$rootDir = (isset($g_root_dir)) ? $g_root_dir : null;
		self::$dbHostname = (isset($g_db_hostname)) ? $g_db_hostname : null;
		self::$dbName = (isset($g_db_name)) ? $g_db_name : null;
		self::$dbPort = (isset($g_db_port)) ? $g_db_port : null;
		self::$dbUsername = (isset($g_db_username)) ? $g_db_username : null;
		self::$dbPassword = (isset($g_db_password)) ? $g_db_password : null;
		self::$dbTablePrefix = (isset($g_table_prefix)) ? $g_table_prefix : null;
		self::$unicode = (isset($g_unicode)) ? $g_unicode : null;
		self::$setSqlMode = (isset($g_set_sql_mode)) ? $g_set_sql_mode : null;
		self::$sqlStrictMode = (isset($g_sql_strict_mode)) ? $g_sql_strict_mode : "off";
		self::$hideUpgradeLink = (isset($g_hide_upgrade_link)) ? $g_hide_upgrade_link : false;

		if (isset($g_custom_cache_folder)) {
			self::$cacheFolder = $g_custom_cache_folder;
		} else {
			self::$cacheFolder = realpath(__DIR__ . "/../../cache/");
		}

		self::$enableBenchmarking = (isset($g_enable_benchmarking)) ? $g_enable_benchmarking : false;
		self::$jsDebugEnabled = isset($g_js_debug) ? $g_js_debug : false;
		self::$maxForms = isset($g_max_forms) ? $g_max_forms : "";
		self::$maxFormFields = isset($g_max_ft_form_fields) ? $g_max_ft_form_fields : "";
		self::$maxNavPages = isset($g_max_nav_pages) ? $g_max_nav_pages : 16;
		self::$searchFormDateFieldFormat = isset($g_search_form_date_field_format) ? $g_search_form_date_field_format : "d/m/y";
		self::$multiFieldValDelimiter = isset($g_multi_val_delimiter) ? $g_multi_val_delimiter : ", ";
		self::$queryStrMultiValSeparator = isset($g_query_str_multi_val_separator) ? $g_query_str_multi_val_separator : ",";
		self::$errorReporting = isset($g_default_error_reporting) ? $g_default_error_reporting : 1;
		self::$devMode = (isset($g_dev_mode)) ? $g_dev_mode : false;
		self::$debugEnabled = isset($g_debug) ? $g_debug : false;
		self::$sessionType = isset($g_session_type) && in_array($g_session_type, array("php", "database")) ? $g_session_type : "php";
		self::$sessionSavePath = isset($g_session_save_path) ? $g_session_save_path : "";
		self::$useSmartyBC = isset($g_use_smarty_bc) ? $g_use_smarty_bc : false;

		// API settings
		self::$apiDebug = isset($g_api_debug) ? $g_api_debug : false;
		self::$apiRecaptchaSiteKey = isset($g_api_recaptcha_site_key) ? $g_api_recaptcha_site_key : "";
		self::$apiRecaptchaSecretKey = isset($g_api_recaptcha_secret_key) ? $g_api_recaptcha_secret_key : "";
		self::$apiRecaptchaLang = isset($g_api_recaptcha_lang) ? $g_api_recaptcha_lang : "en";
		self::$apiHeaderCharset = isset($g_api_header_charset) ? $g_api_header_charset : "utf-8";
		self::$apiSessionsTimeout = isset($g_api_sessions_timeout) ? $g_api_sessions_timeout : 3600;
	}

	public static function initNoSessions()
	{
		self::init(array("start_sessions" => false));
	}

	public static function initNoLogout()
	{
		self::init(array("auto_logout" => false));
	}

	public static function initSmarty()
	{
		self::$smarty = (self::useSmartyBC()) ? new SmartyBC() : new Smarty();
	}

	/**
	 * Called automatically in Core::init(). This initializes a default database connection, accessible via Core::$db
	 */
	public static function initDatabase()
	{
		self::$db = new Database(self::$dbHostname, self::$dbName, self::$dbPort, self::$dbUsername, self::$dbPassword,
			self::$dbTablePrefix);
	}

	public static function getRootUrl()
	{
		return self::$rootURL;
	}

	public static function getRootDir()
	{
		return self::$rootDir;
	}

	public static function isValidPHPVersion()
	{
		return version_compare(phpversion(), self::$requiredPhpVersion, ">=");
	}

	public static function getCoreTables()
	{
		return self::$coreTables;
	}

	public static function getDbTablePrefix()
	{
		return self::$dbTablePrefix;
	}

	public static function getCoreVersion()
	{
		return self::$version;
	}

	public static function getVersionString()
	{
		$version = self::getCoreVersion();
		$release_type = self::getReleaseType();
		if ($release_type === "alpha" || $release_type == "beta") {
			$release_date = self::getReleaseDate();
			$version .= "-{$release_type}-{$release_date}";
		}
		return $version;
	}

	public static function getReleaseDate()
	{
		return self::$releaseDate;
	}

	public static function getReleaseType()
	{
		return self::$releaseType;
	}

	public static function getDbTableCharset()
	{
		return self::$dbTableCharset;
	}

	public static function enableDebugging()
	{
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
	}

	public static function isUnicode()
	{
		return self::$unicode;
	}

	public static function isDevMode()
	{
		return self::$devMode;
	}

	public static function getSqlStrictMode()
	{
		if (self::$setSqlMode) {
			return "off";
		}
		return self::$sqlStrictMode;
	}

	public static function getDefaultLang()
	{
		return self::$defaultLang;
	}

	public static function getDefaultTheme()
	{
		return self::$defaultTheme;
	}

	public static function setHooksEnabled($status)
	{
		self::$hooksEnabled = $status;
	}

	public static function areHooksEnabled()
	{
		return self::$hooksEnabled;
	}

	public static function setCurrentLang($lang)
	{
		self::$currLang = $lang;
		self::$translations = new Translations(self::$currLang);
		self::$L = self::$translations->getStrings();
	}

	public static function getCurrentLang()
	{
		return self::$currLang;
	}

	public static function getPasswordHistorySize()
	{
		return self::$passwordHistorySize;
	}

	public static function getDbName()
	{
		return self::$dbName;
	}

	public static function getRequiredPasswordSpecialChars()
	{
		return self::$requiredPasswordSpecialChars;
	}

	public static function isSmartyDebugEnabled()
	{
		return self::$smartyDebug;
	}

	public static function isJsDebugEnabled()
	{
		return self::$jsDebugEnabled;
	}

	public static function shouldUseSmartySubDirs()
	{
		return self::$smartyUseSubDirs;
	}

	public static function shouldHideUpgradeLink()
	{
		return self::$hideUpgradeLink;
	}

	public static function isBenchmarkingEnabled()
	{
		return self::$enableBenchmarking;
	}

	public static function getBenchmarkStart()
	{
		return self::$benchmarkStart;
	}

	public static function getMaxForms()
	{
		return self::$maxForms;
	}

	public static function getMaxFormFields()
	{
		return self::$maxFormFields;
	}

	public static function getMaxNavPages()
	{
		return self::$maxNavPages;
	}

	public static function getSearchFormDateFieldFormat()
	{
		return self::$searchFormDateFieldFormat;
	}

	public static function getMultiFieldValDelimiter()
	{
		return self::$multiFieldValDelimiter;
	}

	public static function isDebugEnabled()
	{
		return self::$debugEnabled;
	}

	public static function getDefaultErrorReporting()
	{
		return self::$errorReporting;
	}

	public static function getQueryStrMultiValSeparator()
	{
		return self::$queryStrMultiValSeparator;
	}

	public static function getSessionType()
	{
		return self::$sessionType;
	}

	public static function getSessionSavePath()
	{
		return self::$sessionSavePath;
	}

	public static function isUserInitialized()
	{
		return self::$userInitialized;
	}

	public static function getFilenameCharWhitelist()
	{
		return self::$filenameCharWhitelist;
	}

	public static function updateUser($language, $theme, $swatch)
	{
		self::$user->setLang($language);
		self::$user->setTheme($theme);
		self::$user->setSwatch($swatch);

		self::setCurrentLang($language);
	}


	// API-related

	public static function isAPIAvailable()
	{
		return is_file(Core::getAPIPath());
	}

	public static function getAPIPath()
	{
		$root_dir = Core::getRootDir();
		return "$root_dir/global/api/API.class.php";
	}

	/**
	 * The API debug setting is generally configured by adding a $g_api_debug=true var to your config.php file. But for
	 * API form integrations, users may prefer to do use it on a per-form basis. To do that, they can just add the
	 * same variable prior to instantiating the API/calling an old ft_api_* method.
	 * @return bool
	 */
	public static function isAPIDebugEnabled()
	{
		global $g_api_debug;
		return (isset($g_api_debug)) ? $g_api_debug : self::$apiDebug;
	}

	public static function getApiRecaptchaSiteKey()
	{
		return self::$apiRecaptchaSiteKey;
	}

	public static function getAPIRecaptchaSecretKey()
	{
		return self::$apiRecaptchaSecretKey;
	}

	public static function getAPIRecaptchaLang()
	{
		return self::$apiRecaptchaLang;
	}

	public static function getAPIHeaderCharset()
	{
		return self::$apiHeaderCharset;
	}

	public static function getAPISessionsTimeout()
	{
		return self::$apiSessionsTimeout;
	}

	public static function getUpgradeUrl()
	{
		return self::$upgradeUrl;
	}

	public static function useSmartyBC()
	{
		return self::$useSmartyBC;
	}

	public static function getTempCacheHash()
	{
		return self::$tempCache;
	}

	public static function setTempCacheHash($value)
	{
		self::$tempCache = $value;
	}

	/**
	 * N.B. this returns the *available* cache folder, not whatever is defined. It checks to see if the default
	 * cache folder exists and is writable, otherwise
	 */
	public static function getCacheFolder()
	{
		if (is_dir(self::$cacheFolder) && is_writable(self::$cacheFolder)) {
			return self::$cacheFolder;
		}

		return realpath("../../themes/default/cache");
	}

	// the 3.0.15 upgrade tries to create a cache/ folder in the Form Tools root and switch the installation over to use
	// that. In case of problems Form Tools downgrades to using the old default theme's cache folder. This method is
	// used to notify the admin to remedy the situation
	public static function hasInvalidCacheFolder()
	{
		return self::getCacheFolder() !== self::$cacheFolder;
	}


	// private methods

	private static function initUser()
	{
		self::$user = new User();
		self::$currLang = self::$user->getLang();
		self::setCurrentLang(self::$currLang);
		self::$userInitialized = true;
	}
}
