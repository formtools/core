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
$request = array_merge($_GET, $_POST);
Core::setCurrLang($currentLang);

// the methods in this file are only available for incomplete installations
if (Installation::checkInstallationComplete(false)) {
	return;
}

$data = array(
	"error" => "unknown_action"
);

// if the user isn't on hitting the first or second page and they don't have sessions. The reason we allow the second
// page is that it contains important info about their environment and what
$missingPageParam = !isset($request["page"]) || !is_numeric($request["page"]);
if ($missingPageParam || (!Sessions::exists("installing") && $request["page"] > 1)) {
	General::returnJsonResponse($data, 403);
	exit;
}

switch ($request["action"]) {
	case "init":

		// the init request is called on every page refresh, returning all data in sessions. We store everything we need to
		// track for the whole installation process here. Individual page requests to update this info are handled
		// separately
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

			"dbSettings" => array(
				"dbHostname" => Sessions::getWithFallback("dbHostname", "localhost"),
				"dbName" => Sessions::getWithFallback("dbName", ""),
				"dbPort" => Sessions::getWithFallback("dbPort", "3306"),
				"dbUsername" => Sessions::getWithFallback("dbUsername", ""),
				"dbPassword" => Sessions::getWithFallback("dbPassword", ""),
				"dbTablePrefix" => Sessions::getWithFallback("dbTablePrefix", "ft_")
			),

			"folderSettings" => array(
				"useCustomCacheFolder" => Sessions::getWithFallback("folderSettings.useCustomCacheFolder", false),
				"customCacheFolder" => Sessions::getWithFallback("folderSettings.customCacheFolder", realpath("../cache/"))
			),

			"systemInfo" => array(
				"phpVersion" => phpversion(),
				"validPhpVersion" => Core::isValidPHPVersion(),
				"pdoAvailable" => extension_loaded("PDO"),
				"pdoMysqlAvailable" => extension_loaded("pdo_mysql"),
				"suhosinLoaded" => extension_loaded("suhosin"),
				"sessionsLoaded" => extension_loaded("session"),
				"uploadFolderWritable" => is_writable(realpath("../upload")),
				"cacheDirWritable" => is_writable(realpath("../cache/"))
			),

			"adminAccount" => array(
				"firstName" => Sessions::getWithFallback("firstName", ""),
				"lastName" => Sessions::getWithFallback("lastName", ""),
				"email" => Sessions::getWithFallback("email", ""),
				"username" => Sessions::getWithFallback("username", ""),
				"password" => Sessions::getWithFallback("password", "")
			)
		);
		break;

	case "selectLanguage":
		$list = Core::$translations->getList();
		$found = false;
		foreach ($list as $item) {
			if ($item->code === $_GET["lang"]) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			$data["error"] = "invalid_language";
			General::returnJsonResponse($data, 500);
			exit;
		} else {
			Core::setCurrLang($_GET["lang"]);
			Sessions::set("lang", $_GET["lang"]);
			$data = array(
				"i18n" => Core::$L
			);
		}
		break;

	case "saveCacheFolderSettings":
		if (isset($request["useCustomCacheFolder"])) {
			Sessions::set("folderSettings.useCustomCacheFolder", true);
			$customCacheFolder = $request["customCacheFolder"];
			$customCacheFolderExists = is_dir($customCacheFolder);

			if ($customCacheFolderExists) {
				$customCacheFolderWritable = is_writable($customCacheFolder);

				// if the custom cache folder is writable, great - create a blank index.html file in it just to prevent
				// servers configured to list the contents
				if ($customCacheFolderWritable) {
					$indexFile = "$customCacheFolder/index.html";
					if (!file_exists($indexFile)) {
						fopen($indexFile, "w");
					}
					$data = array();
					Sessions::set("folderSettings.customCacheFolder", $customCacheFolder);
				} else {
					$data["error"] = "invalid_folder_permissions";
					General::returnJsonResponse($data, 400);
					exit;
				}
			} else {
				$data["error"] = "invalid_folder";
				General::returnJsonResponse($data, 400);
				exit;
			}
		} else {
			Sessions::set("folderSettings.useCustomCacheFolder", false);
			Sessions::set("folderSettings.customCacheFolder", "");
			$data = array();
		}
		break;

	case "setCacheFolder":
		break;
}

Sessions::set("installing", true);

General::returnJsonResponse($data, 200);




