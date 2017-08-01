<?php

use FormTools\Accounts;
use FormTools\Sessions;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_num_form_submissions
 * Type:     function
 * Purpose:  Displays the number of form submissions for a particular form.
 * -------------------------------------------------------------
 */
function smarty_function_display_num_form_submissions($params, &$smarty)
{
    $form_id = $params["form_id"];
    return Sessions::get("form_{$form_id}_num_submissions");
}
