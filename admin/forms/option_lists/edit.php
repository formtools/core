<?php

require("../../../global/session_start.php");
ft_check_permission("admin");
$request = array_merge($_POST, $_GET);
$list_id = ft_load_field("list_id", "option_list_id", "");
$page    = ft_load_field("page", "field_option_groups_tab", "main");
$order   = ft_load_field("order", "option_list_order");

// used to display the total count of fields using this option list on the Form Fields tab.
$num_fields = ft_get_num_fields_using_option_list($list_id);

if (empty($list_id))
{
  header("location: index.php");
  exit;
}

$links = ft_get_option_list_prev_next_links($list_id, $order);
$prev_tabset_link = (!empty($links["prev_option_list_id"])) ? "edit.php?page=$page&list_id={$links["prev_option_list_id"]}" : "";
$next_tabset_link = (!empty($links["next_option_list_id"])) ? "edit.php?page=$page&list_id={$links["next_option_list_id"]}" : "";


$same_page = ft_get_clean_php_self();
$tabs = array(
  "main" => array(
    "tab_label" => $LANG["word_main"],
    "tab_link" => "{$same_page}?page=main"
      ),
  "form_fields" => array(
    "tab_label" => "{$LANG["phrase_form_fields"]} ($num_fields)",
    "tab_link" => "{$same_page}?page=form_fields"
      )
);

// start compiling the info here
$page_vars = array();
$page_vars["page"] = $page;
$page_vars["unique_page_id"] = "edit_option_list_main_tab";
$page_vars["tabs"] = $tabs;
$page_vars["show_tabset_nav_links"] = true;
$page_vars["prev_tabset_link"] = $prev_tabset_link;
$page_vars["next_tabset_link"] = $next_tabset_link;

switch ($page)
{
  case "main":
    require("page_main.php");
    break;
  case "form_fields":
    require("page_form_fields.php");
    break;

  default:
    require("page_main.php");
    break;
}
