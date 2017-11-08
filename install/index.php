<?php

/**
 * Step 1: select the interface language.
 */

require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;
use FormTools\Sessions;

Core::setHooksEnabled(false);
Core::startSessions();

// note we reset sessions here. This prevents weird things happening when a new installation is taking place
// while an old orphaned FT session exists, containing who-knows-what
Sessions::clearAll();

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
