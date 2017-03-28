<?php

require_once("../global/library.php");

use FormTools\Accounts;
use FormTools\Core;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::init();


// if required, add the user account
$account_created = false;
if (isset($_POST["add_account"])) {
	list($account_created, $g_message) = Accounts::setAdminAccount($_POST);

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

$LANG = Core::$L;

$page = array(
    "step" => 5,
    "g_root_url" => Core::getRootURL(),
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
