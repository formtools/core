<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_view_name
 * Type:     function
 * Purpose:  displays the name of a form
 * -------------------------------------------------------------
 */
function smarty_function_display_view_name($params, &$smarty)
{
  $view_id = (isset($params["view_id"])) ? $params["view_id"] : "";

  if (empty($view_id))
    return;

  $view_info = ft_get_view($view_id);
  return $view_info["view_name"];
}
