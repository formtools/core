<?php

use FormTools\Sessions;


/**
 * Displays the number of form submissions for a particular form. Depends on the actual value being set in sessions
 * elsewhere. That value will depend on the user type, rights, etc. If the session value isn't set, it outputs "-".
 */
function smarty_function_display_num_form_submissions($params, &$smarty)
{
    $form_id = $params["form_id"];
    return Sessions::getWithFallback("form_{$form_id}_num_submissions", "-");
}
