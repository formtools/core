<?php

require("../../../global/session_start.php");
ft_check_permission("admin");
$request = array_merge($_POST, $_GET);

$num_forms = ft_get_form_count();
if (!empty($g_max_ft_forms) && $num_forms > $g_max_ft_forms) // note it's not >=
{
  header("location: ../index.php");
  exit;
}

if (isset($request["code"]) || isset($request["direct"]))
{
  $type = isset($request["code"]) ? "code" : "direct";
  header("location: step2.php?submission_type=$type");
  exit;
}

$form_id = ft_load_field("form_id", "add_form_form_id", "");

$form_info = array();
if (!empty($form_id))
  $form_info = ft_get_form($form_id);

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_values = array();
$page_vars["page"]     = "add_form1";
$page_vars["page_url"] = ft_get_page_url("add_form1");
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_1"]}";
$page_vars["form_info"] = $form_info;
$page_vars["head_js"] =<<< END
var rules = [];
var page_ns = {};
page_ns.current_section = null;
page_ns.show_section = function(section) {
  if (page_ns.current_section != null) {
    $("#" + page_ns.current_section).fadeOut({ duration: 400 });
    setTimeout(function() { $("#" + section).fadeIn({ duration: 400, }); }, 410);
  } else {
    $("#" + section).fadeIn({ duration: 400 });
  }
  page_ns.current_section = section;
  return false;
}
END;

ft_display_page("admin/forms/add/step1.tpl", $page_vars);
