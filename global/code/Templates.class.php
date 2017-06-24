<?php

/**
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Templates
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Templates
{

    /**
     * Not a great name, but this is a helper method that returns a $smarty instance all prepped with the common
     * data & settings used for a page render.
     */
    public static function getPreloadedSmarty($theme)
    {
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();
        $root_url = Core::getRootUrl();

        $smarty = Core::$smarty;

        $smarty->setTemplateDir("$root_dir/themes/$theme");
        $smarty->addPluginsDir(array("$root_dir/global/smarty_plugins"));
        $smarty->setCompileDir("$root_dir/themes/$theme/cache/");

        // check the compile directory has the write permissions
        if (!is_writable($smarty->getCompileDir())) {
            Errors::majorError("Either the theme cache folder doesn't have write-permissions, or your \$g_root_dir value is invalid. Please update the <b>{$smarty->compile_dir}</b> to have full read-write permissions (777 on unix).");
            exit;
        }

        $smarty->assign("LANG", $LANG);
        $smarty->setUseSubDirs(Core::shouldUseSmartySubDirs());
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

//        // if smarty debug is on, enable Smarty debugging
//        if ($g_smarty_debug) {
//            $smarty->debugging = true;
//        }

        $smarty->assign("images_url", "$root_url/themes/$theme/images");
        $smarty->assign("theme_url", "$root_url/themes/$theme");
        $smarty->assign("theme_dir", "$root_dir/themes/$theme");
        $smarty->assign("modules_dir", "$root_url/modules");

        return $smarty;
    }


    public static function hasRequiredParams($smarty, $allParams, $desiredParams)
    {
        foreach ($desiredParams as $param) {
            if (!isset($allParams[$param]) || empty($allParams[$param])) {
                $smarty->triggerError("assign: missing '$param' parameter.");

                //throw new Exception("assign: missing '$param' parameter.");

                return false;
            }
        }

        return true;
    }
}
