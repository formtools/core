<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.module_function
 * Type:     function
 * Name:     module_function
 * Purpose:  this acts as a
 * -------------------------------------------------------------
 */
function smarty_function_module_function($params, &$smarty)
{
	$function_name = $params["name"];

    $plugin_file = $smarty->_get_plugin_filepath("function", $function_name);
    @include_once $plugin_file;

    $function_name ="smarty_function_$function_name";
    if (function_exists($function_name)) {
        eval('$var = $function_name($params, $smarty);');
        echo $var;
    }
}
