<?php

use FormTools\Core;
use FormTools\Fields;
use FormTools\FieldSizes;
use FormTools\FieldValidation;
use FormTools\FieldTypes;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Settings;
use FormTools\Themes;


$sortable_id = "edit_fields";

$success = true;
$message = "";
if (isset($request["update_fields"])) {
	$request["sortable_id"] = $sortable_id;
	list($success, $message) = Forms::updateFormFieldsTab($form_id, $request);
}

$form_info = Forms::getForm($form_id);
if (isset($request["num_fields_per_page"])) {
	$num_fields_per_page = $request["num_fields_per_page"];
    Settings::set(array("admin_num_fields_per_page_{$form_id}" => $request["num_fields_per_page"]));
	$_GET["fields_page"] = 1;
} else {
	$saved_num_fields_per_page = Settings::get("admin_num_fields_per_page_{$form_id}");
	$num_fields_per_page = (!empty($saved_num_fields_per_page)) ? $saved_num_fields_per_page : "all";
}

if (empty($num_fields_per_page)) {
    Settings::set(array("admin_num_fields_per_page_{$form_id}" => "all"));
}

$fields_page = General::loadField("fields_page", "fields_page", 1);

$form_fields = Fields::getFormFields($form_id, array("page" => $fields_page, "num_fields_per_page" => $num_fields_per_page));
$total_form_fields = Fields::getNumFormFields($form_id);

$reserved_words = General::getMysqlReservedWords();
$escaped_words = array();
foreach ($reserved_words as $word) {
	$escaped_words[] = "\"$word\"";
}
$reserved_words = implode(",", $escaped_words);

$field_type_sizes_js = FieldSizes::generateFieldTypeSizesMapJs();
$field_sizes_js      = FieldSizes::generateFieldTypeSizeLabels();
$field_settings_js   = FieldTypes::generateFieldTypeSettingsJs();
$field_validation_js = FieldValidation::generateFieldTypeValidationJs();

$php_self = General::getCleanPhpSelf();
$shared_characteristics_js = FieldTypes::getFieldTypeSettingSharedCharacteristicsJs();


$LANG = Core::$L;
$root_url = Core::getRootUrl();
$max_form_fields = Core::getMaxFormFields();

// compile the template fields
$page_vars["page"]         = "fields";
$page_vars["g_success"]    = $success;
$page_vars["g_message"]    = $message;
$page_vars["page_url"]     = Pages::getPageUrl("edit_form_fields", array("form_id" => $form_id));
$page_vars["head_title"]   = "{$LANG["phrase_edit_form"]} - {$LANG["word_fields"]}";
$page_vars["form_info"]    = $form_info;
$page_vars["form_fields"]  = $form_fields;
$page_vars["order_start_number"] = 1;
$page_vars["num_fields_per_page"] = "all";
$page_vars["pagination"] = "";
$page_vars["sortable_id"] = $sortable_id;
$page_vars["limit_fields"] = (!empty($max_form_fields)) ? true : false;


if ($num_fields_per_page != "all") {
	$page_vars["order_start_number"] = (($fields_page - 1) * $num_fields_per_page) + 1;
	$page_vars["num_fields_per_page"] = $num_fields_per_page;
	$pagination_settings = array(
		"show_total_results" => false,
		"show_page_label"    => false
	);
	$page_vars["pagination"] = General::getPageNav($total_form_fields, $num_fields_per_page, $fields_page, "", "fields_page", "",
		$pagination_settings);
}

$page_vars["head_string"] =<<< END
  <script src="{$root_url}/global/scripts/sortable.js?v=2"></script>
  <script src="{$root_url}/global/scripts/manage_fields.js?v=3"></script>
END;

$replacement_info = array("views_tab_link" => "$php_self?page=views&form_id=$form_id");
$page_vars["text_fields_tab_summary"] = General::evalSmartyString($LANG["text_fields_tab_summary"], $replacement_info);
$page_vars["js_messages"] = array("validation_no_form_field_name", "validation_invalid_form_field_names", "word_okay",
	"word_error", "validation_num_rows_to_add", "word_row", "phrase_please_confirm", "confirm_smart_fill_db_column_fields",
	"confirm_smart_fill_db_column_fields_desc", "word_cancel", "phrase_smart_fill", "validation_no_display_text",
	"validation_no_form_field_name", "validation_duplicate_form_field_name", "validation_no_column_name",
	"validation_col_name_is_reserved_word", "validation_invalid_column_name", "validation_no_two_column_names",
	"phrase_edit_field", "word_close", "phrase_save_changes", "phrase_field_specific_settings", "phrase_edit_field_c",
	"notify_no_field_settings", "word_value", "word_field", "phrase_use_default_value_q", "word_setting",
	"phrase_please_select", "notify_field_changes_saved", "phrase_create_new_option_list", "phrase_edit_option_list",
	"word_no", "word_yes", "validation_no_display_text_single", "validation_no_form_field_single",
	"validation_no_db_column_single", "notify_edit_field_new_field", "notify_edit_option_list_after_save",
	"confirm_save_change_before_redirect", "notify_error_saving_fields", "phrase_select_field", "word_order",
	"word_settings", "phrase_field_type_no_validation", "phrase_validation_rule", "text_error_message_to_show",
	"phrase_no_option_lists_available", "phrase_available_option_lists", "phrase_form_field_contents", "word_validation"
);

$edit_field_onload_js = "";
$limit_fields_enabled_js = ($page_vars["limit_fields"]) ? "fields_ns.limit_fields_enabled = true;\n  fields_ns.max_fields = $max_form_fields;" : "";
if (isset($_GET["field_id"])) {
	$edit_field_onload_js =<<< EOF
  var row_group = $(".sr_order[value={$_GET["field_id"]}]").closest(".row_group");
  if (row_group.length) {
    fields_ns.edit_field(row_group);
  }
EOF;
}

$page_vars["head_js"] =<<<END
var page_ns = {
  reserved_words: [$reserved_words],
  form_id: $form_id,
  shared_characteristics: $shared_characteristics_js
};

$field_type_sizes_js
$field_sizes_js
$field_settings_js
$field_validation_js

$(function() {
  ft.init_inner_tabs();
  fields_ns.num_fields = $total_form_fields;
  $edit_field_onload_js
  $limit_fields_enabled_js
  fields_ns.update_max_field_count();
});
END;

Themes::displayPage("admin/forms/edit/index.tpl", $page_vars);

