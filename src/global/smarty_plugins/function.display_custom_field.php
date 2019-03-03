<?php

use FormTools\FieldTypes;
use FormTools\Templates;


/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_custom_field
 * Type:     function
 * Name:     display_custom_field
 * Purpose:  A smarty wrapper for FieldTypes::generateViewableField
 * -------------------------------------------------------------
 */
function smarty_function_display_custom_field($params, &$smarty)
{
    // note that View ID is optional; settings needs to be passed but can be empty
    if (!Templates::hasRequiredParams($smarty, $params, array("form_id", "submission_id", "field_info", "field_types"))) {
        return;
    }

    echo FieldTypes::generateViewableField($params);
}
