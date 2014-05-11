<?php

/**
 * Form Tools - generic form processing, storage and access script
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License included in this zipfile for more details.
 *
 * This file defines all global variables for Form Tools and imports all functions used throughout
 * the script. DO NOT CHANGE ANY OF THESE VALUES. As of version 1.4.6, all custom settings
 * for your installation of Form Tools should be stored in a separate config.php file which is created
 * by the installation script, and stored in the same folder as this one. The config.php is NEVER
 * overwritten, which makes upgrading a simple matter of overwriting all old files from the latest
 * zipfile.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @version 2.2.x
 * @package 2-2-x
 */


// -------------------------------------------------------------------------------------------------


// SECTION 1: settings you can override in your config.php file

/**
 * This is the base URL of the Form Tools installation on your server. e.g.
 * http://www.yoursite.com/formtools. You can either supply an absolute or relative URL. Note: if
 * you include the full URL, make sure that the "www." part is either included or removed
 * consistently; if you try to log in at http://www.yoursite.com/admin but your $g_root_url is set to
 * http://yoursite.com/admin it will not work! (and vice versa).
 * @global string $g_root_url
 */
$g_root_url = "";

/**
 * The server directory path to your Form Tools folder.
 * @global string $g_root_dir
 */
$g_root_dir = "";

/**
 * The database hostname (most often 'localhost').
 * @global string $g_db_hostname
 */
$g_db_hostname = "";

/**
 * The name of the database. Most often, hosting providers provide you with some sort of user
 * interface for creating databases and assigning user accounts to them. If you are creating a new
 * database for Form Tools, I would recommend "formtools" for clarity.
 * @global string $g_db_name
 */
$g_db_name = "";

/**
 * The MySQL username. Note: this user account must have privileges for adding and deleting tables, and
 * adding and deleting records.
 * @global string $g_db_username
 */
$g_db_username = "";

/**
 * The MySQL password.
 * @global string $g_db_password
 */
$g_db_password = "";

/**
 * This option allows you make a secure connection to the database server using the MYSQL_CLIENT_SSL
 * flag.
 */
$g_db_ssl = false;

/**
 * This value lets you define a custom database prefix for your Form Tools tables. This is handy if
 * Form Tools will be added to an existing database and you want to avoid table naming conflicts.
 * @global string $g_table_prefix
 */
$g_table_prefix = "ft_";

/**
 * This controls the maximum number of pagination links that appear in the Form Tools UI (e.g. for
 * viewing the submission listings page).
 * @global string $g_max_nav_pages
 */
$g_max_nav_pages = 16;

/**
 * This offers support for unicode. All form submissions will be sent as UTF-8. This is enabled for all
 * new installations.
 * @global string $g_unicode
 */
$g_unicode = true;

/**
 * This setting should be enabled PRIOR to including this file in any external script (e.g. the API)
 * that doesn't require the person to be logged into Form Tools. This lets you leverage the Form Tools
 * functionality in the outside world without already being logged into Form Tools.
 */
$g_check_ft_sessions = (isset($g_check_ft_sessions)) ? $g_check_ft_sessions : true;

/**
 * This is set to 1 by default (genuine errors only). Crank it up to 2047 to list every
 * last error/warning/notice that occurs.
 * @global string $g_default_error_reporting
 */
$g_default_error_reporting = 1;

/**
 * This feature currently has limited support in the code, but will be implemented more fully at a
 * later stage. When set to true it provides detailed, technical reasons for errors that occur.
 * @global string $g_debug
 */
$g_debug = true;
$g_smarty_debug = false;
$g_js_debug = false;

/**
 * This tells Smarty to create the compiled templates in subdirectories, which is slightly more efficient.
 * Not compatible on some systems, so it's set to false by default.
 */
$g_smarty_use_sub_dirs = false;

/**
 * This determines the value used to separate the content of array form submissions (e.g. checkboxes
 * in your form that have the same name, or multi-select dropdowns) when submitted via a query
 * string for "direct" form submissions (added in version 1.4.2).
 * @global string $g_query_str_multi_val_separator
 */
$g_query_str_multi_val_separator = ",";

/**
 * For module developers. This prevents the code from automatically deleting your module folder when you're
 * testing your uninstallation function. Defaults to TRUE, but doesn't work on all systems: sometimes the PHP
 * doesn't have the permission to remove the folder.
 */
$g_delete_module_folder_on_uninstallation = true;

/**
 * This setting lets you control the type of sessions the application uses. The default value is "database",
 * but you can change it to "php" if you'd prefer to use PHP sessions. This applies to all users of the program.
 * @global string $g_session_type
 */
$g_session_type = "php"; // "php" or "database"

/**
 * This lets you specify the session save path, used by PHP sessions. By default this isn't set, relying
 * on the default value. But on some systems this value needs to be set.
 */
$g_session_save_path = "";

/**
 * This enables debugging for the API functions. Generally this just causes the database errors and other
 * messages to be outputted along with the problem error code. Enabled by default.
 */
$g_api_debug = true;

/**
 * These two settings are for the ft_api_display_captcha() function. See the API documentation for more
 * information on how that works.
 */
$g_api_recaptcha_public_key  = "";
$g_api_recaptcha_private_key = "";

/**
 * This is used by the ft_api_init_form_page() function when setting up the environment for the webpage;
 * headers are sent with this charset.
 */
$g_api_header_charset = "utf-8";

/**
 * Used for the database charset. For rare cases, the utf8 character set isn't available, so this allows
 * them to change it and install the script.
 */
$g_db_table_charset = "utf8";

