<?php

if (isset($request["update_admin_menu"]))
{
	$info = $_POST;
	$info["account_id"] = $_SESSION["ft"]["account"]["account_id"];
  list($g_success, $g_message) = ft_update_admin_menu($info);
}

$menu = ft_get_admin_menu();

// compile the header information
$page_vars = array();
$page_vars["page"] = "edit_admin_menu";
$page_vars["page_url"] = ft_get_page_url("edit_admin_menu");
$page_vars["tabs"] = $tabs;
$page_vars["head_title"] = "{$_SESSION["ft"]["settings"]["program_name"]} - {$LANG["phrase_setup_options"]}";
$page_vars["menu"] = $menu;
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_menus.js\"></script>";
$page_vars["js_messages"] = array("word_remove", "word_na", "notify_required_admin_pages", "word_form_c", "word_client_c",
  "word_url_c", "word_forms", "word_clients", "word_settings", "phrase_your_account", "word_modules", "word_logout");

ft_display_page("admin/settings/index.tpl", $page_vars);