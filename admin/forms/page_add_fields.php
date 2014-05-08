<?php

if (isset($request["update_add_field"]))
{
  $field_info = array();
  list($g_success, $g_message) = ft_add_form_fields($request, $form_id);

  if ($g_success)
  {
    session_write_close();
    header("Location: edit.php?page=database&form_id=$form_id");
    exit;
  }
  else
    $field_info = $_POST;
}

$form_info = ft_get_form($form_id);
$num_fields = isset($request["num_fields"]) ? $request["num_fields"] : 1;

// compile the templates information
$page_vars["page"]        = "add_fields";
$page_vars["page_url"]    = ft_get_page_url("edit_form_add_fields", array("form_id" => $form_id));
$page_vars["tabs"]        = $tabs;
$page_vars["form_id"]     = $form_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_add_fields"]}";
$page_vars["form_info"]   = $form_info;
$page_vars["form_fields"] = ft_get_form_fields($form_id);
$page_vars["num_fields"]  = $num_fields;
$page_vars["head_string"]  = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/add_fields.js\"></script>";
$page_vars["js_messages"] = array("validation_num_rows_to_add", "phrase_size_tiny", "phrase_size_small", "phrase_size_small",
        "phrase_size_medium", "phrase_size_large", "phrase_size_very_large", "word_string",
        "word_number", "word_delete", "validation_no_display_text", "validation_no_column_name",
        "validation_invalid_column_name", "validation_db_column_name_exists", "validation_no_two_column_names");
$page_vars["head_js"] = "

Event.observe(document, 'dom:loaded',
  function()
  {
    add_fields_ns.add_fields(\"$num_fields\");
    add_fields_ns.toggle_db_column_fields($('auto_generate_col_names').checked);
  });";

ft_display_page("admin/forms/edit.tpl", $page_vars);

?>