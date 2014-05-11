<?php

$email_id = ft_load_field("email_id", "email_id", "");

if (isset($request["update_email_template"]))
  list($g_success, $g_message) = ft_update_email_template($email_id, $request);

$form_info      = ft_get_form($form_id);
$form_fields    = ft_get_form_fields($form_id);
$columns        = ft_get_form_column_names($form_id);
$template_info  = ft_get_email_template($email_id);
$event_trigger_arr =  explode(",", $template_info["email_event_trigger"]);
$template_info["email_event_trigger"] = $event_trigger_arr;
$clients         = $form_info["client_info"];
$admin_info      = ft_get_admin_info();

$edit_email_tab = (isset($_SESSION["ft"]["inner_tabs"]["edit_email_template"])) ? $_SESSION["ft"]["inner_tabs"]["edit_email_template"] : 1;
if (isset($request["edit_email_template"]))
  $edit_email_tab = $request["edit_email_template"];

$form_has_file_upload_field = ft_check_form_has_file_upload_field($form_id);
$file_field_text = ($form_has_file_upload_field) ? $LANG["text_file_field_placeholders_info"] : "";

// values for the test email subpage
$num_submissions = ft_get_submission_count($form_id);
$test_email_format = ft_load_field("test_email_format", "test_email_format");
$test_email_recipient = ft_load_field("test_email_recipient", "test_email_recipient", $admin_info["email"]);
$test_email_data_source = ft_load_field("test_email_data_source", "test_email_data_source", "random_submission");
$test_email_submission_id = ft_load_field("test_email_submission_id", "test_email_submission_id", "");

$views = ft_get_views($form_id);

$filtered_views = array();
$selected_edit_submission_views = array();
$selected_when_sent_views = array();
foreach ($views["results"] as $view)
{
  if (!empty($view["filters"]))
    $filtered_views[] = $view;

  if (in_array($view["view_id"], $template_info["edit_submission_page_view_ids"]))
    $selected_edit_submission_views[] = $view;

  if (in_array($view["view_id"], $template_info["when_sent_view_ids"]))
    $selected_when_sent_views[] = $view;
}

// ------------------------------------------------------------------------------------------------

// compile the template information
$page_vars["page"]       = "edit_email";
$page_vars["page_url"]   = ft_get_page_url("edit_form_email_settings", array("form_id" => $form_id));
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
$page_vars["js_messages"] = array("validation_invalid_email", "validation_no_custom_recipient_email",
  "validation_no_test_email_recipient", "validation_no_test_email_submission_id", "word_cc_c", "word_bcc_c",
  "word_from_c", "word_reply_to_c", "word_subject_c", "phrase_form_email_field_b_c", "phrase_form_email_fields",
  "validation_no_main_email_recipient", "validation_no_email_content", "validation_no_email_template_name",
  "validation_no_email_template_view_mapping_value", "validation_no_email_template_view_id",
  "validation_no_custom_from_email", "validation_invalid_custom_from_email", "validation_no_custom_reply_to_email",
  "validation_invalid_custom_reply_to_email", "validation_no_email_from_field", "phrase_form_field_placeholders"
);

// a little hacky, but not too bad. Override the form nav links so that it always links to the email tab
$page_vars["prev_tabset_link"] = (!empty($links["prev_form_id"])) ? "edit.php?page=emails&form_id={$links["prev_form_id"]}" : "";
$page_vars["next_tabset_link"] = (!empty($links["next_form_id"])) ? "edit.php?page=emails&form_id={$links["next_form_id"]}" : "";

$page_vars["template_info"]  = $template_info;
$page_vars["edit_email_tab"] = $edit_email_tab;
$page_vars["num_submissions"] = $num_submissions;
$page_vars["test_email_format"] = $test_email_format;
$page_vars["test_email_recipient"] = $test_email_recipient;
$page_vars["test_email_data_source"] = $test_email_data_source;
$page_vars["test_email_submission_id"] = $test_email_submission_id;
$page_vars["registered_form_emails"] = ft_get_email_fields($form_id);
$page_vars["head_string"] =<<< END
<script src="$g_root_url/global/scripts/manage_email_templates.js?v=3"></script>
<script src="$g_root_url/global/codemirror/js/codemirror.js"></script>
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
  ft.init_inner_tabs();
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

ft_display_page("admin/forms/edit.tpl", $page_vars);
