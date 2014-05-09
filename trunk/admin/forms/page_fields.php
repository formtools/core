<?php

if (isset($request["update_fields"]))
{
  ft_reorder_form_fields($request, $form_id);
  list($g_success, $g_message) = ft_update_form_fields_tab($form_id, $request);
}

$form_info   = ft_get_form($form_id);
$form_fields = ft_get_form_fields($form_id);

// compile the template fields
$page_vars = array();
$page_vars["page"]        = "fields";
$page_vars["page_url"]    = ft_get_page_url("edit_form_fields", array("form_id" => $form_id));
$page_vars["tabs"]        = $tabs;
$page_vars["form_id"]     = $form_id;
$page_vars["head_title"]  = "{$LANG["phrase_edit_form"]} - {$LANG["word_fields"]}";
$page_vars["form_info"]   = $form_info;
$page_vars["form_fields"] = $form_fields;
$page_vars["image_manager_module_enabled"] = ft_check_module_enabled("image_manager");

$replacement_info = array("views_tab_link" => "{$_SERVER['PHP_SELF']}?page=views&form_id=$form_id");
$page_vars["text_fields_tab_summary"] = ft_eval_smarty_string($LANG["text_fields_tab_summary"], $replacement_info);
$page_vars["js_messages"] = array("validation_no_form_field_name", "validation_invalid_form_field_names");

$page_vars["head_js"] =<<<EOF
var rules = [];
rules.push("function,page_ns.validate_form_field_names");

var page_ns = {};
page_ns.validate_form_field_names = function()
{
  var f = $("display_form");
	var field_ids = $("field_ids").value.split(",");

	var errors = [];
  for (var i=1; i<=field_ids.length; i++)
	{
	  if (f["field_" + field_ids[i] + "_name"] == undefined)
		  continue;

		var curr_field_name_val = f["field_" + field_ids[i] + "_name"].value;

		if (!curr_field_name_val)
		  errors.push([f["field_" + field_ids[i] + "_name"], g.messages["validation_no_form_field_name"]]);
		else if (curr_field_name_val.match(/[^0-9a-zA-Z_]/))
		  errors.push([f["field_" + field_ids[i] + "_name"], g.messages["validation_invalid_form_field_names"]]);
	}

	return errors;
}
EOF;

ft_display_page("admin/forms/edit.tpl", $page_vars);