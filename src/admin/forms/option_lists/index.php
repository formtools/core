<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\OptionLists;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$option_list_page = General::loadField("page", "option_list_page", 1);
$num_option_lists_per_page = Sessions::get("settings.num_option_lists_per_page");

$order = General::loadField("order", "option_list_order");

$success = true;
$message = "";
if (isset($_GET["delete"])) {
    list($success, $message) = OptionLists::deleteOptionList($_GET["delete"]);
}
if (!is_numeric($option_list_page)) {
    $option_list_page = 1;
}

// creates a new option list, optionally based on an existing once
if (isset($request["add_option_list"])) {
	$duplicate_list_id = "";
	if (isset($request["create_option_list_from_list_id"]) && !empty($request["create_option_list_from_list_id"])) {
        $duplicate_list_id = $request["create_option_list_from_list_id"];
    }
	$field_ids = array();
	if (isset($request["field_id"])) {
        $field_ids[] = $request["field_id"];
    }
	$list_id = OptionLists::duplicateOptionList($duplicate_list_id, $field_ids);

    General::redirect("edit.php?page=main&list_id=$list_id");
}

// one additional check. If a user was on page 2 and just deleted (say) option list #11 and there are 10 per page, the
// visible page should be now be 1
$total_num_option_lists = OptionLists::getNumOptionLists();
$total_pages = ceil($total_num_option_lists / $num_option_lists_per_page);
if ($option_list_page > $total_pages) {
    $option_list_page = $total_pages;
}

$list_info = OptionLists::getList(array(
    "page" => $option_list_page,
    "order" => $order,
    "per_page" => Sessions::get("settings.num_option_lists_per_page")
));


$num_option_lists = $list_info["num_results"];
$option_lists     = $list_info["results"];

$updated_field_option_groups = array();
$updated_option_lists = array();
foreach ($option_lists as $option_list) {
	$list_id = $option_list["list_id"];

	// add the number of fields that use this option group
	$option_list["num_fields"] = OptionLists::getNumFieldsUsingOptionList($list_id);
	if ($option_list["num_fields"] > 0) {
		$option_list["fields"] = OptionLists::getFieldsUsingOptionList($list_id, array("group_by_form" => true));
	}

	// add the total number of options in this group
	$option_list["num_option_list_options"] = OptionLists::getNumOptionsInOptionList($list_id);
	$updated_option_lists[] = $option_list;
}

$all_option_lists = OptionLists::getList(array(
    "per_page" => Sessions::get("settings.num_option_lists_per_page")
));

$root_url = Core::getRootUrl();
$LANG = Core::$L;

$page = array(
    "page" => "option_lists",
    "g_success" => $success,
    "g_message" => $message,
    "text_option_list_page" => General::evalSmartyString($LANG["text_option_list_page"], array("link" => "../add/step1.php")),
    "page_url" => Pages::getPageUrl("option_lists"),
    "head_title" => $LANG["phrase_option_lists"],
    "option_lists" => $updated_option_lists,
    "num_option_lists" => $num_option_lists,
    "all_option_lists" => $all_option_lists["results"],
    "order" => $order,
    "js_messages" => array(
        "validation_delete_non_empty_option_list", "confirm_delete_option_list", "phrase_please_confirm",
        "word_yes", "word_no", "word_edit", "word_remove"
    ),
    "pagination" => General::getPageNav($num_option_lists, $num_option_lists_per_page, $option_list_page),
    "head_string" => "<script src=\"$root_url/global/scripts/manage_option_lists.js\"></script>"
);

Themes::displayPage("admin/forms/option_lists/index.tpl", $page);
