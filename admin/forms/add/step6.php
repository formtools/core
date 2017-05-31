<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");


// delete any temporary Smart Fill uploaded files
if (isset($_SESSION["ft"]["smart_fill_tmp_uploaded_files"]) && !empty($_SESSION["ft"]["smart_fill_tmp_uploaded_files"])) {
	foreach ($_SESSION["ft"]["smart_fill_tmp_uploaded_files"] as $file) {
        @unlink($file);
    }
}

$_SESSION["ft"]["method"] = "";
$form_id = General::loadField("form_id", "add_form_form_id", "");
unset($_SESSION["ft"]["add_form_form_id"]);

// ------------------------------------------------------------------------------------------------

$LANG = Core::$L;

$page_vars = array(
    "page" => "add_form6",
    "page_url" => Pages::getPageUrl("add_form6"),
    "head_title" => "{$LANG['phrase_add_form']} - {$LANG["phrase_step_5"]}",
    "form_id" => $form_id,
    "text_add_form_step_5_para"   => General::evalSmartyString($LANG["text_add_form_step_5_para_3"], array("editformlink" => "../edit.php?form_id={$form_id}")),
    "text_add_form_step_5_para_4" => General::evalSmartyString($LANG["text_add_form_step_5_para_4"], array("editformlink" => "../edit.php?form_id={$form_id}")),
    "uploading_files" => $_SESSION["ft"]["uploading_files"],
    "head_css" => ""
);

Themes::displayPage("admin/forms/add/step6.tpl", $page_vars);
