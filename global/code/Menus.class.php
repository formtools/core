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

        if (!in_array("", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "", "v" => $LANG["phrase_please_select"]);
        }
        if (!in_array("custom_url", $omit_pages)) {
            $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_custom"]);
            $select_lines[] = array("type" => "option", "k" => "custom_url", "v" => $LANG["phrase_custom_url"]);
            $select_lines[] = array("type" => "optgroup_close");
        }

        $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_main"]);

        if (!in_array("client_forms", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "client_forms", "v" => $LANG["word_forms"]);
        }
        if (!in_array("client_form_submissions", $omit_pages)) {
            $select_lines[] = array(
                "type" => "option",
                "k" => "client_form_submissions",
                "v" => $LANG["phrase_form_submissions"]
            );
        }
        if (!in_array("client_account", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "client_account", "v" => $LANG["word_account"]);
        }
        if (!in_array("client_account_login", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "client_account_login", "v" => $LANG["phrase_login_info"]);
        }
        if (!in_array("client_account_settings", $omit_pages)) {
            $select_lines[] = array(
                "type" => "option",
                "k" => "client_account_settings",
                "v" => $LANG["phrase_account_settings"]
            );
        }
        if (!in_array("logout", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "logout", "v" => $LANG["word_logout"]);
        }

        $select_lines[] = array("type" => "optgroup_close");

        // if the Pages module is enabled, display any custom pages that have been defined. Only show the optgroup
        // if there's at least ONE page defined
        if (Modules::checkModuleEnabled("pages")) {
            ft_include_module("pages");
            $pages_info = pg_get_pages("all");
            $pages = $pages_info["results"];

            if (count($pages) > 0) {
                $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["phrase_pages_module"]);
                foreach ($pages as $page) {
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
        foreach ($select_lines as $line) {
            switch ($line["type"]) {
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


    /**
     * Retrieves a list of all menus.
     * @return array a hash of view information
     */
    public static function getList($page_num = 1, $per_page = 10)
    {
        $db = Core::$db;

        // determine the LIMIT clause
        if (empty($page_num)) {
            $page_num = 1;
        }
        $first_item = ($page_num - 1) * $per_page;
        $limit_clause = "LIMIT $first_item, $per_page";

        $db->query("
            SELECT *
            FROM 	 {PREFIX}menus
            ORDER BY menu
            $limit_clause
        ");
        $db->execute();
        $results = $db->fetchAll();

        $db->query("SELECT count(*) as c FROM {PREFIX}menus");
        $db->execute();
        $count = $db->fetch();

        // select all account associated with this menu
        $info = array();
        foreach ($results as $row) {
            $db->query("
                SELECT account_id, first_name, last_name, account_type
                FROM   {PREFIX}accounts a 
                WHERE  menu_id = :menu_id
            ");
            $db->bind("menu_id", $row["menu_id"]);
            $db->execute();

            $accounts = array();
            foreach ($db->fetchAll() as $account_row) {
                $accounts[] = $account_row;
            }
            $row["account_info"] = $accounts;
            $info[] = $row;
        }

        $return_hash["results"] = $info;
        $return_hash["num_results"] = $count["c"];

        extract(Hooks::processHookCalls("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

        return $return_hash;
    }


    /**
     * This function builds the dropdown menu that lists all available pages for an administrator account.
     *
     * @param string $default_value
     * @param array $attributes a hash of attributes, e.g. "name" => "row1", "onchange" => "myfunc()"
     * @param boolean $omit_pages this determines which fields should be OMITTED from the generated
     *   HTML; it's an array whose values correspond to the page names found at the top of this file.
     * @return string
     */
    public static function getAdminMenuPagesDropdown($selected, $attributes, $is_building_menu, $omit_pages = array())
    {
        $LANG = Core::$L;

        // stores the non-option lines of the select box: <select>, </select> and the optgroups
        $select_lines   = array();
        $select_lines[] = array("type" => "select_open");

        if (!in_array("", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "", "v" => $LANG["phrase_please_select"]);
        }

        if (!in_array("custom_url", $omit_pages)) {
            $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_custom"]);
            $select_lines[] = array("type" => "option", "k" => "custom_url", "v" => $LANG["phrase_custom_url"]);
            $select_lines[] = array("type" => "optgroup_close");
        }

        $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_forms"]);

        if (!in_array("admin_forms", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "admin_forms", "v" => $LANG["word_forms"]);
        }
        if (!in_array("option_lists", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "option_lists", "v" => $LANG["phrase_option_lists"]);
        }
        if (!in_array("add_form", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "add_form_choose_type", "v" => $LANG["phrase_add_form"]);
        }
        if (!in_array("add_form_internal", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "add_form_internal", "v" => $LANG["phrase_add_form_internal"]);
        }
        if (!in_array("add_form1", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "add_form1", "v" => $LANG["phrase_add_form_external"]);
        }

        if ($is_building_menu) {
            if (!in_array("form_submissions", $omit_pages)) {
                $select_lines[] = array(
                "type" => "option",
                "k" => "form_submissions",
                "v" => $LANG["phrase_form_submissions"]
                );
            }
            if (!in_array("edit_form", $omit_pages)) {
                $select_lines[] = array("type" => "option", "k" => "edit_form", "v" => $LANG["phrase_edit_form"]);
            }
            if (!in_array("edit_form_main", $omit_pages)) {
                $select_lines[] = array("type" => "option", "k" => "edit_form_main", "v" => "{$LANG["phrase_edit_form"]} - {$LANG["word_main"]}");
            }

            if (!in_array("edit_form_fields", $omit_pages)) {
                $select_lines[] = array("type" => "option", "k" => "edit_form_fields", "v" => "{$LANG["phrase_edit_form"]} - {$LANG["word_fields"]}");
            }
            if (!in_array("edit_form_views", $omit_pages)) {
                $select_lines[] = array("type" => "option", "k" => "edit_form_views", "v" => "{$LANG["phrase_edit_form"]} - {$LANG["word_views"]}");
            }
            if (!in_array("edit_form_emails", $omit_pages)) {
                $select_lines[] = array("type" => "option", "k" => "edit_form_emails", "v" => "{$LANG["phrase_edit_form"]} - {$LANG["word_emails"]}");
            }
        }

        $select_lines[] = array("type" => "optgroup_close");

        $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_clients"]);
        if (!in_array("clients", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "clients", "v" => $LANG["word_clients"]);
        }
        if (!in_array("add_client", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "add_client", "v" => $LANG["phrase_add_client"]);
        }

        if ($is_building_menu) {
            if (!in_array("edit_client", $omit_pages)) {
                $select_lines[] = array("type" => "option", "k" => "edit_client", "v" => $LANG["phrase_edit_client"]);
            }
            if (!in_array("edit_client_main", $omit_pages)) {
                $select_lines[] = array("type" => "option", "k" => "edit_client_main", "v" => $LANG["word_main"]);
            }
            if (!in_array("edit_client_permissions", $omit_pages)) {
                $select_lines[] = array("type" => "option", "k" => "edit_client_permissions", "v" => $LANG["word_permissions"]);
            }
        }

        $select_lines[] = array("type" => "optgroup_close");
        $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_modules"]);
        if (!in_array("modules", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "modules", "v" => $LANG["word_modules"]);
        }

        $modules = Modules::getList();
        for ($i=0; $i<count($modules); $i++) {
            $module_id = $modules[$i]["module_id"];
            $module    = $modules[$i]["module_name"];
            $select_lines[] = array("type" => "option", "k" => "module_{$module_id}", "v" => $module);
        }
        $select_lines[] = array("type" => "optgroup_close");

        // if the Pages module is enabled, display any custom pages that have been defined. Note: this would be better handled
        // in the hook added below
        if (Modules::checkModuleEnabled("pages")) {
            Modules::includeModule("pages");
            $pages_info = pg_get_pages("all");
            $pages = $pages_info["results"];

            $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["phrase_pages_module"]);
            for ($i=0; $i<count($pages); $i++) {
                $page_id = $pages[$i]["page_id"];
                $page_name = $pages[$i]["page_name"];
                $select_lines[] = array("type" => "option", "k" => "page_{$page_id}", "v" => $page_name);
            }

            $select_lines[] = array("type" => "optgroup_close");
        }

        extract(Hooks::processHookCalls("middle", compact("select_lines"), array("select_lines")), EXTR_OVERWRITE);

        $select_lines[] = array("type" => "optgroup_open", "label" => $LANG["word_other"]);
        $select_lines[] = array("type" => "option", "k" => "your_account", "v" => $LANG["phrase_your_account"]);
        $select_lines[] = array("type" => "option", "k" => "settings_themes", "v" => "{$LANG["word_themes"]}");
        $select_lines[] = array("type" => "option", "k" => "settings", "v" => $LANG["word_settings"]);
        $select_lines[] = array("type" => "option", "k" => "settings_main", "v" => "{$LANG["word_settings"]} - {$LANG["word_main"]}");
        $select_lines[] = array("type" => "option", "k" => "settings_accounts", "v" => "{$LANG["word_settings"]} - {$LANG["word_accounts"]}");
        $select_lines[] = array("type" => "option", "k" => "settings_files", "v" => "{$LANG["word_settings"]} - {$LANG["word_files"]}");
        $select_lines[] = array("type" => "option", "k" => "settings_menus", "v" => "{$LANG["word_settings"]} - {$LANG["word_menus"]}");
        if (!in_array("logout", $omit_pages)) {
            $select_lines[] = array("type" => "option", "k" => "logout", "v" => $LANG["word_logout"]);
        }
        $select_lines[] = array("type" => "optgroup_close");
        $select_lines[] = array("type" => "select_close");


        // now build the HTML
        $dd = "";
        foreach ($select_lines as $line) {
            switch ($line["type"]) {
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


    /**
     * This function creates a blank client menu with no menu items.
     *
     * @return integer $menu_id
     */
    public static function createBlankClientMenu()
    {
        $db = Core::$db;
        $LANG = Core::$L;

        // to ensure that even new blank menus have unique names, query the database and find
        // the next free menu name of the form "Client Menu (X)" (where "Client Menu" is in the language
        // of the current user)
        $menus = Menus::getMenuList();
        $menu_names = array();
        foreach ($menus as $menu_info) {
            $menu_names[] = $menu_info["menu"];
        }

        $base_client_menu_name = $LANG["phrase_client_menu"];
        $new_menu_name = $base_client_menu_name;

        if (in_array($new_menu_name, $menu_names)) {
            $count = 1;
            $new_menu_name = "$base_client_menu_name ($count)";

            while (in_array($new_menu_name, $menu_names)) {
                $count++;
                $new_menu_name = "$base_client_menu_name ($count)";
            }
        }

        $db->query("
            INSERT INTO {PREFIX}menus (menu, menu_type)
            VALUES (:menu, 'client')
        ");
        $db->bind("menu", $new_menu_name);
        $db->execute();
        $menu_id = $db->getInsertId();

        return $menu_id;
    }


    /**
    * Returns the one (and only) administration menu, and all associated menu items.
    *
    * @return array
    */
    public static function getAdminMenu()
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}menus
            WHERE  menu_type = 'admin'
        ");
        $db->execute();

        $menu_info = $db->fetch();
        $menu_id = $menu_info["menu_id"];

        // now get all the menu items and stash them in a "menu_items" key in $menu_info
        $db->query("
            SELECT *
            FROM   {PREFIX}menu_items
            WHERE  menu_id = :menu_id
            ORDER BY list_order
        ");
        $db->bind("menu_id", $menu_id);
        $db->execute();

        $menu_items = array();
        foreach ($db->fetchAll() as $item) {
            $menu_items[] = $item;
        }

        $menu_info["menu_items"] = $menu_items;

        extract(Hooks::processHookCalls("end", compact("menu_info"), array("menu_info")), EXTR_OVERWRITE);

        return $menu_info;
    }

    /**
     * A wrapper function for ft_get_client_menu (and getAdminMenu). Returns all info
     * about a menu, regardless of type. If it's an admin menu, it'll be returned with an empty "clients"
     * hash key.
     *
     * @param integer $menu_id
     * @return
     */
    public static function getMenu($menu_id)
    {
        return self::getClientMenu($menu_id);
    }

    /**
     * Returns everything about a client menu. Bit of a misnomer, since it also returns the admin menu.
     *
     * @param integer $menu_id
     * @return array
     */
    public static function getClientMenu($menu_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}menus
            WHERE  menu_id = :menu_id
        ");
        $db->bind("menu_id", $menu_id);
        $db->execute();

        $menu_info = $db->fetch();
        $menu_info["menu_items"] = self::getMenuItems($menu_id);

        // get all associated client accounts
        $db->query("
            SELECT *
            FROM   {PREFIX}accounts
            WHERE  menu_id = :menu_id
            ORDER BY first_name
        ");
        $db->bind("menu_id", $menu_id);
        $db->execute();

        $menu_clients = array();
        foreach ($db->fetchAll() as $client) {
            $menu_clients[] = $client;
        }
        $menu_info["clients"] = $menu_clients;

        extract(Hooks::processHookCalls("end", compact("menu_info"), array("menu_info")), EXTR_OVERWRITE);

        return $menu_info;
    }


    /**
     * Returns all menu items for a particular menu.
     * @param integer $menu_id
     * @return array an array of menu hashes
     */
    public static function getMenuItems($menu_id)
    {
        $db = Core::$db;

        // get all the menu items and stash them in a "menu_items" key in $menu_info
        $db->query("
            SELECT *
            FROM   {PREFIX}menu_items
            WHERE  menu_id = :menu_id
            ORDER BY list_order
        ");
        $db->bind("menu_id", $menu_id);
        $db->execute();

        $menu_items = array();
        foreach ($db->fetchAll() as $item) {
            $menu_items[] = $item;
        }

        extract(Hooks::processHookCalls("end", compact("menu_items", "menu_id"), array("menu_items")), EXTR_OVERWRITE);

        return $menu_items;
    }


    /**
     * Called whenever an item is removed from a menu - OUTSIDE of the main administrator update menu
     * pages (client & admin). This updates the order to ensure its consistent (i.e. no gaps). Note:
     * this doesn't update the cached menu. If that's needed, you need to call the ft_cache_account_menu
     * function separately
     *
     * @param integer $menu_id
     */
    public static function updateMenuOrder($menu_id)
    {
        $db = Core::$db;

        // this returns the menu items ordered by list order
        $menu_items = self::getMenuItems($menu_id);

        // now update the list orders to ensure no gaps
        $order = 1;
        foreach ($menu_items as $menu_item) {
            $db->query("
                UPDATE {PREFIX}menu_items
                SET    list_order = :list_order
                WHERE  menu_item_id = :menu_item_id
            ");
            $db->bindAll(array(
                "list_order" => $order,
                "menu_item_id" => $menu_item["menu_item_id"]
            ));
            $order++;
        }

        extract(Hooks::processHookCalls("end", compact("menu_id"), array()), EXTR_OVERWRITE);
    }


    /**
     * This function updates the default menu for multiple accounts simultaneously. It's called when
     * an administrator tries to delete a menu that's current used by some client accounts. They're presented
     * with the option of setting the menu ID for all the clients.
     *
     * There's very little error checking done here...
     *
     * @param string $account_id_str a comma delimited list of account IDs
     * @param integer $theme_id the theme ID
     */
    public static function updateClientMenus($account_ids, $menu_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        if (empty($account_ids) || empty($menu_id)) {
            return array();
        }

        $client_ids = explode(",", $account_ids);
        $menu_info = self::getMenu($menu_id);
        $menu_name = $menu_info["menu"];

        foreach ($client_ids as $client_id) {
            $db->query("
                UPDATE {PREFIX}accounts
                SET menu_id = :menu_id
                WHERE account_id = :client_id
            ");
            $db->bindAll(array(
                "menu_id" => $menu_id,
                "account_id" => $client_id
            ));
            $db->execute();
        }

        $placeholders = array("menu_name" => $menu_name);
        $message = General::evalSmartyString($LANG["notify_client_account_menus_updated"], $placeholders);

        return array(true, $message);
    }


}
