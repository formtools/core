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

$LANG = Core::$L;
$root_url = Core::getRootUrl();
$form_id = General::loadField("form_id", "add_form_form_id", "");

$submission_type = General::loadField("submission_type", "submission_type");

// the form may or may not be created in the DB at this point. If a user's coming back to this page to complete
// setting up their form, update the submission_type - otherwise we have the info in sessions
if (!empty($form_id)) {
    Forms::setSubmissionType($form_id, $submission_type);
}

$page = array();

// start setting up the form
$success = true;
$message = "";
if (isset($request["add_form"])) {
	$request["form_type"]       = "external";
	$request["submission_type"] = $submission_type;
	list($success, $message, $form_id) = Forms::setupForm($request);

	// store the uploading_files value for the duration of this session
    Sessions::set("uploading_files", isset($request['uploading_files']) ? $request['uploading_files'] : "no");

	// form successfully added. Continue to step 2.
	if ($success) {
        General::redirect("step3.php?form_id=$form_id");

    // error. Reload the page with the already entered form values, and display the appropriate error message.
    } else {
		$page = Forms::addFormGetExternalFormValues("post", "", $request);
	}
}

// update this form
else if (isset($request['update_form'])) {

	// store the uploading_files value for the duration of this session
    Sessions::set("uploading_files", isset($request["uploading_files"]) ? $request["uploading_files"] : "no");
	$request["submission_type"] = $submission_type;

	list($success, $message) = Forms::setFormMainSettings($request);
	if ($success) {
        General::redirect("step3.php?form_id=$form_id");
	} else {
        $page = Forms::addFormGetExternalFormValues("post", $form_id, $request);
    }
}

// edit existing form (used for cases where user fails to complete form building process, then returns
// later to finish the job)
else if (!empty($form_id)) {
	$page =  Forms::addFormGetExternalFormValues("database", $form_id);
}
// otherwise, the user is coming to this page for the first time. init the default values
else {
	$page = Forms::addFormGetExternalFormValues("new_form");
}

if (!Sessions::exists("uploading_files")) {
    Sessions::set("uploading_files", "no");
}

$selected_client_ids = array();
for ($i=0; $i<count($page["client_info"]); $i++) {
    $selected_client_ids[] = $page["client_info"][$i]["account_id"];
}

$num_pages_in_multi_page_form = count($page["multi_page_form_urls"]) + 1;

// compile the header information
$page_vars["page"]     = "add_form1";
$page_vars["g_success"] = $success;
$page_vars["g_message"] = $message;
$page_vars["page_url"] = Pages::getPageUrl("add_form2");
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_2"]}";
$page_vars["page_values"] = $page;
$page_vars["form_id"] = $form_id;
$page_vars["sortable_id"] = "multi_page_form_list";
$page_vars["submission_type"] = $submission_type;
$page_vars["num_pages_in_multi_page_form"] = $num_pages_in_multi_page_form;
$page_vars["selected_client_ids"] = $selected_client_ids;
$page_vars["uploading_files"] = Sessions::get("uploading_files");
$page_vars["js_messages"] = array(
    "validation_no_url", "phrase_check_url", "word_page", "validation_invalid_url",
	"word_verified", "word_close", "validation_no_form_url"
);
$page_vars["head_string"] =<<< EOF
  <script src="$root_url/global/scripts/manage_forms.js?v=2"></script>
  <script src="$root_url/global/scripts/sortable.js?v=2"></script>
EOF;

$page_vars["head_js"] =<<< END
ft.click([
  { el: "at1", targets: [{ el: "custom_clients", action: "hide" }] },
  { el: "at2", targets: [{ el: "custom_clients", action: "hide" }] },
  { el: "at3", targets: [{ el: "custom_clients", action: "show" }] }
]);

var rules = [];
rules.push("required,form_name,{$LANG['validation_no_form_name']}");
rules.push("function,mf_ns.check_first_form_url");
rules.push("required,access_type,{$LANG["validation_no_access_type"]}");

rsv.onCompleteHandler = function() {
  ft.select_all("selected_client_ids[]");
  return true;
}

$(function() {
  $("#form_name").focus();
  $(".is_multi_page_form").bind("click", function() {
    if ($(this).val() == "yes") {
      $("#form_url_single, #form_label_single").hide();
      $("#form_url_multiple, #form_label_multiple").show();
    } else {
      $("#form_url_single, #form_label_single").show();
      $("#form_url_multiple, #form_label_multiple").hide();
    }
  });
  ft.init_check_url_buttons();
});

END;

Themes::displayPage("admin/forms/add/step2.tpl", $page_vars);
