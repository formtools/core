<?php

namespace FormTools;



class Menus
{
    /**
     * Used for the hide/show submenu mechanism. Menu pages can set up one-level hierarchies in
     * which one page is considered a child of another. This function find the URL of the parent of a particular
     * menu item. A few conditions:
     *   - if the page doesn't have a parent, it either returns the LAST parent page URL in memory,
     *     (if it's there) or empty string.
     *   - if the page IS a parent, it returns its own page URL
     *   - if the page has more than one parent, it returns the FIRST one only
     *
     * @param string $page_identifier
     * @return string $parent_page_identifier
     */
    public static function getParentPageUrl($page_url)
    {
        $root_url = Core::getRootUrl();
        $page_found = false;
        $last_parent_page_url = "";

        // if there's no menu in memory, the person isn't logged in. Just return the empty string
        if (!Sessions::exists("menu")) {
            return "";
        }

        $menu_items = Sessions::get("menu_items", "menu");

        for ($i=0; $i<count($menu_items); $i++) {
            $curr_page_url = $menu_items[$i]["url"];
            if ($menu_items[$i]["is_submenu"] == "no") {
                $last_parent_page_url = $curr_page_url;
            }
            if ($curr_page_url == $root_url . $page_url) {
                $page_found = true;
                break;
            }
        }

        if (!$page_found) {
            if (Sessions::exists("last_parent_url", "menu")) {
                $found_page = Sessions::get("last_parent_url", "menu");
            } else {
                $found_page = "";
            }
        } else {
            $found_page = $last_parent_page_url;
            Sessions::set("last_parent_url", $found_page, "menu");
        }

        return $found_page;
    }


    /**
     * This function is called whenever an administrator or client logs in. It determines the exact
     * content of a menu and caches it in the "menu" session key.
     *
     * @param integer $account_id
     */
    public static function cacheAccountMenu($account_id)
    {
        $root_url = Core::getRootUrl();
        $menu_info = self::getMenuByAccountId($account_id);

        $menu_template_info = array();
        for ($i=0; $i<count($menu_info["menu_items"]); $i++) {
            $curr_item = $menu_info["menu_items"][$i];
            $url = (preg_match("/^http/", $curr_item["url"])) ? $curr_item["url"] : $root_url . $curr_item["url"];

            $menu_template_info[] = array(
                "url"             => $url,
                "display_text"    => $curr_item["display_text"],
                "page_identifier" => $curr_item["page_identifier"],
                "is_submenu"      => $curr_item["is_submenu"]
            );
        }

        Sessions::set("menu_items", $menu_template_info, "menu");
    }


    /**
     * Returns everything about a user's menu.
     *
     * @param integer $account_id
     */
    public static function getMenuByAccountId($account_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM {PREFIX}menus m, {PREFIX}accounts a
            WHERE a.account_id = :account_id AND
                  m.menu_id = a.menu_id
        ");
        $db->bind("account_id", $account_id);
        $db->execute();
        $menu_info = $db->fetch();
        $menu_id = $menu_info["menu_id"];

        $db->query("
            SELECT *
            FROM   {PREFIX}menu_items
            WHERE  menu_id = :menu_id
            ORDER BY list_order
        ");
        $db->bind("menu_id", $menu_id);
        $db->execute();
        $info = $db->getResultsArray();

        $menu_info["menu_items"] = $info;

        return $menu_info;
    }

}
