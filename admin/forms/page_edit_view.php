<?php

$view_fields_sortable_id = "view_fields";
$submission_list_sortable_id = "submission_list";

// a form ID & view ID should always be set when visiting this page
$view_id = ft_load_field("view_id", "form_{$form_id}_view_id");

// this updates all four sections of the view at once (since all may have been modified)
if (isset($request["update_view"]))
{
  $request["form_id"] = $form_id;
  $request["view_fields_sortable_id"] = $view_fields_sortable_id;
  $request["submission_list_sortable_id"] = $submission_list_sortable_id;
  list($g_success, $g_message) = ft_update_view($view_id, $request);
}

$form_info     = ft_get_form($form_id);
$form_fields   = ft_get_form_fields($form_id, array("include_field_type_info" => true));
$view_info     = ft_get_view($view_id);

$form_database_column_info = ft_get_form_column_names($form_id);
$view_clients  = ft_get_view_clients($view_id);
$view_tabs     = ft_get_view_tabs($view_id);
$grouped_fields = ft_get_grouped_view_fields($view_id);
$field_types = ft_get_field_type_names();

$new_view_submission_defaults = ft_get_new_view_submission_defaults($view_id);

// this returns ALL filters: standard and client map
$standard_filters   = ft_get_view_filters($view_id, "standard");
$client_map_filters = ft_get_view_filters($view_id, "client_map");
$num_standard_filters   = count($standard_filters);
$num_client_map_filters = count($client_map_filters);

$edit_view_tab = (isset($_SESSION["ft"]["inner_tabs"]["edit_view"])) ? $_SESSION["ft"]["inner_tabs"]["edit_view"] : 1;
if (isset($request["edit_view_tab"]))
{
  $edit_view_tab = $request["edit_view_tab"];
  $_SESSION["ft"]["inner_tabs"]["edit_view"] = $edit_view_tab;
}

$view_omit_list = ft_get_public_view_omit_list($view_id);
$num_clients_on_omit_list = count($view_omit_list);

// assumes view_ns.all_form_fields and view_ns.view_tabs JS arrays have been defined in manage_views.js
// The form fields info is needed throughout the Edit View tabs, so stashing them in the page JS makes sense
$js_string = "";
$all_form_fields = array();
$date_field_ids = array();
foreach ($form_fields as $field)
{
  $display_name = htmlspecialchars($field["field_title"]);
  $col_name     = $field["col_name"];
  $field_id        = $field["field_id"];
  $is_system_field = ($field["is_system_field"] == "yes") ? "true" : "false";
  $is_date_field   = ($field["is_date_field"] == "yes") ? "true" : "false";
  $field_type_id = $field["field_type_id"];

  $all_form_fields[] = "{ field_id: $field_id, display_name: \"$display_name\", "
                       . "col_name: \"$col_name\", is_system_field: $is_system_field, "
                       . "is_date_field: $is_date_field, field_type_id: $field_type_id }";

  if ($is_date_field == "true")
    $date_field_ids[] = $field_id;
}
$all_form_fields_js = "view_ns.all_form_fields = [" . implode(",\n", $all_form_fields) . "];";

for ($i=1; $i<=count($view_tabs); $i++)
{
  if (empty($view_tabs["$i"]["tab_label"]))
    continue;

  $tab_name = addslashes($view_tabs["$i"]["tab_label"]);
  $js_string .= "view_ns.view_tabs.push([\"$i\", \"$tab_name\"]);\n";
}

// for the filters
$js_string .= "view_ns.num_standard_filter_rows = $num_standard_filters;\n";
$js_string .= "view_ns.num_client_map_filter_rows = $num_client_map_filters;\n";

// build the selected users <options>
$selected_users_str = "";
$selected_user_ids = array();
for ($i=0; $i<count($view_clients); $i++)
{
  $client_id  = $view_clients[$i]["account_id"];
  $first_name = $view_clients[$i]["first_name"];
  $last_name  = $view_clients[$i]["last_name"];
  $selected_users_str .= "<option value=\"$client_id\">$first_name $last_name</option>\n";

  $selected_user_ids[] = $client_id;
}

// build the available users <options>. This is used to populate the Available Clients section
// of Private accessed forms
$available_users_str = "";
foreach ($form_info["client_info"] as $client)
{
  if (in_array($client["account_id"], $selected_user_ids))
    continue;

  $available_users_str .= "<option value=\"{$client['account_id']}\">{$client['first_name']} {$client['last_name']}</option>\n";
}

