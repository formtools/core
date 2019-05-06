/**
 * rsv.js - Really Simple Validation
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 *
 * v2.5.2, Jan 30 2010
 */

if(typeof rsv=='undefined') rsv={};

// SETTINGS
rsv.displayType="display-html";
rsv.errorFieldClass="rsvErrorField";
rsv.errorTextIntro="Please fix the following error(s) and resubmit:";
rsv.errorJSItemBullet="* ";
rsv.errorHTMLItemBullet="&bull; ";
rsv.errorTargetElementId="ft_message";
rsv.customErrorHandler=(typeof g_rsvErrors != 'undefined') ? g_rsvErrors : null;
rsv.onCompleteHandler=null;

// ~~~~~~~~~~~~~~~~~~~~~~~

/**
 * @param form the name attribute of the form to validate.
 * @param rules an array of the validation rules, each rule a string.
 * @return mixed returns a boolean (success/failure) for "alert-single" and "alert-all" options, and an
 *     array of arrays for return
 */
rsv.validate = function(form, rules)
{
  rsv.returnHash = [];

  // loop through rules
  for (var i=0; i<rules.length; i++)
  {
    // split row into component parts (replace any commas with %%C%% - they will be converted back later)
    var row = rules[i].replace(/\\,/ig, "%%C%%");
    row = row.split(",");

    // while the row begins with "if:..." test the condition. If true, strip the if:..., part and
    // continue evaluating the rest of the line. Keep repeating this while the line begins with an
    // if-condition. If it fails any of the conditions, don't bother validating the rest of the line
    var satisfiesIfConditions = true;
    while (row[0].match("^if:"))
    {
      var cond = row[0];
      cond = cond.replace("if:", "");

      // check if it's a = or != test
      var comparison = "equal";
      var parts = [];
      if (cond.search("!=") != -1)
      {
        parts = cond.split("!=");
        comparison = "not_equal";
      }
      else
        parts = cond.split("=");

      var fieldToCheck = parts[0];
      var valueToCheck = parts[1];

      // find value of FIELDNAME for conditional check
      var fieldnameValue = "";
      if (form[fieldToCheck].type == undefined) // radio
      {
        for (var j=0; j<form[fieldToCheck].length; j++)
        {
          if (form[fieldToCheck][j].checked)
            fieldnameValue = form[fieldToCheck][j].value;
        }
      }
      // single checkbox
      else if (form[fieldToCheck].type == "checkbox")
      {
        if (form[fieldToCheck].checked)
          fieldnameValue = form[parts[0]].value;
      }
      // all other field types
      else
        fieldnameValue = form[parts[0]].value;

      // if the value is NOT the same, we don't need to validate this field. Return.
      if (comparison == "equal" &&  fieldnameValue != valueToCheck)
      {
        satisfiesIfConditions = false;
        break;
      }
      else if (comparison == "not_equal" && fieldnameValue == valueToCheck)
      {
        satisfiesIfConditions = false;
        break;
      }
      else
        row.shift();    // remove this if-condition from line, and continue validating line
    }

    if (!satisfiesIfConditions)
      continue;


    var requirement = row[0];
    var fieldName   = row[1];
    var fieldName2, fieldName3, errorMessage, lengthRequirements, date_flag;


    // help the web developer out a little: this is a very common problem
    if (requirement != "function" && form[fieldName] == undefined)
    {
      alert("RSV Error: the field \"" + fieldName + "\" doesn't exist! Please check your form and settings.");
      return false;
    }

    // if the error field classes has been defined, ALWAYS assume that this field passes the
    // validation and set the class name appropriately (removing the errorFieldClass, if it exists). This
    // ensures that every time the form is submitted, only the fields that contain the latest errors have
    // the error class applied
    if (requirement != "function" && rsv.errorFieldClass)
    {
      if (form[fieldName].type == undefined)
      {
        // style each field individually
        for (var j=0; j<form[fieldName].length; j++)
          rsv.removeClassName(form[fieldName][j], rsv.errorFieldClass, true);
      }
      else
        rsv.removeClassName(form[fieldName], rsv.errorFieldClass);
    }


    // depending on the validation test, store the incoming strings for use later...
    if (row.length == 6)        // valid_date
    {
      fieldName2    = row[2];
      fieldName3    = row[3];
      date_flag     = row[4];
      errorMessage  = row[5];
    }
    else if (row.length == 5)     // reg_exp (WITH flags like g, i, m)
    {
      fieldName2   = row[2];
      fieldName3   = row[3];
      errorMessage = row[4];
    }
    else if (row.length == 4)     // same_as, custom_alpha, reg_exp (without flags like g, i, m)
    {
      fieldName2   = row[2];
      errorMessage = row[3];
    }
    else
      errorMessage = row[2];    // everything else!


    // if the requirement is "length...", rename requirement to "length" for switch statement
    if (requirement.match("^length"))
    {
      lengthRequirements = requirement;
      requirement = "length";
    }

    // if the requirement is "range=...", rename requirement to "range" for switch statement
    if (requirement.match("^range"))
    {
      rangeRequirements = requirement;
      requirement = "range";
    }


    // now, validate whatever is required of the field
    switch (requirement)
    {
      case "required":

        // if radio buttons or multiple checkboxes:
        if (form[fieldName].type == undefined)
        {
          var oneIsChecked = false;
          for (var j=0; j<form[fieldName].length; j++)
          {
            if (form[fieldName][j].checked)
              oneIsChecked = true;
          }
          if (!oneIsChecked)
          {
            if (!rsv.processError(form[fieldName], errorMessage))
              return false;
          }
        }
        else if (form[fieldName].type == "select-multiple")
        {
          var oneIsSelected = false;
          for (var k=0; k<form[fieldName].length; k++)
          {
            if (form[fieldName][k].selected)
              oneIsSelected = true;
          }

          // if no options have been selected, or if there ARE no options in the multi-select
          // dropdown, return false
          if (!oneIsSelected || form[fieldName].length == 0)
          {
            if (!rsv.processError(form[fieldName], errorMessage))
              return false;
          }
        }
        // a single checkbox
        else if (form[fieldName].type == "checkbox")
        {
          if (!form[fieldName].checked)
          {
            if (!rsv.processError(form[fieldName], errorMessage))
              return false;
          }
        }
        // otherwise, just perform ordinary "required" check.
        else if (!form[fieldName].value)
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "digits_only":
        if (form[fieldName].value && form[fieldName].value.match(/\D/))
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "letters_only":
        if (form[fieldName].value && form[fieldName].value.match(/[^a-zA-Z]/))
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "is_alpha":
        if (form[fieldName].value && form[fieldName].value.match(/\W/))
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "custom_alpha":
        var conversion = {
          "L": "[A-Z]",
          "V": "[AEIOU]",
          "l": "[a-z]",
          "v": "[aeiou]",
          "D": "[a-zA-Z]",
          "F": "[aeiouAEIOU]",
          "C": "[BCDFGHJKLMNPQRSTVWXYZ]",
          "x": "[0-9]",
          "c": "[bcdfghjklmnpqrstvwxyz]",
          "X": "[1-9]",
          "E": "[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]"
            };

        var reg_exp_str = "";
        for (var j=0; j<fieldName2.length; j++)
        {
          if (conversion[fieldName2.charAt(j)])
            reg_exp_str += conversion[fieldName2.charAt(j)];
          else
            reg_exp_str += fieldName2.charAt(j);
        }
        var reg_exp = new RegExp(reg_exp_str);

        if (form[fieldName].value && reg_exp.exec(form[fieldName].value) == null)
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "reg_exp":
        var reg_exp_str = fieldName2.replace(/%%C%%/ig, ",");

        if (row.length == 5)
          var reg_exp = new RegExp(reg_exp_str, fieldName3);
        else
          var reg_exp = new RegExp(reg_exp_str);

        if (form[fieldName].value && reg_exp.exec(form[fieldName].value) == null)
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "length":
        comparison_rule = "";
        rule_string = "";

        // if-else order is important here: needs to check for >= before >
        if      (lengthRequirements.match(/length=/))
        {
          comparison_rule = "equal";
          rule_string = lengthRequirements.replace("length=", "");
        }
        else if (lengthRequirements.match(/length>=/))
        {
          comparison_rule = "greater_than_or_equal";
          rule_string = lengthRequirements.replace("length>=", "");
        }
        else if (lengthRequirements.match(/length>/))
        {
          comparison_rule = "greater_than";
          rule_string = lengthRequirements.replace("length>", "");
        }
        else if (lengthRequirements.match(/length<=/))
        {
          comparison_rule = "less_than_or_equal";
          rule_string = lengthRequirements.replace("length<=", "");
        }
        else if (lengthRequirements.match(/length</))
        {
          comparison_rule = "less_than";
          rule_string = lengthRequirements.replace("length<", "");
        }

        // now perform the appropriate validation
        switch (comparison_rule)
        {
          case "greater_than_or_equal":
            if (!(form[fieldName].value.length >= parseInt(rule_string)))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;

          case "greater_than":
            if (!(form[fieldName].value.length > parseInt(rule_string)))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;

          case "less_than_or_equal":
            if (!(form[fieldName].value.length <= parseInt(rule_string)))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;

          case "less_than":
            if (!(form[fieldName].value.length < parseInt(rule_string)))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;

          case "equal":
            var range_or_exact_number = rule_string.match(/[^_]+/);
            var fieldCount = range_or_exact_number[0].split("-");

            // if the user supplied two length fields, make sure the field is within that range
            if (fieldCount.length == 2)
            {
              if (form[fieldName].value.length < fieldCount[0] || form[fieldName].value.length > fieldCount[1])
              {
                if (!rsv.processError(form[fieldName], errorMessage))
                  return false;
              }
            }

            // otherwise, check it's EXACTLY the size the user specified
            else
            {
              if (form[fieldName].value.length != fieldCount[0])
              {
                if (!rsv.processError(form[fieldName], errorMessage))
                  return false;
              }
            }

            break;
        }
        break;

      // this is also true if field is empty [should be same for digits_only]
      case "valid_email":
        if (form[fieldName].value && !rsv.isValidEmail(form[fieldName].value))
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "valid_date":
        var isLaterDate = false;
        if    (date_flag == "later_date")
          isLaterDate = true;
        else if (date_flag == "any_date")
          isLaterDate = false;

        if (!rsv.isValidDate(form[fieldName].value, form[fieldName2].value, form[fieldName3].value, isLaterDate))
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "same_as":
        if (form[fieldName].value != form[fieldName2].value)
        {
          if (!rsv.processError(form[fieldName], errorMessage))
            return false;
        }
        break;

      case "range":
        comparison_rule = "";
        rule_string = "";

        // if-else order is important here: needs to check for >= before >
        if      (rangeRequirements.match(/range=/))
        {
          comparison_rule = "equal";
          rule_string = rangeRequirements.replace("range=", "");
        }
        else if (rangeRequirements.match(/range>=/))
        {
          comparison_rule = "greater_than_or_equal";
          rule_string = rangeRequirements.replace("range>=", "");
        }
        else if (rangeRequirements.match(/range>/))
        {
          comparison_rule = "greater_than";
          rule_string = rangeRequirements.replace("range>", "");
        }
        else if (rangeRequirements.match(/range<=/))
        {
          comparison_rule = "less_than_or_equal";
          rule_string = rangeRequirements.replace("range<=", "");
        }
        else if (rangeRequirements.match(/range</))
        {
          comparison_rule = "less_than";
          rule_string = rangeRequirements.replace("range<", "");
        }

        // now perform the appropriate validation
        switch (comparison_rule)
        {
          case "greater_than_or_equal":
            if (!(form[fieldName].value >= Number(rule_string)))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;

          case "greater_than":
            if (!(form[fieldName].value > Number(rule_string)))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;

          case "less_than_or_equal":
            if (!(form[fieldName].value <= Number(rule_string)))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;

          case "less_than":
            if (!(form[fieldName].value < Number(rule_string)))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;

          case "equal":
            var rangeValues = rule_string.split("-");

            // if the user supplied two length fields, make sure the field is within that range
            if ((form[fieldName].value < Number(rangeValues[0])) || (form[fieldName].value > Number(rangeValues[1])))
            {
              if (!rsv.processError(form[fieldName], errorMessage))
                return false;
            }
            break;
        }
        break;

      case "function":
        custom_function = fieldName;
        eval("var result = " + custom_function + "()");

        if (result.constructor.toString().indexOf("Array") != -1)
        {
          for (var j=0; j<result.length; j++)
          {
            if (!rsv.processError(result[j][0], result[j][1]))
              return false;
          }
        }
        break;

      default:
        alert("Unknown requirement flag in validateFields(): " + requirement);
        return false;
    }
  }


  // if the user has defined a custom event handler, pass the information to it
  if (typeof rsv.customErrorHandler == 'function')
    return rsv.customErrorHandler(form, rsv.returnHash);

  // if the user has chosen "alert-all" or "return-errors", perform the appropriate action
  else if (rsv.displayType == "alert-all")
  {
    var errorStr = rsv.errorTextIntro + "\n\n";
    for (var i=0; i<rsv.returnHash.length; i++)
    {
      errorStr += rsv.errorJSItemBullet + rsv.returnHash[i][1] + "\n";

      // apply the error CSS class (if defined) all the fields and place the focus on the first
      // offending field
      rsv.styleField(rsv.returnHash[i][0], i==0);
    }

    if (rsv.returnHash.length > 0)
    {
      alert(errorStr);
      return false;
    }
  }

  else if (rsv.displayType == "display-html")
  {
    var success = rsv.displayHTMLErrors(form, rsv.returnHash);

    // if it wasn't successful, just return false to stop the form submit, otherwise continue processing
    if (!success)
      return false;
  }

  // finally, if the user has specified a custom onCompleteHandler, use it
  if (typeof rsv.onCompleteHandler == 'function')
    return rsv.onCompleteHandler();
  else
    return true;
}


