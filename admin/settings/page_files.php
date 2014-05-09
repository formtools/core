<?php

if (isset($request["update_files"]))
  list($g_success, $g_message) = ft_update_file_settings($request);

$all_preset_types = array("bmp","gif","jpg","jpeg","png","avi","mp3","mp4","css","js","htm","html","doc","rtf",
  "txt","pdf","xml","csv","zip","tar","tar.gz","swf","fla");

$file_upload_filetypes = explode(",", $_SESSION["ft"]["settings"]["file_upload_filetypes"]);

// now filter out all the preset types to see if the user has entered anything in the "other" field
$other_filetypes = array();
foreach ($file_upload_filetypes as $filetype)
{
  if (!in_array($filetype, $all_preset_types))
    $other_filetypes[] = $filetype;
}
$other_filetypes_str = implode(",", $other_filetypes);
$max_filesize = ft_get_upload_max_filesize();


// compile the list of vars to pass to the page
$page_vars = array();
$page_vars["page"] = "files";
$page_vars["page_url"] = ft_get_page_url("settings_files");
$page_vars["tabs"] = $tabs;
$page_vars["js_messages"] = "";
$page_vars["max_filesize"] = $max_filesize;
$page_vars["file_upload_filetypes"] = $file_upload_filetypes;
$page_vars["other_filetypes"] = $other_filetypes_str;
$page_vars["head_title"] = "{$LANG["word_settings"]} - {$LANG["word_files"]}";
$page_vars["allow_url_fopen"] = (ini_get("allow_url_fopen") == "1");
$page_vars["head_js"] = "
  var rules = [];
  ";

ft_display_page("admin/settings/index.tpl", $page_vars);