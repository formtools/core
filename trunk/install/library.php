<?php

/**
 * This contains the various functions needed for the installation script.
 */

$g_ft_installation_folder = dirname(__FILE__);
$g_default_language       = "en_us.php";
$g_form_tools_version     = "2.0.3-beta-20100731";
$g_is_beta                = "yes";
$g_beta_version           = "";
$g_smarty_use_sub_dirs    = false;


// all session information for the installation script is stored in the $_SESSION["ft_install"] key,
// which is dutifully deleted on completion
if (!isset($_SESSION["ft_install"]))
  $_SESSION["ft_install"] = array();

// include the language file
if (!isset($_SESSION["ft_install"]["lang_file"]))
  $_SESSION["ft_install"]["lang_file"] = $g_default_language;

include("$g_ft_installation_folder/../global/lang/{$_SESSION["ft_install"]["lang_file"]}");

require_once("$g_ft_installation_folder/../global/code/general.php");
require_once("$g_ft_installation_folder/../global/smarty/Smarty.class.php");


// ------------------------------------------------------------------------------------------------


/**
 * Used to render the HTML for the install pages.
 *
 * @param string $template
 * @param array $page_vars
 */
function ft_install_display_page($template, $page_vars)
{
  global $LANG, $g_smarty, $g_success, $g_message, $g_ft_installation_folder,
    $g_smarty_use_sub_dirs, $g_form_tools_version, $g_is_beta, $g_beta_version;

  require_once("$g_ft_installation_folder/../global/smarty/Smarty.class.php");

  clearstatcache();
  $cache_folder = realpath("$g_ft_installation_folder/../themes/default/cache/");

  // always try to set the cache folder to 777 during the installation phase
  @chmod($cache_folder, 0777);

  // run a preliminary permissions check on the default theme's cache folder
  if (!is_readable("$cache_folder/") || !is_writable("$cache_folder/"))
  {
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
          v{$g_form_tools_version}
        </td>
        <td><img src="images/account_section_right.jpg" border="0" /></td>
      </tr>
      </table>
    </div>

    <span style="float:left; padding-top: 8px; padding-right: 10px">
      <a href="http://www.formtools.org" class="no_border"><img src="images/logo.jpg" border="0" width="359" height="61" /></a>
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
  $g_smarty->template_dir = realpath("$g_ft_installation_folder/../themes/default");
  $g_smarty->compile_dir  = $cache_folder;

  $g_smarty->use_sub_dirs = $g_smarty_use_sub_dirs;
  $g_smarty->assign("LANG", $LANG);
  $g_smarty->assign("SESSION", $_SESSION["ft_install"]);
  $g_smarty->assign("same_page", $_SERVER["PHP_SELF"]);
  $g_smarty->assign("dir", $LANG["special_text_direction"]);
  $g_smarty->assign("g_success", $g_success);
  $g_smarty->assign("g_message", $g_message);
  $g_smarty->assign("g_default_theme", "default");
  $g_smarty->assign("g_form_tools_version", $g_form_tools_version);
  $g_smarty->assign("g_is_beta", $g_is_beta);
  $g_smarty->assign("g_beta_version", $g_beta_version);

  // check the "required" vars are at least set so they don't produce warnings when smarty debug is enabled
  if (!isset($page_vars["head_string"])) $page_vars["head_string"] = "";
  if (!isset($page_vars["head_title"]))  $page_vars["head_title"] = "";
  if (!isset($page_vars["head_js"]))     $page_vars["head_js"] = "";
  if (!isset($page_vars["page"]))        $page_vars["page"] = "";

  // if we need to include custom JS messages in the page, add it to the generated JS. Note: even if the js_messages
  // key is defined but still empty, the ft_generate_js_messages function is called, returning the "base" JS - like
  // the JS version of g_root_url. Only if it is not defined will that info not be included.
  $js_messages = (isset($page_vars["js_messages"])) ? ft_generate_js_messages($page_vars["js_messages"]) : "";

  if (!empty($page_vars["head_js"]) || !empty($js_messages))
    $page_vars["head_js"] = "<script type=\"text/javascript\">\n//<![CDATA[\n{$page_vars["head_js"]}\n$js_messages\n//]]>\n</script>";

  if (!isset($page_vars["head_css"]))
    $page_vars["head_css"] = "";
  else if (!empty($page_vars["head_css"]))
    $page_vars["head_css"] = "<style type=\"text/css\">\n{$page_vars["head_css"]}\n</style>";

  // now add the custom variables for this template, as defined in $page_vars
  foreach ($page_vars as $key=>$value)
    $g_smarty->assign($key, $value);

  $g_smarty->display(realpath("$g_ft_installation_folder/$template"));
}


/**
 * Returns a list of available languages for the Form Tools script. To upload more, visit the
 * Form Tools site: http://translations.formtools.org to see what's available!
 */
function ft_install_get_languages()
{
  global $g_ft_installation_folder;

  $language_folder_dir = "$g_ft_installation_folder/../global/lang";

  $available_language_info = array();
  if ($handle = opendir($language_folder_dir))
  {
    while (false !== ($filename = readdir($handle)))
    {
      if ($filename != '.' && $filename != '..' && $filename != "index.php" && preg_match("/.php$/", $filename))
      {
        $language_name = ft_install_get_language_file_info("$language_folder_dir/$filename");
        $available_language_info[$filename] = $language_name;
      }
    }
    closedir($handle);
  }

  // sort the languages alphabetically
  ksort($available_language_info);

  return $available_language_info;
}


/**
 * Helper function which examines a particular language file and returns the language
 * filename (en_us, fr_ca, etc) and the display name ("English (US), French (CA), etc).
 *
 * @param string $file the full path of the language file
 * @return array [0] the language file name<br />
 *               [1] the language display name
 */
function ft_install_get_language_file_info($file)
{
  include($file);
  $defined_vars = get_defined_vars();
  $language_name = $defined_vars["LANG"]["special_language_locale"];

  return $language_name;
}


/**
 * This function generates the content of the config file and returns it.
 */
function ft_install_get_config_file_contents()
{
  // try to fix REQUEST_URI for IIS
  if (empty($_SERVER['REQUEST_URI']))
  {
    // IIS Mod-Rewrite
    if (isset($_SERVER['HTTP_X_ORIGINAL_URL']))
      $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];

    // IIS Isapi_Rewrite
    else if (isset($_SERVER['HTTP_X_REWRITE_URL']))
      $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];

    else
    {
      // some IIS + PHP configurations puts the script-name in the path-info (no need to append it twice)
      if ( isset($_SERVER['PATH_INFO']) )
      {
        if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'])
          $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
        else
      $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
      }

      // append the query string if it exists and isn't null
      if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
        $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
    }
  }

  $root_url = preg_replace("/\/install\/step4\.php$/", "", "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
  $root_dir = preg_replace("/.install$/", "", dirname(__FILE__));
  $root_dir = preg_replace("/\\\/", "\\\\\\", $root_dir);

  $_SESSION["ft_install"]["g_root_dir"] = $root_dir;
  $_SESSION["ft_install"]["g_root_url"] = $root_url;

  $content = "<" . "?php\n\n"
           . "// main program paths - no trailing slashes!\n"
           . "\$g_root_url = \"$root_url\";\n"
           . "\$g_root_dir = \"$root_dir\";\n\n"
           . "// database settings\n"
           . "\$g_db_hostname = \"{$_SESSION["ft_install"]["g_db_hostname"]}\";\n"
           . "\$g_db_name = \"{$_SESSION["ft_install"]["g_db_name"]}\";\n"
           . "\$g_db_username = \"{$_SESSION["ft_install"]["g_db_username"]}\";\n"
           . "\$g_db_password = \"{$_SESSION["ft_install"]["g_db_password"]}\";\n"
           . "\$g_table_prefix = \"{$_SESSION["ft_install"]["g_table_prefix"]}\";\n";

  $content .= "\n?" . ">";

  return $content;
}


