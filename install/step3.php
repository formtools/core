<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\Database;
use FormTools\General;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::startSessions();
Core::initSmarty();
Core::setCurrLang(General::loadField("lang_file", "lang_file", Core::getDefaultLang()));

$hostname = General::loadField("g_db_hostname", "g_db_hostname", "localhost");
$db_name  = General::loadField("g_db_name", "g_db_name", "");
$port     = General::loadField("g_db_port", "g_db_port", "3306");
$username = General::loadField("g_db_username", "g_db_username", "");
$password = General::loadField("g_db_password", "g_db_password", "");
$table_prefix = General::loadField("g_table_prefix", "g_table_prefix", "ft_");

$step_complete = false;
$error = "";
$tables_already_exist = false;
$existing_tables = array();

$LANG = Core::$L;

if (isset($_POST["overwrite_tables"])) {
    $db = new Database($hostname, $db_name, $port, $username, $password, $table_prefix);
    Installation::deleteTables($db, Core::getCoreTables());
    $_POST["create_database"] = 1;
}

if (isset($_POST["create_database"])) {
    list($success, $error) = Installation::checkConnection($hostname, $db_name, $port, $username, $password);

	// all checks out! Now create the database tables
	if ($success) {
        $db = new Database($hostname, $db_name, $port, $username, $password, $table_prefix);

        $existing_tables = General::getExistingTables($db, Core::getCoreTables(), $table_prefix);
		if (empty($existing_tables)) {
            list($success, $error) = Installation::createDatabase($db);
			if ($success) {
                General::redirect("step4.php");
			}
		} else {
			$success = false;
			$tables_already_exist = true;
		}
	}
}

$page = array(
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

$page["head_js"] =<<<END
var rules = [];
rules.push("required,g_db_hostname,{$LANG["validation_no_db_hostname"]}");
rules.push("required,g_db_name,{$LANG["validation_no_db_name"]}");
rules.push("function,checkValidDbName");
rules.push("required,g_db_username,{$LANG["validation_no_db_username"]}");
rules.push("required,g_table_prefix,{$LANG["validation_no_table_prefix"]}");
rules.push("is_alpha,g_table_prefix,{$LANG["validation_invalid_table_prefix"]}");
rsv.displayType = "alert-all";
rsv.errorTextIntro = "{$LANG["phrase_error_text_intro"]}";

function checkValidDbName() {
	var field = $('input[name=g_db_name]')

	if (/[.\\/\\\\]/.test(field.val())) {
		return [[field[0], "{$LANG["validation_db_name"]}"]];
	}

	return true;
}
END;

Installation::displayPage("templates/step3.tpl", $page);
