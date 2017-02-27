<?php

if (class_exists('FormTools\\Installation')) {
    echo "!!!";
}

require("library.php");

if (isset($_POST["next"])) {
	header("location: step2.php");
	exit;
}

$page_vars = array();
$page_vars["step"] = 1;
$page_vars["available_languages"] = FormTools\Installation::getLanguages();
$page_vars["lang_file"] = $lang_file;

FormTools\Installation::displayPage("templates/index.tpl", $page_vars);
