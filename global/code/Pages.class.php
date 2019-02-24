<?php


namespace FormTools;


class Pages
{
    /**
     * This is a hash of page identifiers to URLs. Every page in Form Tools has a unique page identifier
     * to differentiate it from other pages. There had to be a way of mapping a menu item to a "physical"
     * page in the program to allow for customizable menus.
     *
     * It also lets us do away with hardcoding page URLs in various places throughout the script. Instead,
     * we can do it just once, here.
     *
     * The list of pages available to be linked directly to in the menu-building section contains a subset
     * of the pages defined here.
     */
    private static $pageList = array(

        // NOT Logged in pages
        "login"  => "/index.php",
        "logout" => "/index.php?logout",
        "forgot_password" => "/forgot_password.php",
        "error" => "/error.php",

        // ADMIN pages
        "admin_forms" => "/admin/forms/",
        "add_form_choose_type" => "/admin/forms/add/",
        "add_form_internal" => "/admin/forms/add/internal.php",
        "add_form1" => "/admin/forms/add/step1.php?add",
        "add_form2" => "/admin/forms/add/step2.php",
        "add_form3" => "/admin/forms/add/step3.php",
        "add_form4" => "/admin/forms/add/step4.php",
        "add_form5" => "/admin/forms/add/step5.php",
        "add_form6" => "/admin/forms/add/step6.php",

        "form_submissions" => "/admin/forms/submissions.php",
        "option_lists" => "/admin/forms/option_lists/",
        "edit_option_list" => "/admin/forms/option_lists/edit.php",

        "delete_form" => "/admin/forms/delete_form.php",
        "edit_form" => "/admin/forms/edit/",
        "edit_form_main" => "/admin/forms/edit/?page=main",
        "edit_form_public_form_omit_list" => "/admin/forms/edit/?page=public_form_omit_list",
        "edit_form_fields" => "/admin/forms/edit/?page=fields",
        "edit_form_views" => "/admin/forms/edit/?page=views",
        "edit_form_public_view_omit_list" => "/admin/forms/edit/?page=public_view_omit_list",
        "edit_form_emails" => "/admin/forms/edit/?page=emails",
        "edit_form_email_settings" => "/admin/forms/edit/?page=email_settings",

        "edit_admin_menu" => "/admin/settings/index.php?page=edit_admin_menu",
        "edit_client_menu" => "/admin/settings/index.php?page=edit_client_menu",
        "edit_view" => "/admin/forms/edit/?page=edit_view",
        "admin_edit_submission" => "/admin/forms/edit_submission.php",
        "edit_form_field_options" => "/admin/forms/edit/?page=options",
        "clients" => "/admin/clients/",
        "add_client" => "/admin/clients/add.php",
        "edit_client" => "/admin/clients/edit.php",
        "edit_client_main" => "/admin/clients/edit.php?page=main",
        "edit_client_settings" => "/admin/clients/edit.php?page=settings",
        "edit_client_forms" => "/admin/clients/edit.php?page=forms",
        "modules" => "/admin/modules/",
        "modules_about" => "/admin/modules/about.php",
        "your_account" => "/admin/account",
        "settings" => "/admin/settings",
        "settings_main" => "/admin/settings/index.php?page=main",
        "settings_accounts" => "/admin/settings/index.php?page=accounts",
        "settings_files" => "/admin/settings/index.php?page=files",

        // before 2.0.3, themes used to be grouped under "Settings". The settings_themes key is kept
        // to minimize regression
        "settings_themes" => "/admin/themes/index.php",
        "themes_about" => "/admin/themes/about.php",
        "settings_menus" => "/admin/settings/index.php?page=menus",

        // CLIENT pages
        "client_forms" => "/clients/index.php",
        "client_account" => "/clients/account/index.php",
        "client_account_login" => "/clients/account/index.php?page=main",
        "client_account_settings" => "/clients/account/index.php?page=settings",
        "client_form_submissions" => "/clients/forms/index.php",
        "client_edit_submission" => "/clients/forms/edit_submission.php"
    );

