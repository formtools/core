<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Sessions;

Core::init();
Core::$user->checkAuth("client");

$LANG = Core::$L;

$account_id = Sessions::get("account.account_id");

// store the current selected tab in memory
$page = General::loadField("page", "account_page", "main");

$same_page = General::getCleanPhpSelf();
$tabs = array(
	"main"     => array("tab_label" => $LANG["word_main"], "tab_link" => "{$same_page}?page=main"),
	"settings" => array("tab_label" => $LANG["word_settings"], "tab_link" => "{$same_page}?page=settings")
);

$map = array(
    "main" => "page_main.php",
    "settings" => "page_settings.php"
);

if (array_key_exists($page, $map)) {
    require_once($map[$page]);
} else {
    require_once($map["main"]);
}
