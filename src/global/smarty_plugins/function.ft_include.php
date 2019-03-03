<?php

use FormTools\Core;
use FormTools\Templates;
use FormTools\Themes;


/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.ft_include
 * Type:     function
 * Name:     ft_include
 * Purpose:  includes a file - with a fallback. It attempts to include the file in the theme of the
 *           currently logged in user. If the theme isn't defined, or if the template doesn't exist,
 *           it falls back to use the file in the default template. If THAT isn't defined, an error
 *           message is displayed on screen. (The only time this should occur is during development).
 * -------------------------------------------------------------
 */
function smarty_function_ft_include($params, &$smarty)
{
    if (!Templates::hasRequiredParams($smarty, $params, array("file"))) {
        return "";
    }

    // the template ("file") should be an absolute path relative to the root
    $template = $params["file"];
    $theme = Core::$user->getTheme();

    return Core::$smarty->fetch(Themes::getSmartyTemplateWithFallback($theme, $template));
}

