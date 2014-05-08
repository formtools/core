<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

$form_id = ft_load_field("form_id", "add_form_form_id", "");
$form_info   = ft_get_form($form_id);
$form_fields = ft_get_form_fields($form_id);

$form_urls = array();
$form_urls[] = $form_info["form_url"];

foreach ($form_info["multi_page_form_urls"] as $page_info)
	$form_urls[] = $page_info["form_url"];

$iframe_loaded_js_rows = array();
reset($form_urls);
for ($i=1; $i<=count($form_urls); $i++)
{
  $iframe_loaded_js_rows[] = "page_ns.form_{$i}_url = \"{$form_urls[$i-1]}\";";
	$iframe_loaded_js_rows[] = "page_ns.form_{$i}_loaded = false;";
}

$iframe_loaded_js = join("\n", $iframe_loaded_js_rows);
$num_pages = count($form_urls);

// get the list of (non-system) field IDs
$custom_field_ids = array();
foreach ($form_fields as $field_info)
{
	if ($field_info["field_type"] == "system")
	  continue;

	$custom_field_ids[] = $field_info["field_id"];
}

$custom_field_id_str = join(",", $custom_field_ids);


// this chunk of code determines what method should be used to make the form web page(s) available to
// the javascript, to let it parse and Smart Fill the field types and options
$scrape_method = ft_get_js_webpage_parse_method($form_info["form_url"]);

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars["page"] = "add_form5";
$page_vars["page_url"] = ft_get_page_url("add_form5");
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_5"]}";
$page_vars["form_id"] = $form_id;
$page_vars["form_info"] = $form_info;
$page_vars["form_fields"] = $form_fields;
$page_vars["form_urls"] = $form_urls;
$page_vars["scrape_method"] = $scrape_method;
$page_vars["js_messages"] = array("word_na", "word_found", "word_delete", "word_none", "notify_smart_fill_submitted",
  "phrase_not_found", "word_options", "phrase_add_field_values", "phrase_multiple_fields_found", "notify_multiple_fields_found",
  "phrase_field_type", "phrase_form_page", "word_options", "word_select", "word_textbox", "word_password", "word_file",
  "phrase_radio_buttons","word_checkboxes", "word_textarea", "phrase_multi_select", "word_page", "word_na",
  "notify_smart_fill_field_not_found", "validation_select_field_type", "word_resolved", "notify_multi_field_updated",
  "notify_field_updated", "word_skipped", "phrase_field_skipped", "notify_multi_field_selected", "notify_field_selected",
  "word_horizontal", "word_vertical", "word_order", "phrase_field_value", "phrase_display_value", "notify_add_display_values",
  "phrase_previous_field", "phrase_next_field", "validation_smart_fill_upload_all_pages", "notify_smart_fill_upload_fields_fail",
  "notify_smart_fill_files_uploaded_successfully", "validation_upload_html_files_only"
);
$page_vars["head_css"] = "";
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_forms.js\"></script>
<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/mass_smart_fill_fields.js\"></script>";
$page_vars["head_js"] =<<< EOF

var page_ns = {};
page_ns.field_ids = [$custom_field_id_str];
page_ns.num_pages = $num_pages;
$iframe_loaded_js

Event.observe(document, "dom:loaded", function() {
  for (var i=0; i<page_ns.field_ids.length; i++)
  {
    var field_id = page_ns.field_ids[i];
    $("field_" + field_id + "_type").value = "";
  }
  $("next_step").disabled = true;
});
EOF;

ft_display_page("admin/forms/add/step5.tpl", $page_vars);