<?php

/**
 * Used to provide some info to the installation script: localization.
 */
require_once("../global/library.php");

use FormTools\Core;
use FormTools\Database;
use FormTools\General;
use FormTools\Hooks;
use FormTools\Installation;
use FormTools\Modules;
use FormTools\Sessions;
use FormTools\Settings;
use FormTools\Themes;

Core::setHooksEnabled(false);
Core::startSessions();

$currentLang = General::loadField("lang", "lang", Core::getDefaultLang());
$request = array_merge($_GET, $_POST);
Core::setCurrentLang($currentLang);

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
if ($missingPageParam || (!Sessions::exists("fti.installing") && $request["page"] > 1)) {
	General::returnJsonResponse($data, 403);
	exit;
}

$coreTables = Core::getCoreTables();

$statusCode = 200;
switch ($request["action"]) {
	case "init":

		$defaultCacheFolder = realpath("../cache/");
		$selectedCacheFolder = Sessions::getWithFallback("fti.folderSettings.customCacheFolder", $defaultCacheFolder);

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
				"dbHostname" => Sessions::getWithFallback("fti.dbSettings.dbHostname", "localhost"),
				"dbName" => Sessions::getWithFallback("fti.dbSettings.dbName", ""),
				"dbPort" => Sessions::getWithFallback("fti.dbSettings.dbPort", "3306"),
				"dbUsername" => Sessions::getWithFallback("fti.dbSettings.dbUsername", ""),
				"dbPassword" => Sessions::getWithFallback("fti.dbSettings.dbPassword", ""),
				"dbTablePrefix" => Sessions::getWithFallback("fti.dbSettings.dbTablePrefix", "ft_"),
				"dbTablesCreated" => Sessions::getWithFallback("fti.dbSettings.dbTablesCreated", false),
				"dbTablesExist" => Sessions::getWithFallback("fti.dbSettings.dbTablesExist", false)
			),

			"folderSettings" => array(
				"uploadFolder" => realpath("../upload/"),
				"useCustomCacheFolder" => Sessions::getWithFallback("fti.folderSettings.useCustomCacheFolder", false),
				"defaultCacheFolder" => $defaultCacheFolder,
				"customCacheFolder" => $selectedCacheFolder
			),

			"systemInfo" => array(
				"phpVersion" => phpversion(),
				"validPhpVersion" => Core::isValidPHPVersion(),
				"pdoAvailable" => extension_loaded("PDO"),
				"pdoMysqlAvailable" => extension_loaded("pdo_mysql"),
				"suhosinLoaded" => extension_loaded("suhosin"),
				"sessionsLoaded" => extension_loaded("session"),
				"uploadFolderWritable" => is_writable(realpath("../upload")),
				"defaultCacheFolderWritable" => is_writable($defaultCacheFolder),
				"cacheFolderWritable" => is_writable($selectedCacheFolder)
			),

			"adminAccount" => array(
				"firstName" => Sessions::getWithFallback("fti.adminAccount.firstName", ""),
				"lastName" => Sessions::getWithFallback("fti.adminAccount.lastName", ""),
				"email" => Sessions::getWithFallback("fti.adminAccount.email", ""),
				"username" => Sessions::getWithFallback("fti.adminAccount.username", ""),
				"password" => Sessions::getWithFallback("fti.adminAccount.password", "")
			),

			"systemCheckPassed" => Sessions::getWithFallback("fti.systemCheckPassed", false),
			"configFileCreated" => file_exists(realpath("../global/config.php")),
			"accountCreated" => Sessions::getWithFallback("fti.accountCreated", false)
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
			Core::setCurrentLang($_GET["lang"]);
			Sessions::set("lang", $_GET["lang"]);
			$data = array(
				"i18n" => Core::$L
			);
		}
		break;

	case "saveCacheFolderSettings":
		if ($request["useCustomCacheFolder"] == "true") {
			Sessions::set("fti.folderSettings.useCustomCacheFolder", true);
			list($data, $statusCode) = Installation::verifyCustomCacheFolder($request["customCacheFolder"]);
		} else {
			if (!is_writable(realpath("../cache/"))) {
				$data["error"] = "invalid_cache_folder_permissions";
				$data["cacheFolderWritable"] = false;
				$statusCode = 400;
			} else {
				Sessions::set("fti.systemCheckPassed", true);
				Sessions::set("fti.folderSettings.useCustomCacheFolder", false);
				Sessions::set("fti.folderSettings.customCacheFolder", "");
				$data = array(
					"cacheFolderWritable" => true
				);
			}
		}

		if (!is_writable(realpath("../upload"))) {
			$data["error"] = "invalid_upload_folder_permissions";
			$data["uploadFolderWritable"] = false;
			$statusCode = 400;
		} else {
			$data["uploadFolderWritable"] = true;
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

		Sessions::set("fti.dbSettings.dbHostname", $dbHostname);
		Sessions::set("fti.dbSettings.dbName", $dbName);
		Sessions::set("fti.dbSettings.dbPort", $dbPort);
		Sessions::set("fti.dbSettings.dbUsername", $dbUsername);
		Sessions::set("fti.dbSettings.dbPassword", $dbPassword);
		Sessions::set("fti.dbSettings.dbTablePrefix", $dbTablePrefix);

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
					Sessions::set("fti.dbSettings.dbTablesCreated", true);
					Sessions::set("fti.dbSettings.dbTablesExist", true);
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
		$configFileGenerated = Installation::generateConfigFile($request["configFile"]);
		if ($configFileGenerated) {
			$data = array();
			Sessions::set("fti.configFileCreated", true);
		} else {
			$data = array(
				"error" => "error_creating_config_file"
			);
			$statusCode = 400;
		}
		break;

	case "checkConfigFileExists":
		if (file_exists(realpath("../global/config.php"))) {
			Sessions::set("fti.configFileCreated", true);
		} else {
			$data["error"] = "config_file_still_no_exists";
			$statusCode = 400;
		}
		break;

	case "saveAdminAccount":
		$lang = Core::getCurrentLang();
		Core::init(array(
			"init_user" => false,
			"auto_logout" => false
		));
		list($success, $error) = Installation::setAdminAccount($request, $lang);

		if ($success) {
			Core::init(array("auto_logout" => false));

			$data = array();
			Sessions::set("fti.accountCreated", true);

			// now set up the remainder of the script
			Installation::updateDatabaseSettings();
			Hooks::updateAvailableHooks();
			Modules::updateModuleList();
			Themes::updateThemeList();
			Installation::installCoreFieldTypes();
			Modules::installModules();
			Settings::set(array(
				"installation_complete" => "yes",
				"default_language" => $lang
			), "core");

			// send "Welcome to Form Tools!" email
			$email    = Sessions::get("fti.adminAccount.email");
			$username = Sessions::get("fti.adminAccount.username");
			Installation::sendWelcomeEmail($email, $username);
		} else {
			$data = array(
				"error" => $error
			);
		}
		break;
}

Sessions::set("fti.installing", true);

General::returnJsonResponse($data, $statusCode);




