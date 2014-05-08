<?php

if (isset($request["update_email_settings"]))
  list($g_success, $g_message) = ft_register_form_email_info($form_id, $request);

if (isset($request["delete_form_email_id"]))
  list($g_success, $g_message) = ft_unregister_form_email_info($request["delete_form_email_id"]);

$form_info = ft_get_form($form_id);
$columns = ft_get_form_column_names($form_id, "", true);
$registered_form_emails = ft_get_registered_form_emails($form_id);

// remove any columns that are already used in $registered_form_emails
$used_cols = array();
foreach ($registered_form_emails as $row)
{
  $used_cols[] = $row["email_field"];
  $used_cols[] = $row["first_name_field"];
  $used_cols[] = $row["last_name_field"];
}

$trimmed_cols = array();
while (list($key, $field_title) = each($columns))
{
  if (!in_array($key, $used_cols))
    $trimmed_cols[$key] = $field_title;
}

// compile the templates information
$page_vars = array();
$page_vars["page"]       = "email_settings";
$page_vars["page_url"]   = ft_get_page_url("edit_form_email_settings", array("form_id" => $form_id));
$page_vars["tabs"]       = $tabs;
$page_vars["form_id"]    = $form_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_email_settings"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["columns"]    = $trimmed_cols;
$page_vars["registered_form_emails"] = $registered_form_emails;
$page_vars["js_messages"] = array("confirm_delete_email_field_config");
$page_vars["head_string"] =<<<EOF
<script type="text/javascript" src="$g_root_url/global/scripts/manage_email_templates.js"></script>
EOF;
$page_vars["head_js"] =<<<EOF
g.rules = [];
g.rules.push("required,email_field,{$LANG["validation_no_email_config_field"]}");
EOF;

ft_display_page("admin/forms/edit.tpl", $page_vars);