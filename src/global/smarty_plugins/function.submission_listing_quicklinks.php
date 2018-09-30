<?php

use FormTools\Submissions;


/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.submission_listing_quicklinks
 * Type:     function
 * -------------------------------------------------------------
 */
function smarty_function_submission_listing_quicklinks($params, &$smarty)
{
    echo Submissions::displaySubmissionListingQuicklinks($params["context"], $smarty);
}
