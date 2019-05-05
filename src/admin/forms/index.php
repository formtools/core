<?php

require_once("../../global/library.php");

use FormTools\Clients;
use FormTools\Core;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Themes;
use FormTools\Sessions;

Core::init();
Core::$user->checkAuth("admin");

Forms::cacheFormStats();

if (isset($_GET["reset"])) {
	Sessions::set("form_sort_order", "");
	Sessions::set("form_search_keyword", "");
	Sessions::set("form_search_status", "");
	Sessions::set("form_search_client_id", "");
}
$order = General::loadField("order", "form_sort_order", "form_id-DESC");
$keyword = General::loadField("keyword", "form_search_keyword", "");
$status = General::loadField("status", "form_search_status", "");
$client_id = General::loadField("client_id", "form_search_client_id", "");

$search_criteria = array(
	"is_admin" => true,
	"order" => $order,
	"keyword" => $keyword,
	"status" => $status,
	"account_id" => $client_id
);

$clients = Clients::getList();
$num_forms = Forms::getFormCount();
$forms = Forms::searchForms($search_criteria);
$max_forms = Core::getMaxForms();
$LANG = Core::$L;

$page = array(
	"page" => "admin_forms",
	"page_url" => Pages::getPageUrl("admin_forms"),
	"hasInvalidCacheFolder" => Core::hasInvalidCacheFolder(),
	"head_title" => $LANG["word_forms"],
	"has_client" => count($clients) > 0,
	"num_forms" => $num_forms,
	"max_forms_reached" => !empty($max_forms) && $num_forms >= $max_forms,
	"max_forms" => $max_forms,
	"notify_max_forms_reached" => General::evalSmartyString($LANG["notify_max_forms_reached"], array("max_forms" => $max_forms)),
	"forms" => $forms,
	"order" => $order,
	"clients" => $clients,
	"search_criteria" => $search_criteria,
	"pagination" => General::getJsPageNav(count($forms), Sessions::get("settings.num_forms_per_page"), 1),
	"js_messages" => array("word_remove", "word_edit", "phrase_open_form_in_new_tab_or_win", "word_close", "phrase_show_form"),
	"head_js" => "$(function() { ft.init_show_form_links(); });"
);

Themes::displayPage("admin/forms/index.tpl", $page);
