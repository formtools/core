<?php

use FormTools\Core;
use FormTools\Administrator;
use FormTools\Emails;
use FormTools\Fields;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Submissions;
use FormTools\Themes;
use FormTools\Views;

$email_id = General::loadField("email_id", "email_id", "");

$success = true;
$message = "";
if (isset($request["update_email_template"])) {
    list($success, $message) = Emails::updateEmailTemplate($email_id, $request);
}

$form_info      = Forms::getForm($form_id);
$form_fields    = Fields::getFormFields($form_id);
$columns        = Forms::getFormColumnNames($form_id);
$template_info  = Emails::getEmailTemplate($email_id);
$event_trigger_arr = explode(",", $template_info["email_event_trigger"]);
$template_info["email_event_trigger"] = $event_trigger_arr;
$clients         = $form_info["client_info"];
$admin_info      = Administrator::getAdminInfo();

$edit_email_tab = Sessions::getWithFallback("inner_tabs.edit_email_template", 1);
if (isset($request["edit_email_template"]))
	$edit_email_tab = $request["edit_email_template"];

$form_has_file_upload_field = Forms::getNumFileUploadFields($form_id) > 0;
$file_field_text = ($form_has_file_upload_field) ? $LANG["text_file_field_placeholders_info"] : "";

// values for the test email subpage
$num_submissions = Submissions::getSubmissionCount($form_id);
$test_email_format = General::loadField("test_email_format", "test_email_format");
$test_email_recipient = General::loadField("test_email_recipient", "test_email_recipient", $admin_info["email"]);
$test_email_data_source = General::loadField("test_email_data_source", "test_email_data_source", "random_submission");
$test_email_submission_id = General::loadField("test_email_submission_id", "test_email_submission_id", "");

$views = Views::getViews($form_id);

$filtered_views = array();
$selected_edit_submission_views = array();
$selected_when_sent_views = array();
foreach ($views["results"] as $view) {
	if (!empty($view["filters"])) {
        $filtered_views[] = $view;
    }
	if (in_array($view["view_id"], $template_info["edit_submission_page_view_ids"])) {
        $selected_edit_submission_views[] = $view;
    }
	if (in_array($view["view_id"], $template_info["when_sent_view_ids"])) {
        $selected_when_sent_views[] = $view;
    }
}

$root_url = Core::getRootUrl();

// compile the template information
$page_vars["page"]       = "edit_email";
$page_vars["g_success"]  = $success;
$page_vars["g_message"]  = $message;
$page_vars["page_url"]   = Pages::getPageUrl("edit_form_email_settings", array("form_id" => $form_id));
$page_vars["email_id"]   = $email_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_edit_email_template"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["form_fields"] = $form_fields;
$page_vars["clients"]    = $clients;
$page_vars["views"]      = $views["results"];
$page_vars["filtered_views"] = $filtered_views;
$page_vars["selected_edit_submission_views"] = $selected_edit_submission_views;
$page_vars["selected_when_sent_views"] = $selected_when_sent_views;
$page_vars["admin_info"] = $admin_info;
$page_vars["file_field_text"] = $file_field_text;
$page_vars["columns"]    = $columns;
$page_vars["edit_email_advanced_settings"] = Sessions::getWithFallback("edit_email_advanced_settings", "");
$page_vars["js_messages"] = array("validation_invalid_email", "validation_no_custom_recipient_email",
	"validation_no_test_email_recipient", "validation_no_test_email_submission_id", "word_cc_c", "word_bcc_c",
	"word_from_c", "word_reply_to_c", "word_subject_c", "phrase_form_email_field_b_c", "phrase_form_email_fields",
	"validation_no_main_email_recipient", "validation_no_email_content", "validation_no_email_template_name",
	"validation_no_email_template_view_mapping_value", "validation_no_email_template_view_id",
	"validation_no_custom_from_email", "validation_invalid_custom_from_email", "validation_no_custom_reply_to_email",
	"validation_invalid_custom_reply_to_email", "validation_no_email_from_field", "phrase_form_field_placeholders"
);

// a little hacky, but not too bad. Override the form nav links so that it always links to the email tab
$page_vars["prev_tabset_link"] = (!empty($links["prev_form_id"])) ? "?page=emails&form_id={$links["prev_form_id"]}" : "";
$page_vars["next_tabset_link"] = (!empty($links["next_form_id"])) ? "?page=emails&form_id={$links["next_form_id"]}" : "";

$page_vars["template_info"]  = $template_info;
$page_vars["edit_email_tab"] = $edit_email_tab;
$page_vars["num_submissions"] = $num_submissions;
$page_vars["test_email_format"] = $test_email_format;
$page_vars["test_email_recipient"] = $test_email_recipient;
$page_vars["test_email_data_source"] = $test_email_data_source;
$page_vars["test_email_submission_id"] = $test_email_submission_id;
$page_vars["registered_form_emails"] = Emails::getEmailFields($form_id);
$page_vars["head_string"] =<<< END
<link rel="stylesheet" href="$root_url/global/codemirror/lib/codemirror.css" type="text/css" />
<script src="$root_url/global/scripts/manage_email_templates.js?v=3"></script>
<script src="$root_url/global/codemirror/lib/codemirror.js"></script>
<script src="$root_url/global/codemirror/mode/xml/xml.js"></script>
<script src="$root_url/global/codemirror/mode/smarty/smarty.js"></script>
END;

$page_vars["head_js"] =<<< END
rsv.onCompleteHandler = function() {
  ft.select_all($("#selected_edit_submission_views"));
  if ($("#selected_when_sent_views").length) {
    ft.select_all($("#selected_when_sent_views"));
  }
  return true;
}

// log the total number of recipients
$(function() {

  var onTabChange = function (tab) {
    if (tab === 3) {
      html_editor.refresh();
      text_editor.refresh();
    }
  }

  ft.init_inner_tabs(onTabChange);
  emails_ns.num_recipients = parseInt($('#num_recipients').val());
  emails_ns.recipient_num  = parseInt($('#num_recipients').val()) + 1;

  // always set the select recipient field to empty
  $("#recipient_options").val("");
  $("input[name=include_on_edit_submission_page]").bind("change", function() {
    if (this.value == "specific_views") {
      $('#include_on_edit_submission_page_views').show();
    } else {
      $('#include_on_edit_submission_page_views').hide();
    }
  });
  $("input[name=view_mapping_type]").bind("change", function() {
    if (this.value == "specific") {
      $('#when_sent_views').show();
    } else {
      $('#when_sent_views').hide();
    }
  });

  $("#edit_email_template_form").bind("submit", function() {
    return emails_ns.onsubmit_check_email_settings(this);
  });

  $(".placeholders_section").bind("click", function() {
    ft.show_form_field_placeholders_dialog({ form_id: {$form_id} });
  });

  $("#test_email_submission_id").bind("keyup", function() { $("#test_email_data_submission_id").attr("checked", "checked"); });
});
END;

Themes::displayPage("admin/forms/edit/index.tpl", $page_vars);
