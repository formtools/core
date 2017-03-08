<?php

require_once("library.php");

if (isset($_POST["next"])) {
	header("location: step2.php");
	exit;
}

$page_vars = array();
$page_vars["step"] = 1;
$page_vars["available_languages"] = FormTools\Translations::getList();
$page_vars["lang_file"] = $lang_file;

FormTools\Installation::displayPage("templates/index.tpl", $page_vars);
