<?php

/**
 * This is used for the new (React) client-side code. It provides as much info of the current user, depending on whether
 * they're logged in or not, plus localization strings and other general info.
 */
require_once("../library.php");

use FormTools\Core;
Core::init();

$data = array(
	"error" => "unknown_action"
);

switch ($_GET["action"]) {
	case "init":
		$data = array(
			"isAuthenticated" => Core::$user->isLoggedIn(),
			"i18n" => Core::$L,
			"constants" => array(
				"rootDir" => Core::getRootDir(),
				"rootUrl" => Core::getRootUrl(),
				"dataSourceUrl" => Core::getFormToolsDataSource(),
				"coreVersion" => Core::getCoreVersion()
			)
		);
		if ($data["is_logged_in"]) {
			$data["user"] = array(
				"accountId" => Core::$user->getAccountId(),
				"username" => Core::$user->getUsername()
			);
		}
		break;
}


header("Content-Type: text/javascript");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

echo json_encode($data);
