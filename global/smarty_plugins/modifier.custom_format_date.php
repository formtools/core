<?php

use FormTools\General;

/**
 * Smarty custom format plugin. This is the Smarty-friendly version of General::getDate, allowing
 * access to that function within the Smarty templates.
 *
 * Type:     modifier<br>
 * Name:     lower<br>
 * @param string
 * @param string
 * @param string
 * @return string
 */
function smarty_modifier_custom_format_date($date_str, $timezone_offset, $date_format)
{
    return General::getDate($timezone_offset, $date_str, $date_format);
}
