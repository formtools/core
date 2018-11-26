<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\Pages;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");
$root_url = Core::getRootUrl();

$page_vars = array(
    "page"       => "modules",
    "page_url"   => Pages::getPageUrl("modules"),
    "head_title" => $LANG["word_modules"],
	"head_string" => "<script src=\"$root_url/global/scripts/bundle.js\"></script>"
);

Themes::displayPage("admin/modules/index-new.tpl", $page_vars);
