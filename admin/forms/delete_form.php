<?php

require("../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_GET, $_POST);
$form_id = $request["form_id"];
if (!isset($form_id))
{
  session_write_close();
  header("location: $g_root_url/admin/forms/");
  exit;
}

$form_info = ft_get_form($form_id);
if (empty($form_info))
{
  session_write_close();
  header("location: $g_root_url/admin/forms/");
  exit;
}
$form_name = $form_info["form_name"];
$auto_delete_submission_files = $form_info["auto_delete_submission_files"];

// get the names and URLs of all uploaded files. These are displayed in the page for the user
// so there's no doubt about exactly what they're deleting
$file_field_type_ids = ft_get_file_field_type_ids();
$form_fields = ft_get_form_fields($form_id);
$file_field_ids = array();
foreach ($form_fields as $field)
{
  if (!in_array($field["field_type_id"], $file_field_type_ids))
    continue;

  $file_field_ids[] = $field["field_id"];
}
$uploaded_files = ft_get_uploaded_files($form_id, $file_field_ids);

// delete the form
if (isset($_POST["delete_form"]) && $_POST["delete_form"] == "yes")
{
  $delete_files = (isset($_POST['delete_files']) && $_POST['delete_files'] == "yes") ? true : false;
  list($g_success, $g_message) = ft_delete_form($form_id, $delete_files);

  // redirect back to the form list page
  header("location: $g_root_url/admin/forms/");
  exit;
}

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars = array();
$page_vars["head_title"] = $LANG["phrase_delete_form"];
$page_vars["page"]       = "delete_form";
$page_vars["page_url"]   = ft_get_page_url("delete_form");
$page_vars["form_id"]    = $form_id;
$page_vars["form_info"]  = $form_info;
$page_vars["uploaded_files"] = $uploaded_files;
$page_vars["head_js"] =<<< END
var page_ns = {};
page_ns.show_uploaded_files = function(){
  $('#uploaded_files').show(600);
}
var rules = ["required,delete_form,{$LANG["validation_delete_form_confirm"]}"];

$(function() {
  $("#delete_form").focus();
});
END;

ft_display_page("admin/forms/delete_form.tpl", $page_vars);
