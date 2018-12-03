<?php

/**
 * This is used for the new (React) client-side code. It provides as much info of the current user, depending on whether
 * they're logged in or not, plus localization strings and other general info.
 */
require_once("../library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Modules;
use FormTools\Packages;
use FormTools\Request;
use FormTools\Themes;

Core::init();

$data = array(
	"error" => "unknown_action"
);

switch ($_GET["action"]) {
	case "init":

		// TODO CAMEL! All Camel now.
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
				$url .= "/feeds/core/core.json";
				break;
			case "api":
				$url .= "/feeds/api/api.json";
				break;
			case "module":
				$url .= "/feeds/modules/{$_GET["component"]}.json";
				break;
			case "theme":
				$url .= "/feeds/themes/{$_GET["component"]}.json";
				break;
		}

		$data = json_decode(Request::getUrl($url));
		break;

	// these methods can only be called during an installation (TODO: security)
	case "installation_download_single_component":
		$url = urldecode($_GET["url"]);
		$component_type = $_GET["type"];

		$data = array(
			"url" => $url,
			"type" => $component_type
		);
		$data = Packages::downloadAndUnpack($url, $component_type);
		break;

	case "get_installed_components":
		if (!Core::$user->isLoggedIn() || !Core::$user->isAdmin()) {
			$data = array("error" => "no_access");
			return;
		}

		$api_info = array(
			"installed" => false
		);

		$api_available = Core::isAPIAvailable();
		if ($api_available) {
			$api_info = array(
				"installed" => true,
				"version" => General::getApiVersion()
			);
		}

		$data = array(
			"core" => Core::getCoreVersion(),
			"api" => $api_info,
			"themes" => Themes::getList(),
			"modules" => Modules::getList()
		);
		break;

}


header("Content-Type: text/javascript");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

echo json_encode($data);
