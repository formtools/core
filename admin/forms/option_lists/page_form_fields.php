<?php

// this tab displays all the form fields that use this field option group
$list_info = ft_get_option_list($list_id);

$form_fields = array();
if ($num_fields > 0) {
  $form_fields = ft_get_fields_using_option_list($list_id);
}

$forms = ft_get_forms();
$incomplete_forms = array();
foreach ($forms as $form_info)
{
  if ($form_info["is_complete"] == "no")
    $incomplete_forms[] = $form_info["form_id"];
}

// ------------------------------------------------------------------------------------------------

$page_vars["list_info"] = $list_info;
$page_vars["page_url"] = ft_get_page_url("edit_option_list");
$page_vars["head_title"] = $LANG["phrase_edit_option_list"];
$page_vars["num_fields_using_option_list"] = $num_fields;
$page_vars["incomplete_forms"] = $incomplete_forms;
$page_vars["form_fields"] = $form_fields;

ft_display_page("admin/forms/option_lists/edit.tpl", $page_vars);