/**
 * This function attempts to create the config file for the user.
 *
 * @return array
 */
function ft_install_generate_config_file()
{
  $g_root_dir  = $_SESSION["ft_install"]["g_root_dir"];
  $config_file = $_SESSION["ft_install"]["config_file"];

  // try and write to the config.php file directly. This will probably fail, but in the off-chance
  // the permissions are set, it saves the user the hassle of manually creating the file
  $file = "$g_root_dir/global/config.php";
  $handle = @fopen($file, "w");
  if ($handle)
  {
    fwrite($handle, $config_file);
    fclose($handle);
    return true;
  }

  // no such luck! we couldn't create the file on the server. The user will need to do it manually
  return false;
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
function ft_install_create_database($hostname, $db_name, $username, $password, $table_prefix)
{
  global $g_sql, $g_form_tools_version, $g_is_beta, $g_beta_version;

  // connect to the database (since we know this works, having called
  $link = @mysql_connect($hostname, $username, $password);
  @mysql_select_db($db_name);

  $errors = array();
  foreach ($g_sql as $query)
  {
    $query = preg_replace("/%PREFIX%/", $table_prefix, $query);
    $query = preg_replace("/%FORMTOOLSVERSION%/", $g_form_tools_version, $query);
    $query = preg_replace("/%BETAVERSION%/", $g_beta_version, $query);
    $query = preg_replace("/%ISBETA%/", $g_is_beta, $query);

    // execute the queries. If any error occurs, break out of the installation loop, delete any and
    // all tables that have been created
    $result = mysql_query($query)
      or $errors[] = mysql_error();

    // problem! delete any tables we just added
    if (!$result)
    {
      ft_install_failure_delete_tables($hostname, $db_name, $username, $password, $table_prefix);
      break;
    }
  }

  $success = true;
  $message = "";

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
  }

  @mysql_close($link);

  // if there was an error, return the error message
  return array($success, $message);
}


