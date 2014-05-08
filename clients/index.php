<?php

require_once("../global/session_start.php");
ft_check_permission("client");
_ft_cache_form_stats();
$forms = ft_search_forms($_SESSION["ft"]["account"]["account_id"]);

// ------------------------------------------------------------------------------------------

// compile header information
$page_vars = array();
$page_vars["head_title"] = ft_eval_smarty_string($_SESSION["ft"]["account"]["settings"]["page_titles"], array("page" => $LANG["word_forms"]));
$page_vars["page"]     = "client_forms";
$page_vars["page_url"] = ft_get_page_url("client_forms");
$page_vars["forms"] = $forms;

ft_display_page("clients/index.tpl", $page_vars);