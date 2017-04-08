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

        $menu_items = Sessions::get("menu.menu_items");

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
            if (Sessions::exists("menu.last_parent_url")) {
                $found_page = Sessions::get("menu.last_parent_url");
            } else {
                $found_page = "";
            }
        } else {
            $found_page = $last_parent_page_url;
            Sessions::set("menu.last_parent_url", $found_page);
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

        Sessions::set("menu.menu_items", $menu_template_info);
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


    /**
     * Returns an array of menu hashes for all menus in the database. Ordered by menu name.
     *
     * @return array
     */
    public static function getMenuList()
    {
        $db = Core::$db;
        $db->query("
            SELECT *
            FROM   {PREFIX}menus
            ORDER BY menu
        ");
        $db->execute();

        $menus = array();
        foreach ($db->fetchAll() as $row) {
            $menus[] = $row;
        }

        extract(Hooks::processHookCalls("end", compact("menus"), array("menus")), EXTR_OVERWRITE);

        return $menus;
    }


    /**
     * Builds a dropdown of available pages for a client. This is used by the administrator
     * to display the list of pages available for the clients menus, etc.
     *
     * @param string $selected
     * @param array $attributes
     * @param array $omit_pages
     */
    public static function getClientMenuPagesDropdown($selected, $attributes, $omit_pages = array())
    {
        global $LANG;

        // stores the non-option lines of the select box: <select>, </select> and the optgroups
        $select_lines   = array();
        $select_lines[] = array("type" => "select_open");

        if (!in_array("", $omit_pages))
            $select_lines[] = array("type" => "option", "v" => $LANG["phrase_please_select"]);

        if (!in_array("custom_url", $omit_pages))
        {
            $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_custom"]);
            $select_lines[] = array("type" => "option", "k" => "custom_url", "v" => $LANG["phrase_custom_url"]);
            $select_lines[] = array("type" => "optgroup_close");
        }

        $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_main"]);

        if (!in_array("client_forms", $omit_pages))
            $select_lines[] = array("type" => "option", "k" => "client_forms", "v" => $LANG["word_forms"]);
        if (!in_array("client_form_submissions", $omit_pages))
            $select_lines[] = array("type" => "option", "k" => "client_form_submissions", "v" => $LANG["phrase_form_submissions"]);
        if (!in_array("client_account", $omit_pages))
            $select_lines[] = array("type" => "option", "k" => "client_account", "v" => $LANG["word_account"]);
        if (!in_array("client_account_login", $omit_pages))
            $select_lines[] = array("type" => "option", "k" => "client_account_login", "v" => $LANG["phrase_login_info"]);
        if (!in_array("client_account_settings", $omit_pages))
            $select_lines[] = array("type" => "option", "k" => "client_account_settings", "v" => $LANG["phrase_account_settings"]);
        if (!in_array("logout", $omit_pages))
            $select_lines[] = array("type" => "option", "k" => "logout", "v" => $LANG["word_logout"]);

        $select_lines[] = array("type" => "optgroup_close");

        // if the Pages module is enabled, display any custom pages that have been defined. Only show the optgroup
        // if there's at least ONE page defined
        if (ft_check_module_enabled("pages"))
        {
            ft_include_module("pages");
            $pages_info = pg_get_pages("all");
            $pages = $pages_info["results"];

            if (count($pages) > 0)
            {
                $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["phrase_pages_module"]);
                foreach ($pages as $page)
                {
                    $page_id = $page["page_id"];
                    $page_name = $page["page_name"];
                    $select_lines[] = array("type" => "option", "k" => "page_{$page_id}", "v" => $page_name);
                }
                $select_lines[] = array("type" => "optgroup_close");
            }
        }

        extract(Hooks::processHookCalls("middle", compact("select_lines"), array("select_lines")), EXTR_OVERWRITE);

        $select_lines[] = array("type" => "select_close");

        // now build the HTML
        $dd = "";
        foreach ($select_lines as $line)
        {
            switch ($line["type"])
            {
                case "select_open":
                    $attribute_str = "";
                    while (list($key, $value) = each($attributes))
                        $attribute_str .= " $key=\"$value\"";
                    $dd .= "<select $attribute_str>";
                    break;
                case "select_close":
                    $dd .= "</select>";
                    break;
                case "optgroup_open":
                    $dd .= "<optgroup label=\"{$line["label"]}\">";
                    break;
                case "optgroup_close":
                    $dd .= "</optgroup>";
                    break;
                case "option":
                    $key   = $line["k"];
                    $value = $line["v"];
                    $dd .= "<option value=\"{$key}\"" . (($selected == $key) ? " selected" : "") . ">$value</option>\n";
                    break;
            }
        }

        return $dd;
    }

}
