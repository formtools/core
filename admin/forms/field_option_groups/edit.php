<?php

require("../../../global/session_start.php");
ft_check_permission("admin");
$request = array_merge($_POST, $_GET);
$group_id = ft_load_field("group_id", "field_option_group_id", "");
$page     = ft_load_field("page", "field_option_groups_tab", "main");
$form_fields = ft_get_fields_using_field_option_group($group_id);
$num_fields = count($form_fields);

if (empty($group_id))
{
	header("location: index.php");
	exit;
}

$tabs = array(
  "main" => array(
      "tab_label" => $LANG["word_main"],
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=main"
        ),
  "form_fields" => array(
      "tab_label" => "{$LANG["phrase_form_fields"]} ($num_fields)",
      "tab_link" => "{$_SERVER["PHP_SELF"]}?page=form_fields"
        )
    );


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
