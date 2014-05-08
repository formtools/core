<?php

if (isset($request["update_database"]))
{
  $request["form_id"] = $form_id;
  list($g_success, $g_message) = ft_update_form_database_tab($request);
  ft_delete_form_fields($request, $form_id);
}

$form_info   = ft_get_form($form_id);
$form_fields = ft_get_form_fields($form_id);
$reserved_words = ft_get_mysql_reserved_words();

// build our list of "Best Guesses" for Smart Filling the database column names
$best_guesses = array();
$non_system_field_ids = array();
foreach ($form_fields as $field)
{
  if ($field["field_type"] == "system")
    continue;

	$non_system_field_ids[] = $field["field_id"];

	$field_name = $field["field_name"];
	$db_field_guess = preg_replace("/\s+/", "_", $field_name); // replace one or more whitespace chars with _
	$db_field_guess = preg_replace("/[^a-zA-Z0-9_]/", "", $db_field_guess); // trim out any other non-alpha chars
	$db_field_guess = strtolower($db_field_guess);

	if (ft_in_array_case_insensitive($db_field_guess, $reserved_words))
	  $db_field_guess = "ft_$db_field_guess";

  $best_guesses[] = "\"{$field['field_id']}\": \"$db_field_guess\"";
}
$best_guesses_js = join(",\n", $best_guesses);

// convert the reserved words into a JS array, and add the "Smart Fill" option
$escaped_words = array();
foreach ($reserved_words as $word)
	$escaped_words[] = "\"$word\"";

$js = "var page_ns = {};
page_ns.non_system_field_ids = [" . join(",", $non_system_field_ids) . "];
page_ns.reserved_words = [" . join(",", $escaped_words) . "];

page_ns.smart_fill = function()
{
  var smart_fill_values = {
    $best_guesses_js
  };

  if (confirm(\"{$LANG["confirm_smart_fill_db_column_fields"]}\"))
  {
    for (var field_id in smart_fill_values)
      $('col_' + field_id + '_name').value = smart_fill_values[field_id];
  }

  return false;
}

var rules = [];
rules.push(\"function,page_ns.check_non_empty_form_fields\");
rules.push(\"function,page_ns.check_db_column_field_names\");
rules.push(\"function,page_ns.check_db_column_field_reserved_word_conflicts\");


page_ns.check_non_empty_form_fields = function()
{
  var first_error_field = null;
  for (var i=0; i<page_ns.non_system_field_ids.length; i++)
  {
    var curr_field_id   = page_ns.non_system_field_ids[i];
    var curr_field_name = $('field_' + curr_field_id + '_name').value.strip();
    if (!curr_field_name)
    {
      if (!$('field_' + curr_field_id + '_name').hasClassName('rsvErrorField'))
        $('field_' + curr_field_id + '_name').addClassName('rsvErrorField');

      if (first_error_field == null)
        first_error_field = $('field_' + curr_field_id + '_name');
    }
    else
    {
      if ($('field_' + curr_field_id + '_name').hasClassName('rsvErrorField'))
        $('field_' + curr_field_id + '_name').removeClassName('rsvErrorField');
    }
  }

  if (first_error_field != null)
    return [[first_error_field, \"{$LANG["validation_no_form_field_name"]}\"]];

  return true;
}


/**
 * loop through all database columns fields and confirm that they're both entered, valid, and not included
 * in the MySQL reserved word list.
 */
page_ns.check_db_column_field_names = function()
{
  var first_error_field = null;
  for (var i=0; i<page_ns.non_system_field_ids.length; i++)
  {
    var curr_field_id = page_ns.non_system_field_ids[i];
    var db_field_name  = $('col_' + curr_field_id + '_name').value.strip();

    if (!db_field_name)
    {
      $('col_' + curr_field_id + '_name').addClassName('rsvErrorField');
      if (first_error_field == null)
        first_error_field = $('col_' + curr_field_id + '_name');
    }
    else
      $('col_' + curr_field_id + '_name').removeClassName('rsvErrorField');
  }

  if (first_error_field != null)
    return [[first_error_field, \"{$LANG["validation_invalid_column_name_short"]}\"]];

  return true;
}

page_ns.check_db_column_field_reserved_word_conflicts = function()
{
  var first_error_field = null;
  for (var i=0; i<page_ns.non_system_field_ids.length; i++)
  {
    var curr_field_id = page_ns.non_system_field_ids[i];
    var db_field_name  = $('col_' + curr_field_id + '_name').value.strip();

    // now confirm the database name isn't reserved
    db_field_name = db_field_name.toUpperCase();

    if (page_ns.reserved_words.include(db_field_name))
    {
      $('col_' + curr_field_id + '_name').addClassName('rsvErrorField');
      if (first_error_field == null)
        first_error_field = $('col_' + curr_field_id + '_name');
    }
  }

  if (first_error_field != null)
    return [[first_error_field, \"{$LANG["validation_col_name_is_reserved_word"]}\"]];

  return true;
}
";

// compile the templates information
$page_vars = array();
$page_vars["page"]        = "database";
$page_vars["page_url"]    = ft_get_page_url("edit_form_database", array("form_id" => $form_id));
$page_vars["tabs"]        = $tabs;
$page_vars["form_id"]     = $form_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["word_database"]}";
$page_vars["form_info"]   = $form_info;
$page_vars["form_fields"] = $form_fields;
$page_vars["head_js"]     = $js;

ft_display_page("admin/forms/edit.tpl", $page_vars);