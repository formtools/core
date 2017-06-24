<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\Modules;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

Modules::initModulePage();

Themes::displayModulePage("templates/index.tpl");
