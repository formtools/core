<?php

use FormTools\Clients;
use FormTools\Core;
use FormTools\General;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");


$request = array_merge($_POST, $_GET);

$num_forms = Clients::getFormCount();
if (!empty($g_max_ft_forms) && $num_forms > $g_max_ft_forms) { // note it's not >=
	header("location: ../index.php");
	exit;
}

if (isset($request["code"]) || isset($request["direct"])) {
	$type = isset($request["code"]) ? "code" : "direct";
	header("location: step2.php?submission_type=$type");
	exit;
}

$form_id = General::loadField("form_id", "add_form_form_id", "");

$form_info = array();
if (!empty($form_id)) {
    $form_info = Forms::getForm($form_id);
}

// compile the header information
$page_values = array();
$page_vars["page"]     = "add_form1";
$page_vars["page_url"] = Pages::getPageUrl("add_form1");
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_1"]}";
$page_vars["form_info"] = $form_info;
$page_vars["head_js"] =<<< END
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

Themes::displayPage("admin/forms/add/step1.tpl", $page_vars);
