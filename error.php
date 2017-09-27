<?php

require_once("global/library.php");

use FormTools\Core;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

// TODO this needs to just start sessions. It'll break the install script when there are problems
Core::init();

$page_vars = array(
    "page_url"      => Pages::getPageUrl("error"),
    "source"        => "error_page",
    "message_type"  => Sessions::getWithFallback("last_error_type", ""),
    "message"       => Sessions::getWithFallback("last_error", ""),
    "error_debug"   => Sessions::getWithFallback("last_error_debug", "")
);

Themes::displayPage("error.tpl", $page_vars);
