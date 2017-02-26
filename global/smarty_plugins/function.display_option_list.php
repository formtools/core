<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_option_list
 * Type:     function
 * Purpose:  used for incidental cases where you need to display an Option List. It's pretty basic:
 *           you just pass in what format you want to appear (select, radios, checkboxes, multi-select)
 *           the name & ID, and the default value(s). The formatting options (columns etc) are
 *           non-existent right now.
 * -------------------------------------------------------------
 */
function smarty_function_display_option_list($params, &$smarty)
{
  $option_list_id = $params["option_list_id"];
  $format = $params["format"];
  $name   = (isset($params["name"])) ? $params["name"] : "";
  $default_value = (isset($params["default_value"])) ? $params["default_value"] : "";
  $option_list_info = ft_get_option_list($option_list_id);

  switch ($format)
  {
  	case "radios":
  	  $is_grouped = $option_list_info["is_grouped"];
  	  $count = 1;
  	  foreach ($option_list_info["options"] as $group)
  	  {
  	  	if (!empty($group["group_info"]["group_name"]))
  	  	  echo "<div><b>{$group["group_info"]["group_name"]}</b></div>";

  	  	foreach ($group["options"] as $option_info)
  	  	{
  	  	  $value        = htmlspecialchars($option_info["option_value"]);
  	  	  $display_text = $option_info["option_name"];
  	  	  $selected     = "";
  	  	  $checked = ($option_info["option_value"] == $default_value) ? "checked" : "";

  	      echo "<input type=\"radio\" name=\"{$name}\" id=\"{$name}_$count\" value=\"$value\" $checked /> "
  	         . "<label for=\"{$name}_$count\">$display_text</label><br />";
  	      $count++;
  	  	}
  	  }
      break;

  	case "checkboxes":
  	  $is_grouped = $option_list_info["is_grouped"];
  	  $count = 1;
  	  foreach ($option_list_info["options"] as $group)
  	  {
  	  	if (!empty($group["group_info"]["group_name"]))
  	  	  echo "<div><b>{$group["group_info"]["group_name"]}</b></div>";

  	  	foreach ($group["options"] as $option_info)
  	  	{
  	  	  $value        = htmlspecialchars($option_info["option_value"]);
  	  	  $display_text = $option_info["option_name"];
  	  	  $checked      = "";
  	  	  if (is_array($default_value) && in_array($option_info["option_value"], $default_value))
  	  	    $checked = "checked";
  	  	  else if ($option_info["option_value"] == $default_value)
  	  	    $checked = "checked";

  	      echo "<input type=\"checkbox\" name=\"{$name}[]\" id=\"{$name}_$count\" value=\"$value\" $checked /> "
  	         . "<label for=\"{$name}_$count\">$display_text</label><br />";
  	      $count++;
  	  	}
  	  }
      break;

  	case "select":
  	  $is_grouped = $option_list_info["is_grouped"];
  	  $count = 1;

  	  echo "<select name=\"{$name}\">";
  	  foreach ($option_list_info["options"] as $group)
  	  {
  	  	if (!empty($group["group_info"]["group_name"]))
  	  	  echo "<optgroup label=\"{$group["group_info"]["group_name"]}\">";

  	  	foreach ($group["options"] as $option_info)
  	  	{
  	  	  $value        = htmlspecialchars($option_info["option_value"]);
  	  	  $display_text = $option_info["option_name"];
  	  	  $selected = ($option_info["option_value"] == $default_value) ? "selected" : "";

  	      echo "<option value=\"$value\" $selected>$display_text</option>\n";
  	      $count++;
  	  	}

  	  	if (!empty($group["group_info"]["group_name"]))
  	  	  echo "</optgroup>";
  	  }
  	  echo "</select>";
      break;

  	case "multi-select":
  	  $is_grouped = $option_list_info["is_grouped"];
  	  $count = 1;

  	  echo "<select name=\"{$name}[]\" multiple size=\"5\">";
  	  foreach ($option_list_info["options"] as $group)
  	  {
  	  	if (!empty($group["group_info"]["group_name"]))
  	  	  echo "<optgroup label=\"{$group["group_info"]["group_name"]}\">";

  	  	foreach ($group["options"] as $option_info)
  	  	{
  	  	  $value        = htmlspecialchars($option_info["option_value"]);
  	  	  $display_text = $option_info["option_name"];

  	  	  $selected = "";
  	  	  if (is_array($default_value) && in_array($option_info["option_value"], $default_value))
  	  	    $selected = "selected";
  	  	  else if ($option_info["option_value"] == $default_value)
  	  	    $selected = "selected";

  	      echo "<option value=\"$value\" $selected>$display_text</option>\n";
  	      $count++;
  	  	}

  	  	if (!empty($group["group_info"]["group_name"]))
  	  	  echo "</optgroup>";
  	  }
  	  echo "</select>";
      break;
  }
}
