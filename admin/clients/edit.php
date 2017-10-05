<?php

require_once("../../global/library.php");

use FormTools\Clients;
use FormTools\Core;
use FormTools\General;

Core::init();
Core::$user->checkAuth("admin");

$LANG = Core::$L;

$client_id = General::loadField("client_id", "curr_client_id", "");
if (empty($client_id)) {
    General::redirect("index.php");
}

// figure out the "<< prev" and "next >>" links
$order   = General::loadField("order", "client_sort_order", "last_name-ASC");
$keyword = General::loadField("keyword", "client_search_keyword", "");
$status  = General::loadField("status", "client_search_status", "");

// store the current selected tab in memory
$page = General::loadField("page", "client_{$client_id}_page", "main");

$search_criteria = array(
	"order"     => $order,
	"keyword"   => $keyword,
	"status"    => $status
);

$links = Clients::getClientPrevNextLinks($client_id, $search_criteria);

$prev_tabset_link = (!empty($links["prev_account_id"])) ? "edit.php?page=$page&client_id={$links["prev_account_id"]}" : "";
$next_tabset_link = (!empty($links["next_account_id"])) ? "edit.php?page=$page&client_id={$links["next_account_id"]}" : "";

$same_page = General::getCleanPhpSelf();
$tabs = array(
	"main"     => array("tab_label" => $LANG["word_main"], "tab_link" => "{$same_page}?page=main&client_id={$client_id}"),
	"settings" => array("tab_label" => $LANG["word_settings"], "tab_link" => "{$same_page}?page=settings&client_id={$client_id}"),
	"forms"    => array("tab_label" => $LANG["word_forms"], "tab_link" => "{$same_page}?page=forms&client_id={$client_id}")
);

// start compiling the page vars here (save duplicate code!)
$page_vars = array();
$page_vars["tabs"] = $tabs;
$page_vars["show_tabset_nav_links"] = true;
$page_vars["prev_tabset_link"] = $prev_tabset_link;
$page_vars["next_tabset_link"] = $next_tabset_link;
$page_vars["prev_tabset_link_label"] = $LANG["phrase_prev_client"];
$page_vars["next_tabset_link_label"] = $LANG["phrase_next_client"];

$map = array(
    "main" => "page_main.php",
    "settings" => "page_settings.php",
    "forms" => "page_forms.php"
);
if (isset($map[$page])) {
    require_once($map[$page]);
} else {
    require_once($map["main"]);
}
