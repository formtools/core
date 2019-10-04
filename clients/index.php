<?php

require_once("../global/library.php");

use FormTools\Accounts;
use FormTools\Clients;
use FormTools\Core;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("client");

Forms::cacheFormStats();

$LANG = Core::$L;
$account_id = Sessions::get("account.account_id");

if (isset($_GET["reset"])) {
	Sessions::clear("form_sort_order");
	Sessions::clear("form_search_keyword");
}
$order = General::loadField("order", "form_sort_order", "form_name-ASC");
$keyword = General::loadField("keyword", "form_search_keyword", "");

$search_criteria = array(
	"order" => $order,
	"keyword" => $keyword
);

$num_client_forms = count(Clients::getClientForms($account_id));
$client_info = Accounts::getAccountInfo($account_id);

$forms_page_default_message = General::evalSmartyString($client_info["settings"]["forms_page_default_message"]);
$forms = Forms::searchForms(array(
	"account_id" => $account_id,
	"is_admin" => false,
	"order" => $order,
	"keyword" => $keyword,
	"status" => ""
));

// compile header information
$page_vars = array(
	"head_title" => General::evalSmartyString(Sessions::get("account.settings.page_titles"), array("page" => $LANG["word_forms"])),
	"page" => "client_forms",
	"page_url" => Pages::getPageUrl("client_forms"),
	"num_client_forms" => $num_client_forms,
	"forms" => $forms,
	"forms_page_default_message" => $forms_page_default_message,
	"search_criteria" => $search_criteria,
	"js_messages" => array("phrase_open_form_in_new_tab_or_win", "word_close", "phrase_show_form"),
	"head_js" => '$(function() { ft.init_show_form_links(); })'
);

Themes::displayPage("clients/index.tpl", $page_vars);
