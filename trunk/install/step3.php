<?php

/**
 * The first page of the installation script. All HTML is found in the /templates folder.
 */

session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");

require_once("library.php");
require_once("files/sql.php");

$hostname = ft_load_field("g_db_hostname", "g_db_hostname", "localhost", "ft_install");
$db_name  = ft_load_field("g_db_name", "g_db_name", "", "ft_install");
$username = ft_load_field("g_db_username", "g_db_username", "", "ft_install");
$password = ft_load_field("g_db_password", "g_db_password", "", "ft_install");
$g_table_prefix = ft_load_field("g_table_prefix", "g_table_prefix", "ft_", "ft_install");

$step_complete = false;
$error = "";

if (isset($_POST["create_database"]))
{
	// confirm the database settings are correctly entered. If they're not, the error messages are
	// returned by this function, and the page is reloaded to display them
	list($success, $error) = ft_install_check_db_settings($hostname, $db_name, $username, $password);

	// all checks out! Now try to create the database tables
	if ($success)
	{
	  list($success, $error) = ft_install_create_database($hostname, $db_name, $username, $password, $g_table_prefix);
	  if ($success)
	  {
	  	header("location: step4.php");
	  	exit;
	  }
	}
}

// ------------------------------------------------------------------------------------------------

$page_vars = array();
$page_vars["step"] = 3;
$page_vars["error"] = $error;
$page_vars["step_complete"] = $step_complete;
$page_vars["g_db_hostname"] = $hostname;
$page_vars["g_db_name"] = $db_name;
$page_vars["g_db_username"] = $username;
$page_vars["g_db_password"] = $password;
$page_vars["g_table_prefix"] = $g_table_prefix;

$page_vars["head_js"] = "var rules = [];
rules.push(\"required,g_db_hostname,Please enter the database hostname.\");
rules.push(\"required,g_db_name,Please enter the database name.\");
rules.push(\"required,g_db_username,Please enter the database account username.\");

rsv.displayType = \"alert-all\";
rsv.errorTextIntro=\"{$LANG["phrase_error_text_intro"]}\";
";

ft_install_display_page("templates/step3.tpl", $page_vars);