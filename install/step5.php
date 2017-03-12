<?php

// by at this point, the config file exists but the DB isn't fully set up yet
require_once("library.php");
require_once(realpath(__DIR__ . "/../global/config.php"));

use FormTools\Accounts;
use FormTools\Database;
use FormTools\Installation;


// if required, add the user account
$account_created = false;
if (isset($_POST["add_account"])) {
    $db = new Database($g_db_hostname, $g_db_name, "3306", $g_db_username, $g_db_password);
	list($account_created, $g_message) = Accounts::setAdminAccount($db, $_POST, $g_table_prefix);

	// store for later use
	$_SESSION["ft_install"]["email"] = $_POST["email"];
	$_SESSION["ft_install"]["username"] = $_POST["username"];
	$_SESSION["ft_install"]["password"] = $_POST["password"];

	// everything's done! Now just make a few minor updates to the database for this user's configuration
	if ($account_created) {
		Installation::updateDatabaseSettings();
        header("location: step6.php");
		exit;
	}
}

$page = array(
    "step" => 5,
    "g_root_url" => $g_root_url,
    "account_created" => $account_created
);

$page["head_js"] =<<< EOF
var rules = [];
rules.push("required,first_name,{$LANG["validation_no_first_name"]}");
rules.push("required,last_name,{$LANG["validation_no_last_name"]}");
rules.push("required,email,{$LANG["validation_no_admin_email"]}");
rules.push("valid_email,email,{$LANG["validation_invalid_admin_email"]}");
rules.push("required,username,{$LANG["validation_no_username"]}");
rules.push("is_alpha,username,{$LANG['validation_invalid_admin_username']}");
rules.push("required,password,{$LANG["validation_no_password"]}");
rules.push("required,password_2,{$LANG["validation_no_second_password"]}");
rules.push("same_as,password,password_2,{$LANG["validation_passwords_different"]}")

rsv.displayType = "alert-all";
rsv.errorTextIntro = "{$LANG["phrase_error_text_intro"]}";
EOF;

Installation::displayPage("templates/step5.tpl", $page);
