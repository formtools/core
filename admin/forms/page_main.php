<?php

$sortable_id = "multi_page_form_list";

if (isset($request["update_main"]))
  list($g_success, $g_message) = ft_update_form_main_tab($request, $form_id);

$form_info = ft_get_form($form_id);
$form_omit_list = ft_get_public_form_omit_list($form_id);
$num_clients_on_omit_list = count($form_omit_list);

$selected_client_ids = array();
foreach ($form_info["client_info"] as $client_info)
  $selected_client_ids[] = $client_info["account_id"];

$num_pages_in_multi_page_form = count($form_info["multi_page_form_urls"]) + 1;

// ------------------------------------------------------------------------------------------------

// compile the templates information
$page_vars["page"]       = "main";
$page_vars["page_url"]   = ft_get_page_url("edit_form_main", array("form_id" => $form_id));
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["word_main"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["selected_client_ids"] = $selected_client_ids;
$page_vars["num_clients_on_omit_list"] = $num_clients_on_omit_list;
$page_vars["sortable_id"] = $sortable_id;
$page_vars["js_messages"] = array("validation_no_url", "phrase_check_url", "word_page", "validation_invalid_url",
  "word_close", "validation_no_form_url", "phrase_form_field_placeholders");
$page_vars["head_string"] =<<< END
<script src="$g_root_url/global/scripts/manage_forms.js?v=2"></script>
<script src="$g_root_url/global/scripts/sortable.js?v=2"></script>
END;

$page_vars["head_js"] =<<< END
ft.click([
  {
    el: "at1",
    targets: [
      { el: "custom_clients",          action: "hide" },
      { el: "client_omit_list_button", action: "disable" }
    ]
  },
  {
    el: "at2",
    targets: [
      { el: "custom_clients",          action: "hide" },
      { el: "client_omit_list_button", action: "enable" }
    ]
  },
  {
    el: "at3",
    targets: [
      { el: "custom_clients",          action: "show" },
      { el: "client_omit_list_button", action: "disable" }
    ]
  }
]);

var rules = [];
rules.push("required,form_name,{$LANG['validation_no_form_name']}");
rules.push("function,mf_ns.check_first_form_url");
rules.push("required,access_type,{$LANG["validation_no_access_type"]}");
rsv.onCompleteHandler = function() { ft.select_all("selected_client_ids[]"); return true; }

$(function() {
  mf_ns.num_multi_page_form_pages = $num_pages_in_multi_page_form;

  $("#form_type").bind("change", function() {
    $(".form_type_specific_options").hide();
    $("#form_settings__" + this.value).show();
  });

  $("#submission_type").bind("change", function() {
    if ($(this).val() == "direct") {
      $("#redirect_url_row, #form_url_single").show();
      $("#multi_page_form_row, #form_url_multiple").hide();
    } else {
      $("#redirect_url_row").hide();
      $("#multi_page_form_row").show();
    }
  });

  $(".is_multi_page_form").bind("click", function() {
    if ($(this).val() == "yes") {
      $("#form_url_single, #form_label_single").hide();
      $("#form_url_multiple, #form_label_multiple").show();
    } else {
      $("#form_url_single, #form_label_single").show();
      $("#form_url_multiple, #form_label_multiple").hide();
    }
  });

  ft.init_check_url_buttons();
});
END;

ft_display_page("admin/forms/edit.tpl", $page_vars);