/**
 * basic test to find out if the database has been set up (namely: includes the user_account,
 * settings, forms and form_fields tables). Returns true/false.
 */
function ft_install_database_is_setup()
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


/**
 * basic test to find out if the database has been set up (namely: includes the user_account,
 * settings, forms and form_fields tables). Returns true/false.
 */
function ft_install_check_config_file_exists()
{
  if (is_file("../global/config.php"))
    return array(true, "");
  else
    return array(false, "The config.php file does not exist. You need to create it in your /global folder with the content specified in order to continue.");
}


/**
 * This function confirms the database settings entered by the user are correct.
 *
 * @param string $hostname
 * @param string $db_name
 * @param string $username
 * @param string $password
 * @return array
 */
function ft_install_check_db_settings($hostname, $db_name, $username, $password)
{
  global $LANG, $g_ft_installation_folder;

  $db_connection_error = "";
  $db_select_error     = "";

  $link = @mysql_connect($hostname, $username, $password) or $db_connection_error = mysql_error();

  if ($db_connection_error)
  {
    $placeholders = array("db_connection_error" => $db_connection_error);
    $error = ft_install_eval_smarty_string($LANG["notify_install_invalid_db_info"], $placeholders, "default");
    return array(false, $error);
  }
  else
  {
    @mysql_select_db($db_name)
      or $db_select_error = mysql_error();

    if ($db_select_error)
    {
      $placeholders = array("db_select_error" => $db_select_error);
      $error = ft_install_eval_smarty_string($LANG["notify_install_no_db_connection"], $placeholders, "default");
      return array(false, $error);
    }
    else
    {
      @mysql_close($link);
    }
  }

  return array(true, "");
}


function ft_install_eval_smarty_string($placeholder_str, $placeholders = array(), $theme)
{
  global $g_ft_installation_folder, $LANG;

  $smarty = new Smarty();
  $smarty->template_dir = "$g_ft_installation_folder/../global/smarty/";
  $smarty->compile_dir  = "$g_ft_installation_folder/../themes/$theme/cache/";

  $smarty->assign("eval_str", $placeholder_str);
  if (!empty($placeholders))
  {
    while (list($key, $value) = each($placeholders))
      $smarty->assign($key, $value);
  }
  $smarty->assign("LANG", $LANG);

  $output = $smarty->fetch("eval.tpl");

  return $output;
}


/**
 * Creates the administrator account. This is a bit of a misnomer, really, since the blank administrator account
 * always exists with an account ID of 1. This function just updates it.
 *
 * @param array $info
 * @return array
 */
function ft_install_create_admin_account($info)
{
  global $g_table_prefix, $g_root_url;

  $info = ft_sanitize($info);

  $rules = array();
  $rules[] = "required,first_name,Please enter your first name.";
  $rules[] = "required,last_name,Please enter your last name.";
  $rules[] = "required,email,Please enter the administrator email address.";
  $rules[] = "valid_email,email,Please enter a valid administrator email address.";
  $rules[] = "required,username,Please enter your username.";
  $rules[] = "is_alpha,username,Please make sure your username consist of alphanumeric (a-Z and 0-9) characters only.";
  $rules[] = "required,password,Please enter your password.";
  $rules[] = "is_alpha,password,Please make sure your password consist of alphanumeric (a-Z and 0-9) characters only.";
  $rules[] = "required,password_2,Please re-enter your password.";
  $rules[] = "same_as,password,password_2,Please ensure the passwords are the same";
  $errors = validate_fields($info, $rules);

  if (!empty($errors))
  {
    $success = false;
    array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
    $message = join("<br />", $errors);
    return array($success, $message);
  }

  $first_name = $_POST["first_name"];
  $last_name = $_POST["last_name"];
  $email    = $_POST["email"];
  $username = $_POST["username"];
  $password = md5(md5($_POST["password"]));

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

  $success = "";
  $message = "";
  if ($query)
    $success = true;
  else
  {
    $success = false;
    $message = mysql_error();
  }

  return array($success, $message);
}


