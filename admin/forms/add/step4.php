<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

$form_id = ft_load_field("form_id", "add_form_form_id", "");
$sortable_id = "add_form_step4";

// go to next page
if (!empty($_POST) && isset($_POST["next_step"]))
{
  // reorder fields and rename their column names, as per their new order
  $_POST["sortable_id"] = $sortable_id;
  ft_update_form_fields($form_id, $_POST, true);

  $deleted_rows = explode(",", $_POST["{$sortable_id}_sortable__deleted_rows"]);
  ft_delete_form_fields($form_id, $deleted_rows);

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

  if ($field["is_system_field"] == "yes")
    continue;

  // best guess at generating an appropriate Display Name
  $temp = preg_replace("/_/", " ", $field["field_name"]);
  $display_name_guess = ucwords($temp);

  $best_guesses[] = "\"{$field['field_id']}\": \"$display_name_guess\"";
}
$best_guesses_js = implode(",\n", $best_guesses);
$field_id_str = implode(",", $field_ids);

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars["page"] = "add_form4";
$page_vars["page_url"] = ft_get_page_url("add_form4");
$page_vars["sortable_id"] = $sortable_id;
$page_vars["form_id"] = $form_id;
$page_vars["form_fields"] = $form_fields;
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_4"]}";
$page_vars["head_string"] =<<< END
  <script src="{$g_root_url}/global/scripts/sortable.js?v=2"></script>
END;
$page_vars["head_js"] =<<< END
  var page_ns = {};
  page_ns.field_ids = [$field_id_str];
  page_ns.smart_fill_values = {
    $best_guesses_js
  };

  page_ns.smart_fill = function() {
    // if any of the fields contain a value already, inform the user
    var str = '';
    var has_value = false;
    for (var field_id in page_ns.smart_fill_values) {
      if ($('#field_' + field_id + '_display_name').val()) {
        has_value = true;
      }
    }
    if (has_value) {
     ft.create_dialog({
       title:     "{$LANG["phrase_please_confirm"]}",
       content:   "{$LANG["confirm_smart_fill_display_names"]}",
       buttons: [{
           text:  "{$LANG["word_yes"]}",
           click: function() {
             page_ns.fill();
             $(this).dialog("close");
           }
         },
         {
           text:  "{$LANG["word_no"]}",
           click: function() {
             $(this).dialog("close");
           }
         }
       ]
     });
    } else {
      page_ns.fill();
    }
  }

  page_ns.fill = function() {
    for (var field_id in page_ns.smart_fill_values) {
      $("#field_" + field_id + "_display_name").val(page_ns.smart_fill_values[field_id]);
    }
  }

  // called on form submit. Confirms that all fields have a display name
  page_ns.validate_fields = function() {
    var all_filled = true;
    for (var i=0; i<page_ns.field_ids.length; i++) {
      curr_field_id = page_ns.field_ids[i];

      // ignore deleted rows
      if (!$('#field_' + curr_field_id + '_display_name').length) {
        continue;
      }

      if (!$('#field_' + curr_field_id + '_display_name').val()) {
        if (!$('#field_' + curr_field_id + '_display_name').hasClass('rsvErrorField')) {
          $('#field_' + curr_field_id + '_display_name').addClass('rsvErrorField');
        }
        all_filled = false;
      }
      else
      {
        if ($('#field_' + curr_field_id + '_display_name').hasClass('rsvErrorField')) {
          $('#field_' + curr_field_id + '_display_name').removeClass('rsvErrorField');
        }
      }
    }

    if (!all_filled) {
      ft.display_message("ft_message", false, "{$LANG["validation_display_names_incomplete"]}");
      return false;
    }

    return true;
  }
END;

$page_vars["js_messages"] = array("phrase_delete_row");

ft_display_page("admin/forms/add/step4.tpl", $page_vars);
