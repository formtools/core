<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

$form_id = ft_load_field("form_id", "add_form_form_id", "");

if (!empty($_POST) && isset($_POST['action']))
{
  // reorder fields
  ft_reorder_form_fields($_POST, $form_id, true);

  // update their values
  ft_set_form_database_settings($_POST, $form_id);

  // delete unwanted fields
  ft_delete_form_fields($_POST, $form_id);
}

// go to next page
else if (!empty($_POST) && isset($_POST['next_step']))
{
	// reorder fields
  ft_reorder_form_fields($_POST, $form_id, true);

  // update their values
  ft_set_form_database_settings($_POST, $form_id);

  // delete unwanted fields
  ft_delete_form_fields($_POST, $form_id);

  session_write_close();
  header("location: step5.php?form_id=$form_id");
  exit;
}

$form_fields = ft_get_form_fields($form_id);
$form_setup  = ft_get_form($form_id);

// build the best guesses and list of field IDs
$best_guesses = array();
$field_ids    = array();
foreach ($form_fields as $field)
{
	$field_ids[] = $field["field_id"];

  if ($field["field_type"] == "system")
    continue;

  // best guess at generating an appropriate Display Name
  $temp = preg_replace("/_/", " ", $field["field_name"]);
  $display_name_guess = ucwords($temp);

  $best_guesses[] = "\"{$field['field_id']}\": \"$display_name_guess\"";
}
$best_guesses_js = join(",\n", $best_guesses);
$field_id_str = join(",", $field_ids);

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars["page"] = "add_form4";
$page_vars["page_url"] = ft_get_page_url("add_form4");
$page_vars["form_id"] = $form_id;
$page_vars["form_fields"] = $form_fields;
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_4"]}";
$page_vars["head_js"] = "
	var page_ns = {};
  page_ns.field_ids = [$field_id_str];

	page_ns.smart_fill = function()
	{
	  var smart_fill_values = {
	    $best_guesses_js
	  };

	  // if any of the fields contain a value already, inform the user
	  var str = '';
	  var has_value = false;
	  for (var field_id in smart_fill_values)
	  {
	    if ($('field_' + field_id + '_display_name').value)
	      has_value = true;
	  }

	  var fill_fields = true;
	  if (has_value)
	    fill_fields = confirm(\"{$LANG["confirm_smart_fill_display_names"]}\");

	  if (fill_fields)
	  {
	    for (var field_id in smart_fill_values)
	      $('field_' + field_id + '_display_name').value = smart_fill_values[field_id];
	  }
	}

	// called on form submit. Confirms that all fields have a display name
	page_ns.validate_fields = function()
	{
	  var all_filled = true;
	  for (var i=0; i<page_ns.field_ids.length; i++)
	  {
	    curr_field_id = page_ns.field_ids[i];
	    if (!$('field_' + curr_field_id + '_display_name').value)
	    {
	      if (!$('field_' + curr_field_id + '_display_name').hasClassName('rsvErrorField'))
	        $('field_' + curr_field_id + '_display_name').addClassName('rsvErrorField');
	      all_filled = false;
      }
      else
      {
	      if ($('field_' + curr_field_id + '_display_name').hasClassName('rsvErrorField'))
	        $('field_' + curr_field_id + '_display_name').removeClassName('rsvErrorField');
      }
    }

    if (!all_filled)
    {
      ft.display_message(\"ft_message\", false, \"{$LANG["validation_display_names_incomplete"]}\");
      return false;
    }

    return true;
  }
	";

ft_display_page("admin/forms/add/step4.tpl", $page_vars);