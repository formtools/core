<?php

function smarty_function_js_files($params)
{
    $html = "";
    $root_url = $params["root_url"];
    $module_folder = $params["module_folder"];
    foreach ($params["files"] as $file) {
        $html .= "<script src=\"$root_url/modules/$module_folder/$file\"></script>\n";
    }
    return $html;
}
