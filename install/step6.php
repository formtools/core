<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::initNoLogout();

Installation::installCoreFieldTypes();

$root_url = Core::getRootUrl();

$page = array(
    "step" => 6,
    "g_root_url" => Core::getRootUrl(),
    "head_string" => "<script src=\"{$root_url}/dist/scripts/bundle.js\"></script>"
);

Installation::displayPage("templates/step6.tpl", $page);
