<?php

/**
 * Form Tools - generic form processing, storage and access script
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License included in this zipfile for more details.
 *
 * The Core class added in 2.3.0. This replaces the old /global/library.php file. It's a singleton that's
 * instantiated for all page loads and contains all the core functionality / objects / data etc. used
 * throughout the script. You continue to override the available settings in /global/config.php
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @version 2.3.x
 * @package 2-3-x
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Core {


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
     * The MySQL username. Note: this user account must have privileges for adding and deleting tables, and
     * adding and deleting records.
     */
    private static $dbUsername = "";

    /**
     * The MySQL password.
     */
    private static $dbPassword = "";

    /**
     * This option allows you make a secure connection to the database server using the MYSQL_CLIENT_SSL
     * flag.
     */
    private static $dbSSLEnabled = false;

    /**
     * This value lets you define a custom database prefix for your Form Tools tables. This is handy if
     * Form Tools will be added to an existing database and you want to avoid table naming conflicts.
     */
    private static $dbTablePrefix = "ft_";

    /**
     * This controls the maximum number of pagination links that appear in the Form Tools UI (e.g. for
     * viewing the submission listings page).
     */
    private static $maxNavPages = 16;

    /**
     * This offers support for unicode. All form submissions will be sent as UTF-8. This is enabled for all
     * new installations.
     */
    private static $unicode = true;

    /**
     * This setting should be enabled PRIOR to including this file in any external script (e.g. the API)
     * that doesn't require the person to be logged into Form Tools. This lets you leverage the Form Tools
     * functionality in the outside world without already being logged into Form Tools.
     */
    private static $checkFTSessions = true;
    //$g_check_ft_sessions = (isset($g_check_ft_sessions)) ? $g_check_ft_sessions : true;

    /**
     * This is set to 1 by default (genuine errors only). Crank it up to 2047 to list every
     * last error/warning/notice that occurs.
     */
    private static $defaultErrorReporting = 1;

    /**
     * Various debug settings. As of 2.3.0 these are of varying degrees of being supported.
     */
    private static $debug = true;
    private static $smartyDebug = false;
    private static $jsDebug = false;
    private static $apiDebug = true;

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
    private static $queryStrMultiValSeparator = ",";

    /**
     * For module developers. This prevents the code from automatically deleting your module folder when you're
     * testing your uninstallation function. Defaults to TRUE, but doesn't work on all systems: sometimes the PHP
     * doesn't have the permission to remove the folder.
     */
    private static $deleteModuleFolderOnUninstallation = true;

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
     * These two settings are for the ft_api_display_captcha() function. See the API documentation for more
     * information on how that works.
     */
    private static $apiRecaptchaPublicKey  = "";
    private static $apiRecaptchaPrivateKey = "";

    /**
     * This is used by the ft_api_init_form_page() function when setting up the environment for the webpage;
     * headers are sent with this charset.
     */
    private static $apiHeaderCharset = "utf-8";

    /**
     * Used for the database charset. For rare cases, the utf8 character set isn't available, so this allows
     * them to change it and install the script.
     */
    private static $dbTableCharset = "utf8";

    /**
     * The default sessions timeout for the API. Default is 1 hour (3600 seconds)
     */
    private static $apiSessionsTimeout = 3600;

    /**
     * Permissible characters in a filename. All other characters are stripped out. *** including a hyphen here
     * leads to problems. ***
     */
    private static $filenameCharWhitelist = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_0123456789";

    /**
     * Special chars, required in password (optional setting through interface).
     */
    private static $passwordSpecialChars = "~!@#$%^&";

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
    private static $searchFormDateFieldFormat = "d/m/y";

    /**
     * Added in 2.1.0 and enabled by default. This overrides the default SQL mode for any query, to prevent
     * problems that may arise due to MySQL strict mode being on.
     */
    private static $setSqlMode = true;

    /**
     * This hides the upgrade link in the administrator's UI.
     */
    private static $hideUpgradeLink = false;

    /**
     * Limits the number of forms that can be stored in the database.
     */
    private static $maxForms = "";

    /**
     * Limits the number of fields that can be stored for a form.
     */
    private static $maxFormFields = "";


    // -------------------------------------------------------------------------------------------------


    // SECTION 2: internal settings. These can't be overridden.

    /**
     * The database instance automatically instantiated by Core::init(). This allows any code to just
     * reference Core::$db for any database interaction.
     */
    public static $db;

    /**
     * Tracks whether the user's configuration file exists.
     */
    private static $configFileExists = false;

    /**
     * The current version of the Form Tools Core.
     */
    private static $version = "2.3.0-dev";

    /**
     * The release type: alpha, beta or main
     */
    private static $releaseType = "main";

    /**
     * The release date: YYYYMMDD
     */
    private static $releaseDate = "20170225";

    /**
     * The minimum required PHP version needed to run Form Tools.
     */
    private static $requiredPhpVersion = "5.3";

    /**
     * The minimum required MySQL version needed to run Form Tools.
     */
    private static $requiredMysqlVersion = "4.1.2";

    /**
     * This is an if-all-else-fails value. It should NEVER be changed.
     */
    private static $defaultTheme = "default";

    /**
     * This determines the value used in the database to separate multiple field values (checkboxes and
     * multi-select boxes) and image filenames (main image, main thumb, search results thumb). It's strongly
     * recommended to leave this value alone.
     */
    private static $multiValDelimiter = ", ";

    /**
     * Used throughout the script to store any and all temporary error / notification messages. Don't change
     * or remove - defining them here prevents unwanted PHP notices.
     */
    private static $g_success = "";
    private static $g_message = "";

    /**
     * Simple benchmarking code. When enabled, this outputs a page load time in the footer.
     */
    private static $enableBenchmarking = false;
    private static $benchmarkStart     = "";

    /**
     * Used for caching data sets during large, repeat operations.
     */
    private static $cache = array();


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

    /**
     * Initializes the Core singleton for use throughout Form Tools.
     */
    public static function init() {
        self::loadConfigFile();

        // explicitly set the error reporting value
        //error_reporting($g_default_error_reporting);

//        if ($g_enable_benchmarking) {
//            $g_benchmark_start = ft_get_microtime_float();
//        }

    }

    /**
     * @access public
     */
    public static function checkConfigFileExists() {
        return self::$configFileExists;
    }


    /**
     * Loads the user's config file. If successful, it updates the various private member vars
     * with whatever's been defined.
     * @access private
     */
    private static function loadConfigFile() {
        $configFilePath = realpath(__DIR__ . "/../config.php");
        if (!file_exists($configFilePath)) {
            return;
        }
        require_once($configFilePath);

//        self::$configFileExists = true;
//        require_once($settingsFilePath);
//
//        self::$rootURL    = (isset($rootURL)) ? $dbHostname : null;
//        self::$dbHostname = (isset($dbHostname)) ? $dbHostname : null;
//        self::$dbName     = (isset($dbName)) ? $dbName : null;
//        self::$dbUsername = (isset($dbUsername)) ? $dbUsername : null;
//        self::$dbPassword = (isset($dbPassword)) ? $dbPassword : null;
//        self::$dbTablePrefix = (isset($dbTablePrefix)) ? $dbTablePrefix : null;
    }


}
