<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_edit_submission_view_dropdown
 * Type:     function
 * Name:     form_view_fields_dropdown
 * Purpose:  used on the edit submission page. This displays a dropdown of available Views for the
 *           submission.
 * -------------------------------------------------------------
 */
function smarty_function_display_edit_submission_view_dropdown($params, &$smarty)
{
  global $LANG;

	if (empty($params["form_id"]))
  {
	  $smarty->trigger_error("assign: missing 'form_id' parameter.");
    return;
  }
  if (empty($params["view_id"]))
  {
	  $smarty->trigger_error("assign: missing 'view_id' parameter.");
    return;
  }
  if (empty($params["submission_id"]))
  {
    $smarty->trigger_error("assign: missing 'submission_id' parameter.");
    return;
  }
  if (empty($params["account_id"]))
  {
    $smarty->trigger_error("assign: missing 'account_id' parameter.");
    return;
  }

  $is_admin      = ($params["is_admin"]) ? $params["is_admin"] : false;
  $form_id       = $params["form_id"];
  $view_id       = $params["view_id"];
  $submission_id = $params["submission_id"];
  $account_id    = $params["account_id"];

  if ($is_admin)
    $views = ft_get_form_views($form_id);
  else
    $views = ft_get_form_views($form_id, $account_id);

  // loop through the Views assigned to this user and IFF the view contains the submission,
  // add it to the dropdown list
  if (count($views) > 1)
  {
  	$same_page = ft_get_clean_php_self();
	  $html = "<select onchange=\"window.location='{$same_page}?form_id=$form_id&submission_id=$submission_id&view_id=' + this.value\">
	    <optgroup label=\"Views\">\n";
	  foreach ($views as $view_info)
	  {
	  	$curr_view_id   = $view_info["view_id"];
	  	$curr_view_name = $view_info["view_name"];
	    if (ft_check_view_contains_submission($form_id, $curr_view_id, $submission_id))
	    {
	      $selected = ($curr_view_id == $view_id) ? " selected" : "";
	      $html .="<option value=\"$curr_view_id\"{$selected}>$curr_view_name</option>";
	    }
	  }
	  $html .= "</optgroup></select>\n";
  }

  return $html;
}
