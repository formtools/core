<?php

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

$account_id = Sessions::get("account.account_id");

if (isset($_GET["reset"])) {
    Sessions::clear("form_sort_order");
    Sessions::clear("form_search_keyword");
}
$order   = General::loadField("order", "form_sort_order", "form_name-ASC");
$keyword = General::loadField("keyword", "form_search_keyword", "");

$search_criteria = array(
	"order"   => $order,
	"keyword" => $keyword
);

$num_client_forms = count(Clients::getClientForms($account_id));
//$forms            = Forms::searchForms(array("account_id" => $account_id, "is_admin" => false, ... plus $search_criteria);
$client_info      = Accounts::getAccountInfo($account_id);
$forms_page_default_message = General::evalSmartyString($client_info["settings"]["forms_page_default_message"]);

// ------------------------------------------------------------------------------------------

$LANG = Core::$L;

// compile header information
$page_vars = array();
$page_vars["head_title"] = General::evalSmartyString(Sessions::get("account.settings.page_titles"), array("page" => $LANG["word_forms"]));
$page_vars["page"]     = "client_forms";
$page_vars["page_url"] = Pages::getPageUrl("client_forms");
$page_vars["num_client_forms"] = $num_client_forms;
$page_vars["forms"] = $forms;
$page_vars["forms_page_default_message"] = $forms_page_default_message;
$page_vars["search_criteria"] = $search_criteria;
$page_vars["js_messages"] = array("phrase_open_form_in_new_tab_or_win", "word_close", "phrase_show_form");
$page_vars["head_js"] =<<< END
$(function() { ft.init_show_form_links(); });
END;

Themes::displayPage("clients/index.tpl", $page_vars);