    public static function registerPage($key, $url) {
        self::$pageList[$key] = $url;
    }

    public static function getList () {
        return self::$pageList;
    }


    /**
     * This was added to solve the problem of being able to construct a valid URL for the login
     * function, to redirect to whatever custom login page the admin/client has selected. The
     * page identifier is stored in the login_page field (rename to login_page_identifier?).
     *
     * There seems to be some cross-over between this function and Pages::getPageUrl. Think about!
     *
     * @param string $page_identifier special strings that have meaning to Form Tools, used to identify
     *                  pages within its interface. See the top of /global/code/menus.php for a list.
     * @param string $custom_options again used by Form Tools
     * @param array $args an arbitrary hash of key-value pairs to be passed in the query string
	 * @return string
     */
    public static function constructPageURL($page_identifier, $custom_options = "", $args = array())
    {
        $url = "";
        extract(Hooks::processHookCalls("start", compact("url", "page_identifier", "custom_options", "args"), array("url")), EXTR_OVERWRITE);

        if (!empty($url)) {
            return $url;
        }

        $pages = self::$pageList;

        switch ($page_identifier) {
            case "custom_url":
                $url = $custom_options;
                break;

            case "client_form_submissions":
            case "form_submissions":
            case "edit_form":
            case "edit_form_main":
            case "edit_form_fields":
            case "edit_form_views":
            case "edit_form_emails":
                $joiner = (strpos($pages[$page_identifier], "?")) ? "&" : "?";
                $url = $pages[$page_identifier] . $joiner . "form_id=" . $custom_options;
                break;

            case "edit_client":
            case "edit_client_main":
            case "edit_client_permissions":
                $joiner = (strpos($pages[$page_identifier], "?")) ? "&" : "?";
                $url = $pages[$page_identifier] . $joiner . "client_id=" . $custom_options;
                break;

            default:
                // modules
                if (preg_match("/^module_(\d+)/", $page_identifier, $matches)) {
                    $moduleId = $matches[1];
                    $moduleInfo = Modules::getModule($moduleId);
                    if (!empty($moduleInfo)) {
                        $moduleFolder = $moduleInfo["module_folder"];
                        $url = "/modules/$moduleFolder/";
                    }

                // pages (from the Pages module). This should be removed from the Core, and make it use the hook defined above
                } else if (preg_match("/^page_(\d+)/", $page_identifier, $matches)) {
                    $pageId = $matches[1];
                    $url = "/modules/pages/page.php?id=$pageId";
                } else {
                    $url = $pages[$page_identifier];
                }
                break;
        }

        if (!empty($args)) {
            $params = array();
            foreach ($args as $key => $value) {
                $params[] = "$key=$value";
            }
            if (strpos("?", $url) === false) {
                $url .= "?" . implode("&", $params);
            } else {
                $url .= "&" . implode("&", $params);
            }
        }

        return $url;
    }


    /**
     * Used on every page within Form Tools, this function builds the URL for a page *as it should be*, e.g. if they're
     * editing a submission, the URL would contain a submission ID and form ID, BUT the user may have added something
     * extra. This function builds the "proper" URL for use by the custom menus; it lets the script know what parent
     * menu to show for any given page.
     *
     * @param string $page_identifier
     * @param array $params
     * @return string
     */
    public static function getPageUrl($page_identifier, $params = array())
    {
        $url = self::$pageList[$page_identifier];

        $query_pairs = array();
        foreach ($params as $key => $value) {
            $query_pairs[] = "$key=$value";
        }

        $query_str = join("&", $query_pairs);

        // only include the ? if it's not already there
        $full_url = $url;
        if (!empty($query_str)) {
            if (strpos($url, "?")) {
                $full_url .= "&{$query_str}";
            } else {
                $full_url .= "?{$query_str}";
            }
        }

        extract(Hooks::processHookCalls("end", compact("page_identifier", "params", "full_url"), array("full_url")), EXTR_OVERWRITE);

        return $full_url;
    }


}
