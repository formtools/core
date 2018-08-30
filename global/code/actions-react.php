<?php

/**
 * This is used for the new (React) client-side code. It provides as much info of the current user, depending on whether
 * they're logged in or not, plus localization strings and other general info.
 */
require_once("../library.php");

use FormTools\Core;
use FormTools\Request;

Core::init();

$data = array(
	"error" => "unknown_action"
);

switch ($_GET["action"]) {
	case "init":
		$data = array(
			"is_logged_in" => Core::$user->isLoggedIn(),
			"i18n" => Core::$L,
			"constants" => array(
				"root_dir" => Core::getRootDir(),
				"root_url" => Core::getRootUrl(),
				"data_source_url" => Core::getFormToolsDataSource(),
				"core_version" => Core::getCoreVersion()
			)
		);
		if ($data["is_logged_in"]) {
			$data["user"] = array(
				"account_id" => Core::$user->getAccountId(),
				"username" => Core::$user->getUsername()
			);
		}
		break;

	case "get_component_info":
		if (!in_array($_GET["type"], array("core", "api", "module", "theme")) || empty($_GET["component"]) || !is_string($_GET["component"])) {
			break;
		}

		$url = Core::getFormToolsDataSource();
		switch ($_GET["type"]) {
			case "core":
				$url .= "/core/core.json";
				break;
			case "api":
				$url .= "/api/api.json";
				break;
			case "module":
				$url .= "/modules/{$_GET["component"]}.json";
				break;
			case "theme":
				$url .= "/themes/{$_GET["component"]}.json";
				break;
		}

		$data = json_decode(Request::getUrl($url));
		break;
}


header("Content-Type: text/javascript");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

echo json_encode($data);
