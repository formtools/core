<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::initNoLogout();

Installation::installCoreFieldTypes();

$page = array(
    "step" => 6,
    "g_root_url" => Core::getRootUrl()
);

Installation::displayPage("templates/step6.tpl", $page);
