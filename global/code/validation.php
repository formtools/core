<?php

/**
 * validation.php
 * --------------
 *
 * v2.3.4, Jan 2012
 *
 * This script provides generic validation for any web form. For a discussion and example usage
 * of this script, go to http://www.benjaminkeen.com/software/php_validation
 *
 * This script is written by Ben Keen with additional code contributed by Mihai Ionescu and
 * Nathan Howard. It is free to distribute, to re-write - to do what ever you want with it. The RSV
 * script, found in /global/scripts is the client-side counterpart to this script. Both work exactly
 * the same way, so once you write validation for one, you can literally & paste them to use them in
 * the other. See the user doc for more info.
 */

// ------------------------------------------------------------------------------------------------

/**
 * Generic form field validation.
 *
 * @param array fields the POST / GET fields from a form which need to be validated
 * @param array rules an array of the validation rules. Each rule is a string of the form:
 *
 *  "[if:FIELDNAME=VALUE,]REQUIREMENT,fieldname[,fieldname2 [,fieldname3, date_flag]],error message"
 *
 *             if:FIELDNAME=VALUE,   This allows us to only validate a field
 *                         only if a fieldname FIELDNAME has a value VALUE. This
 *                         option allows for nesting; i.e. you can have multiple
 *                         if clauses, separated by a comma. They will be examined
 *                         in the order in which they appear in the line.
 *
 *             Valid REQUIREMENT strings are:
 *               "required"     - field must be filled in
 *               "digits_only"  - field must contain digits only
 *               "is_alpha"     - field must only contain alphanumeric characters (0-9, a-Z)
 *               "custom_alpha" - field must be of the custom format specified.
 *                     fieldname:  the name of the field
 *                     fieldname2: a character or sequence of special characters. These characters are:
 *                         L   An uppercase Letter.          V   An uppercase Vowel.
 *                         l   A lowercase letter.           v   A lowercase vowel.
 *                         D   A letter (upper or lower).    F   A vowel (upper or lower).
 *                         C   An uppercase Consonant.       x   Any number, 0-9.
 *                         c   A lowercase consonant.        X   Any number, 1-9.
 *                         E   A consonant (upper or lower).
 *               "reg_exp"      - field must match the supplied regular expression.
 *                     fieldname:  the name of the field
 *                     fieldname2: the regular expression
 *                     fieldname3: (optional) flags for the reg exp (like i for case insensitive
 *               "letters_only" - field must only contains letters (a-Z)
 *
 *               "length=X"     - field has to be X characters long
 *               "length=X-Y"   - field has to be between X and Y (inclusive) characters long
 *               "length>X"     - field has to be greater than X characters long
 *               "length>=X"    - field has to be greater than or equal to X characters long
 *               "length<X"     - field has to be less than X characters long
 *               "length<=X"    - field has to be less than or equal to X characters long*
 *
 *               "valid_email"  - field has to be valid email address
 *               "valid_date"   - field has to be a valid date
 *                     fieldname:  MONTH
 *                     fieldname2: DAY
 *                     fieldname3: YEAR
 *                     date_flag:  "later_date" / "any_date"
 *               "same_as"     - fieldname is the same as fieldname2 (for password comparison)
 *
 *               "range=X-Y"    - field must be a number between the range of X and Y inclusive
 *               "range>X"      - field must be a number greater than X
 *               "range>=X"     - field must be a number greater than or equal to X
 *               "range<X"      - field must be a number less than X
 *               "range<=X"     - field must be a number less than or equal to X
 *
 *
 * Comments:   With both digits_only, valid_email and is_alpha options, if the empty string is passed
 *             in it won't generate an error, thus allowing validation of non-required fields. So,
 *             for example, if you want a field to be a valid email address, provide validation for
 *             both "required" and "valid_email".
 */
