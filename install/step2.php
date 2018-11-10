<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::startSessions();

Installation::checkInstallationComplete();

Core::initSmarty();
Core::setCurrLang(General::loadField("lang_file", "lang_file", Core::getDefaultLang()));

// folder permissions
$upload_folder_writable = is_writable(realpath("../upload"));
$cache_dir_writable = is_writable(realpath("../cache"));

$page = array(
    "step" => 2,
    "phpversion" => phpversion(),
    "valid_php_version" => Core::isValidPHPVersion(),
    "pdo_available" => extension_loaded("PDO"),
    "pdo_mysql_available" => extension_loaded("pdo_mysql"),
    "ziparchive_available" => class_exists("ZipArchive"),
    "suhosin_loaded" => extension_loaded("suhosin"),
    "sessions_loaded" => extension_loaded("session"),
    "upload_folder_writable" => $upload_folder_writable,
    "cache_dir_writable" => $cache_dir_writable,
    "js_messages" => array(
        "word_error", "validation_incomplete_license_keys", "notify_invalid_license_keys",
        "word_close", "word_invalid", "word_verified", "word_continue"
    )
);

Installation::displayPage("templates/step2.tpl", $page);
