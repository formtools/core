<?php

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
  if (empty($params["location"]))
  {
    $smarty->trigger_error("assign: missing 'location' parameter.");
    return;
  }

  echo ft_process_template_hook_calls($params["location"], $smarty->_tpl_vars, $params);
}
