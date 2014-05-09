<?php

$menu_page = ft_load_field("menu_page", "menu_page", 1);

// if required, delete the menu. If any clients were assigned to this menu, the success response will
// include their names
if (isset($request["delete"]))
  list($g_success, $g_message) = ft_delete_client_menu($request["delete"]);

if (isset($_GET["mass_assign"]))
  list($g_success, $g_message) = ft_update_client_menus($_GET["accounts"], $_GET["menu_id"]);

$menus = ft_get_menus($menu_page);

// compile the header information
$page_vars = array();
$page_vars["page"] = "menus";
$page_vars["page_url"] = ft_get_page_url("settings_menus");
$page_vars["tabs"] = $tabs;
$page_vars["head_title"] = "{$LANG["word_settings"]} - {$LANG["word_menus"]}";
$page_vars["menus"] = $menus["results"];
$page_vars["total_num_menus"] = $menus["num_results"];
$page_vars["pagination"] = ft_get_page_nav($menus["num_results"], $_SESSION["ft"]["settings"]["num_menus_per_page"], $menu_page, "page=menus", "menu_page");
$page_vars["js_messages"] = array("word_remove");

$page_vars["head_js"] =<<< END
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

ft_display_page("admin/settings/index.tpl", $page_vars);