/**
 * Processes an error message, the behaviour of which is according to the rsv.displayType setting.
 * It either alerts the error (with "alert-one") or stores the field node and error message in a
 * hash to return / display once all rules are processed.
 *
 * @param obj the offending form field
 * @param message the error message string
 * @return boolean whether or not to continue processing
 */
rsv.processError = function(obj, message)
{
  message = message.replace(/%%C%%/ig, ",");

  var continueProcessing = true;
  switch (rsv.displayType)
  {
    case "alert-one":
      alert(message);
      rsv.styleField(obj, true);
      continueProcessing = false;
      break;

    case "alert-all":
    case "display-html":
      rsv.returnHash.push([obj, message]);
      break;
  }

  return continueProcessing;
}


/**
 * This function is the default handler for the "display-html" display type. This generates the errors
 * as HTML and inserts them into the target node (rsv.errorTargetElementId).
 */
rsv.displayHTMLErrors = function(f, errorInfo)
{
  var errorHTML = rsv.errorTextIntro + "<br /><br />";
  for (var i=0; i<errorInfo.length; i++)
  {
    errorHTML += rsv.errorHTMLItemBullet + errorInfo[i][1] + "<br />";
    rsv.styleField(errorInfo[i][0], i==0);
  }

  if (errorInfo.length > 0)
  {
    document.getElementById(rsv.errorTargetElementId).style.display = "block";
    document.getElementById(rsv.errorTargetElementId).innerHTML = errorHTML;
    return false;
  }

  return true;
}


