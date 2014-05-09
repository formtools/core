<?php

$head_js =<<< EOF
if (typeof fo_ns == 'undefined')
  var fo_ns = {};
fo_ns.field_type = "file";

var rules = [];
rules.push("required,field_title,{$LANG["validation_no_field_title"]}");

Event.observe(document, "dom:loaded", function() { $("field_type").value = fo_ns.field_type; file_settings_ns.init_page(); });
EOF;

if (isset($request["update_file_settings"]))
{
  $field_id = $request["field_id"];
  list($g_success, $g_message) = ft_update_field_file_settings($form_id, $field_id, $request);
}

$form_info = ft_get_form($form_id);
$field_info = ft_get_form_field($field_id);
$file_upload_dir = (empty($field_info['file_upload_dir'])) ? "$g_root_dir/files" : $field_info['file_upload_dir'];
$file_upload_url = (empty($field_info['file_upload_url'])) ? "$g_root_url/files" : $field_info['file_upload_url'];

if (array_key_exists("file_upload_url", $field_info["settings"]))
{
	$file_upload_url = $field_info["settings"]["file_upload_url"];
	$file_upload_dir = $field_info["settings"]["file_upload_dir"];

	unset($field_info["settings"]["file_upload_url"]);
	unset($field_info["settings"]["file_upload_dir"]);
	$field_info["settings"]["file_upload_folder"] = array(
	  "file_upload_url" => $file_upload_url,
	  "file_upload_dir" => $file_upload_dir
	    );
}

// condense the list of default file upload types
$file_upload_filetypes = explode(",", $_SESSION["ft"]["settings"]["file_upload_filetypes"]);
sort($file_upload_filetypes);
$sorted_file_upload_filetypes = implode(",", $file_upload_filetypes);

// ------------------------------------------------------------------------------------------------

// compile the templates information
$page_vars = array();
$page_vars["page"]       = "field_options";
$page_vars["page_url"]   = ft_get_page_url("edit_form_field_options", array("form_id" => $form_id));
$page_vars["tabs"]       = $tabs;
$page_vars["previous_field_link"] = $previous_field_link;
$page_vars["next_field_link"] = $next_field_link;
$page_vars["form_id"]    = $form_id;
$page_vars["field_id"]    = $field_id;
$page_vars["allow_url_fopen"] = (ini_get("allow_url_fopen") == "1");
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["word_files"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["field"]      = $field_info;
$page_vars["max_filesize"] = ft_get_upload_max_filesize();
$page_vars["file_upload_dir"] = $file_upload_dir;
$page_vars["file_upload_url"] = $file_upload_url;
$page_vars["file_upload_filetypes"] = $sorted_file_upload_filetypes;
$page_vars["js_messages"] = array("word_delete", "notify_setting_already_overwritten");
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"{$g_root_url}/global/scripts/manage_file_settings.js\"></script>
<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_field_options.js\"></script>";

$page_vars["head_js"] = $head_js;

ft_display_page("admin/forms/edit.tpl", $page_vars);