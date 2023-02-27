<?php

/**
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Templates
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use Smarty, SmartyBC;


class Templates
{

    /**
     * Not a great name, but this is a helper method that returns a $smarty instance all prepped with the common
     * data & settings used for a page render. For any other smarty usage, you don't need all the stuff this adds: just
     * use getBasicSmarty().
     */
    public static function getPageRenderSmarty($theme, $page_vars = array())
    {
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();
        $root_url = Core::getRootUrl();

        $smarty = Core::$smarty;

        $smarty->setTemplateDir("$root_dir/themes/$theme");
        $smarty->addPluginsDir(array("$root_dir/global/smarty_plugins"));
        $smarty->setCompileDir(Core::getCacheFolder());

        // check the compile directory has the write permissions
        if (!is_writable($smarty->getCompileDir())) {
            Errors::majorError("Either the theme cache folder doesn't have write-permissions, or your \$g_root_dir value is invalid. Please update the <b>{$smarty->compile_dir}</b> to have full read-write permissions (777 on unix).");
            exit;
        }

        $smarty->assign("LANG", $LANG);
        $smarty->setUseSubDirs(Core::shouldUseSmartySubDirs());
        $smarty->setDebugging(Core::isSmartyDebugEnabled());
        $smarty->setErrorReporting(Core::getDefaultErrorReporting());

        $smarty->assign("g_debug", Core::isDebugEnabled());
        $smarty->assign("g_root_dir", $root_dir);
        $smarty->assign("g_root_url", $root_url);
        $smarty->assign("same_page", General::getCleanPhpSelf());
        $smarty->assign("g_js_debug", Core::isJsDebugEnabled() ? "true" : "false");
        $smarty->assign("query_string", $_SERVER["QUERY_STRING"]);
        $smarty->assign("dir", $LANG["special_text_direction"]);
        $smarty->assign("g_enable_benchmarking", Core::isBenchmarkingEnabled());
        $smarty->assign("is_logged_in", Core::$user->isLoggedIn());
        $smarty->assign("account_type", Core::$user->getAccountType());
        $smarty->assign("g_js_debug", Core::isJsDebugEnabled() ? "true" : "false");

        $smarty->assign("g_hide_upgrade_link", Core::shouldHideUpgradeLink());
        $smarty->assign("images_url", "$root_url/themes/$theme/images");
        $smarty->assign("theme_url", "$root_url/themes/$theme");
        $smarty->assign("theme_dir", "$root_dir/themes/$theme");
        $smarty->assign("modules_dir", "$root_url/modules");

        if (Core::$user->isLoggedIn()) {
            $account = Sessions::get("account");
            $smarty->assign("settings", Sessions::get("settings"));
            $smarty->assign("account", Sessions::get("account"));
            $smarty->assign("menu_items", Sessions::get("menu.menu_items"));
            $smarty->assign("footer_text", isset($account["settings"]["footer_text"]) ? $account["settings"]["footer_text"] : "");
        } else {
            $smarty->assign("menu_items", array());
            $smarty->assign("footer_text", "");
        }

        // check the "required" vars are at least set so they don't produce warnings
        if (!isset($page_vars["head_string"])) $page_vars["head_string"] = "";
        if (!isset($page_vars["head_title"]))  $page_vars["head_title"] = "";
        if (!isset($page_vars["head_js"]))     $page_vars["head_js"] = "";
        if (!isset($page_vars["page"]))        $page_vars["page"] = "";

        // if we need to include custom JS messages in the page, add it to the generated JS. Note: even if the js_messages
        // key is defined but still empty, the General::generateJsMessages function is called, returning the "base" JS - like
        // the JS version of g_root_url. Only if it is not defined will that info not be included.
        $js_messages = (isset($page_vars["js_messages"])) ? General::generateJsMessages($page_vars["js_messages"]) : "";

        if (!empty($page_vars["head_js"]) || !empty($js_messages)) {
            $page_vars["head_js"] = "<script>\n//<![CDATA[\n{$page_vars["head_js"]}\n$js_messages\n//]]>\n</script>";
        }

        if (!isset($page_vars["head_css"])) {
            $page_vars["head_css"] = "";
        } else if (!empty($page_vars["head_css"])) {
            $page_vars["head_css"] = "<style type=\"text/css\">\n{$page_vars["head_css"]}\n</style>";
        }

        // now add the custom variables for this template, as defined in $page_vars
        foreach ($page_vars as $key => $value) {
            $smarty->assign($key, $value);
        }

        $success = (isset($page_vars["g_success"])) ? $page_vars["g_success"] : "";
        $message = (isset($page_vars["g_message"])) ? $page_vars["g_message"] : "";

        // if this page has been told to display a custom message, override g_success and g_message
        if (isset($_GET["message"])) {
            list($found, $s, $m) = General::displayCustomPageMessage($_GET["message"]);

            if ($found) {
                $success = $s;
                $message = $m;
            }
        }
        $smarty->assign("g_success", $success);
        $smarty->assign("g_message", $message);

        return $smarty;
    }

    public static function getBasicSmarty($theme)
    {
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();
        $root_url = Core::getRootUrl();

        $smarty = Core::useSmartyBC() ? new SmartyBC() : new Smarty();
        $smarty->setTemplateDir("$root_dir/themes/$theme");
        $smarty->addPluginsDir(array("$root_dir/global/smarty_plugins"));
        $smarty->setCompileDir(Core::getCacheFolder());

        // check the compile directory has the write permissions
        if (!is_writable($smarty->getCompileDir())) {
            Errors::majorError("Either the theme cache folder doesn't have write-permissions, or your \$g_root_dir value is invalid. Please update the <b>{$smarty->compile_dir}</b> to have full read-write permissions (777 on unix).");
            exit;
        }

        $smarty->assign("LANG", $LANG);
        $smarty->assign("g_root_dir", $root_dir);
        $smarty->assign("g_root_url", $root_url);
        $smarty->setUseSubDirs(Core::shouldUseSmartySubDirs());

        return $smarty;
    }

    public static function hasRequiredParams($smarty, $allParams, $desiredParams)
    {
        foreach ($desiredParams as $param) {
            if (!isset($allParams[$param]) || empty($allParams[$param])) {
                echo "assign: missing '$param' parameter.";
                return false;
            }
        }

        return true;
    }
}
