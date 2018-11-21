<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\Pages;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$page_vars = array(
    "page"       => "modules",
    "page_url"   => Pages::getPageUrl("modules"),
    "head_title" => $LANG["word_modules"]
);

Themes::displayPage("admin/modules/index-new.tpl", $page_vars);