function validate_fields($fields, $rules)
{
  $errors = array();

  // loop through rules
  for ($i=0; $i<count($rules); $i++)
  {
    // split row into component parts
    $row = explode(",", $rules[$i]);

    // while the row begins with "if:..." test the condition. If true, strip the if:..., part and
    // continue evaluating the rest of the line. Keep repeating this while the line begins with an
    // if-condition. If it fails any of the conditions, don't bother validating the rest of the line
    $satisfies_if_conditions = true;
    while (preg_match("/^if:/", $row[0]))
    {
      $condition = preg_replace("/^if:/", "", $row[0]);

      // check if it's a = or != test
      $comparison = "equal";
      $parts = array();
      if (preg_match("/!=/", $condition))
      {
        $parts = explode("!=", $condition);
        $comparison = "not_equal";
      }
      else
        $parts = explode("=", $condition);

      $field_to_check = $parts[0];
      $value_to_check = $parts[1];

      if ($comparison == "equal")
      {
        $fail = false;

        // if the field being compared against doesn't exist, we reasonably say that the value won't be the same
        if (!array_key_exists($field_to_check, $fields))
          $fail = true;

        // if the value being passed is an array (e.g. a checkbox / multi-select field), what would be considered
        // "equal"? I think as long as the array contains AT LEAST that value, it's fair to pass this test
        else if (is_array($fields[$field_to_check]))
        {
          if (!in_array($fields[$field_to_check], $fields))
            $fail = true;
        }

        // lastly, do a straight string test
        else if ($fields[$field_to_check] != $value_to_check)
          $fail = true;

        if ($fail)
        {
          $satisfies_if_conditions = false;
          break;
        }
        else
        {
          array_shift($row);
        }
      }
      else if ($comparison == "not_equal")
      {
        $fail = true;

        // if the field doesn't even exist, we can say they're not equal (i.e. doesn't fail!)
        if (!array_key_exists($field_to_check, $fields))
          $fail = false;

        else if (is_array($fields[$field_to_check]))
        {
          if (!in_array($fields[$field_to_check], $fields))
            $fail = false;
        }

        else if ($fields[$field_to_check] != $value_to_check)
          $fail = false;

        if ($fail)
        {
          $satisfies_if_conditions = false;
          break;
        }
        else
        {
          array_shift($row);
        }
      }
      else
        array_shift($row);    // remove this if-condition from line, and continue validating line
    }

    if (!$satisfies_if_conditions)
      continue;


    $requirement = $row[0];
    $field_name  = $row[1];

    // depending on the validation test, store the incoming strings for use later...
    if (count($row) == 6)        // valid_date
    {
      $field_name2   = $row[2];
      $field_name3   = $row[3];
      $date_flag     = $row[4];
      $error_message = $row[5];
    }
    else if (count($row) == 5)     // reg_exp (WITH flags like g, i, m)
    {
      $field_name2   = $row[2];
      $field_name3   = $row[3];
      $error_message = $row[4];
    }
    else if (count($row) == 4)     // same_as, custom_alpha, reg_exp (without flags like g, i, m)
    {
      $field_name2   = $row[2];
      $error_message = $row[3];
    }
    else
      $error_message = $row[2];    // everything else!


    // if the requirement is "length=...", rename requirement to "length" for switch statement
    if (preg_match("/^length/", $requirement))
    {
      $length_requirements = $requirement;
      $requirement         = "length";
    }

    // if the requirement is "range=...", rename requirement to "range" for switch statement
    if (preg_match("/^range/", $requirement))
    {
      $range_requirements = $requirement;
      $requirement        = "range";
    }


    // now, validate whatever is required of the field
    switch ($requirement)
    {
      case "required":
        if (!isset($fields[$field_name]) || $fields[$field_name] == "")
          $errors[] = $error_message;
        break;

      case "digits_only":
        if (isset($fields[$field_name]) && preg_match("/\D/", $fields[$field_name]))
          $errors[] = $error_message;
        break;

      case "letters_only":
        if (isset($fields[$field_name]) && preg_match("/[^a-zA-Z]/", $fields[$field_name]))
          $errors[] = $error_message;
        break;

      // doesn't fail if field is empty
      case "valid_email":
        $regexp="/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";
        if (isset($fields[$field_name]) && !empty($fields[$field_name]) && !preg_match($regexp, $fields[$field_name]))
          $errors[] = $error_message;
        break;

      case "length":
        $comparison_rule = "";
        $rule_string     = "";

        if      (preg_match("/length=/", $length_requirements))
        {
          $comparison_rule = "equal";
          $rule_string = preg_replace("/length=/", "", $length_requirements);
        }
        else if (preg_match("/length>=/", $length_requirements))
        {
          $comparison_rule = "greater_than_or_equal";
          $rule_string = preg_replace("/length>=/", "", $length_requirements);
        }
        else if (preg_match("/length<=/", $length_requirements))
        {
          $comparison_rule = "less_than_or_equal";
          $rule_string = preg_replace("/length<=/", "", $length_requirements);
        }
        else if (preg_match("/length>/", $length_requirements))
        {
          $comparison_rule = "greater_than";
          $rule_string = preg_replace("/length>/", "", $length_requirements);
        }
        else if (preg_match("/length</", $length_requirements))
        {
          $comparison_rule = "less_than";
          $rule_string = preg_replace("/length</", "", $length_requirements);
        }

        switch ($comparison_rule)
        {
          case "greater_than_or_equal":
            if (!(strlen($fields[$field_name]) >= $rule_string))
              $errors[] = $error_message;
            break;
          case "less_than_or_equal":
            if (!(strlen($fields[$field_name]) <= $rule_string))
              $errors[] = $error_message;
            break;
          case "greater_than":
            if (!(strlen($fields[$field_name]) > $rule_string))
              $errors[] = $error_message;
            break;
          case "less_than":
            if (!(strlen($fields[$field_name]) < $rule_string))
              $errors[] = $error_message;
            break;
          case "equal":
            // if the user supplied two length fields, make sure the field is within that range
            if (preg_match("/-/", $rule_string))
            {
              list($start, $end) = explode("-", $rule_string);
              if (strlen($fields[$field_name]) < $start || strlen($fields[$field_name]) > $end)
                $errors[] = $error_message;
            }
            // otherwise, check it's EXACTLY the size the user specified
            else
            {
              if (strlen($fields[$field_name]) != $rule_string)
                $errors[] = $error_message;
            }
            break;
        }
        break;

      case "range":
        $comparison_rule = "";
        $rule_string     = "";

        if      (preg_match("/range=/", $range_requirements))
        {
          $comparison_rule = "equal";
          $rule_string = preg_replace("/range=/", "", $range_requirements);
        }
        else if (preg_match("/range>=/", $range_requirements))
        {
          $comparison_rule = "greater_than_or_equal";
          $rule_string = preg_replace("/range>=/", "", $range_requirements);
        }
        else if (preg_match("/range<=/", $range_requirements))
        {
          $comparison_rule = "less_than_or_equal";
          $rule_string = preg_replace("/range<=/", "", $range_requirements);
        }
        else if (preg_match("/range>/", $range_requirements))
        {
          $comparison_rule = "greater_than";
          $rule_string = preg_replace("/range>/", "", $range_requirements);
        }
        else if (preg_match("/range</", $range_requirements))
        {
          $comparison_rule = "less_than";
          $rule_string = preg_replace("/range</", "", $range_requirements);
        }

        switch ($comparison_rule)
        {
          case "greater_than":
            if (!($fields[$field_name] > $rule_string))
              $errors[] = $error_message;
            break;
          case "less_than":
            if (!($fields[$field_name] < $rule_string))
              $errors[] = $error_message;
            break;
          case "greater_than_or_equal":
            if (!($fields[$field_name] >= $rule_string))
              $errors[] = $error_message;
            break;
          case "less_than_or_equal":
            if (!($fields[$field_name] <= $rule_string))
              $errors[] = $error_message;
            break;
          case "equal":
            list($start, $end) = explode("-", $rule_string);

            if (($fields[$field_name] < $start) || ($fields[$field_name] > $end))
              $errors[] = $error_message;
            break;
        }
        break;

      case "same_as":
        if ($fields[$field_name] != $fields[$field_name2])
          $errors[] = $error_message;
        break;

      case "valid_date":
        // this is written for future extensibility of isValidDate function to allow
        // checking for dates BEFORE today, AFTER today, IS today and ANY day.
        $is_later_date = false;
        if    ($date_flag == "later_date")
          $is_later_date = true;
        else if ($date_flag == "any_date")
          $is_later_date = false;

        if (!is_valid_date($fields[$field_name], $fields[$field_name2], $fields[$field_name3], $is_later_date))
          $errors[] = $error_message;
        break;

      case "is_alpha":
        if (!isset($fields[$field_name]) || preg_match('/[^A-Za-z0-9]/', $fields[$field_name]))
          $errors[] = $error_message;
        break;

      case "custom_alpha":
        $chars = array();
        $chars["L"] = "[A-Z]";
        $chars["V"] = "[AEIOU]";
        $chars["l"] = "[a-z]";
        $chars["v"] = "[aeiou]";
        $chars["D"] = "[a-zA-Z]";
        $chars["F"] = "[aeiouAEIOU]";
        $chars["C"] = "[BCDFGHJKLMNPQRSTVWXYZ]";
        $chars["x"] = "[0-9]";
        $chars["c"] = "[bcdfghjklmnpqrstvwxyz]";
        $chars["X"] = "[1-9]";
        $chars["E"] = "[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]";

        $reg_exp_str = "";
        for ($j=0; $j<strlen($field_name2); $j++)
        {
          if (array_key_exists($field_name2[$j], $chars))
            $reg_exp_str .= $chars[$field_name2[$j]];
          else
            $reg_exp_str .= $field_name2[$j];
        }

        if (!empty($fields[$field_name]) && !preg_match("/$reg_exp_str/", $fields[$field_name]))
          $errors[] = $error_message;
        break;

      case "reg_exp":
        $reg_exp_str = $field_name2;

        // rather crumby, but...
        if (count($row) == 5)
          $reg_exp = "/" . $reg_exp_str . "/" . $row[3];
        else
          $reg_exp = "/" . $reg_exp_str . "/";

        if (!empty($fields[$field_name]) && !preg_match($reg_exp, $fields[$field_name]))
          $errors[] = $error_message;
        break;

      default:
        die("Unknown requirement flag in validate_fields(): $requirement");
        break;
    }
  }

  return $errors;
}


/**
 * Checks a date is valid / is later than current date
 *
 * @param integer $month an integer between 1 and 12
 * @param integer $day an integer between 1 and 31 (depending on month)
 * @param integer $year a 4-digit integer value
 * @param boolean $is_later_date if true, the function verifies the date being passed in is LATER
 * than the current date.
 */
function is_valid_date($month, $day, $year, $is_later_date)
{
  // depending on the year, calculate the number of days in the month
  if ($year % 4 == 0)      // LEAP YEAR
    $days_in_month = array(31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
  else
    $days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);


  // first, check the incoming month and year are valid.
  if (!$month || !$day || !$year) return false;
  if (1 > $month || $month > 12)  return false;
  if ($year < 0)                  return false;
  if (1 > $day || $day > $days_in_month[$month-1]) return false;


  // if required, verify the incoming date is LATER than the current date.
  if ($is_later_date)
  {
    // get current date
    $today = date("U");
    $date = mktime(0, 0, 0, $month, $day, $year);
    if ($date < $today)
      return false;
  }

  return true;
}
