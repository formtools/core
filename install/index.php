<?php

require_once("library.php");

use FormTools\Installation;
use FormTools\Translations;


if (isset($_POST["next"])) {
	header("location: step2.php");
	exit;
}

$page = array(
    "step" => 1,
    "available_languages" => Translations::getList(),
    "lang_file" => $lang_file
);

Installation::displayPage("templates/index.tpl", $page);
