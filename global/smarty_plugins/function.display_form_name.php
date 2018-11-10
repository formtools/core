<?php

use FormTools\Forms;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_form_name
 * Type:     function
 * Purpose:  displays the name of a form
 * -------------------------------------------------------------
 */
function smarty_function_display_form_name($params, &$smarty)
{
    $form_id = (isset($params["form_id"])) ? $params["form_id"] : "";

    if (empty($form_id)) {
        return "";
    }

    $form_info = Forms::getForm($form_id);

    return $form_info["form_name"];
}
