<?php

/**
 * The installation class. Added in 2.3.0.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-3-x
 * @subpackage Installation
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;


use Smarty, PDO;


/**
 * Form Tools Installation class.
 */
class Installation
{
    /**
     * Helper function which examines a particular language file and returns the language
     * filename (en_us, fr_ca, etc) and the display name ("English (US), French (CA), etc).
     *
     * @param string $file the full path of the language file
     * @return array [0] the language file name<br />
     *               [1] the language display name
     */
    public static function getLanguageFileInfo($file)
    {
        include_once($file);
        $defined_vars = get_defined_vars();

        $language_name = $defined_vars["LANG"]["special_language_locale"];
        return $language_name;
    }

    /**
     * This function attempts to create the config file for the user.
     * @return bool
     */
    public static function generateConfigFile()
    {
        $config_file = $_SESSION["ft_install"]["config_file"];

        // try and write to the config.php file directly. This will probably fail, but in the off-chance
        // the permissions are set, it saves the user the hassle of manually creating the file. I changed this
        // to use a relative path and realpath() in 2.1.0...
        $file = realpath("../global") . DIRECTORY_SEPARATOR . "config.php";

        $handle = @fopen($file, "w");
        if ($handle) {
            fwrite($handle, $config_file);
            fclose($handle);
            return true;
        }

        // no such luck! we couldn't create the file on the server. The user will need to do it manually
        return false;
    }


    /**
     * basic test to find out if the database has been set up (namely: includes the user_account,
     * settings, forms and form_fields tables). Returns true/false.
    public static function databaseIsSetup()
    {
        global $g_db_name, $g_table_prefix, $g_sql;

        $is_setup = false;

        $g_sql = mysql_query("SHOW TABLES FROM $g_db_name");

        $table_names = array();
        while ($table_info = mysql_fetch_array($sql, MYSQL_NUM))
            $table_names[] = $table_info[0];

        if (in_array("{$g_table_prefix}settings", $table_names) && in_array("{$g_table_prefix}forms", $table_names) &&
        in_array("{$g_table_prefix}form_fields", $table_names) && in_array("{$g_table_prefix}accounts", $table_names))
            $is_setup = true;

        return $is_setup;
    }
    */


    /**
     * basic test to find out if the database has been set up (namely: includes the user_account,
     * settings, forms and form_fields tables). Returns true/false.
     */
    public static function checkConfigFileExists()
    {
        if (is_file("../global/config.php")) {
            return array(true, "");
        } else {
            return array(
                false,
                "The config.php file does not exist. You need to create it in your /global folder with the content specified in order to continue."
            );
        }
    }


    /**
     * Added in 2.1.5, this is a wrapped for the Core Field Types module's installation function. It's called on the
     * final step of the installation script. The module is unique; it's installation function can only be called for
     * fresh installations. It's called separately prior to other module installation functions to ensure the field
     * type tables are populated prior to other custom field type modules.
     */
    public static function installCodeFieldTypes($module_folder)
    {
        require_once(realpath(__DIR__ . "/../../modules/$module_folder/library.php"));
        return cft_install_module();
    }


    /**
     * This function is basically a hardcoded rollback mechanism to delete any and all database tables, called in the event
     * of something going wrong during database creation.
     *
     * @param string $hostname
     * @param string $db_name
     * @param string $username
     * @param string $password
     * @param string $table_prefix
     */
    public static function deleteTables($hostname, $db_name, $username, $password, $table_prefix)
    {
        global $g_ft_tables;

        $link = @mysql_connect($hostname, $username, $password);
        @mysql_select_db($db_name);

        foreach ($g_ft_tables as $table) {
            mysql_query("DROP TABLE {$table_prefix}{$table}");
        }

        @mysql_close($link);
    }


