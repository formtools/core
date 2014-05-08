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

// store the current selected tab in memory
$page = ft_load_field("page", "client_{$client_id}_page", "main");

$tabs = array(
  "main"     => array("tab_label" => $LANG["word_main"], "tab_link" => "{$_SERVER["PHP_SELF"]}?page=main&client_id={$client_id}"),
  "settings" => array("tab_label" => $LANG["word_settings"], "tab_link" => "{$_SERVER["PHP_SELF"]}?page=settings&client_id={$client_id}"),
  "forms"    => array("tab_label" => $LANG["word_forms"], "tab_link" => "{$_SERVER["PHP_SELF"]}?page=forms&client_id={$client_id}")
    );

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