/**
 * This is called after the database is created and all the various settings (like root URL, etc) are
 * determined. It updates the database to set the various default settings.
 */
function ft_install_update_db_settings()
{
  global $g_root_dir, $g_root_url, $g_is_beta, $g_beta_version;

  // we add slashes since in PC paths like c:\www\whatever the \'s get lost en route
  $core_settings = array(
    "default_logout_url" => $g_root_url,
    "file_upload_dir"    => addslashes($g_root_dir) . "/upload",
    "file_upload_url"    => "$g_root_url/upload",
    "is_beta"            => $g_is_beta,
    "beta_version"       => $g_beta_version
  );
  ft_set_settings($core_settings, "core");

  $export_manager_settings = array(
    "file_upload_dir" => addslashes($g_root_dir) . "/upload",
    "file_upload_url" => "$g_root_url/upload"
      );
  ft_set_settings($export_manager_settings, "export_manager");
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
function ft_install_failure_delete_tables($hostname, $db_name, $username, $password, $table_prefix)
{
  $tables = array(
    "accounts", "account_settings", "client_forms", "client_views", "email_templates", "email_template_edit_submission_views",
    "email_template_recipients", "field_options", "field_settings", "forms", "form_fields", "menus", "menu_items", "modules",
    "module_export_groups", "module_export_group_clients", "module_export_types", "module_menu_items", "module_pages",
    "public_form_omit_list", "public_view_omit_list", "settings", "themes", "views", "view_fields", "view_filters", "view_tabs"
  );

  // connect to the database (since we know this works, having called
  $link = @mysql_connect($hostname, $username, $password);
  @mysql_select_db($db_name);

  foreach ($tables as $table)
    mysql_query("DROP TABLE {$table_prefix}{$table}");

  @mysql_close($link);
}


function ft_install_get_module_list()
{
  global $g_table_prefix, $g_root_dir;

  $modules_folder = "$g_root_dir/modules";
  $module_list = array();

  // if we couldn't open the modules folder, it doesn't exist or something went wrong
  $dh = opendir($modules_folder);
  if (!$dh)
    return array(false, "");

  while (($folder = readdir($dh)) !== false)
  {
    if (is_dir("$modules_folder/$folder") && $folder != "." && $folder != "..")
    {
      $info = ft_get_module_info_file_contents($folder);

      if (empty($info))
        continue;

      $info = ft_sanitize($info);

      // check the required info file fields
      $required_fields = array("author", "version", "date", "origin_language", "supports_ft_versions");
      $all_found = true;
      foreach ($required_fields as $field)
      {
        if (empty($info[$field]))
          $all_found = false;
      }
      if (!$all_found)
        continue;

      // now check the language file contains the two required fields: module_name and module_description
      $lang_file = "$modules_folder/$folder/lang/{$info["origin_language"]}.php";
      $lang_info = _ft_get_module_lang_file_contents($lang_file);
      $lang_info = ft_sanitize($lang_info);

      // check the required language file fields
      if ((!isset($lang_info["module_name"]) || empty($lang_info["module_name"])) ||
          (!isset($lang_info["module_description"]) || empty($lang_info["module_description"])))
        continue;

      $module_list = array(
        "module_name" => $lang_info["module_name"],
        "version" => $info["version"]
      );
    }
  }
  closedir($dh);

  return $module_list;
}


/**
 * This is sent at the very last step. It emails the administrator a short welcome email containing their
 * login information, with a few links to resources on our site.
 *
 * Note: this is ALWAYS sent with mail(), since the Swift Mailer plugin won't have been configured yet.
 *
 * @param string $password the unencrypted password
 */
function ft_install_send_welcome_email($email, $username, $password)
{
  global $g_root_dir, $g_root_url;

  // 1. build the email content
  $placeholders = array(
    "login_url" => $g_root_url,
    "username"  => $username,
    "password"  => $password
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
