<?php

if (isset($request["update_email_settings"]))
  list($g_success, $g_message) = ft_set_field_as_email_field($form_id, $request);

if (isset($request["delete_form_email_id"]))
  list($g_success, $g_message) = ft_unset_field_as_email_field($request["delete_form_email_id"]);

$form_info = ft_get_form($form_id);
$form_fields = ft_get_form_fields($form_id);

$registered_form_emails = ft_get_email_fields($form_id);

// remove any columns that are already used in $registered_form_emails
$used_cols = array();
foreach ($registered_form_emails as $row)
{
  $used_cols[] = $row["email_field_id"];
  $used_cols[] = $row["first_name_field_id"];
  $used_cols[] = $row["last_name_field_id"];
}

$trimmed_cols = array();
foreach ($form_fields as $field_info)
{
  if (!in_array($field_info["field_id"], $used_cols) && $field_info["is_system_field"] == "no")
    $trimmed_cols[$field_info["field_id"]] = $field_info["field_title"];
}

// compile the templates information
$page_vars["page"]       = "email_settings";
$page_vars["page_url"]   = ft_get_page_url("edit_form_email_settings", array("form_id" => $form_id));
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_email_settings"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["columns"]    = $trimmed_cols;
$page_vars["registered_form_emails"] = $registered_form_emails;
$page_vars["js_messages"] = array("confirm_delete_email_field_config", "phrase_please_confirm", "word_yes", "word_no", "word_remove");
$page_vars["head_string"] =<<<END
<script src="$g_root_url/global/scripts/manage_email_templates.js?v=3"></script>
END;

$page_vars["head_js"] =<<<END
g.rules = [];
g.rules.push("required,email_field_id,{$LANG["validation_no_email_config_field"]}");
END;

ft_display_page("admin/forms/edit.tpl", $page_vars);