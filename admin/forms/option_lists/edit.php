<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\OptionLists;

Core::init();
Core::$user->checkAuth("admin");


$request = array_merge($_POST, $_GET);
$LANG = Core::$L;

$list_id = General::loadField("list_id", "option_list_id", "");
$page    = General::loadField("page", "field_option_groups_tab", "main");
$order   = General::loadField("order", "option_list_order");

// used to display the total count of fields using this option list on the Form Fields tab.
$num_fields = OptionLists::getNumFieldsUsingOptionList($list_id);

if (empty($list_id)) {
    General::redirect("index.php");
    exit;
}

$links = OptionLists::getOptionListPrevNextLinks($list_id, $order);
$prev_tabset_link = (!empty($links["prev_option_list_id"])) ? "edit.php?page=$page&list_id={$links["prev_option_list_id"]}" : "";
$next_tabset_link = (!empty($links["next_option_list_id"])) ? "edit.php?page=$page&list_id={$links["next_option_list_id"]}" : "";


$same_page = General::getCleanPhpSelf();
$tabs = array(
	"main" => array(
		"tab_label" => $LANG["word_main"],
		"tab_link" => "{$same_page}?page=main"
	),
	"form_fields" => array(
		"tab_label" => "{$LANG["phrase_form_fields"]} ($num_fields)",
		"tab_link" => "{$same_page}?page=form_fields"
	)
);

$page_vars = array();
$page_vars["page"] = $page;
$page_vars["unique_page_id"] = "edit_option_list_main_tab";
$page_vars["tabs"] = $tabs;
$page_vars["show_tabset_nav_links"] = true;
$page_vars["prev_tabset_link"] = $prev_tabset_link;
$page_vars["next_tabset_link"] = $next_tabset_link;

switch ($page) {
	case "main":
		require("page_main.php");
		break;
	case "form_fields":
		require("page_form_fields.php");
		break;

	default:
		require("page_main.php");
		break;
}
