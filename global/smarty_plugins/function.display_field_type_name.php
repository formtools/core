<?php

use FormTools\FieldTypes;
use FormTools\General;


/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_field_type_name
 * Type:     function
 * Purpose:  displays the name of a field type.
 * -------------------------------------------------------------
 */
function smarty_function_display_field_type_name($params, &$smarty)
{
    $field_type_id = (isset($params["field_type_id"])) ? $params["field_type_id"] : "";
    if (empty($field_type_id)) {
        return;
    }

    $field_type_info = FieldTypes::getFieldType($field_type_id);

    echo General::evalSmartyString($field_type_info["field_type_name"]);
}
