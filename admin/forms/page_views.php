<?php

$sortable_id = "view_list";
$form_info = ft_get_form($form_id);

// this is called when the user clicks Update OR deletes a group. The delete group first updates the
// view order to ensure that whatever group is being deleted actually has the View that the user expects
if (isset($request["update_views"]) || isset($request["{$sortable_id}_sortable__delete_group"]))
{
  $request["sortable_id"] = $sortable_id;
  list($g_success, $g_message) = ft_update_views($form_id, $request);

  if (isset($request["{$sortable_id}_sortable__delete_group"]))
    list($g_success, $g_message) = ft_delete_view_group($request["{$sortable_id}_sortable__delete_group"]);
}

// if the user deleted all their Views & View Groups, a special "add default view" option appears
if (isset($request["recreate_initial_view"]))
{
  list($g_success, $g_message) = ft_add_default_view($form_id);
}

$grouped_views = ft_get_grouped_views($form_id, array("omit_empty_groups" => false, "include_clients" => true));

// figure out how many Views we're dealing with
$num_views = 0;
foreach ($grouped_views as $curr_group)
  $num_views += count($curr_group["views"]);

// ------------------------------------------------------------------------------------------------

// compile the template information
$page_vars["page"]       = "views";
$page_vars["page_url"]   = ft_get_page_url("edit_form_views", array("form_id" => $form_id));
$page_vars["grouped_views"] = $grouped_views;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["word_views"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["sortable_id"] = $sortable_id;
$page_vars["js_messages"] = array("phrase_remove_row", "phrase_create_group", "word_cancel", "phrase_delete_view",
  "word_yes", "word_no", "confirm_delete_view", "notify_view_deleted", "phrase_please_confirm",
  "confirm_delete_group", "phrase_create_new_view");
$page_vars["num_views"] = $num_views;
$page_vars["head_string"] =<<< END
<script src="$g_root_url/global/scripts/sortable.js?v=2"></script>
<script src="$g_root_url/global/scripts/manage_views.js?v=4"></script>
END;

ft_display_page("admin/forms/edit.tpl", $page_vars);
