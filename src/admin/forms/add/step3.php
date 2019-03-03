<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$root_url = Core::getRootUrl();
$LANG = Core::$L;

$form_id = General::loadField("form_id", "add_form_form_id", "");

if (isset($_POST["submission_type"])) {
    Forms::setSubmissionType($form_id, $_POST["submission_type"]);
}

// if returning from a later stage and the user wants to resubmit the test submission, update the form
if (isset($_GET['uninitialize']) && $_GET['uninitialize'] == 1) {
    Forms::uninitializeForm($form_id);
}

// retrieve the form info
$form_info = Forms::getForm($form_id);

// determine the input field values for cutting & pasting
$hidden_fields = '<input type="hidden" name="form_tools_initialize_form" value="1" />' . "\n"
	. '<input type="hidden" name="form_tools_form_id" value="' . $form_id . '" />';
$form_tag = '<form action="' . $root_url . '/process.php" method="post"';

if (Sessions::get("uploading_files")== "yes") {
    $form_tag .= ' enctype="multipart/form-data"';
}

$form_tag .= '>';

$replacement_info = array("linktoform" => "{$form_info['form_url']}");
$direct_form_para_2 = General::evalSmartyString($LANG["text_add_form_step_2_para_3"], $replacement_info);

$replacement_info = array(
	"varname" => "<b>\$submission_hash</b>",
	"postvar" => "\$_POST",
	"sessionvar" => "\$_SESSION"
);
$code_form_para_2 = General::evalSmartyString($LANG["text_add_form_step_2_para_6"], $replacement_info);

$success = true;
$message = "";
if (isset($_POST["refresh"]) && $form_info["is_initialized"] == "no") {
	$success = false;
	$message = $LANG["notify_no_test_submission"];
}

// compile the header information
$page_vars = array(
    "page" => "add_form3",
    "g_success" => $success,
    "g_message" => $message,
    "page_url" => Pages::getPageUrl("add_form3"),
    "head_title" => "{$LANG['phrase_add_form']} - {$LANG["phrase_step_3"]}",
    "form_id" => $form_id,
    "form_tag" => $form_tag,
    "form_info" => $form_info,
    "hidden_fields" => $hidden_fields,
    "direct_form_para_2" => $direct_form_para_2,
    "code_form_para_2" => $code_form_para_2,
);

$current_section = (!empty($form_info["submission_type"])) ? "\"{$form_info["submission_type"]}\"" : "null";
$page_vars["head_js"] =<<< END
var rules = [];
var page_ns = {};
page_ns.current_section = $current_section;
page_ns.show_section = function(section) {
  if (page_ns.current_section != null) {
    $("#" + page_ns.current_section).fadeOut({ duration: 400 });
    setTimeout(function() { $("#" + section).fadeIn({ duration: 400, }); }, 410);
  } else {
    $("#" + section).fadeIn({ duration: 400 });
  }

  // if the user just selected a submission type, highlight the appropriate box and store the
  // type to send along to the database. This information isn't needed outside of the Add Form
  // process, but it's nice to be able to re-fill the appropriate submission type box when
  if (section == 'direct') {
    $('#direct_box').removeClass('grey_box');
    $('#direct_box').addClass('blue_box');
    $('#code_box').removeClass('blue_box');
    $('#code_box').addClass('grey_box');
  }
  if (section == 'code') {
    $('#direct_box').addClass('grey_box');
    $('#direct_box').removeClass('blue_box');
    $('#code_box').addClass('blue_box');
    $('#code_box').removeClass('grey_box');
  }
  if (section != 'direct' && section != 'code') {
    $('#direct_box').addClass('grey_box');
    $('#direct_box').removeClass('blue_box');
    $('#code_box').addClass('grey_box');
    $('#code_box').removeClass('blue_box');
  }
  page_ns.current_section = section;
}

rsv.onCompleteHandler = function() { ft.select_all('selected_user_ids[]'); return true; }
END;

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

Themes::displayPage("admin/forms/add/step3.tpl", $page_vars);
