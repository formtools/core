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
$edit_email_tab  = ft_load_field("edit_email_tab", "edit_email_tab", 1);
$clients         = $form_info["client_info"];
$admin_info      = ft_get_admin_info();

$settings = $_SESSION["ft"]["settings"];
$datetime_example = ft_get_date($settings["timezone_offset"], ft_get_current_datetime(), $settings["default_date_format"]);
$replacement_info = array("currenttime" => "<span class=\"highlighted_text bold\">$datetime_example</span>");
$submission_date_str = ft_eval_smarty_string($LANG["text_form_submission_date_placeholder"], $replacement_info);

$form_has_file_upload_field = ft_check_form_has_file_upload_field($form_id);
$file_field_text = ($form_has_file_upload_field) ? $LANG["text_file_field_placeholders_info"] : "";

// values for the test email subpage
$num_submissions = ft_get_submission_count($form_id);
$test_email_format = ft_load_field("test_email_format", "test_email_format");
$test_email_recipient = ft_load_field("test_email_recipient", "test_email_recipient", $admin_info["email"]);
$test_email_data_source = ft_load_field("test_email_data_source", "test_email_data_source", "random_submission");
$test_email_submission_id = ft_load_field("test_email_submission_id", "test_email_submission_id", "");

$views = ft_get_views($form_id, "all");

$filtered_views = array();
$selected_edit_submission_views = array();
foreach ($views["results"] as $view)
{
  if (!empty($view["filters"]))
    $filtered_views[] = $view;

  if (in_array($view["view_id"], $template_info["edit_submission_page_view_ids"]))
    $selected_edit_submission_views[] = $view;
}


// ------------------------------------------------------------------------------------------------


// compile the template information
$page_vars = array();
$page_vars["page"]       = "edit_email";
$page_vars["page_url"]   = ft_get_page_url("edit_form_email_settings", array("form_id" => $form_id));
$page_vars["email_id"]   = $email_id;
$page_vars["tabs"]       = $tabs;
$page_vars["form_id"]    = $form_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_edit_email_template"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["form_fields"] = $form_fields;
$page_vars["clients"]    = $clients;
$page_vars["views"]      = $views["results"];
$page_vars["filtered_views"] = $filtered_views;
$page_vars["selected_edit_submission_views"] = $selected_edit_submission_views;
$page_vars["admin_info"] = $admin_info;
$page_vars["submission_date_str"] = $submission_date_str;
$page_vars["file_field_text"] = $file_field_text;
$page_vars["columns"]    = $columns;
$page_vars["js_messages"]    = array("validation_invalid_email", "validation_no_custom_recipient_email",
                                     "validation_no_test_email_recipient", "validation_no_test_email_submission_id",
                                     "word_cc_c", "word_bcc_c", "word_from_c", "word_reply_to_c", "word_subject_c",
                                     "phrase_form_email_field_b_c", "phrase_form_email_fields");
$page_vars["template_info"]  = $template_info;
$page_vars["edit_email_tab"] = $edit_email_tab;
$page_vars["num_submissions"] = $num_submissions;
$page_vars["test_email_format"] = $test_email_format;
$page_vars["test_email_recipient"] = $test_email_recipient;
$page_vars["test_email_data_source"] = $test_email_data_source;
$page_vars["test_email_submission_id"] = $test_email_submission_id;
$page_vars["registered_form_emails"] = ft_get_registered_form_emails($form_id);
$page_vars["head_string"] =<<< EOF
<script src="$g_root_url/global/scripts/manage_email_templates.js?v=2"></script>
<script src="$g_root_url/global/codemirror/js/codemirror.js"></script>
EOF;

$page_vars["head_js"] =<<< EOF
rsv.onCompleteHandler = function() { ft.select_all($("selected_edit_submission_views")); return true; }

var page_ns = {};

page_ns.onsubmit_check_email_settings = function(f)
{
  // Configuration tab
  var rules = [];
  rules.push("required,email_template_name,{$LANG["validation_no_email_template_name"]}");
  rules.push("required,view_mapping_type,{$LANG["validation_no_email_template_view_mapping_value"]}");
  rules.push("if:view_mapping_type=specific,required,view_mapping_view_id,{$LANG["validation_no_email_template_view_id"]}");
  if (!rsv.validate(f, rules))
    return ft.change_inner_tab(1, 5, "edit_email_tab"); // this always returns false;

  // Headers tab
  var rules = [];
  rules.push("function,page_ns.check_one_main_recipient");
  rules.push("required,email_from,{$LANG["validation_no_email_from_field"]}");
  rules.push("if:email_from=custom,required,custom_from_email,{$LANG["validation_no_custom_from_email"]}");
  rules.push("if:email_from=custom,valid_email,custom_from_email,{$LANG["validation_invalid_custom_from_email"]}");
  rules.push("if:email_reply_to=custom,required,custom_reply_to_email,{$LANG["validation_no_custom_reply_to_email"]}");
  rules.push("if:email_reply_to=custom,valid_email,custom_reply_to_email,{$LANG["validation_invalid_custom_reply_to_email"]}");
  if (!rsv.validate(f, rules))
    return ft.change_inner_tab(2, 5, "edit_email_tab"); // this always returns false

  var rules = [];
  rules.push("function,page_ns.check_one_template_defined");
  if (!rsv.validate(f, rules))
    return ft.change_inner_tab(3, 5, "edit_email_tab"); // this always returns false

  return true;
}

page_ns.check_one_main_recipient = function()
{
  if (emails_ns.num_recipients == 0)
  {
    return [[$('recipient_options'), "{$LANG["validation_no_main_email_recipient"]}"]];
  }
  else
  {
    var has_one_main_recipient = false;
    for (var i=0; i<=emails_ns.recipient_num; i++)
    {
      if ($('recipient_' + i + '_type') && $('recipient_' + i + '_type').value == '')
        var has_one_main_recipient = true;
    }

    if (!has_one_main_recipient)
      return [[$('recipient_options'), "{$LANG["validation_no_main_email_recipient"]}"]];
  }

  return true;
}


page_ns.toggle_advanced_settings = function()
{
  var display_setting = $('advanced_settings').getStyle('display');
  var is_visible = false;

  if (display_setting == 'none')
  {
    Effect.BlindDown($('advanced_settings'));
    is_visible = true;
  }
  else
    Effect.BlindUp($('advanced_settings'));

  var page_url = g.root_url + "/global/code/actions.php";
  new Ajax.Request(page_url, {
    parameters: { action: "remember_edit_email_advanced_settings", edit_email_advanced_settings: is_visible },
    method: 'post'
      });

}

/**
 * This confirms that the user has entered at least one of the HTML and text templates.
 */
page_ns.check_one_template_defined = function()
{
  var html_template = html_editor.getCode();
  html_template = html_template.strip();
  var text_template = text_editor.getCode();
  text_template = text_template.strip();

  if (html_template.strip() == "" && text_template.strip() == "")
    return [[$('html_template'), "{$LANG["validation_no_email_content"]}"]];

  return true;
}


page_ns.change_include_on_edit_submission_page = function(selected)
{
  if (selected == "specific_views")
    $('include_on_edit_submission_page_views').show();
  else
    $('include_on_edit_submission_page_views').hide();
}


// log the total number of recipients
Event.observe(document, 'dom:loaded',
  function()
  {
    emails_ns.num_recipients = parseInt($('num_recipients').value);
    emails_ns.recipient_num  = parseInt($('num_recipients').value) + 1;

    // always set the select recipient field to empty
    $("recipient_options").value = "";
  });
EOF;

ft_display_page("admin/forms/edit.tpl", $page_vars);
