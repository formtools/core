<?php

use FormTools\Clients;
use FormTools\Core;
use FormTools\General;
use FormTools\Themes;


Core::init();
Core::$user->checkAuth("admin");

_ft_cache_form_stats();
if (isset($_GET["reset"])) {
	$_SESSION["ft"]["form_sort_order"] = "";
	$_SESSION["ft"]["form_search_keyword"] = "";
	$_SESSION["ft"]["form_search_status"] = "";
	$_SESSION["ft"]["form_search_client_id"] = "";
}
$order     = General::loadField("order", "form_sort_order", "form_id-DESC");
$keyword   = General::loadField("keyword", "form_search_keyword", "");
$status    = General::loadField("status", "form_search_status", "");
$client_id = General::loadField("client_id", "form_search_client_id", "");

$search_criteria = array(
	"order"     => $order,
	"keyword"   => $keyword,
	"status"    => $status,
	"client_id" => $client_id
);

$num_forms = Clients::getFormCount();
$forms     = ft_search_forms($client_id, true, $search_criteria);
$clients   = Clients::getList();


// compile template info
$page_vars = array(
    "page" => "admin_forms",
    "page_url" => ft_get_page_url("admin_forms"),
    "head_title" => $LANG["word_forms"],
    "has_client" => (count($clients) > 0) ? true : false,
    "num_forms" => $num_forms,
    "max_forms_reached" => (!empty($g_max_ft_forms) && $num_forms >= $g_max_ft_forms) ? true : false,
    "max_forms" => $g_max_ft_forms,
    "notify_max_forms_reached" => ft_eval_smarty_string($LANG["notify_max_forms_reached"], array("max_forms" => $g_max_ft_forms)),
    "forms" => $forms,
    "order" => $order,
    "clients" => $clients,
    "search_criteria" => $search_criteria,
    "pagination" => ft_get_dhtml_page_nav(count($forms), $_SESSION["ft"]["settings"]["num_forms_per_page"], 1),
    "js_messages" => array("word_remove", "word_edit", "phrase_open_form_in_new_tab_or_win", "word_close", "phrase_show_form")
);

$page_vars["head_js"] =<<< END
$(function() {
  ft.init_show_form_links();
});
END;

Themes::displayPage("admin/forms/index.tpl", $page_vars);
