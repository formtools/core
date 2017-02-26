<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_account_name
 * Type:     function
 * Purpose:  simple helper function to display the name of a user account, determined by account ID.
 * -------------------------------------------------------------
 */
function smarty_function_display_account_name($params, &$smarty)
{
  $account_id = (isset($params["account_id"])) ? $params["account_id"] : "";
  $format     = (isset($params["format"])) ? $params["format"] : "first_last"; // first_last, or last_first

  if (empty($account_id))
    return;

  $account_info = ft_get_account_info($account_id);

  if ($format == "first_last")
    $html = "{$account_info["first_name"]} {$account_info["last_name"]}";
  else
    $html = "{$account_info["last_name"]}, {$account_info["first_name"]}";

  return $html;
}
