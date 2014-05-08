<?php

require("../../../global/session_start.php");
ft_check_permission("admin");
$field_option_groups_page = ft_load_field("page", "field_option_groups_page", 1);
$request = array_merge($_POST, $_GET);


if (isset($_GET["delete"]))
  list($g_success, $g_message) = ft_delete_field_option_group($_GET["delete"]);

if (!is_numeric($field_option_groups_page))
	$field_option_groups_page = 1;

if (isset($request["add_field_option_group"]))
{
	$duplicate_group_id = "";
	if (isset($request["create_field_option_group_from_group_id"]) && !empty($request["create_field_option_group_from_group_id"]))
	  $duplicate_group_id = $request["create_field_option_group_from_group_id"];

	$field_ids = array();
  if (isset($request["field_id"]))
    $field_ids[] = $request["field_id"];

	$group_id = ft_duplicate_field_option_group($duplicate_group_id, $field_ids);

	session_write_close();
  header("Location: edit.php?page=main&group_id=$group_id");
  exit;
}

$group_info = ft_get_field_option_groups($field_option_groups_page);
$num_field_option_groups = $group_info["num_results"];
$field_option_groups     = $group_info["results"];

$updated_field_option_groups = array();
foreach ($field_option_groups as $group_info)
{
	$group_id = $group_info["group_id"];

	// add the number of fields that use this option group
	$group_info["num_fields"] = ft_get_num_fields_using_field_option_group($group_id);

	// add the total number of options in this group
  $field_group_options = ft_get_field_group_options($group_id);
  $group_info["num_field_group_options"] = count($field_group_options);

	$updated_field_option_groups[] = $group_info;
}

$all_field_option_groups = ft_get_field_option_groups("all");

// ------------------------------------------------------------------------------------------------

// compile template info
$page_vars = array();
$page_vars["page"] = "field_option_groups";
$page_vars["text_field_option_group_page"] = ft_eval_smarty_string($LANG["text_field_option_group_page"], array("link" => "../add/"));
$page_vars["page_url"] = ft_get_page_url("field_option_groups");
$page_vars["head_title"] = $LANG["phrase_field_option_groups"];
$page_vars["field_option_groups"] = $updated_field_option_groups;
$page_vars["num_field_option_groups"] = $num_field_option_groups;
$page_vars["all_field_option_groups"] = $all_field_option_groups["results"];
$page_vars["js_messages"] = array("validation_field_option_group_has_assigned_fields", "confirm_delete_field_option_group");
$page_vars["pagination"] = ft_get_page_nav($num_field_option_groups, $_SESSION["ft"]["settings"]["num_field_option_groups_per_page"], $field_option_groups_page);
$page_vars["head_js"] =<<< EOF
var page_ns = {};

/**
 * Deletes a field option group. The second "may_delete" boolean parameter is determined by whether this
 * field option group is used by any fields. If it is, they can't delete it: they need to re-assign the fields
 * to other option groups or change the field types.
 */
page_ns.delete_field_option_group = function(group_id, may_delete)
{
  if (!may_delete)
  {
    var link = "edit.php?page=form_fields&group_id=" + group_id;
    var message = g.messages["validation_field_option_group_has_assigned_fields"].replace(/\{\\\$link\}/, link);
    ft.display_message("ft_message", false, message);
    return false;
  }

  if (confirm(g.messages["confirm_delete_field_option_group"]))
    window.location = "index.php?delete=" + group_id;

  return false;
}

EOF;
ft_display_page("admin/forms/field_option_groups/index.tpl", $page_vars);
