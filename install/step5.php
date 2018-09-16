<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;
use FormTools\Sessions;

Core::setHooksEnabled(false);
Core::loadConfigFile();
Core::startSessions();
Core::initSmarty();
Core::initDatabase();
Core::setCurrLang(General::loadField("lang_file", "lang_file", Core::getDefaultLang()));

// if required, add the user account
$account_created = false;
if (isset($_POST["add_account"])) {
	list($account_created, $g_message) = Installation::setAdminAccount($_POST);

	// store for later use
    Sessions::set("install_email", $_POST["email"]);
    Sessions::set("install_username", $_POST["username"]);

	// everything's done! Now just make a few minor updates to the database for this user's configuration
	if ($account_created) {
		Installation::updateDatabaseSettings();
        General::redirect("step6.php");
	}
}

$LANG = Core::$L;

$page = array(
    "step" => 5,
    "g_root_url" => Core::getRootUrl(),
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
rules.push("same_as,password,password_2,{$LANG["validation_passwords_different"]}");

rsv.displayType = "alert-all";
rsv.errorTextIntro = "{$LANG["phrase_error_text_intro"]}";
EOF;

Installation::displayPage("templates/step5.tpl", $page);