    // deprecate this bad boy
    public static function sanitize_no_db($input)
    {
        if (is_array($input)) {
            $output = array();
            foreach ($input as $k=>$i) {
                $output[$k] = ft_install_sanitize_no_db($i);
            }
        } else {
            if (get_magic_quotes_gpc()) {
                $input = stripslashes($input);
            }
            $output = addslashes($input);
        }
        return $output;
    }


    public static function evalSmartyString($placeholder_str, $placeholders = array(), $theme = "default")
    {
        global $LANG;

        $smarty = new Smarty();
        $smarty->template_dir = INSTALLATION_FOLDER . "/../global/smarty/";
        $smarty->compile_dir  = INSTALLATION_FOLDER . "/../themes/$theme/cache/";

        $smarty->assign("eval_str", $placeholder_str);
        if (!empty($placeholders)) {
            while (list($key, $value) = each($placeholders)) {
                $smarty->assign($key, $value);
            }
        }
        $smarty->assign("LANG", $LANG);
        $output = $smarty->fetch(realpath(__DIR__ . "/../smarty_plugins/eval.tpl"));

        return $output;
    }

    /**
     * Used to render the HTML for the install pages.
     *
     * @param string $template
     * @param array $page_vars
     */
    public static function displayPage($template, $page_vars)
    {
        global $LANG, $g_smarty, $g_success, $g_message, $g_current_version, $g_release_type, $g_release_date;

        clearstatcache();
        $theme_folder   = realpath(INSTALLATION_FOLDER . "/../themes/default/");
        $cache_folder   = "$theme_folder/cache/";

        // always try to set the cache folder to 777
        @chmod($cache_folder, 0777);

        $version_string = $g_current_version;
        if ($g_release_type == "alpha") {
            $version_string .= "-alpha-$g_release_date";
        } else if ($g_release_type == "beta") {
            $version_string .= "-beta-$g_release_date";
        }

        if (!is_readable("$cache_folder/") || !is_writable("$cache_folder/")) {
            echo <<< EOF
<html>
<head>
<link rel="stylesheet" type="text/css" href="files/main.css">
</head>
<body>

<div id="container">
  <div id="header">

    <div style="float:right">
      <table cellspacing="0" cellpadding="0" height="25">
      <tr>
        <td><img src="images/account_section_left.jpg" border="0" /></td>
        <td id="account_section">
          <b>{$version_string}</b>
        </td>
        <td><img src="images/account_section_right.jpg" border="0" /></td>
      </tr>
      </table>
    </div>

    <span style="float:left; padding-top: 8px; padding-right: 10px">
      <a href="https://formtools.org" class="no_border"><img src="../themes/default/images/logo_green.jpg" border="0" height="61" /></a>
    </span>
  </div>
  <div id="content">

    <div class="notify">
      {$LANG["text_default_theme_cache_folder_not_writable"]}
    </div>

  </div>
</div>
</body>
</html>
EOF;
            exit;
        }

        $g_smarty = new Smarty();
        $g_smarty->template_dir = $theme_folder;
        $g_smarty->compile_dir  = $cache_folder;
        $g_smarty->use_sub_dirs = false;
        $g_smarty->assign("LANG", $LANG);
        $g_smarty->assign("SESSION", $_SESSION["ft_install"]);
        $g_smarty->assign("same_page", $_SERVER["PHP_SELF"]);
        $g_smarty->assign("dir", $LANG["special_text_direction"]);
        $g_smarty->assign("g_success", $g_success);
        $g_smarty->assign("g_message", $g_message);
        $g_smarty->assign("g_default_theme", "default");
        $g_smarty->assign("version_string", $version_string);

        // check the "required" vars are at least set so they don't produce warnings when smarty debug is enabled
        if (!isset($page_vars["head_string"])) $page_vars["head_string"] = "";
        if (!isset($page_vars["head_title"]))  $page_vars["head_title"] = "";
        if (!isset($page_vars["head_js"]))     $page_vars["head_js"] = "";
        if (!isset($page_vars["page"]))        $page_vars["page"] = "";

        // if we need to include custom JS messages in the page, add it to the generated JS. Note: even if the js_messages
        // key is defined but still empty, the ft_generate_js_messages function is called, returning the "base" JS - like
        // the JS version of g_root_url. Only if it is not defined will that info not be included.
        $js_messages = (isset($page_vars["js_messages"])) ? ft_generate_js_messages($page_vars["js_messages"]) : "";

        if (!empty($page_vars["head_js"]) || !empty($js_messages)) {
            $page_vars["head_js"] = "<script type=\"text/javascript\">\n//<![CDATA[\n{$page_vars["head_js"]}\n$js_messages\n//]]>\n</script>";
        }

        if (!isset($page_vars["head_css"])) {
            $page_vars["head_css"] = "";
        } else if (!empty($page_vars["head_css"])) {
            $page_vars["head_css"] = "<style type=\"text/css\">\n{$page_vars["head_css"]}\n</style>";
        }

        // now add the custom variables for this template, as defined in $page_vars
        foreach ($page_vars as $key=>$value) {
            $g_smarty->assign($key, $value);
        }

        $g_smarty->display(INSTALLATION_FOLDER . "/$template");
    }


