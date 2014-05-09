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
$form_name = $form_info["form_name"];
$auto_delete_submission_files = $form_info["auto_delete_submission_files"];

// get the names and URLs of all uploaded files
$form_fields = ft_get_form_fields($form_id);
$file_field_hash = array(); // field_id => upload folder URL
foreach ($form_fields as $field)
{
	// TODO
  if ($field["field_type_id"] == "file")
  {
  	$field_id = $field["field_id"];
  	$field_settings = ft_get_extended_field_settings($field_id);
  	$file_field_hash[$field["field_id"]] = array($field_settings["file_upload_dir"], $field_settings["file_upload_url"]);
  }
}

// now get all files for each ID
$files_uploaded = array(); // field_id => files uploaded
if (!empty($file_field_hash))
{
  foreach ($file_field_hash as $field_id => $upload_url)
  {
    $uploaded_files = ft_get_uploaded_filenames($form_id, $field_id);

    $files = array();
    foreach ($uploaded_files as $file)
    {
    	$filename_only = preg_replace("/.*([\/\\\])/", "", $file);
    	$files[] = $filename_only;
    }
    $files_uploaded[$field_id] = $files;
  }
}

// delete the form
if (isset($_POST["delete_form"]) && $_POST["delete_form"] == "yes")
{
  $delete_files = (isset($_POST['delete_files']) && $_POST['delete_files'] == "yes") ? true : false;
  ft_delete_form($form_id, $delete_files);

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
$page_vars["files_uploaded"] = $files_uploaded;
$page_vars["file_field_hash"] = $file_field_hash;
$page_vars["head_js"] =<<< END
var page_ns = {};
page_ns.show_uploaded_files = function(){
  // TODO
  Effect.Appear('uploaded_files', { duration: 0.6 });
}
var rules = ["required,delete_form,{$LANG["validation_delete_form_confirm"]}"];

$(function() {
  $("#delete_form").focus();
});
END;

ft_display_page("admin/forms/delete_form.tpl", $page_vars);