/**
 * Highlights a field that fails a validation test IF the errorFieldClass setting is set. In addition,
 * if the focus parameter is set to true, it places the focus on the field.
 *
 * @param the offending form field element
 * @param boolean whether or not to place the mouse focus on the field
 */
rsv.styleField = function(field, focus)
{
  if (!rsv.errorFieldClass)
    return;

  // if "field" is an array, it's a radio button. Focus on the first element
  if (field.type == undefined)
  {
    if (focus)
      field[0].focus();

    // style each field individually
    for (var i=0; i<field.length; i++)
      rsv.addClassName(field[i], rsv.errorFieldClass, true);
  }
  else
  {
    if (rsv.errorFieldClass)
      rsv.addClassName(field, rsv.errorFieldClass, true);
    if (focus)
      field.focus();
  }
}


/**
 * Tests a string is a valid email. NOT the most elegant function...
 */
rsv.isValidEmail = function(str)
{
  var str2 = str.replace(/^\s*/, "");
  var s = str2.replace(/\s*$/, "");

  var at = "@";
  var dot = ".";
  var lat = s.indexOf(at);
  var lstr = s.length;
  var ldot = s.indexOf(dot);

  if (s.indexOf(at)==-1 ||
     (s.indexOf(at)==-1 || s.indexOf(at)==0 || s.indexOf(at)==lstr) ||
     (s.indexOf(dot)==-1 || s.indexOf(dot)==0 || s.indexOf(dot)==lstr) ||
     (s.indexOf(at,(lat+1))!=-1) ||
     (s.substring(lat-1,lat)==dot || s.substring(lat+1,lat+2)==dot) ||
     (s.indexOf(dot,(lat+2))==-1) ||
     (s.indexOf(" ")!=-1))
  {
    return false;
  }

  return true;
}


