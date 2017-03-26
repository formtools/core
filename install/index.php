<?php

/**
 * Step 1: select the interface language.
 */

require_once("library.php");

use FormTools\Core;
use FormTools\Installation;

Core::init();

if (isset($_POST["next"])) {
	header("location: step2.php");
	exit;
}

$page = array(
    "step" => 1,
    "available_languages" => Core::$translations->getList(),
    "lang" => Core::getCurrentLang()
);

Installation::displayPage("templates/index.tpl", $page);
