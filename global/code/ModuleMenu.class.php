<?php

/**
 * Module menus.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class ModuleMenu
{

    /**
     * This is called implicitly by the Themes::displayModulePage function (only!). That function is used
     * to display any module page; it automatically calls this function to load any custom navigation
     * menu items for a particular module. Then, the theme's modules_header.tpl template uses this
     * information to render the module nav in an appropriate style.
     *
     * Note: to resolve path issues for developers when specifying the paths of the menu items, they may
     * enter the {$module_dir} Smarty placeholder, which is here escaped to the appropriate URL.
     *
     * @param integer $module_id
     */
    public static function getMenuItems($module_id, $module_folder)
    {
        $db = Core::$db;
        $root_url = Core::getRootUrl();

        $db->query("
            SELECT *
            FROM {PREFIX}module_menu_items
            WHERE module_id = :module_id
            ORDER BY list_order ASC
        ");
        $db->bind("module_id", $module_id);
        $db->execute();

        $placeholders = array(
        "module_dir" => "$root_url/modules/$module_folder"
        );

        $menu_items = array();
        foreach ($db->fetchAll() as $row) {
            $row["url"] = General::evalSmartyString($row["url"], $placeholders);
            $menu_items[] = $row;
        }

        extract(Hooks::processHookCalls("end", compact("menu_items", "module_id", "module_folder"), array("menu_items")), EXTR_OVERWRITE);

        return $menu_items;
    }


    public static function addMenuItems($module_id, $module)
    {
        $LANG = Core::$L;
        $L = $module->getLangStrings();

        $order = 1;
        $module_nav = $module->getModuleNav();


        foreach ($module_nav as $lang_key => $info) {
            $url        = $info[0];
            $is_submenu = ($info[1]) ? "yes" : "no";

            if (empty($lang_key) || empty($url)) {
                continue;
            }

            // odd this. Why not just store the lang string in the DB? That way it'll be translated for each user...
            $display_text = isset($L[$lang_key]) ? $L[$lang_key] : $LANG[$lang_key];

            ModuleMenu::addMenuItem($module_id, $display_text, $url, $is_submenu, $order);
            $order++;
        }
    }

    public static function addMenuItem($module_id, $display_text, $url, $is_submenu, $order)
    {
        $db = Core::$db;

        $db->query("
            INSERT INTO {PREFIX}module_menu_items (module_id, display_text, url, is_submenu, list_order)
            VALUES (:module_id, :display_text, :url, :is_submenu, :nav_order)
        ");
        $db->bindAll(array(
            "module_id" => $module_id,
            "display_text" => $display_text,
            "url" => $url,
            "is_submenu" => $is_submenu,
            "nav_order" => $order
        ));
        $db->execute();
    }


    public static function resetModuleNav($module_id, $nav, $L)
    {
        $LANG = Core::$L;

        ModuleMenu::clearModuleNav($module_id);

        $order = 1;
		foreach ($nav as $lang_file_key => $info) {
            $url        = $info[0];
            $is_submenu = ($info[1]) ? "yes" : "no";
            if (empty($lang_file_key) || empty($url)) {
                continue;
            }

            $display_text = isset($L[$lang_file_key]) ? $L[$lang_file_key] : $LANG[$lang_file_key];
            ModuleMenu::addMenuItem($module_id, $display_text, $url, $is_submenu, $order);
            $order++;
        }
    }


    public static function clearModuleNav($module_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}module_menu_items WHERE module_id = :module_id");
        $db->bind("module_id", $module_id);
        $db->execute();
    }

}

