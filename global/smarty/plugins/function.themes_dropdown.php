<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.themes_dropdown
 * Type:     function
 * Name:     themes_dropdown
 * Purpose:  displays a list of enabled themes. As of 2.2.0, this includes some dynamic code to hide/show
 *           a second "Swatch" dropdown, depending on whether the theme uses swatches or not.
 * -------------------------------------------------------------
 */
function smarty_function_themes_dropdown($params, &$smarty)
{
  global $LANG;

  if (empty($params["name_id"]))
  {
    $smarty->trigger_error("assign: missing 'name_id' parameter. This is used to give the select field a name and id value.");
    return;
  }
  $default_value   = (isset($params["default"])) ? $params["default"] : "";
  $default_swatch  = (isset($params["default_swatch"])) ? $params["default_swatch"] : "";
  $onchange        = (isset($params["onchange"])) ? $params["onchange"] : "";

  // we always give theme dropdowns a special "ft_themes_dropdown" class. This is used to dynamically
  // add the event handlers to hide/show the appropriate swatch dropdown
  $attributes = array(
    "id"       => $params["name_id"],
    "name"     => $params["name_id"],
    "class"    => "ft_themes_dropdown",
    "onchange" => $onchange
      );

  $attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
    if (!empty($value))
      $attribute_str .= " $key=\"$value\"";
  }

  $themes = ft_get_themes();

  $html = "<select $attribute_str>
           <option value=\"\">{$LANG["phrase_please_select"]}</option>";

  $swatch_info = array();
  foreach ($themes as $theme)
  {
    if ($theme["is_enabled"] == "no")
      continue;

    $selected = ($theme["theme_folder"] == $default_value) ? "selected" : "";
    $html .= "<option value=\"{$theme["theme_folder"]}\" {$selected}>{$theme["theme_name"]}</option>";

    if ($theme["uses_swatches"] == "yes")
    {
      $swatch_info[$theme["theme_folder"]] = $theme["swatches"];
    }
  }
  $html .= "</select>";

  // now generate swatch dropdowns for all themes that have them. This is by far the simplest solution,
  // since there will always be very few themes and even fewer that have
  while (list($theme_folder, $swatches) = each($swatch_info))
  {
    $classes = array("{$params["name_id"]}_swatches");
    if ($theme_folder != $default_value)
      $classes[] = "hidden";
    $class_str = implode(" ", $classes);

    $html .= "<select name=\"{$theme_folder}_{$params["name_id"]}_swatches\" id=\"{$theme_folder}_{$params["name_id"]}_swatches\" class=\"$class_str\">"
             . "<option value=\"\">{$LANG["phrase_select_swatch"]}</option>";

    $pairs = explode("|", $swatches);
    foreach ($pairs as $pair)
    {
      list($swatch, $swatch_label) = explode(",", $pair);

      $selected = "";
      if ($theme_folder == $default_value && $default_swatch == $swatch)
        $selected = "selected";

      $swatch_label = ft_eval_smarty_string($swatch_label);
      $html .= "<option value=\"{$swatch}\" {$selected}>{$swatch_label}</option>";
    }

    $html .= "</select>";
  }

  return $html;
}

