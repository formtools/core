<?php

require("../../../global/session_start.php");
ft_check_permission("admin");
$option_list_page = ft_load_field("page", "option_list_page", 1);
$request = array_merge($_POST, $_GET);
$num_option_lists_per_page = $_SESSION["ft"]["settings"]["num_option_lists_per_page"];

$order = ft_load_field("order", "option_list_order");

if (isset($_GET["delete"]))
  list($g_success, $g_message) = ft_delete_option_list($_GET["delete"]);

if (!is_numeric($option_list_page))
  $option_list_page = 1;

if (isset($request["add_option_list"]))
{
  $duplicate_list_id = "";
  if (isset($request["create_option_list_from_list_id"]) && !empty($request["create_option_list_from_list_id"]))
    $duplicate_list_id = $request["create_option_list_from_list_id"];

  $field_ids = array();
  if (isset($request["field_id"]))
    $field_ids[] = $request["field_id"];

  $list_id = ft_duplicate_option_list($duplicate_list_id, $field_ids);

  session_write_close();
  header("Location: edit.php?page=main&list_id=$list_id");
  exit;
}

// one additional check. If a user was on page 2 and just deleted (say) option list #11 and there are 10 per page, the
// visible page should be now be 1
$total_num_option_lists = ft_get_num_option_lists();
$total_pages = ceil($total_num_option_lists / $num_option_lists_per_page);
if ($option_list_page > $total_pages)
  $option_list_page = $total_pages;

$list_info = ft_get_option_lists($option_list_page, $order);
$num_option_lists = $list_info["num_results"];
$option_lists     = $list_info["results"];

$updated_field_option_groups = array();
$updated_option_lists = array();
foreach ($option_lists as $option_list)
{
  $list_id = $option_list["list_id"];

  // add the number of fields that use this option group
  $option_list["num_fields"] = ft_get_num_fields_using_option_list($list_id);
  if ($option_list["num_fields"] > 0) {
    $option_list["fields"] = ft_get_fields_using_option_list($list_id, array("group_by_form" => true));
  }

  // add the total number of options in this group
  $option_list["num_option_list_options"] = ft_get_num_options_in_option_list($list_id);
  $updated_option_lists[] = $option_list;
}

$all_option_lists = ft_get_option_lists("all");

// ------------------------------------------------------------------------------------------------

// compile template info
$page_vars = array();
$page_vars["page"] = "option_lists";
$page_vars["text_option_list_page"] = ft_eval_smarty_string($LANG["text_option_list_page"], array("link" => "../add/step1.php"));
$page_vars["page_url"] = ft_get_page_url("option_lists");
$page_vars["head_title"] = $LANG["phrase_option_lists"];
$page_vars["option_lists"] = $updated_option_lists;
$page_vars["num_option_lists"] = $num_option_lists;
$page_vars["all_option_lists"] = $all_option_lists["results"];
$page_vars["order"] = $order;
$page_vars["js_messages"] = array("validation_delete_non_empty_option_list", "confirm_delete_option_list",
  "phrase_please_confirm", "word_yes", "word_no", "word_edit", "word_remove");
$page_vars["pagination"] = ft_get_page_nav($num_option_lists, $num_option_lists_per_page, $option_list_page);
$page_vars["head_string"] =<<< END
<script src="$g_root_url/global/scripts/manage_option_lists.js"></script>
END;

ft_display_page("admin/forms/option_lists/index.tpl", $page_vars);
