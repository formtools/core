<?php

require("../../global/library.php");

use FormTools\Modules;
use FormTools\Themes;

Modules::initModulePage();

Themes::displayModulePage("templates/help.tpl");