    /**
     * This is sent at the very last step. It emails the administrator a short welcome email containing their
     * login information, with a few links to resources on our site.
     *
     * Note: this is ALWAYS sent with mail(), since the Swift Mailer plugin won't have been configured yet.
     *
     * @param string $password the unencrypted password
     */
    public static function sendWelcomeEmail($email, $username, $password)
    {
        global $g_root_dir, $g_root_url;

        // 1. build the email content
        $placeholders = array(
            "login_url" => $g_root_url,
            "username" => $username,
            "password" => $password
        );
        $smarty_template_email_content = file_get_contents("$g_root_dir/global/emails/installed.tpl");
        $email_content = ft_eval_smarty_string($smarty_template_email_content, $placeholders);

        // 2. build the email subject line
        $smarty_template_email_subject = file_get_contents("$g_root_dir/global/emails/installed_subject.tpl");
        $email_subject = trim(ft_eval_smarty_string($smarty_template_email_subject, array()));

        // send email [note: the double quotes around the email recipient and content are intentional:
        // some systems fail without it]
        @mail("$email", $email_subject, $email_content);
    }


    /**
     * This is called after the database is created and all the various settings (like root URL, etc) are
     * determined. It updates the database to set the various default settings.
     */
    public static function updateDatabaseSettings()
    {
        global $g_root_dir, $g_root_url;

        // we add slashes since in PC paths like c:\www\whatever the \'s get lost en route
        $core_settings = array(
            "default_logout_url" => $g_root_url,
            "file_upload_dir"    => addslashes($g_root_dir) . "/upload",
            "file_upload_url"    => "$g_root_url/upload"
        );
        ft_set_settings($core_settings, "core");

        // ??? no good!
        $export_manager_settings = array(
            "file_upload_dir" => addslashes($g_root_dir) . "/upload",
            "file_upload_url" => "$g_root_url/upload"
        );
        ft_set_settings($export_manager_settings, "export_manager");
    }

