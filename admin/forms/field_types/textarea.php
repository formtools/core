<?php

if (isset($request["update"]))
{
  list($g_success, $g_message) = ft_update_field($form_id, $field_id, $request);

  // reset $field_info with the latest values
  $field_info = ft_get_form_field($field_id);
}

$head_js =<<< EOF
if (typeof fo_ns == 'undefined')
  var fo_ns = {};
fo_ns.field_type = "textarea";

var rules = [];
rules.push("required,field_title,{$LANG["validation_no_field_title"]}");

Event.observe(document, "dom:loaded", function() { $("field_type").value = fo_ns.field_type; });
EOF;

// -------------------------------------------------------------------------------------------

// compile the template fields
$page_vars = array();
$page_vars["page"]          = "field_options";
$page_vars["page_url"]      = ft_get_page_url("edit_form_field_options", array("form_id" => $form_id));
$page_vars["tabs"]          = $tabs;
$page_vars["form_id"]       = $form_id;
$page_vars["form_info"]     = $form_info;
$page_vars["field"]         = $field_info;
$page_vars["previous_field_link"] = $previous_field_link;
$page_vars["next_field_link"] = $next_field_link;
$page_vars["head_title"]    = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_edit_field_options"]}";
$page_vars["js_messages"]   = array("notify_smart_fill_failure", "notify_smart_fill_failure", "phrase_unknown_field_type_c", "validation_num_rows_to_add");
$page_vars["head_js"]       = $head_js;
$page_vars["head_string"]   = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_field_options.js\"></script>";

ft_display_page("admin/forms/edit.tpl", $page_vars);