<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.forms_dropdown
 * Type:     function
 * Name:     form_dropdown
 * Purpose:  generates a dropdown of all forms in the database (ordered by form name).
 * -------------------------------------------------------------
 */
function smarty_function_forms_dropdown($params, &$smarty)
{
	global $LANG;

	if (empty($params["name_id"]))
  {
	  $smarty->trigger_error("assign: missing 'name_id' parameter. This is used to give the select field a name and id value.");
    return;
  }

  $default_value = (isset($params["default"])) ? $params["default"] : ""; // may be array or single form ID

  $onchange      = (isset($params["onchange"])) ? $params["onchange"] : "";
  $style         = (isset($params["style"])) ? $params["style"] : "";
  $include_blank_option = (isset($params["include_blank_option"])) ? $params["include_blank_option"] : false;
  $hide_incomplete_forms = (isset($params["hide_incomplete_forms"])) ? $params["hide_incomplete_forms"] : true;
  $omit_forms    = (isset($params["omit_forms"])) ? $params["omit_forms"] : array(); // a list of forms to omit from the list

  // for multiple-select dropdowns
  $is_multiple    = (isset($params["is_multiple"]) && $params["is_multiple"] === true) ? true : false;
  $multiple_size  = (isset($params["multiple_size"])) ? $params["multiple_size"] : 4;
  $selected_forms = (isset($params["selected"])) ? $params["selected"] : array();

  // if this option is set, it only shows those form in the array
  $only_show_forms = (isset($params["only_show_forms"])) ? $params["only_show_forms"] : array();

  // this option tells the function that if there's only a single form, display it as straight text
  // rather than in a dropdown. Only compatible with the non-multiple dropdown list
  $display_single_form_as_text = (isset($params["display_single_form_as_text"])) ? $params["display_single_form_as_text"] : false;

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

  	if ($is_multiple)
  	  $attribute_str .= " multiple size=\"$multiple_size\"";
  }

  $forms = ft_get_forms();
  $rows = array();

  foreach ($forms as $form_info)
  {
  	if ($form_info["is_complete"] == "no" && $hide_incomplete_forms)
  	  continue;

  	$form_id   = $form_info["form_id"];
  	$form_name = $form_info["form_name"];

  	if (in_array($form_id, $omit_forms))
  	  continue;

  	if (!empty($only_show_forms) && !in_array($form_id, $only_show_forms))
  	  continue;

  	$rows[] = array("form_id" => $form_id, "form_name" => $form_name);
  }


  $html = "";
  if (count($rows) == 1 && $display_single_form_as_text)
  {
  	$html = $rows[0]["form_name"];
  }
  else
  {
  	$options = array();

  	if ($include_blank_option)
      $options[] = "<option value=\"\">{$LANG["phrase_please_select"]}</option>";

  	foreach ($rows as $row_info)
  	{
  	  $selected = "";
  	  if (is_array($default_value) && in_array($row_info["form_id"], $default_value))
 	      $selected = "selected";
  	  else if ($default_value == $row_info["form_id"])
  	    $selected = "selected";

      $options[] = "<option value=\"{$row_info["form_id"]}\" $selected>{$row_info["form_name"]}</option>";
  	}
   	$html = "<select $attribute_str>" . join("\n", $options) . "</select>";
  }

  return $html;
}

