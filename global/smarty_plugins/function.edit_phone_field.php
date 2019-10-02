<?php

function smarty_function_edit_phone_field($params, &$smarty)
{
    $format = $smarty->getTemplateVars("phone_number_format");
    $comments = $smarty->getTemplateVars("comments");
    $values = explode("|", $smarty->getTemplateVars("VALUE"));
    $name   = $smarty->getTemplateVars("NAME");
    $pieces = preg_split("/(x+)/", $format, 0, PREG_SPLIT_DELIM_CAPTURE);
    $counter = 1;

    $html = "";
    foreach ($pieces as $piece) {
        if (strlen($piece) == 0) {
            continue;
        }
        if ($piece[0] == "x") {
            $size = strlen($piece);
            $value = htmlspecialchars((isset($values[$counter-1])) ? $values[$counter-1] : "");
            $html .= "<input type=\"text\" name=\"{$name}_$counter\" value=\"$value\" size=\"$size\" maxlength=\"$size\" />";
            $counter++;
        } else {
            $html .= $piece;
        }
    }

    if ($comments) {
        $html .= "<div class=\"cf_field_comments\">$comments</div>";
    }

    return $html;
}
