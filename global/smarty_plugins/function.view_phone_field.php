<?php

function smarty_function_view_phone_field($params, &$smarty)
{
    $format = $smarty->getTemplateVars("phone_number_format");
    $values = explode("|", $smarty->getTemplateVars("VALUE"));
    $pieces = preg_split("/(x+)/", $format, 0, PREG_SPLIT_DELIM_CAPTURE);
    $counter = 1;
    $output = "";
    $has_content = false;

    foreach ($pieces as $piece) {
        if (empty($piece)) {
            continue;
        }
        if ($piece[0] == "x") {
            $value = (isset($values[$counter-1])) ? $values[$counter-1] : "";
            $output .= $value;
            if (!empty($value)) {
                $has_content = true;
            }
            $counter++;
        } else {
            $output .= $piece;
        }
    }

    return (!empty($output) && $has_content) ? $output : "";
}
