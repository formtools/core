<?php

/**
 * Used to provide some info to the installation script: localization.
 */
require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Sessions;

Core::setHooksEnabled(false);
Core::startSessions();

$currentLang = General::loadField("lang", "lang", Core::getDefaultLang());
Core::setCurrLang($currentLang);
$root_url = Core::getRootUrl();

$data = array(
	"error" => "unknown_action"
);

switch ($_GET["action"]) {
	case "init":
		$data = array(
			"isAuthenticated" => false,
			"i18n" => Core::$L,
			"availableLanguages" => Core::$translations->getList(),
			"language" => $currentLang,
			"constants" => array(
				"rootDir" => Core::getRootDir(),
				"rootUrl" => "../",
				"coreVersion" => Core::getCoreVersion()
			)
		);
		break;

	case "selectLanguage":
		// check the lang is valid

		Core::setCurrLang($_GET["lang"]);
		Sessions::set("lang", $_GET["lang"]);

		$data = array(
			"i18n" => Core::$L
		);
		break;
}

header("Content-Type: text/javascript");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

echo json_encode($data);
