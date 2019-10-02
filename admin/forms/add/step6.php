<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");
$root_url = Core::getRootUrl();
$LANG = Core::$L;

// delete any temporary Smart Fill uploaded files
if (Sessions::isNonEmpty("smart_fill_tmp_uploaded_files")) {
	foreach (Sessions::get("smart_fill_tmp_uploaded_files") as $file) {
        @unlink($file);
    }
}

Sessions::set("method", "");
$form_id = General::loadField("form_id", "add_form_form_id", "");
Sessions::clear("add_form_form_id");

$page_vars = array(
    "page" => "add_form6",
    "page_url" => Pages::getPageUrl("add_form6"),
    "head_title" => "{$LANG['phrase_add_form']} - {$LANG["phrase_step_6"]}",
    "form_id" => $form_id,
    "text_add_form_step_5_para"   => General::evalSmartyString($LANG["text_add_form_step_5_para_3"], array("editformlink" => "../edit.php?form_id={$form_id}")),
    "text_add_form_step_5_para_4" => General::evalSmartyString($LANG["text_add_form_step_5_para_4"], array("editformlink" => "../edit.php?form_id={$form_id}")),
    "uploading_files" => Sessions::get("uploading_files"),
    "head_css" => ""
);

$page_vars["head_string"] =<<< END
<link href="$root_url/global/codemirror/lib/codemirror.css" rel="stylesheet" type="text/css" />
<script src="$root_url/global/codemirror/lib/codemirror.js"></script>
<script src="$root_url/global/codemirror/mode/xml/xml.js"></script>
<script src="$root_url/global/codemirror/mode/smarty/smarty.js"></script>
<script src="$root_url/global/codemirror/mode/php/php.js"></script>
<script src="$root_url/global/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="$root_url/global/codemirror/mode/css/css.js"></script>
<script src="$root_url/global/codemirror/mode/javascript/javascript.js"></script>
<script src="$root_url/global/codemirror/mode/clike/clike.js"></script>
END;

Themes::displayPage("admin/forms/add/step6.tpl", $page_vars);
