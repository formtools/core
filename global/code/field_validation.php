<?php

/**
 * This file contains all functions for generating the field validation code. The actual validation script
 * is a standalone script, and found in validation.php (same folder).
 *
 * @copyright Encore Web Studios 2011
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-1-x
 * @subpackage FieldValidation
 */


// -------------------------------------------------------------------------------------------------


/**
 * Returns all defined validation rules for a field.
 *
 * @param integer $field_id
 */
function ft_get_field_validation($field_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_validation fv, {$g_table_prefix}field_type_validation_rules ftvr
    WHERE  fv.field_id = $field_id AND
           ftvr.rule_id = fv.rule_id
  ");

  $rules = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $rules[] = $row;
  }

  return $rules;
}


/**
 * Used on the Edit Submission pages. This generates the actual JS used for the RSV validation, according
 * to whatever rules the user has specified.
 *
 * @param array $grouped_fields
 */
function ft_generate_submission_js_validation($grouped_fields)
{
  $js_lines = array();
  $custom_func_errors = array();
  foreach ($grouped_fields as $group_info)
  {
    foreach ($group_info["fields"] as $field_info)
    {
      if (empty($field_info["validation"]))
        continue;

      $field_name  = $field_info["field_name"];
      $field_title = $field_info["field_title"];
      $field_id    = $field_info["field_id"];

      foreach ($field_info["validation"] as $rule_info)
      {
      	$rsv_rule = $rule_info["rsv_rule"];
      	$placeholders = array(
      	  "field"      => $field_title,
      	  "field_name" => $field_name
      	);
        $message = ft_eval_smarty_string($rule_info["error_message"], $placeholders);

        if ($rsv_rule == "function")
      	{
      		$custom_function = $rule_info["custom_function"];
      		$new_rule = "rules.push(\"function,$custom_function\")";
      		if (!in_array($new_rule, $js_lines))
            $js_lines[] = $new_rule;

          $custom_func_errors[] = "{field:\"$field_name\",field_id:$field_id,func:\"$custom_function\",err:\"$message\"}";
      	}
      	else
      	{
      		$rsv_field_name = ft_eval_smarty_string($rule_info["rsv_field_name"], $placeholders);
          $js_lines[] = "rules.push(\"$rsv_rule,$rsv_field_name,$message\")";
      	}
      }
    }
  }

  // kind of a hack, but passable. The RSV custom function string doesn't pass the error string in the rule; so
  // we just store it in a JS object in the page for use by the functions
  if (!empty($custom_func_errors))
  {
  	$js_lines[] = "var rsv_custom_func_errors = []";
  	foreach ($custom_func_errors as $js_obj)
  	{
      $js_lines[] = "rsv_custom_func_errors.push($js_obj)";
  	}
  }

  $js = "";
  if (!empty($js_lines))
  {
    $rules = implode(";\n", $js_lines);
    $js =<<< END
$(function() {
  $("#edit_submission_form").bind("submit", function() { return rsv.validate(this, rules); });
  rsv.customErrorHandler = ms.submit_form;
});
var rules = [];
$rules
END;
  }

  return $js;
}


/**
 * Added in 2.1.4 to return all field type validation information.
 *
 * @param $options
 */
function ft_generate_field_type_validation_js($options = array())
{
  global $g_table_prefix, $LANG;

  // for use in dev work
  $minimize = false;

  $delimiter = "\n";
  if ($minimize)
    $delimiter = "";

  $namespace = isset($options["page_ns"]) ? $options["page_ns"] : "page_ns";
  $js_key    = isset($options["js_key"]) ? $options["js_key"] : "field_type_id";

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}field_type_validation_rules
    ORDER BY field_type_id, list_order
  ");

  $grouped_rules = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $field_type_id = $row["field_type_id"];
    if (!array_key_exists($field_type_id, $grouped_rules))
      $grouped_rules[$field_type_id] = array();

    $grouped_rules[$field_type_id][] = array(
      "rule_id"    => $row["rule_id"],
      "rule_label" => ft_eval_smarty_string($row["rule_label"]),
      "default_error_message" => ft_eval_smarty_string($row["default_error_message"])
    );
  }

  $curr_js = array("{$namespace}.field_validation = {};");
  while (list($field_type_id, $rules) = each($grouped_rules))
  {
    $curr_js[] = "{$namespace}.field_validation[\"field_type_{$field_type_id}\"] = [";
    $curr_rules = array();
    foreach ($rules as $rule_info)
    {
      $curr_rules[] = "{ rule_id: {$rule_info["rule_id"]}, label: \"{$rule_info["rule_label"]}\", error: \"{$rule_info["default_error_message"]}\" }";
    }
    $curr_js[] = implode(",$delimiter", $curr_rules);
    $curr_js[] = "];";
  }

  $rows[] = implode("$delimiter", $curr_js);

  return implode("$delimiter", $rows);
}


/**
 * Deletes any validation defined for a particular field. This is called on the main Edit Form -> Fields tab, after a
 * field has it's field type changed.
 *
 * @param integer $field_id
 */
function ft_delete_field_validation($field_id)
{
  global $g_table_prefix;
  @mysql_query("DELETE FROM {$g_table_prefix}field_validation WHERE field_id = $field_id");
}


// to be added in a later version (2.1.5?)
function ft_get_php_field_validation_rules($field_ids)
{

}
