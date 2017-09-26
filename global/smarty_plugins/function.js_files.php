<?php

function smarty_function_js_files($params)
{
    $html = "";
    foreach ($params["files"] as $file) {
        $html .= "<script src=\"$file\"></script>\n";
    }
    return $html;
}
