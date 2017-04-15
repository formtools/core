<?php

use FormTools\Core;
use FormTools\Modules;
use FormTools\Themes;

require_once("../../global/library.php");

Core::init();

Modules::initModulePage();

Themes::displayModulePage("templates/index.tpl");
