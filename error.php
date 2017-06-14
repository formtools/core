<?php

require_once("global/library.php");

use FormTools\Core;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

Core::startSessions();

$page_vars = array();
$page_vars["page_url"]      = Pages::getPageUrl("error");
$page_vars["source"]        = "error_page";
$page_vars["message_type"]  = Sessions::getWithFallback("last_error_type", "");
$page_vars["message"]       = Sessions::getWithFallback("last_error", "");
$page_vars["error_debug"]   = Sessions::getWithFallback("last_error_debug", "");

Themes::displayPage("error.tpl", $page_vars);
