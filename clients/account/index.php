<?php

require_once("../../global/session_start.php");
ft_check_permission("client");
$request = array_merge($_POST, $_GET);
$account_id = $_SESSION["ft"]["account"]["account_id"];

// store the current selected tab in memory
$page = ft_load_field("page", "account_page", "main");

$same_page = ft_get_clean_php_self();
$tabs = array(
  "main"     => array("tab_label" => $LANG["word_main"], "tab_link" => "{$same_page}?page=main"),
  "settings" => array("tab_label" => $LANG["word_settings"], "tab_link" => "{$same_page}?page=settings")
    );

// ------------------------------------------------------------------------------------------

switch ($page)
{
  case "main":
    include("page_main.php");
    break;
  case "settings":
    include("page_settings.php");
    break;
  default:
    include("page_main.php");
    break;
}