/**
 * Returns true if string parameter is empty or whitespace characters only.
 */
rsv.isWhitespace = function(s)
{
  var whitespaceChars = " \t\n\r\f";
  if ((s == null) || (s.length == 0))
    return true;

  for (var i=0; i<s.length; i++)
  {
    var c = s.charAt(i);
    if (whitespaceChars.indexOf(c) == -1)
      return false;
  }

  return true;
}


/*
 * Checks incoming date is valid. If any of the date parameters fail, it returns a string
 * message denoting the problem.
 *
 * @param month an integer between 1 and 12
 * @param day an integer between 1 and 31 (depending on month)
 * @year a 4-digit integer value
 * @isLaterDate a boolean value. If true, the function verifies the date being passed in is LATER
 *   than the current date
 */
rsv.isValidDate = function(month, day, year, isLaterDate)
{
  // depending on the year, calculate the number of days in the month
  var daysInMonth;
  if ((year % 4 == 0) && ((year % 100 != 0) || (year % 400 == 0))) // LEAP YEAR
    daysInMonth = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
  else
    daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

  // check the incoming month and year are valid
  if (!month || !day || !year)               return false;
  if (1 > month || month > 12)               return false;
  if (year < 0)                              return false;
  if (1 > day || day > daysInMonth[month-1]) return false;

  // if required, verify the incoming date is LATER than the current date.
  if (isLaterDate)
  {
    // get current date
    var today = new Date();
    var currMonth = today.getMonth() + 1; // since returns 0-11
    var currDay   = today.getDate();
    var currYear  = today.getFullYear();

    // zero-pad today's month & day
    if (String(currMonth).length == 1)  currMonth = "0" + currMonth;
    if (String(currDay).length == 1)  currDay   = "0" + currDay;
    var currDate = String(currYear) + String(currMonth) + String(currDay);

    // zero-pad incoming month & day
    if (String(month).length == 1)  month = "0" + month;
    if (String(day).length == 1)  day   = "0" + day;
    incomingDate = String(year) + String(month) + String(day);

    if (Number(currDate) > Number(incomingDate))
      return false;
  }

  return true;
}


rsv.addClassName = function(objElement, strClass, mayAlreadyExist)
{
  if (objElement.className)
  {
    var arrList = objElement.className.split(' ');

    if (mayAlreadyExist)
    {
      var strClassUpper = strClass.toUpperCase();

      // find all instances and remove them
      for (var i = 0; i < arrList.length; i++)
      {
        if ( arrList[i].toUpperCase() == strClassUpper )
        {
          arrList.splice(i, 1);
          i--;
        }
      }
    }

    arrList[arrList.length] = strClass;
    objElement.className = arrList.join(' ');
  }

  else
    objElement.className = strClass;
}


rsv.removeClassName = function(objElement, strClass)
{
  if (objElement.className)
  {
    var arrList = objElement.className.split(' ');
    var strClassUpper = strClass.toUpperCase();

    for (var i=0; i<arrList.length; i++)
    {
      if (arrList[i].toUpperCase() == strClassUpper)
      {
        arrList.splice(i, 1);
        i--;
      }
    }

    objElement.className = arrList.join(' ');
  }
}