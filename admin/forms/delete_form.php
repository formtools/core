<?php

require_once("../../global/library.php");

use FormTools\Core;
use FormTools\Fields;
use FormTools\FieldTypes;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$LANG = Core::$L;
$root_url = Core::getRootUrl();

$request = array_merge($_GET, $_POST);
$form_id = $request["form_id"];
if (!isset($form_id)) {
    General::redirect("$root_url/admin/forms/");
	exit;
}

$form_info = Forms::getForm($form_id);
if (empty($form_info)) {
    General::redirect("$root_url/admin/forms/");
    exit;
}
$form_name = $form_info["form_name"];
$auto_delete_submission_files = $form_info["auto_delete_submission_files"];

// get the names and URLs of all uploaded files. These are displayed in the page for the user
// so there's no doubt about exactly what they're deleting
$file_field_type_ids = FieldTypes::getFileFieldTypeIds();
$form_fields = Fields::getFormFields($form_id);
$file_field_ids = array();
foreach ($form_fields as $field) {
	if (!in_array($field["field_type_id"], $file_field_type_ids)) {
        continue;
    }
    $file_field_ids[] = $field["field_id"];
}
$uploaded_files = Fields::getUploadedFiles($form_id, $file_field_ids);

// delete the form
$success = true;
$message = "";
if (isset($_POST["delete_form"]) && $_POST["delete_form"] == "yes") {
	$delete_files = (isset($_POST['delete_files']) && $_POST['delete_files'] == "yes") ? true : false;
	list($success, $message) = Forms::deleteForm($form_id, $delete_files);

	// redirect back to the form list page
    General::redirect("$root_url/admin/forms/?message=form_deleted");
	exit;
}

// compile the header information
$page_vars = array(
    "head_title" => $LANG["phrase_delete_form"],
    "g_success"  => $success,
    "g_message"  => $message,
    "page"       => "delete_form",
    "page_url"   => Pages::getPageUrl("delete_form"),
    "form_id"    => $form_id,
    "form_info"  => $form_info,
    "uploaded_files" => $uploaded_files
);

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

Themes::displayPage("admin/forms/delete_form.tpl", $page_vars);
