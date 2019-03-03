<?php

use FormTools\Hooks;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.template_hook
 * Type:     function
 * Name:     template_hook
 * Purpose:  processes whatever template hooks are defined at a particular template
 *           location.
 * -------------------------------------------------------------
 */
function smarty_function_template_hook($params, &$smarty)
{
    if (!Templates::hasRequiredParams($smarty, $params, array("location"))) {
        return "";
    }

    Hooks::processTemplateHookCalls($params["location"], $smarty->getTemplateVars(), $params);
}
