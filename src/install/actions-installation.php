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
			),
			"installation" => array(
				"dbHostname" => Sessions::getWithFallback("dbHostname", "localhost"),
				"dbName" => Sessions::getWithFallback("dbName", ""),
				"dbPort" => Sessions::getWithFallback("dbPort", "3306"),
				"dbUsername" => Sessions::getWithFallback("dbUsername", ""),
				"dbPassword" => Sessions::getWithFallback("dbPassword", ""),
				"dbTablePrefix" => Sessions::getWithFallback("dbTablePrefix", "ft_")
			)
		);
		break;

	case "selectLanguage":
		// check the lang is valid
		$list = Core::$translations->getList();
		$found = false;
		foreach ($list as $item) {
			if ($item->code === $_GET["lang"]) {
				$found = true;
				break;
			}
		}
		if (!$found) {

			// TODO throw rest error
			// header()

		} else {
			Core::setCurrLang($_GET["lang"]);
			Sessions::set("lang", $_GET["lang"]);
			$data = array(
				"i18n" => Core::$L
			);
		}
		break;

	case "getSystemCheckResults":
		$upload_folder_writable = is_writable(realpath("../upload"));
		$cache_dir_writable = is_writable(realpath("../cache/"));

		$data = array(
			"cacheFolder" => "/cache/",
			"customCacheFolder" => realpath("../cache"), // !empty($customCache_folder) ? $custom_cache_folder : realpath("../cache/"),
			"useCustomCacheFolder" => true, // !empty($custom_cache_folder),
			"phpVersion" => phpversion(),
			"validPhpVersion" => Core::isValidPHPVersion(),
			"pdoAvailable" => extension_loaded("PDO"),
			"pdoMysqlAvailable" => extension_loaded("pdo_mysql"),
			"suhosinLoaded" => extension_loaded("suhosin"),
			"sessionsLoaded" => extension_loaded("session"),
			"uploadFolderWritable" => $upload_folder_writable,
			"cacheDirWritable" => $cache_dir_writable
		);
		break;

	case "setCacheFolder":
		break;
}

header("Content-Type: text/javascript");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

echo json_encode($data);






