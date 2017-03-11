<?php

require_once("library.php");

// check valid PHP Version
$valid_php_version = (version_compare(phpversion(), $g_required_php_version, ">="));

// folder permissions
$upload_folder_writable            = is_writable(realpath(INSTALLATION_FOLDER . "/../upload"));
$default_theme_cache_dir_writable  = is_writable(realpath(INSTALLATION_FOLDER . "/../themes/default/cache"));

//"mysql_get_client_info" => mysql_get_client_info(),

$page_vars = array(
    "step" => 2,
    "valid_php_version" => $valid_php_version,
    "pdo_available" => extension_loaded("PDO"),
    "pdo_mysql_available" => extension_loaded("pdo_mysql"),
    "suhosin_loaded" => true, //extension_loaded("suhosin"),
    "sessions_loaded" => extension_loaded("session"),
    "phpversion" => phpversion(),
    "upload_folder_writable" => $upload_folder_writable,
    "default_theme_cache_dir_writable" => $default_theme_cache_dir_writable,
    "core_field_types_module_available" => FormTools\Installation::checkModuleAvailable("core_field_types"),
    "js_messages" => array(
        "word_error", "validation_incomplete_license_keys", "notify_invalid_license_keys",
        "word_close", "word_invalid", "word_verified", "word_continue"
    )
);

FormTools\Installation::displayPage("templates/step2.tpl", $page_vars);