// get the ID of the previous and next field options. We should probably cache this, but until I'm sure
// it's slowing things down, we'll keep it simple
$ordered_view_ids = ft_get_view_ids($form_id, true);
$previous_view_link = "<span class=\"light_grey\">{$LANG["phrase_previous_view"]}</span>";
$next_view_link = "<span class=\"light_grey\">{$LANG["phrase_next_view"]}</span>";
$num_views = count($ordered_view_ids);

$same_page = ft_get_clean_php_self();
for ($i=0; $i<$num_views; $i++)
{
  $curr_view_id = $ordered_view_ids[$i];
  if ($curr_view_id == $view_id)
  {
    if ($i != 0)
    {
      $previous_view_id = $ordered_view_ids[$i-1];
      $previous_view_link = "<a href=\"{$same_page}?page=edit_view&form_id=$form_id&view_id=$previous_view_id\">{$LANG["phrase_previous_view"]}</a>";
    }
    if ($i != $num_views - 1)
    {
      $next_view_id = $ordered_view_ids[$i+1];
      $next_view_link = "<a href=\"{$same_page}?page=edit_view&form_id=$form_id&view_id=$next_view_id\">{$LANG["phrase_next_view"]}</a>";
    }
  }
}

// override the form nav links so that it always links to the Views page
$page_vars["prev_tabset_link"] = (!empty($links["prev_form_id"])) ? "edit.php?page=views&form_id={$links["prev_form_id"]}" : "";
$page_vars["next_tabset_link"] = (!empty($links["next_form_id"])) ? "edit.php?page=views&form_id={$links["next_form_id"]}" : "";

// -----------------------------------------------------------------------------------------------

// compile the templates information
$page_vars["page"]       = "edit_view";
$page_vars["page_url"]   = ft_get_page_url("edit_view");
$page_vars["view_id"]    = $view_id;
$page_vars["grouped_fields"] = $grouped_fields;
$page_vars["new_view_submission_defaults"] = $new_view_submission_defaults;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_edit_view"]}";
$page_vars["standard_filters"]     = $standard_filters;
$page_vars["client_map_filters"]   = $client_map_filters;
$page_vars["num_standard_filters"] = $num_standard_filters;
$page_vars["num_client_map_filters"] = $num_client_map_filters;
$page_vars["edit_view_tab"] = $edit_view_tab;
$page_vars["form_info"]   = $form_info;
$page_vars["view_tabs"]   = $view_tabs;
$page_vars["view_info"]   = $view_info;
$page_vars["form_fields"] = $form_fields;
$page_vars["field_types"] = $field_types;
$page_vars["previous_view_link"] = $previous_view_link;
$page_vars["next_view_link"] = $next_view_link;
$page_vars["selected_users"] = $selected_users_str;
$page_vars["available_users"] = $available_users_str;
$page_vars["form_database_column_info"] = $form_database_column_info;
$page_vars["selected_user_ids"] = $selected_user_ids;
$page_vars["num_clients_on_omit_list"] = $num_clients_on_omit_list;
$page_vars["date_field_ids"] = $date_field_ids;
$page_vars["view_fields_sortable_id"] = $view_fields_sortable_id;
$page_vars["submission_list_sortable_id"] = $submission_list_sortable_id;

$page_vars["head_string"] =<<< END
  <script src="$g_root_url/global/scripts/jquery-ui-timepicker-addon.js"></script>
  <script src="$g_root_url/global/scripts/sortable.js?v=2"></script>
  <script src="$g_root_url/global/scripts/manage_views.js?v=4"></script>
END;

$replacements = array("user_doc_link" => "http://docs.formtools.org/userdoc2_1/index.php?page=view_filters");
$page_vars["text_filters_tips"] = ft_eval_smarty_string($LANG["text_filters_tips"], $replacements);
$replacements = array("number" => "<input type=\"text\" name=\"num_standard_filter_rows\" id=\"num_standard_filter_rows\" value=\"1\" size=\"2\" />");
$page_vars["add_standard_filter_num_rows_input_field"] = ft_eval_smarty_string($LANG["phrase_add_num_rows"], $replacements);
$replacements = array("number" => "<input type=\"text\" name=\"num_client_map_filter_rows\" id=\"num_client_map_filter_rows\" value=\"1\" size=\"2\" />");
$page_vars["add_client_map_filter_num_rows_input_field"] = ft_eval_smarty_string($LANG["phrase_add_num_rows"], $replacements);
$page_vars["js_messages"] = array("word_remove", "validation_no_tabs_defined", "phrase_all_fields_displayed",
        "validation_invalid_tab_assign_values", "validation_num_rows_to_add", "phrase_please_select", "word_before",
        "word_after", "word_equals", "phrase_not_equal", "word_like", "phrase_not_like", "validation_no_view_name",
        "validation_no_num_submissions_per_page", "validation_no_view_fields", "validation_no_column_selected",
        "validation_no_view_fields_selected", "phrase_first_name", "phrase_last_name", "phrase_company_name",
        "word_email", "word_notes", "word_id", "phrase_remove_row", "phrase_available_tabs", "word_close",
        "phrase_add_fields", "phrase_create_group", "word_cancel", "word_yes", "word_no", "phrase_auto_size", "word_width_c");

