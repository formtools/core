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
    public static function getPreloadedSmarty($theme) {
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();
        $root_url = Core::getRootUrl();

        $smarty = Core::$smarty;

        $smarty->setTemplateDir(array(
            "$root_dir/themes/$theme",
            "$root_dir/global/smarty_plugins/"
        ));
        $smarty->setCompileDir("$root_dir/themes/$theme/cache/");
        $smarty->assign("LANG", $LANG);
        $smarty->setUseSubDirs(Core::shouldUseSmartySubDirs());
        $smarty->assign("g_root_dir", $root_dir);
        $smarty->assign("g_root_url", $root_url);
        $smarty->assign("same_page", General::getCleanPhpSelf());

        // theme-specific vars
        $smarty->assign("images_url", "$root_url/themes/$theme/images");
        $smarty->assign("theme_url", "$root_url/themes/$theme");
        $smarty->assign("theme_dir", "$root_dir/themes/$theme");

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
