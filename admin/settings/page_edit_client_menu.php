<?php

$is_new_menu = false;
$sortable_id = "edit_client_menu";

if (isset($request["create_new_menu"]))
{
  $menu_id = ft_create_blank_client_menu();
  $is_new_menu = true;
}
else
  $menu_id = $request["menu_id"];


if (isset($request["update_client_menu"]))
{
  $info = $_POST;
  $info["sortable_id"] = $sortable_id;
  list($g_success, $g_message) = ft_update_client_menu($info);
}

$menu_info = ft_get_client_menu($menu_id);
$num_menu_items = count($menu_info["menu_items"]);
$selected_client_ids = array();
foreach ($menu_info["clients"] as $client_info)
  $selected_client_ids[] = $client_info["account_id"];

// get a list of all menus names; this is used to ensure the uniqueness of the menu names to ward
// against confusion
$menus = ft_get_menu_list();
$menu_names = array();
foreach ($menus as $curr_menu_info)
{
  if ($menu_id == $curr_menu_info["menu_id"])
    continue;

  $menu_names[] = "\"" . htmlspecialchars($curr_menu_info["menu"]) . "\"";
}

$menu_list = implode(",", $menu_names);

$js = "var page_ns = {};
page_ns.menu_names = [$menu_list];
mm.num_rows = $num_menu_items;
";

if ($num_menu_items == 0)
{
  $js .= "$(function() { mm.add_menu_item_row(); });";
}

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars = array();
$page_vars["page"] = "edit_client_menu";
$page_vars["page_url"] = ft_get_page_url("edit_client_menu");
$page_vars["tabs"] = $tabs;
$page_vars["head_title"] = "{$_SESSION["ft"]["settings"]["program_name"]} - {$LANG["phrase_setup_options"]} - {$LANG["word_menus"]}";
$page_vars["menu"] = $menu_info;
$page_vars["is_new_menu"] = $is_new_menu;
$page_vars["selected_client_ids"] = $selected_client_ids;
$page_vars["sortable_id"] = $sortable_id;
$page_vars["head_string"] =<<< END
  <script src="$g_root_url/global/scripts/sortable.js?v=2"></script>
  <script src="$g_root_url/global/scripts/manage_menus.js"></script>
END;
$page_vars["js_messages"] = array("word_remove", "word_na", "word_form_c", "word_client_c", "word_url_c",
  "validation_menu_name_taken", "phrase_delete_row", "phrase_connect_rows", "phrase_disconnect_rows");
$page_vars["head_js"] = $js;

ft_display_page("admin/settings/index.tpl", $page_vars);
