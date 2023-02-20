<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\Forms;
use FormTools\General;
use FormTools\Themes;
use FormTools\Pages;
use FormTools\Sessions;

Core::init();
Core::$user->checkAuth("client");

if (isset($request["external"])) {
    General::redirect("step1.php");
} else if (isset($request["internal"])) {
    General::redirect("internal.php");
}

if (isset($request["new_form"])) {
    Sessions::clear("add_form_form_id");
}

$LANG = Core::$L;
$num_forms = Forms::getFormCount();
$max_forms = Core::getMaxForms();

$head_js =<<< END
$(function() {
    $("#select_external").bind("click", function() {
        var continue_decoded = $("<div />").html("{$LANG["word_continue_rightarrow"]}").text();
        ft.create_dialog({
            dialog:     $("#add_external_form_dialog"),
            title:      "{$LANG["word_checklist"]}",
            popup_type: "info",
            min_width:  600,
            buttons: [{
                text: continue_decoded,
                click: function() {
                    window.location = "step1.php";
                }
            },
            {
                text: "{$LANG["word_cancel"]}",
                click: function() {
                    $(this).dialog("close");
                }
            }]
        });
    });
});
END;

$page = array(
    "page"     => "add_form_choose_type",
    "page_url" => Pages::getPageUrl("add_form_choose_type"),
    "head_title" => "{$LANG['phrase_add_form']}",
    "max_forms_reached" => !empty($max_forms) && $num_forms >= $max_forms,
    "max_forms" => $max_forms,
    "notify_max_forms_reached" => General::evalSmartyString($LANG["notify_max_forms_reached"], array("max_forms" => $max_forms)),
    "head_js" => $head_js
);

Themes::displayPage("admin/forms/add/index.tpl", $page);
