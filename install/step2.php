<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;
use FormTools\Sessions;

Core::setHooksEnabled(false);
Core::startSessions();

Installation::checkInstallationComplete();

$success = true;
$message = "";
$custom_cache_folder = "";
if (isset($request["next"])) {

	if (isset($request["use_custom_cache_folder"])) {
		$custom_cache_folder = $request["custom_cache_folder"];
		$custom_cache_folder_exists = is_dir($custom_cache_folder);

		if ($custom_cache_folder_exists) {
			$custom_cache_folder_writable = is_writable($custom_cache_folder);

			// if the custom cache folder is writable, great - create a blank index.html file in it just to prevent
			// servers configured to list the contents
			if ($custom_cache_folder_writable) {
				$index_file = "$custom_cache_folder/index.html";
				if (!file_exists($index_file)) {
					fopen($index_file, "w");
				}
				Sessions::set("g_custom_cache_folder", $custom_cache_folder);
			} else {
				$success = false;
				$message = "The custom cache folder you entered needs to have full read-write permissions.";
			}
		} else {
			$success = false;
			$message = "The custom cache folder you entered does not exist.";
		}
	} else {
		Sessions::set("g_custom_cache_folder", "");
	}

	if ($success) {
		header("location: step3.php");
		exit;
	}
}

Core::initSmarty();
Core::setCurrLang(General::loadField("lang_file", "lang_file", Core::getDefaultLang()));

$upload_folder_writable = is_writable(realpath("../upload"));
$cache_dir_writable = is_writable(realpath("../cache/"));

$page = array(
	"g_success" => $success,
    "g_message" => $message,
    "step" => 2,
    "cache_folder" => "/cache/",
    "custom_cache_folder" => !empty($custom_cache_folder) ? $custom_cache_folder : realpath("../cache/"),
	"use_custom_cache_folder" => !empty($custom_cache_folder),
    "phpversion" => phpversion(),
    "valid_php_version" => Core::isValidPHPVersion(),
    "pdo_available" => extension_loaded("PDO"),
    "pdo_mysql_available" => extension_loaded("pdo_mysql"),
    "suhosin_loaded" => extension_loaded("suhosin"),
    "sessions_loaded" => extension_loaded("session"),
    "upload_folder_writable" => $upload_folder_writable,
    "cache_dir_writable" => $cache_dir_writable,
    "js_messages" => array(
        "word_error", "word_close", "word_continue"
    )
);

$page["head_string"] =<<< END
<script src="files/installation.js"></script>
END;

Installation::displayPage("templates/step2.tpl", $page);
