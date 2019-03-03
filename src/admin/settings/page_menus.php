<?php

use FormTools\Core;
use FormTools\General;
use FormTools\Menus;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;


$menu_page = General::loadField("menu_page", "menu_page", 1);

// if required, delete the menu. If any clients were assigned to this menu, the success response will
// include their names
$success = true;
$message = "";
if (isset($request["delete"])) {
    list($success, $message) = Menus::deleteClientMenu($request["delete"]);
}
if (isset($_GET["mass_assign"])) {
    list($success, $message) = Menus::updateClientMenus($_GET["accounts"], $_GET["menu_id"]);
}

$menus = Menus::getList($menu_page, Sessions::get("settings.num_menus_per_page"));
$LANG = Core::$L;

$head_js =<<< END
var page_ns = {
  delete_menu_dialog: $("<div></div>")
}

page_ns.delete_menu = function(menu_id) {
  ft.create_dialog({
    dialog:   page_ns.delete_menu_dialog,
    title:    "{$LANG["phrase_please_confirm"]}",
    content:  "{$LANG["confirm_delete_menu"]}",
    popup_type: "warning",
    buttons: {
      "{$LANG["word_yes"]}": function() {
        window.location = "index.php?page=menus&delete=" + menu_id;
        $(this).dialog("close");
      },
      "{$LANG["word_no"]}": function() {
        $(this).dialog("close");
      }
    }
  });

  return false;
}
END;


$page_vars = array(
    "page" => "menus",
    "g_success" => $success,
    "g_message" => $message,
    "page_url" => Pages::getPageUrl("settings_menus"),
    "tabs" => $tabs,
    "head_title" => "{$LANG["word_settings"]} - {$LANG["word_menus"]}",
    "menus" => $menus["results"],
    "total_num_menus" => $menus["num_results"],
    "pagination" => General::getPageNav($menus["num_results"], Sessions::get("settings.num_menus_per_page"), $menu_page, "page=menus", "menu_page"),
    "js_messages" => array("word_remove"),
    "head_js" => $head_js
);

Themes::displayPage("admin/settings/index.tpl", $page_vars);
