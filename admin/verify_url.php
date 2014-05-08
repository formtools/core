<?php

/**
 * This generates a popup to let the user / reject a URL.
 */
require_once("../global/session_start.php");

$request = array_merge($_POST, $_GET);

$page_vars = array();
$page_vars["url"] = urldecode($request["url"]);
$page_vars["form_page"] = $request["form_page"];
$page_vars["js_messages"] = array("word_update", "validation_no_url", "validation_invalid_url");

ft_display_page("admin/verify_url.tpl", $page_vars);