<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

$sortable_id = "review_field_options";
$form_id = ft_load_field("form_id", "add_form_form_id", "");
$form_info   = ft_get_form($form_id);
$form_fields = ft_get_form_fields($form_id);

$form_urls = array();
if ($form_info["is_multi_page_form"] == "yes")
{
  foreach ($form_info["multi_page_form_urls"] as $page_info)
    $form_urls[] = $page_info["form_url"];
}
else
{
  $form_urls[] = $form_info["form_url"];
}

$iframe_loaded_js_rows = array();
reset($form_urls);
for ($i=1; $i<=count($form_urls); $i++)
{
  $iframe_loaded_js_rows[] = "page_ns.form_{$i}_url = \"{$form_urls[$i-1]}\";";
  $iframe_loaded_js_rows[] = "page_ns.form_{$i}_loaded = false;";
}

$iframe_loaded_js = implode("\n", $iframe_loaded_js_rows);
$num_pages = count($form_urls);

// get the list of (non-system) field IDs
$custom_field_ids = array();
foreach ($form_fields as $field_info)
{
  if ($field_info["is_system_field"] == "yes")
    continue;

  $custom_field_ids[] = $field_info["field_id"];
}

$custom_field_id_str = implode(",", $custom_field_ids);


// this chunk of code determines what method should be used to make the form web page(s) available to
// the javascript, to let it parse and Smart Fill the field types and options
$scrape_method = ft_get_js_webpage_parse_method($form_info["form_url"]);
$raw_field_types_js = ft_get_raw_field_types_js();
$field_size_labels_js = ft_generate_field_type_size_labels();

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
$page_vars["text_add_form_field_types_multiple_fields_found8"] = ft_eval_smarty_string($LANG["text_add_form_field_types_multiple_fields_found8"], array("ONCLICK" => "ft.display_message('ft_message', 1, $('#upload_files_text').html())"));
$page_vars["sortable_id"] = $sortable_id;
$page_vars["js_messages"] = array("word_na", "word_found", "word_delete", "word_none", "notify_smart_fill_submitted",
  "phrase_not_found", "word_options", "phrase_multiple_fields_found", "notify_multiple_fields_found",
  "phrase_field_type", "phrase_form_page", "word_options", "word_select", "word_textbox", "word_password", "word_file",
  "phrase_radio_buttons","word_checkboxes", "word_textarea", "phrase_multi_select", "word_page", "word_na",
  "notify_smart_fill_field_not_found", "validation_select_field_type", "word_resolved", "notify_multi_field_updated",
  "notify_field_updated", "word_skipped", "phrase_field_skipped", "notify_multi_field_selected", "notify_field_selected",
  "word_horizontal", "word_vertical", "word_order", "phrase_field_value", "phrase_display_value", "notify_add_display_values",
  "phrase_previous_field", "phrase_next_field", "validation_smart_fill_upload_all_pages", "notify_smart_fill_upload_fields_fail",
  "notify_smart_fill_files_uploaded_successfully", "validation_upload_html_files_only", "word_okay", "word_error",
  "word_yes", "word_no", "phrase_please_confirm", "confirm_refresh_page"
);
$page_vars["head_css"] = "";
$page_vars["head_string"] =<<< END
<script src="$g_root_url/global/scripts/sortable.js?v=2"></script>
<script src="$g_root_url/global/scripts/manage_forms.js?v=2"></script>
<script src="$g_root_url/global/scripts/external_form_smart_fill.js"></script>
END;

$page_vars["head_js"] =<<< END
var page_ns = {};
page_ns.field_ids = [$custom_field_id_str];
page_ns.num_pages = $num_pages;
$iframe_loaded_js
$raw_field_types_js
$field_size_labels_js

$(function() {
  for (var i=0; i<page_ns.field_ids.length; i++) {
    var field_id = page_ns.field_ids[i];
    $("#field_" + field_id + "_type").val("");
  }
  $("#next_step").disabled = true;

  $(".multiple_field_types").live("change keyup", function() {
    var field_type_id = $(this).val();
    var tmp = $(this).attr("id").match(/field_(\d+)_type/);
    var field_id = tmp[1];

    $.each(page_ns.raw_field_types, function(key, field_types) {
      for (var i=0; i<field_types.length; i++) {
        if (field_types[i].field_type_id == field_type_id) {
          sf_ns.create_field_size_dropdown(field_id, field_types[i].compatible_field_sizes);
          return false;
        }
      }
    });
  });
});
END;

ft_display_page("admin/forms/add/step5.tpl", $page_vars);
