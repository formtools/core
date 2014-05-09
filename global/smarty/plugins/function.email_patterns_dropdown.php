<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.email_patterns_dropdown
 * Type:     function
 * Name:     email_patterns_dropdown
 * Purpose:  generates a dropdown of all email patterns for a particular type (html or text),
 *           grouped by optgroup.
 * -------------------------------------------------------------
 */
function smarty_function_email_patterns_dropdown($params, &$smarty)
{
  global $LANG;

  if (empty($params["type"]))
  {
    $smarty->trigger_error("assign: missing 'type' parameter. This should be set to 'html' or 'text'.");
    return;
  }
  if (empty($params["form_id"]))
  {
    $smarty->trigger_error("assign: missing 'form_id' parameter. This should be set to 'html' or 'text'.");
    return;
  }

  $type = $params["type"];
  $form_id  = $params["form_id"];
  $onchange = (isset($params["onchange"])) ? $params["onchange"] : "";

  $email_patterns  = ft_get_email_patterns($form_id);

  $optgroups = array();
  foreach ($email_patterns["{$type}_patterns"] as $pattern_info)
  {
    $pattern_name = $pattern_info["pattern_name"];
    $content      = $pattern_info["content"];
    $optgroup     = $pattern_info["optgroup"];

    if (!isset($optgroups[$optgroup]))
      $optgroups[$optgroup] = array();

    $optgroups[$optgroup][] = array($pattern_name, $content);
  }

  // now construct the HTML
  $content = "";
  $html = "<select onchange=\"$onchange\">
             <option value=\"\">{$LANG["phrase_please_select"]}</option>";

  $count = 1;
  while (list($key, $patterns) = each($optgroups))
  {
    $html .= "<optgroup label=\"$key\">";

    foreach ($patterns as $pattern_info)
    {
      $html .= "<option value=\"$count\">{$pattern_info[0]}</option>";
      $content .= "<textarea id=\"{$type}_{$count}\">{$pattern_info[1]}</textarea>";
      $count++;
    }

    $html .= "</optgroup>";
  }

  $html .= "</select>";

  // add the content in a hidden div
  $html .= "<div style=\"display:none\">$content</div>";

  return $html;
}

