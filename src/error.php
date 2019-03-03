<?php

require_once("global/library.php");

use FormTools\Core;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

Core::init();
Core::setHooksEnabled(false);
Core::startSessions();
Core::initSmarty();

$page_vars = array(
    "page_url"      => Pages::getPageUrl("error"),
    "source"        => "error_page",
    "message_type"  => Sessions::getWithFallback("last_error_type", ""),
    "message"       => Sessions::getWithFallback("last_error", ""),
    "error_debug"   => Sessions::getWithFallback("last_error_debug", "")
);

// TODO this method may require more things than we have available. See the installation
Themes::displayPage("error.tpl", $page_vars);
