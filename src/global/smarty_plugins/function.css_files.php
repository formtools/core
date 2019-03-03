<?php

function smarty_function_css_files($params)
{
    $html = "";
    foreach ($params["files"] as $file) {
        $html .= "<link type=\"text/css\" rel=\"stylesheet\" href=\"$file\">\n";
    }
    return $html;
}
