<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::init();


$config_file = Installation::getConfigFileContents();

$config_file_generated = "";
if (isset($_POST["generate_file"])) {
	$config_file_generated = Installation::generateConfigFile($config_file);
	if ($config_file_generated) {
		header("location: step5.php");
		exit;
	}
}

if (isset($_POST["check_config_contents"])) {
	list ($g_success, $g_message) = Installation::checkConfigFileExists();

	// great: the user has managed to manually create the file. Continue to the next step.
	if ($g_success) {
		header("location: step5.php");
		exit;
	}
	$config_file_generated = false;
}

$page = array(
    "step" => 4,
    "config_file" => $config_file,
    "config_file_generated" => $config_file_generated
);

Installation::displayPage("templates/step4.tpl", $page);
