<?php

require_once("library.php");

$hostname = ft_load_field("g_db_hostname", "g_db_hostname", "localhost", "ft_install");
$db_name  = ft_load_field("g_db_name", "g_db_name", "", "ft_install");
$username = ft_load_field("g_db_username", "g_db_username", "", "ft_install");
$password = ft_load_field("g_db_password", "g_db_password", "", "ft_install");
$g_table_prefix = ft_load_field("g_table_prefix", "g_table_prefix", "ft_", "ft_install");

$step_complete = false;
$error = "";
$tables_already_exist = false;
$existing_tables = array();

if (isset($_POST["track_license_keys"]))
{
	$module_folders = $_POST["module_folders"];
	$data = array();
	foreach ($module_folders as $module_folder)
	{
		$data[$module_folder] = array(
		  "k"  => $_POST["{$module_folder}_k"],
		  "ek" => $_POST["{$module_folder}_ek"]
		);
	}
	$_SESSION["ft_install"]["premium_module_keys"] = $data;
}

if (isset($_POST["overwrite_tables"]))
{
  ft_install_delete_tables($hostname, $db_name, $username, $password, $g_table_prefix);
  $_POST["create_database"] = 1;
}

if (isset($_POST["create_database"]))
{
  // confirm the database settings are correctly entered. If they're not, the error messages are
  // returned by this function, and the page is reloaded to display them
  list($success, $error) = ft_install_check_db_settings($hostname, $db_name, $username, $password);

  // all checks out! Now try to create the database tables
  if ($success)
  {
    $existing_tables = ft_check_no_existing_tables($hostname, $db_name, $username, $password, $g_table_prefix);

    if (empty($existing_tables))
    {
      list($success, $error) = ft_install_create_database($hostname, $db_name, $username, $password, $g_table_prefix);
      if ($success)
      {
        header("location: step4.php");
        exit;
      }
    }
    else
    {
      $success = false;
      $tables_already_exist = true;
    }
  }
}

// ------------------------------------------------------------------------------------------------

$page_vars = array();
$page_vars["step"] = 3;
$page_vars["error"] = $error;
$page_vars["step_complete"] = $step_complete;
$page_vars["tables_already_exist"] = $tables_already_exist;
$page_vars["existing_tables"] = $existing_tables;
$page_vars["g_db_hostname"] = $hostname;
$page_vars["g_db_name"] = $db_name;
$page_vars["g_db_username"] = $username;
$page_vars["g_db_password"] = $password;
$page_vars["g_table_prefix"] = $g_table_prefix;

$page_vars["head_js"] =<<<EOF
var rules = [];
rules.push("required,g_db_hostname,{$LANG["validation_no_db_hostname"]}");
rules.push("required,g_db_name,{$LANG["validation_no_db_name"]}");
rules.push("required,g_db_username,{$LANG["validation_no_db_username"]}");
rules.push("required,g_table_prefix,{$LANG["validation_no_table_prefix"]}");
rules.push("is_alpha,g_table_prefix,{$LANG["validation_invalid_table_prefix"]}");
rsv.displayType = "alert-all";
rsv.errorTextIntro = "{$LANG["phrase_error_text_intro"]}";
EOF;

ft_install_display_page("templates/step3.tpl", $page_vars);
