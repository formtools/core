<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$num_forms = Forms::getFormCount();
$max_forms = Core::getMaxForms();
if (!empty($max_forms) && $num_forms > $max_forms) { // note it's not >=
    General::redirect("../");
}

if (isset($request["code"]) || isset($request["direct"])) {
	$type = isset($request["code"]) ? "code" : "direct";
    General::redirect("step2.php?submission_type=$type");
}

$form_id = General::loadField("form_id", "add_form_form_id", "");

$form_info = array();
if (!empty($form_id)) {
    $form_info = Forms::getForm($form_id);
}
$LANG = Core::$L;

$head_js =<<< END
var rules = [];
var page_ns = {};
page_ns.current_section = null;
page_ns.show_section = function(section) {
  if (page_ns.current_section != null) {
    $("#" + page_ns.current_section).fadeOut({ duration: 400 });
    setTimeout(function() { $("#" + section).fadeIn({ duration: 400, }); }, 410);
  } else {
    $("#" + section).fadeIn({ duration: 400 });
  }
  page_ns.current_section = section;
  return false;
}
END;

$page = array(
    "page" => "add_form1",
    "page_url" => Pages::getPageUrl("add_form1"),
    "has_api" => Core::isAPIAvailable(),
    "head_title" => "{$LANG['phrase_add_form']} - {$LANG["phrase_step_1"]}",
    "form_info" => $form_info,
    "head_js" => $head_js
);

Themes::displayPage("admin/forms/add/step1.tpl", $page);