$field_type_map_lines = array();
while(list($field_type_id, $field_type_name) = each($field_types))
{
	$field_type_map_lines[] = "  \"ft{$field_type_id}\": \"$field_type_name\"";
}

$field_type_map = implode(",\n", $field_type_map_lines);
$field_type_map_js = "view_ns.field_type_map = {\n$field_type_map\n};";

$page_vars["head_js"] =<<< END
$all_form_fields_js
$field_type_map_js
$js_string

$(function() {
  ft.init_inner_tabs();

  // if there are no filters, init the form with a single, empty filter
  if (view_ns.num_standard_filter_rows == 0) {
    view_ns.add_standard_filters(1);
  }
  if (view_ns.num_client_map_filter_rows == 0) {
    view_ns.add_client_map_filters("1");
  }

  $(".adding_field_ids").live("change", function() {
    if (this.checked) {
      $(this).closest("li").addClass("selected_row");
    } else {
      $(this).closest("li").removeClass("selected_row");
    }
  });

  $(".tab_label").bind("blur", view_ns.update_tab_dropdowns);
  $(".add_field_link").live("click", view_ns.add_fields_dialog);

  $("input[name=access_type]").bind("click change", function() {
    $("#custom_clients").hide();
    $("#client_omit_list_button").attr("disabled", "disabled");

    var form_type = $(this).val();
    if (form_type == "public") {
      $("#client_omit_list_button").attr("disabled", "");
    }

    if (form_type == "private") {
      $("#custom_clients").show();
    }
  });

  $("input[name=may_add_submissions]").bind("click change", function() {
    if (this.value == "yes") {
      $("#add_submission_default_values").show();
    } else {
      $("#add_submission_default_values").hide();
    }
  });

  $(".custom_add_group_link").live("click", view_ns.add_field_group);

  $(".new_submission_field_id").live("change", function() {
    var field_id = $(this).val();
    var option_list_id;
    for (var i=0; i<view_ns.all_form_fields.length; i++) {
      if (view_ns.all_form_fields[i].field_id == field_id) {
        option_list_id = view_ns.all_form_fields[i].option_list_id;
        break;
      }
    }

    var col3 = $(this).closest("ul").find(".col3");
    if (field_id == "") {
      col3.html("");
    } else if (option_list_id == null) {
      col3.html("<input type=\"text\" name=\"new_submission_field_value\" class=\"new_submission_field_value\" />");
    }
  });

  $("#no_new_submission_default_values a").bind("click", function(e) {
    $("#no_new_submission_default_values").addClass("hidden");
    $("#new_submission_default_values").removeClass("hidden");
    view_ns.add_default_values_for_submission();
    e.preventDefault();
  });

  $(".auto_size").live("change", function() {
    var li = $(this).parent();
    if (this.checked) {
      li.find("label").addClass("black").removeClass("light_grey");
      li.addClass("light_grey");
      li.find(".custom_width").attr("disabled", "disabled").val("");
    } else {
      li.find("label").addClass("light_grey").removeClass("black");
      li.removeClass("light_grey");
      li.find(".custom_width").attr("disabled", "").focus();
    }
  });
});

var page_ns = {
  clientFields: [
    { val: "account_id",             text: "{$LANG["word_id"]}", section: "{$LANG["phrase_core_fields"]}" },
    { val: "first_name",             text: "{$LANG["phrase_first_name"]}", section: "{$LANG["phrase_core_fields"]}" },
    { val: "last_name",              text: "{$LANG["phrase_last_name"]}", section: "{$LANG["phrase_core_fields"]}" },
    { val: "email",                  text: "{$LANG["word_email"]}", section: "{$LANG["phrase_core_fields"]}" },
    { val: "settings__company_name", text: "{$LANG["phrase_company_name"]}", section: "{$LANG["phrase_core_fields"]}" }
  ]
}
END;

ft_display_page("admin/forms/edit.tpl", $page_vars);
