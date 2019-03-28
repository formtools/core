<?php

require_once("../library.php");

use FormTools\Core;
use FormTools\FieldTypes;

// currently this file is told to never cache. Need a better solution in the long term
header("Content-Type: text/javascript");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

Core::initNoSessions();
echo FieldTypes::getFieldTypeResources("js");
