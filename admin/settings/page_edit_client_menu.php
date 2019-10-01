<?php

use FormTools\Core;
use FormTools\Menus;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

$is_new_menu = false;
$sortable_id = "edit_client_menu";

if (isset($request["create_new_menu"])) {
	$menu_id = Menus::createBlankClientMenu();
	$is_new_menu = true;
} else {
    $menu_id = $request["menu_id"];
}

$success = true;
$message = "";
if (isset($request["update_client_menu"])) {
	$info = $_POST;
	$info["sortable_id"] = $sortable_id;
	list($success, $message) = Menus::updateClientMenu($info);
}

$menu_info = Menus::getClientMenu($menu_id);
$num_menu_items = count($menu_info["menu_items"]);
$selected_client_ids = array();
foreach ($menu_info["clients"] as $client_info) {
    $selected_client_ids[] = $client_info["account_id"];
}

// get a list of all menus names; this is used to ensure the uniqueness of the menu names to ward
// against confusion
$menus = Menus::getMenuList();
$menu_names = array();
foreach ($menus as $curr_menu_info) {
	if ($menu_id == $curr_menu_info["menu_id"]) {
        continue;
    }
	$menu_names[] = "\"" . htmlspecialchars($curr_menu_info["menu"]) . "\"";
}

$menu_list = implode(",", $menu_names);

$js = "var page_ns = {};
page_ns.menu_names = [$menu_list];
mm.num_rows = $num_menu_items;
";

if ($num_menu_items == 0) {
	$js .= "$(function() { mm.add_menu_item_row(); });";
}

// ------------------------------------------------------------------------------------------------

$LANG = Core::$L;
$root_url = Core::getRootUrl();
$program_name = Sessions::get("settings.program_name");

$page_vars = array(
    "page" => "edit_client_menu",
    "g_success" => $success,
    "g_message" => $message,
    "page_url" => Pages::getPageUrl("edit_client_menu"),
    "tabs" => $tabs,
    "head_title" => "$program_name - {$LANG["phrase_setup_options"]} - {$LANG["word_menus"]}",
    "menu" => $menu_info,
    "is_new_menu" => $is_new_menu,
    "selected_client_ids" => $selected_client_ids,
    "sortable_id" => $sortable_id,
    "js_messages" => array(
        "word_remove", "word_na", "word_form_c", "word_client_c", "word_url_c",
        "validation_menu_name_taken", "phrase_delete_row", "phrase_connect_rows", "phrase_disconnect_rows"
    ),
    "head_js" => $js
);

$page_vars["head_string"] =<<< END
  <script src="$root_url/global/scripts/sortable.js?v=2"></script>
  <script src="$root_url/global/scripts/manage_menus.js"></script>
END;

Themes::displayPage("admin/settings/index.tpl", $page_vars);
