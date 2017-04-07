<?php

/**
 * General methods. Added in 2.3.0 - will replace the older genera.php file.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-3-x
 * @subpackage Database
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO;


class General
{
    /**
     * Helper function that's used on Step 2 to confirm that the Core Field Types module folder exists.
     *
     * @param string $module_folder
     */
    public static function checkModuleAvailable($module_folder)
    {
        return is_dir(realpath(__DIR__ . "/../../modules/$module_folder"));
    }


    /**
     * Gets a list of known Form Tools tables in a database.
     * @return array
     */
    public static function getExistingTables(Database $db, array $all_tables, $table_prefix)
    {
        $db->query("SHOW TABLES");

        $prefixed_tables = array();
        foreach ($all_tables as $table_name) {
            $prefixed_tables[] = $table_prefix . $table_name;
        }

        $existing_tables = array();
        foreach ($db->fetchAll(PDO::FETCH_NUM) as $row) {
            $curr_table = $row[0];
            if (in_array($curr_table, $prefixed_tables)) {
                $existing_tables[] = $curr_table;
            }
        }

        return $existing_tables;
    }


    /**
     * Helper method to convert an array to rows of HTML in bullet points.
     * @return array
     */
    public static function getErrorListHTML(array $errors) {
        array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
        return join("<br />", $errors);
    }

    /**
     * Returns a date in Y-m-d H:i:s format, generally used for inserting into a MySQL
     * datetime field.
     *
     * @param string $timestamp an optional Unix timestamp to convert to a datetime
     * @return string the current datetime in string format
     * */
    public static function getCurrentDatetime($timestamp = "")
    {
        if (!empty($timestamp)) {
            $datetime = date("Y-m-d H:i:s", $timestamp);
        } else {
            $datetime = date("Y-m-d H:i:s");
        }
        return $datetime;
    }


    /**
     * Checks to see if a database table exists. Handy for modules to check to see if they've been installed
     * or not.
     *
     * @return boolean
     */
    public static function checkDbTableExists($table)
    {
        $db = Core::$db;
        $db_name = Core::getDbName();

        $found = false;
        $db->query("SHOW TABLES FROM :db_name");
        $db->bind(":db_name", $db_name);
        $db->execute();
        foreach ($db->fetchAll() as $row) {
            if ($row[0] == $table) {
                $found = true;
                break;
            }
        }
        return $found;
    }


    /**
     * Helper function to convert a MySQL datetime to a unix timestamp.
     *
     * @param string $datetime
     * @return string
     */
    public static function convertDatetimeToTimestamp($datetime)
    {
        list($date, $time) = explode(" ", $datetime);
        list($year, $month, $day) = explode("-", $date);
        list($hours, $minutes, $seconds) = explode(":", $time);

        return mktime($hours, $minutes, $seconds, $month, $day, $year);
    }


    /**
     * This is used for major errors, especially when no database connection can be made. All it does is output
     * the error string with no other dependencies - not even language strings. This is always output in English.
     *
     * @param string $error
     */
    public static function displaySeriousError($error) {
        echo <<< END
<!DOCTYPE>
<html>
<head>
  <title>Error</title>
  <style type="text/css">
  h1 {
    margin: 0px 0px 16px 0px;
  }
  body {
    background-color: #f9f9f9;
    text-align: center;
    font-family: verdana;
    font-size: 11pt;
    line-height: 22px;
  }
  div {
    -webkit-border-radius: 20px;
    -moz-border-radius: 20px;
    border-radius: 20px;
    border: 1px solid #666666;
    padding: 40px;
    background-color: white;
    width: 600px;
    text-align: left;
    margin: 30px auto;
    word-wrap: break-word;
  }
  </style>
</head>
<body>
<div class="error">
  <h1>Uh-oh.</h1>
  {$error}
</div>
</body>
</html>
END;
    }

    /**
     * Helps manage long strings by adding either an ellipsis or inserts a inserts a <br /> at the position specified,
     * and returns the result.
     *
     * @param string $str The string to manipulate.
     * @param string $length The max length of the string / place to insert <br />
     * @param string $flag "ellipsis" / "page_break"
     * @return string The modified string.
     */
    public static function trimString($str, $length, $flag = "ellipsis")
    {
        if (mb_strlen($str) < $length) {
            $new_string = $str;
        } else {
            if ($flag == "ellipsis") {
                $new_string = mb_substr($str, 0, $length) . "...";
            } else {
                $parts = mb_str_split($str, $length);
                $new_string = join("<br />", $parts);
            }
        }

        return $new_string;
    }


    /**
     * Checks that the currently logged in client is permitted to view a particular form View. This is called
     * on the form submissions and edit submission pages, to ensure the client isn't trying to look at something
     * they shouldn't. Any time it fails, it logs them out with a message informing them that they're not allowed
     * to access that page. (FYI, it's possible that this scenario could happen honestly: e.g. if the administrator
     * creates a client menu containing links to particular forms; then accidentally assigning a client to the menu
     * that doesn't have permission to view the form).
     *
     * This relies on the $_SESSION["ft"]["permissions"] key being set by the login function: it contains the form
     * and View IDs that this.
     *
     * Because of this, any time the administrator changes the permissions for a client, they'll need te re-login to
     * access that new information.
     *
     * Very daft this function doesn't return a boolean, but oh well. The fourth param was added to get around that.
     *
     * @param integer $form_id The unique form ID
     * @param integer $client_id The unique client ID
     * @param integer $view_id
     * @param boolean
     */
    public static function checkClientMayView($client_id, $form_id, $view_id, $return_boolean = false)
    {
        $permissions = isset($_SESSION["ft"]["permissions"]) ? $_SESSION["ft"]["permissions"] : array();

        extract(Hooks::processHookCalls("main", compact("client_id", "form_id", "view_id", "permissions"), array("permissions")), EXTR_OVERWRITE);

        $may_view = true;
        if (!array_key_exists($form_id, $permissions)) {
            $may_view = false;
            if (!$return_boolean) {
                Core::$user->logout("notify_invalid_permissions");
            }
        } else {
            if (!empty($view_id) && !in_array($view_id, $permissions[$form_id])) {
                $may_view = false;
                if (!$return_boolean) {
                    Core::$user->logout("notify_invalid_permissions");
                }
            }
        }

        return $may_view;
    }


    /**
     * This invaluable little function is used for storing and overwriting the contents of a single
     * form field in sessions based on a sequence of priorities.
     *
     * It assumes that a variable name can be found in GET, POST or SESSIONS (or all three). What this
     * function does is return the value stored in the most important variable (GET first, POST second,
     * SESSIONS third), and update sessions at the same time. This is extremely helpful in situations
     * where you don't want to keep having to submit the same information from page to page.
     * The third parameter sets a default value.
     *
     * @param string $field_name the field name
     * @param string $session_name the session key for this field name
     * @param string $default_value the default value for the field
     * @return string the field value
     */
    public static function loadField($field_name, $session_name, $default_value = "")
    {
        $field = $default_value;

        if (isset($_GET[$field_name])) {
            $field = $_GET[$field_name];
            Sessions::set($session_name, $field);
        } else if (isset($_POST[$field_name])) {
            $field = $_POST[$field_name];
            Sessions::set($session_name, $field);
        } else if (Sessions::exists($session_name)) {
            $field = Sessions::get($session_name);
        }

        return $field;
    }


    /**
     * Used to convert language file strings into their JS-compatible counterparts, all within an
     * "g" namespace.
     *
     * @param array keys The $LANG keys
     * @param array keys The $L keys
     * @return string $js the javascript string (WITHOUT the <script> tags)
     */
    public static function generateJsMessages($keys = array(), $module_keys = array())
    {
        $LANG = Core::$L;
        $theme = Core::$user->getTheme();

        $js_rows = array();
        if (!empty($keys)) {
            for ($i=0; $i<count($keys); $i++) {
                $key = $keys[$i];
                if (array_key_exists($key, $LANG)) {
                    $str = preg_replace("/\"/", "\\\"", $LANG[$key]);
                    $js_rows[] = "g.messages[\"$key\"] = \"$str\";";
                }
            }
        }

        if (!empty($module_keys)) {
            for ($i=0; $i<count($module_keys); $i++) {
                $key = $module_keys[$i];
                if (array_key_exists($key, $L)) {
                    $str = preg_replace("/\"/", "\\\"", $L[$key]);
                    $js_rows[] = "g.messages[\"$key\"] = \"$str\";";
                }
            }
        }
        $rows = join("\n", $js_rows);

        $js =<<< END
if (typeof g == "undefined") {
  g = {};
}
g.theme_folder = "$theme";
g.messages     = [];
$rows
END;

        extract(Hooks::processHookCalls("end", compact("js"), array("js")), EXTR_OVERWRITE);

        return $js;
    }


    /**
     * Added in 2.1.0. The idea behind this is that every now and then, we need to display a custom message
     * in a page - e.g. after redirecting somewhere, or some unusual case. These situations are handled by passing
     * a ?message=XXX query string parameter. This function is called in the ft_display_page function directly
     * so it all happens "automatically" with no additional configuration needed on each page.
     *
     * Caveats:
     * - it will override $g_success and $g_message to always output it in the page. This is good! But keep it in mind.
     * - the messages should be very simple and not contain relative links. Bear in mind the user can hack it and paste
     *   those flags onto any page.
     *
     * @param $flag
     */
    public static function displayCustomPageMessage($flag)
    {
        global $LANG;

        $g_success = "";
        $g_message = "";
        switch ($flag) {
            case "no_views":
                $g_success = false;
                $g_message = $LANG["notify_no_views"];
                break;
            case "notify_internal_form_created":
                $g_success = true;
                $g_message = $LANG["notify_internal_form_created"];
                break;
            case "change_temp_password":
                $g_success = true;
                $g_message = $LANG["notify_change_temp_password"];
                break;
            case "new_submission":
                $g_success = true;
                $g_message = $LANG["notify_new_submission_created"];
                break;
            case "notify_sessions_timeout":
                $g_success = true;
                $g_message = $LANG["notify_sessions_timeout"];
                break;
        }

        extract(Hooks::processHookCalls("end", compact("flag"), array("g_success", "g_message")), EXTR_OVERWRITE);

        return array($g_success, $g_message);
    }


    /**
     * This function evaluates any string that contains Smarty content. It parses the email templates, filename
     * strings and other such functionality. It uses on the eval.tpl template, found in /global/smarty.
     *
     * @param string $placeholder_str the string containing the placeholders / Smarty logic
     * @param array $placeholders a hash of values to pass to the template. The contents of the
     *    current language file is ALWAYS sent.
     * @param string $theme
     * @return string a string containing the output of the eval()'d smarty template
     */
    public static function evalSmartyString($placeholder_str, $placeholders = array(), $theme = "", $plugin_dirs = array())
    {
        $LANG = Core::$L;
        $rootDir = Core::getRootDir();

        $theme = Core::$user->getTheme();
        $smarty = new \Smarty();
        $smarty->template_dir = "$rootDir/global/smarty/";
        $smarty->compile_dir  = "$rootDir/themes/$theme/cache/";

        foreach ($plugin_dirs as $dir) {
            $smarty->plugins_dir[] = $dir;
        }

        $smarty->assign("eval_str", $placeholder_str);
        if (!empty($placeholders)) {
            while (list($key, $value) = each($placeholders)) {
                $smarty->assign($key, $value);
            }
        }
        $smarty->assign("LANG", $LANG);

        $output = $smarty->fetch(realpath(__DIR__ . "/../smarty_plugins/eval.tpl"));

        extract(Hooks::processHookCalls("end", compact("output", "placeholder_str", "placeholders", "theme"), array("output")), EXTR_OVERWRITE);

        return $output;
    }


    /**
     * Helper function to remove all but those chars specified in the section param.
     *
     * @param string string to examine
     * @param string string of acceptable chars
     * @return string the cleaned string
     */
    public static function stripChars($str, $whitelist = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789")
    {
	    $valid_chars = preg_quote($whitelist);
	    return preg_replace("/[^$valid_chars]/", "", $str);
    }


    /**
     * Another security-related function. This returns a clean version of PHP_SELF for use in the templates. This wards
     * against URI Cross-site scripting attacks.
     *
     * @return string the cleaned $_SERVER["PHP_SELF"]
     */
    public static function getCleanPhpSelf()
    {
        return htmlspecialchars(strip_tags($_SERVER['PHP_SELF']), ENT_QUOTES);
    }


    /**
     * Currently used for encoding passwords. This will be updated in future versions for proper encryption.
     * @param $password
     */
    public static function encode($string) {
        return md5(md5($string));
    }


    /**
     * This updates the version of the API in the database. It's called on installation and whenever someone logs in.
     * It's used to keep the API version up to date in the database so that whenever the user clicks their UPGRADE
     * button, the correct API version is passed to the upgrade script to let it know if it needs to be upgraded or
     * not.
     */
    public static function updateApiVersion()
    {
        $root_dir = Core::getRootDir();

        $api_file = "$root_dir/global/api/api.php";
        if (is_file($api_file)) {
            include_once($api_file);

            if (!isset($g_api_version) || empty($g_api_version)) {
                return;
            }
            Settings::set(array("api_version" => $g_api_version));
        }
    }


    /**
     * Helper function to construct a valid URL. This will probably be improved and renamed in future.
     *
     * @param string $base_url
     * @param string $query_string
     */
    public static function constructUrl($url, $query_str = "")
    {
        $valid_url = $url;
        if (!empty($query_str)) {
            // only include the ? if it's not already there
            if (strpos($url, "?")) {
                $valid_url .= "&{$query_str}";
            } else {
                $valid_url .= "?{$query_str}";
            }
        }
        return $valid_url;
    }


    /**
     * Displays basic << 1 2 3 >> navigation for lists, each linking to the current page.
     *
     * This function has exactly the same purpose as display_page_nav, except that the pages are
     * hidden/shown with DHTML instead of separate server-side calls per page. This technique is better
     * for lists that contain a smaller number of items, e.g. the client and forms listing pages.
     *
     * ASSUMPTION: the JS counterpart function with the same function is defined in the calling page.
     * That function does all the work of hiding/showing pages, updating the "viewing X-Y"
     * text, enabling disabling the << and >> arrows, and storing the current page in sessions. This
     * function merely sets up the base HTML + JS.
     *
     * This function uses a dhtml_pagination.tpl Smarty template file, found in the current theme's root
     * folder.
     *
     * @param integer $num_results The total number of results found.
     * @param integer $num_per_page The max number of results to list per page.
     * @param integer $current_page The current page number being examined (defaults to 1).
     */
    public static function getJsPageNav($num_results, $num_per_page, $current_page = 1)
    {
        $theme = Core::$user->getTheme();
        $root_dir = Core::getRootDir();
        $root_url = Core::getRootUrl();
        $LANG = Core::$L;

        $smarty = Core::$smarty;
        $smarty->setTemplateDir("$root_dir/themes/$theme");
        $smarty->setCompileDir("$root_dir/themes/$theme/cache/");
        $smarty->setUseSubDirs(Core::shouldUseSmartySubDirs());
        $smarty->assign("LANG", $LANG);
        //$smarty->assign("SESSION", $_SESSION["ft"]);
        $smarty->assign("g_root_dir", $root_dir);
        $smarty->assign("g_root_url", $root_url);
        $smarty->assign("samepage", General::getCleanPhpSelf());
        $smarty->assign("num_results", $num_results);
        $smarty->assign("num_per_page", $num_per_page);
        $smarty->assign("current_page", $current_page);

        // find the range that's being displayed (e.g 11 to 20)
        $range_start = ($current_page - 1) * $num_per_page + 1;
        $range_end   = $range_start + $num_per_page - 1;
        $range_end   = ($range_end > $num_results) ? $num_results : $range_end;

        $smarty->assign("range_start", $range_start);
        $smarty->assign("range_end", $range_end);

        $viewing_range = "";
        if ($num_results > $num_per_page) {
            $replacement_info = array(
                "startnum" => "<span id='nav_viewing_num_start'>$range_start</span>",
                "endnum"   => "<span id='nav_viewing_num_end'>$range_end</span>"
            );
            $viewing_range = General::evalSmartyString($LANG["phrase_viewing_range"], $replacement_info);
        }
        $smarty->assign("viewing_range", $viewing_range);
        $smarty->assign("total_pages", ceil($num_results / $num_per_page));

        // now process the template and return the HTML
        return $smarty->fetch(Themes::getSmartyTemplateWithFallback($theme, "dhtml_pagination.tpl"));
    }


    /**
     * Displays basic &lt;&lt; 1 2 3 >> navigation for lists, each linking to the current page.
     *
     * This uses the pagination.tpl template, found in the theme's root folder.
     *
     * *** This function kind of sucks now... I just kept adding params and over time it's become totally daft. This
     * should be refactored to do a JS-like extend() option on the various permitted settings ***
     *
     * @param integer $num_results The total number of results found.
     * @param integer $num_per_page The max number of results to list per page.
     * @param integer $current_page The current page number being examined (defaults to 1).
     * @param string $pass_along_str The string to include in nav links.
     * @param string $page_str The string used in building the page nav to indicate the page number
     * @param string $theme the theme name
     * @param array $settings a hash with the following settings:
     *                   "show_total_results" => true/false (default: true)
     *                   "show_page_label"    => true/false (default: true)
     */
    public static function getPageNav($num_results, $num_per_page, $current_page = 1, $pass_along_str = "",
        $page_str = "page", $theme = "", $settings = array())
    {
        //global $g_max_nav_pages, $g_smarty_debug, $g_smarty, $g_smarty_use_sub_dirs;
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();
        $root_url = Core::getRootUrl();

        $current_page = ($current_page < 1) ? 1 : $current_page;

        if (empty($theme)) {
            $theme = Sessions::get("account.theme");
        }

        // TODO enough of this! Move to helper for all instances

        $smarty = Core::$smarty;
        $smarty->setTemplateDir("$root_dir/themes/$theme");
        $smarty->setCompileDir("$root_dir/themes/$theme/cache/");
        $smarty->setUseSubDirs(Core::shouldUseSmartySubDirs());
        $smarty->assign("LANG", $LANG);

//        if (isset($_SESSION["ft"])) {}
//            $smarty->assign("SESSION", $_SESSION["ft"]);

        $smarty->assign("g_root_dir", $root_dir);
        $smarty->assign("g_root_url", $root_url);
        $smarty->assign("samepage", General::getCleanPhpSelf());
        $smarty->assign("num_results", $num_results);
        $smarty->assign("num_per_page", $num_per_page);
        $smarty->assign("current_page", $current_page);
        $smarty->assign("page_str", $page_str);
        $smarty->assign("show_total_results", (isset($settings["show_total_results"])) ? $settings["show_total_results"] : true);
        $smarty->assign("show_page_label", (isset($settings["show_page_label"])) ? $settings["show_page_label"] : true);


        // display the total number of results found
        $range_start = ($current_page - 1) * $num_per_page + 1;
        $range_end   = $range_start + $num_per_page - 1;
        $range_end   = ($range_end > $num_results) ? $num_results : $range_end;

        $smarty->assign("range_start", $range_start);
        $smarty->assign("range_end", $range_end);

        $viewing_range = "";
        if ($num_results > $num_per_page) {
            $replacement_info = array(
            "startnum" => "<span id='nav_viewing_num_start'>$range_start</span>",
            "endnum"   => "<span id='nav_viewing_num_end'>$range_end</span>"
            );
            $viewing_range = General::evalSmartyString($LANG["phrase_viewing_range"], $replacement_info);
        }
        $total_pages = ceil($num_results / $num_per_page);
        $smarty->assign("viewing_range", $viewing_range);
        $smarty->assign("total_pages", $total_pages);

        // piece together additional query string values
        if (!empty($pass_along_str)) {
            $smarty->assign("query_str", "&{$pass_along_str}");
        }

        // determine the first and last pages to show page nav links for
        $half_total_nav_pages  = floor(Core::getMaxNavPages() / 2);
        $first_page = ($current_page > $half_total_nav_pages) ? $current_page - $half_total_nav_pages : 1;
        $last_page  = (($current_page + $half_total_nav_pages) < $total_pages) ? $current_page + $half_total_nav_pages : $total_pages;

        $smarty->assign("first_page", $first_page);
        $smarty->assign("last_page", $last_page);

        $smarty->assign("include_first_page_direct_link", (($first_page != 1) ? true : false));
        $smarty->assign("include_last_page_direct_link", (($first_page != $total_pages) ? true : false));


        // now process the template and return the HTML
        return $smarty->fetch(Themes::getSmartyTemplateWithFallback($theme, "pagination.tpl"));
    }


}

