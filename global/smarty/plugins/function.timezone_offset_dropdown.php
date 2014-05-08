<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.timezone_offset_dropdown
 * Type:     function
 * Name:     timezone_offset_dropdown
 * Purpose:  generates a dropdown of all available timezone offsets in the program.
 * -------------------------------------------------------------
 */
function smarty_function_timezone_offset_dropdown($params, &$smarty)
{
	global $LANG;

	if (empty($params["name_id"]))
  {
	  $smarty->trigger_error("assign: missing 'name_id' parameter. This is used to give the select field a name and id value.");
    return;
  }
  $default = (isset($params["default"])) ? $params["default"] : "";

  $attributes = array(
    "id"   => $params["name_id"],
    "name" => $params["name_id"],
      );

	$attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
  	if (!empty($value))
  	  $attribute_str .= " $key=\"$value\"";
  }

  $seconds_in_hour = (60 * 60);


  $dd = "<select $attribute_str>
	        <option value=\"-18\"" . (($default == "-18") ? " selected" : "") . ">- 18 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (18 * $seconds_in_hour)))) . "</option>
	        <option value=\"-17\"" . (($default == "-17") ? " selected" : "") . ">- 17 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (17 * $seconds_in_hour)))) . "</option>
	        <option value=\"-16\"" . (($default == "-16") ? " selected" : "") . ">- 16 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (16 * $seconds_in_hour)))) . "</option>
	        <option value=\"-15\"" . (($default == "-15") ? " selected" : "") . ">- 15 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (15 * $seconds_in_hour)))) . "</option>
	        <option value=\"-14\"" . (($default == "-14") ? " selected" : "") . ">- 14 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (14 * $seconds_in_hour)))) . "</option>
	        <option value=\"-13\"" . (($default == "-13") ? " selected" : "") . ">- 13 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (13 * $seconds_in_hour)))) . "</option>
	        <option value=\"-12\"" . (($default == "-12") ? " selected" : "") . ">- 12 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (12 * $seconds_in_hour)))) . "</option>
	        <option value=\"-11\"" . (($default == "-11") ? " selected" : "") . ">- 11 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (11 * $seconds_in_hour)))) . "</option>
	        <option value=\"-10\"" . (($default == "-10") ? " selected" : "") . ">- 10 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (10 * $seconds_in_hour)))) . "</option>
	        <option value=\"-9\"" . (($default == "-9") ? " selected" : "") . ">- 9   {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (9 * $seconds_in_hour)))) . "</option>
	        <option value=\"-8\"" . (($default == "-8") ? " selected" : "") . ">- 8  {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (8 * $seconds_in_hour)))) . "</option>
	        <option value=\"-7\"" . (($default == "-7") ? " selected" : "") . ">- 7   {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (7 * $seconds_in_hour)))) . "</option>
	        <option value=\"-6\"" . (($default == "-6") ? " selected" : "") . ">- 6   {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (6 * $seconds_in_hour)))) . "</option>
	        <option value=\"-5\"" . (($default == "-5") ? " selected" : "") . ">- 5 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (5 * $seconds_in_hour)))) . "</option>
	        <option value=\"-4\"" . (($default == "-4") ? " selected" : "") . ">- 4 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (4 * $seconds_in_hour)))) . "</option>
	        <option value=\"-3.5\"" . (($default == "-3.5") ? " selected" : "") . ">- 3.5 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (3.5 * $seconds_in_hour)))) . "</option>
	        <option value=\"-3\"" . (($default == "-3") ? " selected" : "") . ">- 3 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (3 * $seconds_in_hour)))) . "</option>
	        <option value=\"-2\"" . (($default == "-2") ? " selected" : "") . ">- 2 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") - (2 * $seconds_in_hour)))) . "</option>
	        <option value=\"-1\"" . (($default == "-1") ? " selected" : "") . ">- 1 {$LANG["word_hour"]} " . (date("(g:i A)", (date("U") - (1 * $seconds_in_hour)))) . "</option>
	        <option value=\"0\"" . (($default == "0") ? " selected" : "") . ">{$LANG["phrase_no_offset"]} " . (date("(g:i A)")) . "</option>
	        <option value=\"1\"" . (($default == "1") ? " selected" : "") . ">+ 1 {$LANG["word_hour"]} " . (date("(g:i A)", (date("U") + (1 * $seconds_in_hour)))) . "</option>
	        <option value=\"2\"" . (($default == "2") ? " selected" : "") . ">+ 2 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (2 * $seconds_in_hour)))) . "</option>
	        <option value=\"3\"" . (($default == "3") ? " selected" : "") . ">+ 3 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (3 * $seconds_in_hour)))) . "</option>
	        <option value=\"3.5\"" . (($default == "3.5") ? " selected" : "") . ">+ 3.5 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (3.5 * $seconds_in_hour)))) . "</option>
	        <option value=\"4\"" . (($default == "4") ? " selected" : "") . ">+ 4 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (4 * $seconds_in_hour)))) . "</option>
	        <option value=\"4.5\"" . (($default == "4.5") ? " selected" : "") . ">+ 4.5 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (4.5 * $seconds_in_hour)))) . "</option>
	        <option value=\"5\"" . (($default == "5") ? " selected" : "") . ">+ 5 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (5 * $seconds_in_hour)))) . "</option>
	        <option value=\"5.5\"" . (($default == "5.5") ? " selected" : "") . ">+ 5.5 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (5.5 * $seconds_in_hour)))) . "</option>
	        <option value=\"6\"" . (($default == "6") ? " selected" : "") . ">+ 6 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (6 * $seconds_in_hour)))) . "</option>
	        <option value=\"6.5\"" . (($default == "6.5") ? " selected" : "") . ">+ 6.5 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (6.5 * $seconds_in_hour)))) . "</option>
	        <option value=\"7\"" . (($default == "7") ? " selected" : "") . ">+ 7 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (7 * $seconds_in_hour)))) . "</option>
	        <option value=\"8\"" . (($default == "8") ? " selected" : "") . ">+ 8 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (8 * $seconds_in_hour)))) . "</option>
	        <option value=\"9\"" . (($default == "9") ? " selected" : "") . ">+ 9 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (9 * $seconds_in_hour)))) . "</option>
	        <option value=\"9.5\"" . (($default == "9.5") ? " selected" : "") . ">+ 9.5 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (9.5 * $seconds_in_hour)))) . "</option>
	        <option value=\"10\"" . (($default == "10") ? " selected" : "") . ">+ 10 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (10 * $seconds_in_hour)))) . "</option>
	        <option value=\"11\"" . (($default == "11") ? " selected" : "") . ">+ 11 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (11 * $seconds_in_hour)))) . "</option>
	        <option value=\"12\"" . (($default == "12") ? " selected" : "") . ">+ 12 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (12 * $seconds_in_hour)))) . "</option>
	        <option value=\"13\"" . (($default == "13") ? " selected" : "") . ">+ 13 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (13 * $seconds_in_hour)))) . "</option>
	        <option value=\"14\"" . (($default == "14") ? " selected" : "") . ">+ 14 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (14 * $seconds_in_hour)))) . "</option>
	        <option value=\"15\"" . (($default == "15") ? " selected" : "") . ">+ 15 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (15 * $seconds_in_hour)))) . "</option>
	        <option value=\"16\"" . (($default == "16") ? " selected" : "") . ">+ 16 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (16 * $seconds_in_hour)))) . "</option>
	        <option value=\"17\"" . (($default == "17") ? " selected" : "") . ">+ 17 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (17 * $seconds_in_hour)))) . "</option>
	        <option value=\"18\"" . (($default == "18") ? " selected" : "") . ">+ 18 {$LANG["word_hours"]} " . (date("(g:i A)", (date("U") + (18 * $seconds_in_hour)))) . "</option>
	      </select>";

  return $dd;
}

?>