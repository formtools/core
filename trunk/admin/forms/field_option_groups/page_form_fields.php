<?php

// this tab displays all the form fields that use this field option group
$group_info  = ft_get_field_option_group($group_id);

// ------------------------------------------------------------------------------------------------

// compile template info
$page_vars = array();
$page_vars["page"] = $page;
$page_vars["tabs"] = $tabs;
$page_vars["group_info"] = $group_info;
$page_vars["page_url"] = ft_get_page_url("edit_field_option_group");
$page_vars["head_title"] = $LANG["phrase_edit_field_option_group"];
$page_vars["form_fields"] = $form_fields;

ft_display_page("admin/forms/field_option_groups/edit.tpl", $page_vars);