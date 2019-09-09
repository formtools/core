<?php

/**
 * Used to provide some info to the installation script: localization.
 */
require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;
use FormTools\Sessions;

Core::setHooksEnabled(false);
Core::startSessions();

$currentLang = General::loadField("lang", "lang", Core::getDefaultLang());
Core::setCurrLang($currentLang);

// the methods in this file are only available for incomplete installations
if (Installation::checkInstallationComplete(false)) {
	return;
}

$data = array(
	"error" => "unknown_action"
);

// if the user isn't on hitting the first page and they don't have sessions, we
$restartInstallation = false;
$missingPageParam = !isset($_GET["page"]) || !is_numeric($_GET["page"]);
if ($missingPageParam || (!Sessions::exists("installing") && $_GET["page"] != 1)) {
	$restartInstallation = true;
	$data["restartInstallation"] = $restartInstallation;
	General::returnJsonResponse($data, 403);
	exit;
}

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

	// Step 2
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

	// Step 2 when the user clicks continue. This checks any custom cache folder settings the user entered are valid
	case "saveCacheFolderSettings":

//		if (isset($request["useCustomCacheFolder"])) {
//			$custom_cache_folder = $request["custom_cache_folder"];
//			$custom_cache_folder_exists = is_dir($custom_cache_folder);
//
//			if ($custom_cache_folder_exists) {
//				$custom_cache_folder_writable = is_writable($custom_cache_folder);
//
//				// if the custom cache folder is writable, great - create a blank index.html file in it just to prevent
//				// servers configured to list the contents
//				if ($custom_cache_folder_writable) {
//					$index_file = "$custom_cache_folder/index.html";
//					if (!file_exists($index_file)) {
//						fopen($index_file, "w");
//					}
//					Sessions::set("g_custom_cache_folder", $custom_cache_folder);
//				} else {
//					$success = false;
//					$message = "The custom cache folder you entered needs to have full read-write permissions.";
//				}
//			} else {
//				$success = false;
//				$message = "The custom cache folder you entered does not exist.";
//			}
//		} else {
//			Sessions::set("g_custom_cache_folder", "");
//		}

		break;

	case "setCacheFolder":
		break;
}

Sessions::set("installing", true);

General::returnJsonResponse($data, 200);




