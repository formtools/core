<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

$request = array_merge($_POST, $_GET);
if (isset($request["external"]))
{
  header("location: step1.php");
  exit;
}
else if (isset($request["internal"]))
{
  header("location: internal.php");
  exit;
}

if (isset($request["new_form"]))
{
  $_SESSION["ft"]["add_form_form_id"] = "";
}

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_values = array();
$page_vars["page"]     = "add_form_choose_type";
$page_vars["page_url"] = ft_get_page_url("add_form_choose_type");
$page_vars["head_title"] = "{$LANG['phrase_add_form']}";
$page_vars["head_js"] =<<< END

$(function() {
  $("#select_external").bind("click", function() {
    var continue_decoded = $("<div />").html("{$LANG["word_continue_rightarrow"]}").text();
    ft.create_dialog({
      dialog:     $("#add_external_form_dialog"),
      title:      "{$LANG["word_checklist"]}",
      popup_type: "info",
      min_width:  600,
      buttons: [{
        text: continue_decoded,
        click: function() {
          window.location = "step1.php";
        }
      },
      {
        text: "{$LANG["word_cancel"]}",
        click: function() {
          $(this).dialog("close");
        }
      }]
    });
  });
});

END;

ft_display_page("admin/forms/add/index.tpl", $page_vars);
