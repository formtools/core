<?php

// our Smarty instance, used for rendering the webpages
$g_smarty = new Smarty();

$config_file_exists = false;
if (is_file(__DIR__ . "/config.php")) {
	$config_file_exists = true;
	include_once(__DIR__ . "/config.php");
}


if ($config_file_exists) {

	// if the config file exists, we can assume the user isn't installed
	//$g_link = ft_db_connect();

	// load the appropriate language file
	$g_language = ft_get_ui_language();
	require_once(__DIR__ . "/lang/{$g_language}.php");

	if (isset($_GET["logout"])) {
        Core::$user->logout();
    }
}
