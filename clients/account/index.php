<?php

use FormTools\Core;
use FormTools\General;


Core::init();
Core::$user->checkAuth("client");


$request = array_merge($_POST, $_GET);
$account_id = $_SESSION["ft"]["account"]["account_id"];

// store the current selected tab in memory
$page = General::loadField("page", "account_page", "main");

$same_page = General::getCleanPhpSelf();
$tabs = array(
	"main"     => array("tab_label" => $LANG["word_main"], "tab_link" => "{$same_page}?page=main"),
	"settings" => array("tab_label" => $LANG["word_settings"], "tab_link" => "{$same_page}?page=settings")
);

// ------------------------------------------------------------------------------------------

switch ($page)
{
	case "main":
		include("page_main.php");
		break;
	case "settings":
		include("page_settings.php");
		break;
	default:
		include("page_main.php");
		break;
}
