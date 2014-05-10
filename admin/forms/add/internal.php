<?php

require("../../../global/session_start.php");
ft_check_permission("admin");
$request = array_merge($_POST, $_GET);

if (isset($request["add_form"]))
{
  list($g_success, $g_message, $new_form_id) = ft_create_internal_form($request);
  if ($g_message)
  {
    header("location: ../edit.php?form_id={$new_form_id}&message=notify_internal_form_created");
    exit;
  }
}

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_values = array();
$page_vars["page"]     = "add_form_internal";
$page_vars["page_url"] = ft_get_page_url("add_form_internal");
$page_vars["head_title"] = "{$LANG['phrase_add_form']}";
$page_vars["head_js"] =<<< END
ft.click([
  { el: "at1", targets: [{ el: "custom_clients", action: "hide" }] },
  { el: "at2", targets: [{ el: "custom_clients", action: "hide" }] },
  { el: "at3", targets: [{ el: "custom_clients", action: "show" }] }
]);

$(function() {
  $("#form_name").focus();
  $("#create_internal_form").bind("submit",function(e) {
    var rules = [];
    rules.push("required,form_name,{$LANG["validation_no_form_name"]}");
    rules.push("required,num_fields,{$LANG["validation_no_num_form_fields"]}");
    rules.push("digits_only,num_fields,{$LANG["validation_invalid_num_form_fields"]}");
    rules.push("range<=1000,num_fields,{$LANG["validation_internal_form_too_many_fields"]}");
    rules.push("required,access_type,{$LANG["validation_no_access_type"]}");
    if (!rsv.validate(this, rules)) {
      e.preventDefault();
    }
    ft.select_all("selected_client_ids[]");
  });
});
END;

ft_display_page("admin/forms/add/internal.tpl", $page_vars);
