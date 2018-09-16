<?php

use FormTools\Packages;

require_once("../global/library.php");

$url = "http://localhost:8888/formtools-site/cdn.formtools.org/modules/arbitrary_settings-2.0.2.zip";

print_r(Packages::downloadComponentZip($url));
