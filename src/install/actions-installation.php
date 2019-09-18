<?php

/**
 * Used to provide some info to the installation script: localization.
 */
require_once("../global/library.php");

use FormTools\Core;
use FormTools\Database;
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

$coreTables = Core::getCoreTables();

$statusCode = 200;
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
				"rootDir" => realpath(__DIR__ . "/../"),
				"rootUrl" => "../",
				"coreVersion" => Core::getCoreVersion()
			),

			"dbSettings" => array(
				"dbHostname" => Sessions::getWithFallback("dbSettings.dbHostname", "localhost"),
				"dbName" => Sessions::getWithFallback("dbSettings.dbName", ""),
				"dbPort" => Sessions::getWithFallback("dbSettings.dbPort", "3306"),
				"dbUsername" => Sessions::getWithFallback("dbSettings.dbUsername", ""),
				"dbPassword" => Sessions::getWithFallback("dbSettings.dbPassword", ""),
				"dbTablePrefix" => Sessions::getWithFallback("dbSettings.dbTablePrefix", "ft_"),
				"dbTablesCreated" => Sessions::getWithFallback("dbSettings.dbTablesCreated", false),
				"dbTablesExist" => Sessions::getWithFallback("dbSettings.dbTablesExist", false)
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
				"firstName" => Sessions::getWithFallback("adminAccount.firstName", ""),
				"lastName" => Sessions::getWithFallback("adminAccount.lastName", ""),
				"email" => Sessions::getWithFallback("adminAccount.email", ""),
				"username" => Sessions::getWithFallback("adminAccount.username", ""),
				"password" => Sessions::getWithFallback("adminAccount.password", "")
			),

			"configFileCreated" => Sessions::getWithFallback("configFileCreated", true)
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
			$statusCode = 500;
		} else {
			Core::setCurrLang($_GET["lang"]);
			Sessions::set("lang", $_GET["lang"]);
			$data = array(
				"i18n" => Core::$L
			);
		}
		break;

	case "saveCacheFolderSettings":
		if ($request["useCustomCacheFolder"] == "true") {
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
					$statusCode = 400;
				}
			} else {
				$data["error"] = "invalid_folder";
				$statusCode = 400;
			}
		} else {
			Sessions::set("folderSettings.useCustomCacheFolder", false);
			Sessions::set("folderSettings.customCacheFolder", "");
			$data = array();
		}
		break;

	case "saveDbSettings":
		$dbHostname = $request["dbHostname"];
		$dbName = $request["dbName"];
		$dbPort = $request["dbPort"];
		$dbUsername = $request["dbUsername"];
		$dbPassword = $request["dbPassword"];
		$dbTablePrefix = $request["dbTablePrefix"];
		$overwriteExistingTables = $request["overwrite"];

		Sessions::set("dbSettings.dbHostname", $dbHostname);
		Sessions::set("dbSettings.dbName", $dbName);
		Sessions::set("dbSettings.dbPort", $dbPort);
		Sessions::set("dbSettings.dbUsername", $dbUsername);
		Sessions::set("dbSettings.dbPassword", $dbPassword);
		Sessions::set("dbSettings.dbTablePrefix", $dbTablePrefix);

		list($success, $errorMsg) = Installation::checkConnection($dbHostname, $dbName, $dbPort, $dbUsername, $dbPassword);
		if ($success) {
			$db = new Database($dbHostname, $dbName, $dbPort, $dbUsername, $dbPassword, $dbTablePrefix);

			if ($overwriteExistingTables == "true") {
				Installation::deleteTables($db, $coreTables);
			}

			$existingTables = General::getExistingTables($db, $coreTables, $dbTablePrefix);

			if (empty($existingTables)) {
				list($success, $error) = Installation::createDatabase($db);

				// any time the user progresses from this step dole up the latest
				if ($success) {
					$data = array();
					Sessions::set("dbSettings.dbTablesCreated", true);
					Sessions::set("dbSettings.dbTablesExist", true);
				} else {
					$data = array(
						"error" => "db_creation_error",
						"response" => $error
					);
					$statusCode = 400;
				}
			} else {
				$data = array(
					"error" => "db_tables_already_exist",
					"tables" => $existingTables
				);
				$statusCode = 400;
			}
		} else {
			$data = array(
				"error" => "db_connection_error",
				"response" => $errorMsg
			);
			$statusCode = 400;
		}
		break;

	case "createConfigFile":
//		$configFileGenerated = Installation::generateConfigFile($request["configFile"]);
//		if ($configFileGenerated) {
//			$data = array();
//		} else {
			$data = array(
				"error" => "error_creating_config_file"
			);
			$statusCode = 400;
//		}
		break;

	case "saveAdminAccount":
		Core::initDatabase();
		list($success, $error) = Installation::setAdminAccount($request);
		if ($success) {
			$data = array();
		} else {
			$data = array(
				"error" => $error
			);
		}
		break;
}

Sessions::set("installing", true);

General::returnJsonResponse($data, $statusCode);




