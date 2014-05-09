<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

$form_id = ft_load_field("form_id", "add_form_form_id", "");

if (isset($_POST["submission_type"]))
  @mysql_query("UPDATE {$g_table_prefix}forms SET submission_type = '{$_POST["submission_type"]}' WHERE form_id=$form_id");

// if returning from a later stage and the user wants to resubmit the test submission, update the form
if (isset($_GET['uninitialize']) && $_GET['uninitialize'] == 1)
  ft_uninitialize_form($form_id);

// retrieve the form info
$form_info = ft_get_form($form_id);

// determine the input field values for cutting & pasting
$hidden_fields = '<input type="hidden" name="form_tools_initialize_form" value="1" />' . "\n"
               . '<input type="hidden" name="form_tools_form_id" value="' . $form_id . '" />';
$form_tag = '<form action="' . $g_root_url . '/process.php" method="post"';

if ($_SESSION["ft"]["uploading_files"] == "yes")
  $form_tag .= ' enctype="multipart/form-data"';

$form_tag .= '>';

$replacement_info = array("linktoform" => "{$form_info['form_url']}");
$direct_form_para_2 = ft_eval_smarty_string($LANG["text_add_form_step_2_para_3"], $replacement_info);

$replacement_info = array(
  "varname" => "<b>\$submission_hash</b>",
  "postvar" => "\$_POST",
  "sessionvar" => "\$_SESSION"
    );
$code_form_para_2 = ft_eval_smarty_string($LANG["text_add_form_step_2_para_6"], $replacement_info);


if (isset($_POST["refresh"]) && $form_info["is_initialized"] == "no")
{
  $g_success = false;
  $g_message = $LANG["notify_no_test_submission"];
}

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars["page"]     = "add_form3";
$page_vars["page_url"] = ft_get_page_url("add_form3");
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_3"]}";
$page_vars["form_id"] = $form_id;
$page_vars["form_tag"] = $form_tag;
$page_vars["form_info"] = $form_info;
$page_vars["hidden_fields"] = $hidden_fields;
$page_vars["direct_form_para_2"] = $direct_form_para_2;
$page_vars["code_form_para_2"] = $code_form_para_2;

$current_section = (!empty($form_info["submission_type"])) ? "\"{$form_info["submission_type"]}\"" : "null";
$page_vars["head_js"] =<<< END
var rules = [];
var page_ns = {};
page_ns.current_section = $current_section;
page_ns.show_section = function(section) {
  if (page_ns.current_section != null) {
    $("#" + page_ns.current_section).fadeOut({ duration: 400 });
    setTimeout(function() { $("#" + section).fadeIn({ duration: 400, }); }, 410);
  } else {
    $("#" + section).fadeIn({ duration: 400 });
  }

  // if the user just selected a submission type, highlight the appropriate box and store the
  // type to send along to the database. This information isn't needed outside of the Add Form
  // process, but it's nice to be able to re-fill the appropriate submission type box when
  if (section == 'direct') {
    $('#direct_box').removeClass('grey_box');
    $('#direct_box').addClass('blue_box');
    $('#code_box').removeClass('blue_box');
    $('#code_box').addClass('grey_box');
  }
  if (section == 'code') {
    $('#direct_box').addClass('grey_box');
    $('#direct_box').removeClass('blue_box');
    $('#code_box').addClass('blue_box');
    $('#code_box').removeClass('grey_box');
  }
  if (section != 'direct' && section != 'code') {
    $('#direct_box').addClass('grey_box');
    $('#direct_box').removeClass('blue_box');
    $('#code_box').addClass('grey_box');
    $('#code_box').removeClass('blue_box');
  }
  page_ns.current_section = section;
}

rsv.onCompleteHandler = function() { ft.select_all('selected_user_ids[]'); return true; }
END;

ft_display_page("admin/forms/add/step3.tpl", $page_vars);
