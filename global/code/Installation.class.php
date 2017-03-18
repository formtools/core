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


use Smarty, PDOException;


/**
 * Form Tools Installation class.
 */
class Installation
{

    /**
     * This function attempts to create the config file for the user.
     * @return bool
     */
    public static function generateConfigFile($config_file)
    {
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
        if (is_file(realpath(__DIR__ . "/../config.php"))) {
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
    public static function installCoreFieldTypes($module_folder)
    {
        require_once(realpath(__DIR__ . "/../../modules/$module_folder/library.php"));
        return cft_install_module();
    }


    /**
     * This function is basically a hardcoded rollback mechanism to delete any and all database tables, called in the event
     * of something going wrong during database creation.
     *
     * @param Database $db
     * @param string $table_prefix
     */
    public static function deleteTables(Database $db, array $all_tables, $table_prefix)
    {
        try {
            $db->beginTransaction();
            foreach ($all_tables as $table) {
                $db->query("DROP TABLE IF EXISTS {$table_prefix}$table");
                $db->execute();
            }
            $db->processTransaction();
        } catch (PDOException $e) {
            $db->rollbackTransaction();
        }
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
        global $LANG, $g_smarty, $g_success, $g_message, $g_release_type, $g_release_date;

        $version = Core::getCoreVersion();

        clearstatcache();
        $theme_folder   = realpath(INSTALLATION_FOLDER . "/../themes/default/");
        $cache_folder   = "$theme_folder/cache/";

        // always try to set the cache folder to 777
        @chmod($cache_folder, 0777);

        if ($g_release_type == "alpha") {
            $version .= "-alpha-$g_release_date";
        } else if ($g_release_type == "beta") {
            $version .= "-beta-$g_release_date";
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
          <b>{$version}</b>
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
        $g_smarty->assign("version", $version);

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
        $rootURL = Core::getRootURL();
        $rootDir = Core::getRootDir();

        // we add slashes since in PC paths like c:\www\whatever the \'s get lost en route
        $core_settings = array(
            "default_logout_url" => $rootURL,
            "file_upload_dir"    => addslashes($rootDir) . "/upload",
            "file_upload_url"    => "$rootURL/upload"
        );
        Settings::set($core_settings, "core");

        // TODO
        $export_manager_settings = array(
            "file_upload_dir" => addslashes($rootDir) . "/upload",
            "file_upload_url" => "$rootURL/upload"
        );
        Settings::set($export_manager_settings, "export_manager");
    }

    /**
     * This function creates the Form Tools database tables.
     */
    public static function createDatabase(Database $db, $table_prefix, $sql)
    {
        try {
            $db->beginTransaction();

            // suppress strict mode
            $db->query("SET SQL_MODE=''");
            $db->execute();

            foreach ($sql as $query) {
                $query = preg_replace("/%PREFIX%/", $table_prefix, $query);
                $query = preg_replace("/%FORMTOOLSVERSION%/", Core::getCoreVersion(), $query);
                $query = preg_replace("/%FORMTOOLSRELEASEDATE%/", Core::getReleaseDate(), $query);
                $query = preg_replace("/%FORMTOOLSRELEASETYPE%/", Core::getReleaseType(), $query);
                $query = preg_replace("/%CHARSET%/", Core::getDbTableCharset(), $query);

                $db->query($query);
                $db->execute();
            }

            $db->processTransaction();
        } catch (PDOException $e) {
            $db->rollbackTransaction();
            return array(false, $e->getMessage());
        }

        return array(true, "");
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
            . "\$g_db_port = \"{$_SESSION["ft_install"]["g_db_port"]}\";\n"
            . "\$g_db_name = \"{$_SESSION["ft_install"]["g_db_name"]}\";\n"
            . "\$g_db_username = \"{$username}\";\n"
            . "\$g_db_password = \"{$password}\";\n"
            . "\$g_table_prefix = \"{$_SESSION["ft_install"]["g_table_prefix"]}\";\n";

        $content .= "\n?" . ">";

        return $content;
    }
}

