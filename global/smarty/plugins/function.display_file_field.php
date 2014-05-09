<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_file_field
 * Type:     function
 * Name:     display_file_field
 * Purpose:  used on the edit submission page to show a link to a file. Depending on
 *           whether Lightbox has been enabled for files, it will open the file directly or not.
 * -------------------------------------------------------------
 */
function smarty_function_display_file_field($params, &$smarty)
{
	global $LANG;

	if (empty($params["field_id"]))
  {
	  $smarty->trigger_error("assign: missing 'field_id' parameter.");
    return;
  }
	if (empty($params["filename"]))
  {
	  $smarty->trigger_error("assign: missing 'filename' parameter.");
    return;
  }

  $field_id = (isset($params["field_id"])) ? $params["field_id"] : "";
  $filename = (isset($params["filename"])) ? $params["filename"] : "";

  $field_settings = ft_get_extended_field_settings($field_id);

  $file_upload_url             = $field_settings["file_upload_url"];
//  $display_files_with_lightbox = $field_settings["display_files_with_lightbox"];
  $filename_label              = ft_trim_string($filename, 80); // trim the filename to prevent it being too long

  $html = "<a href=\"$file_upload_url/$filename\"";

  if (isset($params["show_in_new_window"]))
  {
  	if ($params["show_in_new_window"] === true)
  	  $html .= " target=\"_blank\"";
  }
//  else if ($display_files_with_lightbox == "yes")
//    $html .= " rel=\"lightbox\" title=\"$filename\"";
  else
    $html .= " target=\"_blank\"";

  $html .= ">$filename_label</a>";

  return $html;
}
