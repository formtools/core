<?php

require_once("library.php");

use FormTools\Database;
use FormTools\General;
use FormTools\Installation;


$hostname = ft_load_field("g_db_hostname", "g_db_hostname", "localhost", "ft_install");
$db_name  = ft_load_field("g_db_name", "g_db_name", "", "ft_install");
$port     = ft_load_field("g_db_port", "g_db_port", "3306", "ft_install");
$username = ft_load_field("g_db_username", "g_db_username", "", "ft_install");
$password = ft_load_field("g_db_password", "g_db_password", "", "ft_install");
$table_prefix = ft_load_field("g_table_prefix", "g_table_prefix", "ft_", "ft_install");

$step_complete = false;
$error = "";
$tables_already_exist = false;
$existing_tables = array();

if (isset($_POST["overwrite_tables"])) {
    Installation::deleteTables($hostname, $db_name, $username, $password, $g_table_prefix);
    $_POST["create_database"] = 1;
}

if (isset($_POST["create_database"])) {
    list($success, $error) = Database::checkConnection($hostname, $db_name, $port, $username, $password);

	// all checks out! Now try to create the database tables
	if ($success) {
        $db = new Database($hostname, $db_name, $port, $username, $password);

        $existing_tables = General::getExistingTables($db, $g_ft_tables, $table_prefix);
		if (empty($existing_tables)) {
            list($success, $error) = Installation::createDatabase($db, $table_prefix);
//			if ($success) {
//				header("location: step4.php");
//				exit;
//			}
		} else {
			$success = false;
			$tables_already_exist = true;
		}
	}
}

$page_vars = array(
    "step" => 3,
    "error" => $error,
    "step_complete" => $step_complete,
    "tables_already_exist" => $tables_already_exist,
    "existing_tables" => $existing_tables,
    "g_db_hostname" => $hostname,
    "g_db_name" => $db_name,
    "g_db_port" => $port,
    "g_db_username" => $username,
    "g_db_password" => $password,
    "g_table_prefix" => $table_prefix
);

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

Installation::displayPage("templates/step3.tpl", $page_vars);
