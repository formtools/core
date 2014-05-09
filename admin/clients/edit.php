<?php

require("../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);
$client_id = ft_load_field("client_id", "curr_client_id", "");
if (empty($client_id))
{
  header("location: index.php");
  exit;
}

// figure out the "<< prev" and "next >>" links
$order   = ft_load_field("order", "client_sort_order", "last_name-ASC");
$keyword = ft_load_field("keyword", "client_search_keyword", "");
$status  = ft_load_field("status", "client_search_status", "");

// store the current selected tab in memory
$page = ft_load_field("page", "client_{$client_id}_page", "main");

$search_criteria = array(
  "order"     => $order,
  "keyword"   => $keyword,
  "status"    => $status
    );

$links = ft_get_client_prev_next_links($client_id, $search_criteria);

$prev_tabset_link = (!empty($links["prev_account_id"])) ? "edit.php?page=$page&client_id={$links["prev_account_id"]}" : "";
$next_tabset_link = (!empty($links["next_account_id"])) ? "edit.php?page=$page&client_id={$links["next_account_id"]}" : "";

$same_page = ft_get_clean_php_self();
$tabs = array(
  "main"     => array("tab_label" => $LANG["word_main"], "tab_link" => "{$same_page}?page=main&client_id={$client_id}"),
  "settings" => array("tab_label" => $LANG["word_settings"], "tab_link" => "{$same_page}?page=settings&client_id={$client_id}"),
  "forms"    => array("tab_label" => $LANG["word_forms"], "tab_link" => "{$same_page}?page=forms&client_id={$client_id}")
    );

// start compiling the page vars here (save duplicate code!)
$page_vars = array();
$page_vars["tabs"] = $tabs;
$page_vars["show_tabset_nav_links"] = true;
$page_vars["prev_tabset_link"] = $prev_tabset_link;
$page_vars["next_tabset_link"] = $next_tabset_link;
$page_vars["prev_tabset_link_label"] = $LANG["phrase_prev_client"];
$page_vars["next_tabset_link_label"] = $LANG["phrase_next_client"];

switch ($page)
{
  case "main":
    include("page_main.php");
    break;
  case "settings":
    include("page_settings.php");
    break;
  case "forms":
    include("page_forms.php");
    break;

  default:
    include("page_main.php");
    break;
}