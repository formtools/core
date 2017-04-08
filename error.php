<?php

require_once("global/session_start.php");

$page_vars = array();
$page_vars["page_url"]      = Pages::getPageUrl("error");
$page_vars["source"]        = "error_page";
$page_vars["message_type"]  = isset($_SESSION["ft"]["last_error_type"])  ? $_SESSION["ft"]["last_error_type"] : "";
$page_vars["message"]       = isset($_SESSION["ft"]["last_error"])       ? $_SESSION["ft"]["last_error"] : "";
$page_vars["error_debug"]   = isset($_SESSION["ft"]["last_error_debug"]) ? $_SESSION["ft"]["last_error_debug"] : "";

Themes::displayPage("error.tpl", $page_vars);
