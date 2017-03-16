<?php

require_once("library.php");

use FormTools\Core;
use FormTools\Installation;
use FormTools\General;

// folder permissions
$upload_folder_writable           = is_writable(realpath(INSTALLATION_FOLDER . "/../upload"));
$default_theme_cache_dir_writable = is_writable(realpath(INSTALLATION_FOLDER . "/../themes/default/cache"));

$page = array(
    "step" => 2,
    "phpversion" => phpversion(),
    "valid_php_version" => Core::isValidPHPVersion(),
    "pdo_available" => extension_loaded("PDO"),
    "pdo_mysql_available" => extension_loaded("pdo_mysql"),
    "suhosin_loaded" => extension_loaded("suhosin"),
    "sessions_loaded" => extension_loaded("session"),
    "upload_folder_writable" => $upload_folder_writable,
    "default_theme_cache_dir_writable" => $default_theme_cache_dir_writable,
    "core_field_types_module_available" => General::checkModuleAvailable("core_field_types"),
    "js_messages" => array(
        "word_error", "validation_incomplete_license_keys", "notify_invalid_license_keys",
        "word_close", "word_invalid", "word_verified", "word_continue"
    )
);

Installation::displayPage("templates/step2.tpl", $page);
