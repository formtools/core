<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.ft_include
 * Type:     function
 * Name:     ft_include
 * Purpose:  includes a file - with a fallback. It attempts to include the file in the theme of the
 *           currently logged in user. If the theme isn't defined, or if the template doesn't exist,
 *           it falls back to use the file in the default template. If THAT isn't defined, an error
 *           message is displayed on screen. (The only time this should occur is during development).
 * -------------------------------------------------------------
 */
function smarty_function_ft_include($params, &$smarty)
{
	global $LANG, $g_default_theme, $g_root_dir, $g_smarty;

	if (empty($params["file"]))
  {
	  $smarty->trigger_error("assign: missing 'file' parameter. This is required.");
    return;
  }

  // the template ("file") should be an absolute path relative to the
  $template = $params["file"];
  $theme = (isset($_SESSION["ft"]["account"]["theme"])) ? $_SESSION["ft"]["account"]["theme"] : $g_default_theme;
	$html = $g_smarty->fetch(ft_get_smarty_template_with_fallback($theme, $template));

  return $html;
}

