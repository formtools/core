<?php

use FormTools\General;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.eval_smarty_string
 * Type:     function
 * Name:     eval_smarty_string
 * Purpose:  a Smarty wrapper for General::evalSmartyString, for use in the templates.
 *           To use this function, just pass the main placeholder string through
 *           the placeholder_str="" attribute, and any placeholders as their own
 *           attributes, e.g.
 *
 *       {eval_smarty_string placeholder_str=$LANG.notify_theme_cache_folder_not_writable
 *         folder="{$g_root_dir}/themes/{$theme_info.theme_folder}/cache/"}
 * -------------------------------------------------------------
 */
function smarty_function_eval_smarty_string($params, &$smarty)
{
    if (!Templates::hasRequiredParams($smarty, $params, array("placeholder_str"))) {
        return "";
    }

    $placeholders = $params;
    unset($placeholders["placeholder_str"]);
    $placeholder_str = $params["placeholder_str"];

    return General::evalSmartyString($placeholder_str, $placeholders);
}
