<?php

/**
 * Step 1: select the interface language.
 */

require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::startSessions();
Core::initSmarty();
Core::setCurrLang(General::loadField("lang_file", "lang_file", Core::getDefaultLang()));

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
