<?php

/**
 * Smarty custom format plugin. This is the Smarty-friendly version of ft_get_date, allowing
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
  return ft_get_date($timezone_offset, $date_str, $date_format);
}
