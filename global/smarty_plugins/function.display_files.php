<?php

/**
 * Used within email templates to display a file/list of files.
 * @param $params
 * @return string
 */
function smarty_function_display_files($params)
{
	$delim = isset($params["delim"]) ? $params["delim"] : ", ";
	$link  = isset($params["link"]) ? $params["link"] : true;
	$folder = isset($params["folder"]) ? $params["folder"] : "";
	$format = isset($params["format"]) ? $params["format"] : "text";

    $html = "";

    if (empty($params["files"])) {
    	return $html;
	}

    $files = array();
    foreach ($params["files"] as $file) {
    	if ($format === "html") {
			if ($link) {
				$files[] = "<a href=\"$folder/$file\">$file</a>";
			} else {
				$files[] = $file;
			}
		} else {
			if ($link) {
				$files[] = "$folder/$file";
			} else {
				$files[] = $file;
			}
		}
	}

    return implode($delim, $files);
}
