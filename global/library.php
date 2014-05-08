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
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @version 2.0.0
 * @package 2-0-0
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
 * database for Form Tools, I would recommend "form_tools" for clarity. Enter the database name
 * here.
 * @global string $g_db_name
 */
$g_db_name = "";

/**
 * The MySQL username. Note: this user account must have privileges for creating new tables, in
 * addition to adding and removing records.
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
 * last error/warning/notice that occurs. For Beta versions, it's set to 2047.
 * @global string $g_default_error_reporting
 */
$g_default_error_reporting = 2047;

/**
 * This feature currently has limited support in the code, but will be implemented more fully at a
 * later stage. When set to true it provides detailed, technical reasons for errors that occur.
 * @global string $g_debug
 */
$g_debug = true;
$g_smarty_debug = false;

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
 * The default sessions timeout for the API. Default is 1 hour (3600 seconds)
 */
$g_api_sessions_timeout = 3600;


// -------------------------------------------------------------------------------------------------

// SECTION 2: the settings below should NOT be overwritten

/**
 * The current version of the Form Tools Core.
 */
$g_current_version = "2.0.0-beta-20091213";

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


// include all code libraries
$folder = dirname(__FILE__);
$config_file_exists = false;
if (is_file("$folder/config.php"))
{
  $config_file_exists = true;
  include_once("$folder/config.php");
}


// explicitly set the error reporting value
error_reporting($g_default_error_reporting);

require_once("$folder/code/administrator.php");
require_once("$folder/code/accounts.php");
require_once("$folder/code/clients.php");
require_once("$folder/code/emails.php");
require_once("$folder/code/fields.php");
require_once("$folder/code/field_option_groups.php");
require_once("$folder/code/files.php");
require_once("$folder/code/forms.php");
require_once("$folder/code/general.php");
require_once("$folder/code/hooks.php");
require_once("$folder/code/languages.php");
require_once("$folder/code/menus.php");
require_once("$folder/code/modules.php");
require_once("$folder/code/sessions.php");
require_once("$folder/code/settings.php");
//require_once("$folder/code/stabilizer.php");
require_once("$folder/code/submissions.php");
require_once("$folder/code/themes.php");
require_once("$folder/code/upgrade.php");
require_once("$folder/code/validation.php");
require_once("$folder/code/views.php");
require_once("$folder/smarty/Smarty.class.php");

if ($config_file_exists && (!isset($g_defer_init_page) || !$g_defer_init_page))
{
  // our Smarty instance, used for rendering the webpages
  $g_smarty = new Smarty();

  // load the appropriate language file
  $g_language = ft_get_ui_language();
  require_once("$folder/lang/{$g_language}.php");

  // if the config file exists, we can assume the user isn't installed
  $g_link = ft_db_connect();

  if (isset($_GET["logout"]))
    ft_logout_user();
}