<?php

require_once("../global/session_start.php");
ft_check_permission("client");
_ft_cache_form_stats();

$account_id = $_SESSION["ft"]["account"]["account_id"];

if (isset($_GET["reset"]))
{
  $_SESSION["ft"]["form_sort_order"] = "";
  $_SESSION["ft"]["form_search_keyword"] = "";
}
$order   = ft_load_field("order", "form_sort_order", "form_name-ASC");
$keyword = ft_load_field("keyword", "form_search_keyword", "");

$search_criteria = array(
  "order"   => $order,
  "keyword" => $keyword
    );

$num_client_forms = count(ft_get_client_forms($account_id));
$forms            = ft_search_forms($account_id, false, $search_criteria);
$client_info      = ft_get_account_info($account_id);
$forms_page_default_message = ft_eval_smarty_string($client_info["settings"]["forms_page_default_message"]);

// ------------------------------------------------------------------------------------------

// compile header information
$page_vars = array();
$page_vars["head_title"] = ft_eval_smarty_string($_SESSION["ft"]["account"]["settings"]["page_titles"], array("page" => $LANG["word_forms"]));
$page_vars["page"]     = "client_forms";
$page_vars["page_url"] = ft_get_page_url("client_forms");
$page_vars["num_client_forms"] = $num_client_forms;
$page_vars["forms"] = $forms;
$page_vars["forms_page_default_message"] = $forms_page_default_message;
$page_vars["search_criteria"] = $search_criteria;
$page_vars["js_messages"] = array("phrase_open_form_in_new_tab_or_win", "word_close", "phrase_show_form");
$page_vars["head_js"] =<<< END
$(function() { ft.init_show_form_links(); });
END;

ft_display_page("clients/index.tpl", $page_vars);
