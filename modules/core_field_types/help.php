<?php

require("../../global/library.php");

use FormTools\Core;
use FormTools\Modules;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

Modules::initModulePage();

Themes::displayModulePage("templates/help.tpl");
