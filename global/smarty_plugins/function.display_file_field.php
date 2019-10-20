<?php

use FormTools\Fields;
use FormTools\General;
use FormTools\Templates;


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
    if (!Templates::hasRequiredParams($smarty, $params, array("field_id", "filename"))) {
        return "";
    }

    $field_id = (isset($params["field_id"])) ? $params["field_id"] : "";
    $filename = (isset($params["filename"])) ? $params["filename"] : "";

    $field_settings = Fields::getExtendedFieldSettings($field_id);

    $file_upload_url = $field_settings["file_upload_url"];
    $filename_label = General::trimString($filename, 80); // trim the filename to prevent it being too long

    $html = "<a href=\"$file_upload_url/$filename\"";

    if (isset($params["show_in_new_window"])) {
        if ($params["show_in_new_window"] === true) {
            $html .= " target=\"_blank\"";
        }
    } else {
        $html .= " target=\"_blank\"";
    }

    $html .= ">$filename_label</a>";

    return $html;
}