/**
 * The default sessions timeout for the API. Default is 1 hour (3600 seconds)
 */
$g_api_sessions_timeout = 3600;

/**
 * Permissible characters in a filename. All other characters are stripped out. *** including a hyphen here
 * leads to problems. ***
 */
$g_filename_char_whitelist = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_0123456789";

/**
 * Special chars, required in password (optional setting through interface).
 */
$g_password_special_chars = "~!@#$%^&";

/**
 * The size of the password_history setting in the settings table. Form Tools keeps track of the last 10
 * passwords, to (optionally) prevent users from re-using a password that they used before.
 */
$g_password_history_size = 10;

/**
 * Determines the format of the date range string when searching a date field. Note: this only accepts two
 * values: d/m/y or m/d/y. This is because this value is used by both the daterangepicker element and
 * on the server. I don't want to fuss around with too many formats; it's confusing enough!
 */
$g_search_form_date_field_format = "d/m/y";

/**
 * Added in 2.1.0 and enabled by default. This overrides the default SQL mode for any query, to prevent
 * problems that may arise due to MySQL strict mode being on.
 */
$g_set_sql_mode = true;

/**
 * This hides the upgrade link in the administrator's UI.
 */
$g_hide_upgrade_link = false;

/**
 * Limits the number of forms that can be stored in the database.
 */
$g_max_ft_forms = "";

/**
 * Limits the number of fields that can be stored for a form.
 */
$g_max_ft_form_fields = "";

// -------------------------------------------------------------------------------------------------


// SECTION 2: the settings below should NOT be overwritten

/**
 * The current version of the Form Tools Core.
 */
$g_current_version = "2.2.5";

/**
 * The release type: alpha, beta or main
 */
$g_release_type = "main";

/**
 * The release date: YYYYMMDD
 */
$g_release_date = "20120503";

/**
 * The minimum required PHP version needed to run Form Tools.
 */
$g_required_php_version = "4.3";

/**
 * The minimum required MySQL version needed to run Form Tools.
 */
$g_required_mysql_version = "4.1.2";

/**
 * This is an if-all-else-fails value. It should NEVER be changed.
 * @global string $g_default_theme
 */
$g_default_theme = "default";

/**
 * This determines the value used in the database to separate multiple field values (checkboxes and
 * multi-select boxes) and image filenames (main image, main thumb, search results thumb). It's strongly
 * recommended that unless you have a programmatical reason, you should leave it to the default comma-space
 * value.
 * @global string $g_multi_val_delimiter
 */
$g_multi_val_delimiter = ", ";

/**
 * Used throughout the script to store any and all temporary error / notification messages. Don't change
 * or remove - defining them here prevents unwanted PHP notices.
 */
$g_success = "";
$g_message = "";

/**
 * Simple benchmarking code. When enabled, this outputs a page load time in the footer.
 */
$g_enable_benchmarking = false;
$g_benchmark_start     = "";

/**
 * Used for caching data sets during large, repeat operations.
 */
$g_cache = array();

/**
 * Added in 2.1.0 to provide better error checking on the login page. This is used to confirm that all the Core
 * tables do in fact exist before letting the user log in.
 */
$g_ft_tables = array(
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

// include all code libraries
$config_file_exists = false;
if (is_file(dirname(__FILE__) . "/config.php"))
{
  $config_file_exists = true;
  include_once(dirname(__FILE__) . "/config.php");
}

// explicitly set the error reporting value
error_reporting($g_default_error_reporting);

require_once(dirname(__FILE__) . "/code/administrator.php");
require_once(dirname(__FILE__) . "/code/accounts.php");
require_once(dirname(__FILE__) . "/code/clients.php");
require_once(dirname(__FILE__) . "/code/emails.php");
require_once(dirname(__FILE__) . "/code/fields.php");
require_once(dirname(__FILE__) . "/code/field_sizes.php");
require_once(dirname(__FILE__) . "/code/field_types.php");
require_once(dirname(__FILE__) . "/code/field_validation.php");
require_once(dirname(__FILE__) . "/code/files.php");
require_once(dirname(__FILE__) . "/code/forms.php");
require_once(dirname(__FILE__) . "/code/general.php");
require_once(dirname(__FILE__) . "/code/hooks.php");
require_once(dirname(__FILE__) . "/code/languages.php");
require_once(dirname(__FILE__) . "/code/list_groups.php");
require_once(dirname(__FILE__) . "/code/menus.php");
require_once(dirname(__FILE__) . "/code/modules.php");
require_once(dirname(__FILE__) . "/code/option_lists.php");
require_once(dirname(__FILE__) . "/code/sessions.php");
require_once(dirname(__FILE__) . "/code/settings.php");
require_once(dirname(__FILE__) . "/code/submissions.php");
require_once(dirname(__FILE__) . "/code/themes.php");
require_once(dirname(__FILE__) . "/code/upgrade.php");
require_once(dirname(__FILE__) . "/code/validation.php");
require_once(dirname(__FILE__) . "/code/views.php");
require_once(dirname(__FILE__) . "/smarty/Smarty.class.php");


if ($config_file_exists && (!isset($g_defer_init_page) || !$g_defer_init_page))
{
  $g_link = ft_db_connect();

  // our Smarty instance, used for rendering the webpages
  $g_smarty = new Smarty();

  // load the appropriate language file
  $g_language = ft_get_ui_language();
  require_once(dirname(__FILE__) . "/lang/{$g_language}.php");

  if (isset($_GET["logout"]))
    ft_logout_user();
}

if ($g_enable_benchmarking)
{
  $g_benchmark_start = ft_get_microtime_float();
}