    /**
     * This function creates the database tables.
     *
     * @param string $hostname
     * @param string $db_name
     * @param string $username
     * @param string $password
     * @return array returns an array with two indexes: [0] true/false, depending on whether the
     *               operation was a success. [1] error message / empty string if success.
     */
    public static function createDatabase($db, $table_prefix)
    {
        global $g_sql, $g_current_version, $g_release_type, $g_release_date, $g_db_table_charset;

        // suppress strict mode
//        @mysql_query("SET SQL_MODE=''", $link);

        $errors = array();
        foreach ($g_sql as $query) {
            $query = preg_replace("/%PREFIX%/", $table_prefix, $query);
            $query = preg_replace("/%FORMTOOLSVERSION%/", $g_current_version, $query);
            $query = preg_replace("/%FORMTOOLSRELEASEDATE%/", $g_release_date, $query);
            $query = preg_replace("/%FORMTOOLSRELEASETYPE%/", $g_release_type, $query);
            $query = preg_replace("/%CHARSET%/", $g_db_table_charset, $query);

            // execute the queries. If any error occurs, break out of the installation loop, delete any and
            // all tables that have been created
            $result = mysql_query($query) or $errors[] = $query . " - <b>" . mysql_error() . "</b>";

//            // problem! delete any tables we just added
//            if (!$result) {
//                self::deleteTables($hostname, $db_name, $username, $password, $table_prefix);
//                break;
//            }
        }

        $success = true;
        $message = "";

        if (!empty($errors)) {
            $success = false;
            array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
            $message = join("<br />", $errors);
        }

        // if there was an error, return the error message
        return array($success, $message);
    }


    /**
     * Creates the administrator account. This is a bit of a misnomer, really, since the blank administrator account
     * always exists with an account ID of 1. This function just updates it.
     *
     * @param array $info
     * @return array
     */
    public static function createAdminAccount($info)
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
            $success = false;
            array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
            $message = join("<br />", $errors);
            return array($success, $message);
        }

        $first_name = $info["first_name"];
        $last_name  = $info["last_name"];
        $email      = $info["email"];
        $username   = $info["username"];
        $password   = md5(md5($info["password"]));

        $query = mysql_query("
            UPDATE {$g_table_prefix}accounts
            SET    first_name = '$first_name',
                last_name = '$last_name',
                email = '$email',
                username = '$username',
                password = '$password',
                logout_url = '$g_root_url'
            WHERE account_id = 1
        ");

        $success = true;
        $message = "";
        if (!$query) {
            $success = false;
            $message = mysql_error();
        }

        return array($success, $message);
    }


    /**
     * This function generates the content of the config file and returns it.
     */
    public static function getConfigFileContents()
    {
        // try to fix REQUEST_URI for IIS
        if (empty($_SERVER['REQUEST_URI'])) {
            // IIS Mod-Rewrite
            if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
            }

            // IIS Isapi_Rewrite
            else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
            } else {
                // some IIS + PHP configurations puts the script-name in the path-info (no need to append it twice)
                if ( isset($_SERVER['PATH_INFO']) ) {
                    if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
                        $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
                    } else {
                        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
                    }
                }

                // append the query string if it exists and isn't null
                if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                    $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
                }
            }
        }

        $root_url = preg_replace("/\/install\/step4\.php$/", "", "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
        $root_dir = preg_replace("/.install$/", "", INSTALLATION_FOLDER);
        $root_dir = preg_replace("/\\\/", "\\\\\\", $root_dir);

        $_SESSION["ft_install"]["g_root_dir"] = INSTALLATION_FOLDER;
        $_SESSION["ft_install"]["g_root_url"] = $root_url;

        $username = preg_replace('/\$/', '\\\$', $_SESSION["ft_install"]["g_db_username"]);
        $password = preg_replace('/\$/', '\\\$', $_SESSION["ft_install"]["g_db_password"]);

        $content = "<" . "?php\n\n"
            . "// main program paths - no trailing slashes!\n"
            . "\$g_root_url = \"$root_url\";\n"
            . "\$g_root_dir = \"$root_dir\";\n\n"
            . "// database settings\n"
            . "\$g_db_hostname = \"{$_SESSION["ft_install"]["g_db_hostname"]}\";\n"
            . "\$g_db_name = \"{$_SESSION["ft_install"]["g_db_name"]}\";\n"
            . "\$g_db_username = \"{$username}\";\n"
            . "\$g_db_password = \"{$password}\";\n"
            . "\$g_table_prefix = \"{$_SESSION["ft_install"]["g_table_prefix"]}\";\n";

        $content .= "\n?" . ">";

        return $content;
    }
}

