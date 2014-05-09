<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

// delete any temporary Smart Fill uploaded files
if (isset($_SESSION["ft"]["smart_fill_tmp_uploaded_files"]) && !empty($_SESSION["ft"]["smart_fill_tmp_uploaded_files"]))
{
  foreach ($_SESSION["ft"]["smart_fill_tmp_uploaded_files"] as $file)
    @unlink($file);
}

$_SESSION["ft"]["method"] = "";
$form_id = ft_load_field("form_id", "add_form_form_id", "");
unset($_SESSION["ft"]["add_form_form_id"]);

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars["page"] = "add_form6";
$page_vars["page_url"] = ft_get_page_url("add_form6");
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_5"]}";
$page_vars["form_id"] = $form_id;
$page_vars["text_add_form_step_5_para"]   = ft_eval_smarty_string($LANG["text_add_form_step_5_para_3"], array("editformlink" => "../edit.php?form_id={$form_id}"));
$page_vars["text_add_form_step_5_para_4"] = ft_eval_smarty_string($LANG["text_add_form_step_5_para_4"], array("editformlink" => "../edit.php?form_id={$form_id}"));
$page_vars["uploading_files"] = $_SESSION["ft"]["uploading_files"];
$page_vars["head_css"] = "";

ft_display_page("admin/forms/add/step6.tpl", $page_vars);
