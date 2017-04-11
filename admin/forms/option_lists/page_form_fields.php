<?php

use FormTools\Core;
use FormTools\Forms;
use FormTools\OptionLists;
use FormTools\Pages;
use FormTools\Themes;

$LANG = Core::$L;


// this tab displays all the form fields that use this field option group
$list_info = OptionLists::getOptionList($list_id);

$form_fields = array();
if ($num_fields > 0) {
	$form_fields = OptionLists::getFieldsUsingOptionList($list_id);
}

$forms = Forms::getForms();
$incomplete_forms = array();
foreach ($forms as $form_info) {
	if ($form_info["is_complete"] == "no") {
        $incomplete_forms[] = $form_info["form_id"];
    }
}

$page_vars["list_info"] = $list_info;
$page_vars["page_url"] = Pages::getPageUrl("edit_option_list");
$page_vars["head_title"] = $LANG["phrase_edit_option_list"];
$page_vars["num_fields_using_option_list"] = $num_fields;
$page_vars["incomplete_forms"] = $incomplete_forms;
$page_vars["form_fields"] = $form_fields;

Themes::displayPage("admin/forms/option_lists/edit.tpl", $page_vars);
