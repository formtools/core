<?php


namespace FormTools\Modules;

use FormTools\Core,
    FormTools\Module as FormToolsModule;


class Pages extends FormToolsModule {

    /**
     * Updates the (one
     * and only) setting on the Settings page.
     *
     * @param array $info
     * @return array [0] true/false
     *               [1] message
     */
    public static function updateSettings($info)
    {
        $L = Core::$L;

        $settings = array("num_pages_per_page" => $info["num_pages_per_page"]);
        Modules::setModuleSettings($settings);

        return array(true, $L["notify_settings_updated"]);
    }


    /**
     * Adds a new page to the module_pages table.
     *
     * @param array $info
     * @return array standard return array
     */
    public static function addPage($info)
    {
        $LANG = Core::$L;
        $db = Core::$db;

        $content_type = $info["content_type"];
        $access_type = $info["access_type"];
        $use_wysiwyg = $info["use_wysiwyg_hidden"];

        $content = $info["codemirror_content"];
        if ($content_type == "html" && $use_wysiwyg == "yes") {
            $content = $info["wysiwyg_content"];
        }

        $db->query("
            INSERT INTO {PREFIX}module_pages (page_name, content_type, access_type, use_wysiwyg, heading, content)
            VALUES (:page_name, :content_type, :access_type, :use_wysiwyg, :heading, :content
        ");
        $db->bindAll(array(
            "page_name" => $info["page_name"],
            "content_type" => $content_type,
            "access_type" => $access_type,
            "use_wysiwyg" => $use_wysiwyg,
            "heading" => $info["heading"],
            "content" => $content
        ));
        $db->execute();

        $page_id = $db->getInsertId();

        if ($access_type == "private") {
            foreach ($info["selected_client_ids"] as $client_id) {
                $db->query("
                    INSERT INTO {PREFIX}module_pages_clients (page_id, client_id)
                    VALUES (:page_id, :client_id)
                ");
                $db->bindAll(array(
                    "page_id" => $page_id,
                    "client_id" => $client_id
                ));
                $db->execute();
            }
        }

        if ($page_id != 0) {
            $success = true;
            $message = $LANG["notify_page_added"];
        } else {
            $success = false;
            $message = $LANG["notify_page_not_added"];
        }

        return array($success, $message, $page_id);
    }


    /**
     * Deletes a page.
     *
     * TODO: delete this page from any menus.
     *
     * @param integer $page_id
     */
    public static function deletePage($page_id)
    {
        $db = Core::$db;

        //$L;

        if (empty($page_id) || !is_numeric($page_id)) {
            return array(false, "");
        }

        $db->query("DELETE FROM {PREFIX}module_pages WHERE page_id = :page_id");
        $db->bind("page_id", $page_id);
        $db->execute();

        $db->query("
            DELETE FROM {PREFIX}menu_items
            WHERE page_identifier = :page_identifier
        ");
        $db->bind("page_identifier", "page_{$page_id}");
        $db->execute();

        // this is dumb, but better than nothing. If we just updated any menus, re-cache the admin menu
        // just in case
        if (mysql_affected_rows()) {
            ft_cache_account_menu(1);
        }

        return array(true, $L["notify_delete_page"]);
    }


    /**
     * Returns all information about a particular Page.
     *
     * @param integer $page_id
     * @return array
     */
    function pg_get_page($page_id)
    {
        global $g_table_prefix;

        $query = mysql_query("SELECT * FROM {$g_table_prefix}module_pages WHERE page_id = $page_id");
        $page_info = mysql_fetch_assoc($query);

        $clients_query = mysql_query("SELECT * FROM {$g_table_prefix}module_pages_clients WHERE page_id = $page_id");
        $page_info["clients"] = array();
        while ($row = mysql_fetch_assoc($clients_query))
        {
            $page_info["clients"][] = $row["client_id"];
        }

        return $page_info;
    }


    /**
     * Returns a page worth of Pages from the Pages module.
     *
     * @param mixed $num_per_page a number or "all"
     * @param integer $page_num
     * @return array
     */
    function pg_get_pages($num_per_page, $page_num = 1)
    {
        global $g_table_prefix;

        if ($num_per_page == "all")
        {
            $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}module_pages
      ORDER BY heading
        ");
        }
        else
        {
            // determine the offset
            if (empty($page_num)) { $page_num = 1; }
            $first_item = ($page_num - 1) * $num_per_page;

            $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}module_pages
      ORDER BY heading
      LIMIT $first_item, $num_per_page
        ") or handle_error(mysql_error());
        }

        $count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}module_pages");
        $count_hash = mysql_fetch_assoc($count_query);
        $num_results = $count_hash["c"];

        $infohash = array();
        while ($field = mysql_fetch_assoc($query))
            $infohash[] = $field;

        $return_hash["results"] = $infohash;
        $return_hash["num_results"] = $num_results;

        return $return_hash;
    }


    /**
     * Updates a page.
     *
     * @param integer $page_id
     * @param array
     */
    function pg_update_page($page_id, $info)
    {
        global $g_table_prefix, $LANG;

        $info = ft_sanitize($info);
        $page_name = $info["page_name"];
        $heading   = $info["heading"];
        $access_type = $info["access_type"];
        $content_type = $info["content_type"];
        $use_wysiwyg = $info["use_wysiwyg_hidden"];

        $content = $info["codemirror_content"];
        if ($content_type == "html" && $use_wysiwyg == "yes")
            $content = $info["wysiwyg_content"];

        mysql_query("
    UPDATE {$g_table_prefix}module_pages
    SET    page_name = '$page_name',
           content_type = '$content_type',
           access_type = '$access_type',
           use_wysiwyg = '$use_wysiwyg',
           heading = '$heading',
           content = '$content'
    WHERE  page_id = $page_id
      ");

        @mysql_query("DELETE FROM {$g_table_prefix}module_pages_clients WHERE page_id = $page_id");
        if ($access_type == "private")
        {
            foreach ($info["selected_client_ids"] as $client_id)
            {
                mysql_query("INSERT INTO {$g_table_prefix}module_pages_clients (page_id, client_id) VALUES ($page_id, $client_id)");
            }
        }

        return array(true, $LANG["notify_page_updated"]);
    }


    /**
     * The installation script for the Pages module. This creates the module_pages database table.
     */
    public static function install($module_id)
    {
        global $g_table_prefix, $LANG;

        // our create table query
        $queries = array();
        $queries[] = "
    CREATE TABLE {$g_table_prefix}module_pages (
      page_id mediumint(8) unsigned NOT NULL auto_increment,
      page_name varchar(50) NOT NULL,
      access_type enum('admin','public','private') NOT NULL default 'admin',
      content_type enum('html','php','smarty') NOT NULL default 'html',
      use_wysiwyg enum('yes','no') NOT NULL default 'yes',
      heading varchar(255) default NULL,
      content text,
      PRIMARY KEY (page_id)
      ) DEFAULT CHARSET=utf8
      ";

        $queries[] = "
    CREATE TABLE IF NOT EXISTS {$g_table_prefix}module_pages_clients (
      page_id mediumint(9) unsigned NOT NULL,
      client_id mediumint(9) unsigned NOT NULL,
      PRIMARY KEY (page_id, client_id)
    ) DEFAULT CHARSET=utf8
      ";

        $queries[] = "INSERT INTO {$g_table_prefix}settings (setting_name, setting_value, module) VALUES ('num_pages_per_page', '10', 'pages')";

        $has_problem = false;
        foreach ($queries as $query)
        {
            $result = @mysql_query($query);
            if (!$result)
            {
                $has_problem = true;
                break;
            }
        }

        // if there was a problem, remove all the table and return an error
        $success = true;
        $message = "";
        if ($has_problem)
        {
            $success = false;
            @mysql_query("DROP TABLE {$g_table_prefix}module_pages");
            $mysql_error = mysql_error();
            $message     = ft_eval_smarty_string($LANG["pages"]["notify_problem_installing"], array("error" => $mysql_error));
        }

        return array($success, $message);
    }


    function upgrade($old_version, $new_version)
    {
        global $g_table_prefix;

        $old_version_info = ft_get_version_info($old_version);
        $new_version_info = ft_get_version_info($new_version);

        if ($old_version_info["release_date"] < 20091020)
        {
            // update the pages table
            @mysql_query("ALTER TABLE {$g_table_prefix}module_pages ADD content_type ENUM('html','php','smarty') NOT NULL DEFAULT 'html' AFTER page_name");
            @mysql_query("ALTER TABLE {$g_table_prefix}module_pages ADD use_wysiwyg ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER content_type");
            @mysql_query("ALTER TABLE {$g_table_prefix}module_pages ADD access_type ENUM('admin','public','private') NOT NULL DEFAULT 'admin' AFTER page_name");

            @mysql_query("
      CREATE TABLE IF NOT EXISTS {$g_table_prefix}module_pages_clients (
        page_id mediumint(9) unsigned NOT NULL,
        client_id mediumint(9) unsigned NOT NULL,
        PRIMARY KEY (page_id, client_id)
      ) DEFAULT CHARSET=utf8
        ");
        }

        if ($old_version_info["release_date"] < 20100911)
        {
            @mysql_query("ALTER TABLE {$g_table_prefix}module_pages TYPE=MyISAM");
            @mysql_query("ALTER TABLE {$g_table_prefix}module_pages ENGINE=MyISAM");
            @mysql_query("ALTER TABLE {$g_table_prefix}module_pages_clients TYPE=MyISAM");
            @mysql_query("ALTER TABLE {$g_table_prefix}module_pages_clients ENGINE=MyISAM");
        }
    }


    /**
     * The uninstallation script for the Pages module. This basically does a little clean up
     * on the database to ensure it doesn't leave any footprints. Namely:
     *   - the module_pages table is removed
     *   - any references in client or admin menus to any Pages are removed
     *   - if the default login page for any user account was a Page, it attempts to reset it to
     *     a likely login page (the Forms page for both).
     *
     * The message returned by the script informs the user the module has been uninstalled, and warns them
     * that any references to any of the Pages in the user accounts has been removed.
     *
     * @return array [0] T/F, [1] success message
     */
    function uninstall($module_id)
    {
        global $g_table_prefix, $LANG;

        $pages = mysql_query("SELECT page_id FROM {$g_table_prefix}module_pages");
        while ($row = mysql_fetch_assoc($pages))
        {
            $page_id = $row["page_id"];
            mysql_query("DELETE FROM {$g_table_prefix}menu_items WHERE page_identifier = 'page_{$page_id}");
        }

        // delete the Pages module tables
        @mysql_query("DROP TABLE {$g_table_prefix}module_pages");
        @mysql_query("DROP TABLE {$g_table_prefix}module_pages_clients");

        // update sessions in case a Page was in the administrator's account menu
        ft_cache_account_menu($account_id = $_SESSION["ft"]["account"]["account_id"]);

        mysql_query("DELETE FROM {$g_table_prefix}settings WHERE module = 'pages'");

        return array(true, $LANG["pages"]["notify_module_uninstalled"]);
    }
}
