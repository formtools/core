<?php

if (isset($request["update"]))
	list ($g_success, $g_message) = ft_update_field_option_group($group_id, $request);

$group_info = ft_get_field_option_group($group_id);

$placeholders = array(
  "link1" => "edit.php?page=form_fields",
  "link2" => "index.php?add_field_option_group=1&create_field_option_group_from_group_id={$group_info["group_id"]}"
    );

// get a list of all existing field option groups names; this is used to ensure the uniqueness of the group names
// and ward against confusion
$groups = ft_get_field_option_groups("all");
$group_names = array();
foreach ($groups["results"] as $curr_group_info)
{
	if ($group_id == $curr_group_info["group_id"])
	  continue;

  $group_names[] = "\"" . htmlspecialchars($curr_group_info["group_name"]) . "\"";
}

$group_name_list = join(",", $group_names);

$existing_group_names_js = "page_ns.group_names = [$group_name_list];";

// ------------------------------------------------------------------------------------------------

// compile template info
$page_vars = array();
$page_vars["page"] = $page;
$page_vars["group_info"] = $group_info;
$page_vars["text_field_option_group_used_by_fields"] = ft_eval_smarty_string($LANG["text_field_option_group_used_by_fields"], $placeholders);
$page_vars["tabs"] = $tabs;
$page_vars["page_url"] = ft_get_page_url("edit_field_option_group");
$page_vars["head_title"] = $LANG["phrase_edit_field_option_group"];
$page_vars["form_fields"] = $form_fields;
$page_vars["num_fields_using_group"] = ft_get_num_fields_using_field_option_group($group_id);
$page_vars["js_messages"] = array("word_delete", "validation_no_smart_fill_values", "validation_invalid_url",
  "validation_smart_fill_no_field_found", "validation_smart_fill_cannot_fill", "validation_smart_fill_invalid_field_type",
  "validation_smart_fill_upload_all_pages", "validation_upload_html_files_only", "validation_smart_fill_no_page",
  "validation_no_field_option_group_name", "validation_field_option_group_name_taken");
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_field_option_groups.js\"></script>";
$page_vars["head_js"] =<<< EOF
var page_ns = {};
page_ns.page_initialized = false;

$existing_group_names_js

Event.observe(document, "dom:loaded", function() { sf_ns.num_rows = $("num_rows").value; });
EOF;

ft_display_page("admin/forms/field_option_groups/edit.tpl", $page_vars);