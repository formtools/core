<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.date_range_search_dropdown
 * Type:     function
 * Name:     date_range_search_dropdown
 * Purpose:  generates a dropdown of all forms in the database (ordered by form name).
 * -------------------------------------------------------------
 */
function smarty_function_date_range_search_dropdown($params, &$smarty)
{
	global $LANG;

	if (empty($params["name_id"]))
  {
	  $smarty->trigger_error("assign: missing 'name_id' parameter. This is used to give the select field a name and id value.");
    return;
  }
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

  $default_value = (isset($params["default"])) ? $params["default"] : "";
  $onchange      = (isset($params["onchange"])) ? $params["onchange"] : "";
  $style         = (isset($params["style"])) ? $params["style"] : "";
  $form_id = $params["form_id"];
  $view_id = $params["view_id"];

  $attributes = array(
    "id"   => $params["name_id"],
    "name" => $params["name_id"],
    "onchange" => $onchange,
    "style" => $style
      );

	$attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
  	if (!empty($value))
  	  $attribute_str .= " $key=\"$value\"";
  }

  $search_days   = ft_get_search_days($view_id);
  $search_months = ft_get_search_months($view_id);

  $rows = array();
  $rows[] = "<option value=\"\">{$LANG["phrase_select_date_range"]}</option>";
  if (!empty($search_days))
  {
	  $rows[] = "<optgroup label=\"{$LANG["phrase_common_dates"]}\">";
	  $rows[] = "<option value=\"1\" " . (($default_value == "1") ? "selected" : "") . ">{$LANG["phrase_last_day"]}</option>";

	  if ($search_days > 1)
	    $rows[] = "<option value=\"2\" " . (($default_value == "2") ? "selected" : "") . ">{$LANG["phrase_last_2_days"]}</option>";
	  if ($search_days > 2)
	    $rows[] = "<option value=\"3\" " . (($default_value == "3") ? "selected" : "") . ">{$LANG["phrase_last_3_days"]}</option>";
	  if ($search_days > 3)
	    $rows[] = "<option value=\"5\" " . (($default_value == "5") ? "selected" : "") . ">{$LANG["phrase_last_5_days"]}</option>";
	  if ($search_days > 5)
	    $rows[] = "<option value=\"7\" " . (($default_value == "7") ? "selected" : "") . ">{$LANG["phrase_last_week"]}</option>";
	  if ($search_days > 7)
	    $rows[] = "<option value=\"30\" " . (($default_value == "30") ? "selected" : "") . ">{$LANG["phrase_last_month"]}</option>";
	  if ($search_days > 30)
	    $rows[] = "<option value=\"365\" " . (($default_value == "365") ? "selected" : "") . ">{$LANG["phrase_last_year"]}</option>";

	  $rows[] = "</optgroup>";
  }

  if (!empty($search_months))
  {
	  $rows[] = "<optgroup label=\"{$LANG["phrase_specific_months"]}\">";

	  while (list($val, $display_text) = each($search_months))
	  	$rows[] = "<option value=\"$val\" " . (($default_value == $val) ? "selected" : "") . ">$display_text</option>";

    $rows[] = "</optgroup>";
  }

  $dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}

?>