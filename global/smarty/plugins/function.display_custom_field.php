<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_custom_field
 * Type:     function
 * Name:     display_custom_field
 * Purpose:  A smarty wrapper for ft_generate_viewable_field.
 * -------------------------------------------------------------
 */
function smarty_function_display_custom_field($params, &$smarty)
{
  // note that View ID is optional
  $required_params = array("form_id", "submission_id", "field_info", "field_types", "settings");

  foreach ($required_params as $param)
  {
    if (!isset($params[$param]))
    {
//      $smarty->trigger_error("assign: missing '$param' parameter.");
      return;
    }
  }

  echo ft_generate_viewable_field($params);
}
