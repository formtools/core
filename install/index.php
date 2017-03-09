<?php

require_once("library.php");

if (isset($_POST["next"])) {
	header("location: step2.php");
	exit;
}

$page_vars = array(
    "step" => 1,
    "available_languages" => FormTools\Translations::getList(),
    "lang_file" => $lang_file
);

FormTools\Installation::displayPage("templates/index.tpl", $page_vars);
