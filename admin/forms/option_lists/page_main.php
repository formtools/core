<?php

$sortable_id = "option_list";
if (isset($request["update_page"]))
{
  $request["sortable_id"] = $sortable_id;
  list($g_success, $g_message) = ft_update_option_list($list_id, $request);
}

$list_info = ft_get_option_list($list_id);
$total_options = 0;
foreach ($list_info["options"] as $option_info)
{
  $total_options += count($option_info["options"]);
}

$placeholders = array(
  "link1" => "edit.php?page=form_fields",
  "link2" => "index.php?add_option_list=1&create_option_list_from_list_id={$list_info["list_id"]}"
);

// get a list of all existing Option Lists; this is used to ensure the uniqueness of the option list names
// (necessary only from a user point of view)
$lists = ft_get_option_lists("all");
$list_names = array();
foreach ($lists["results"] as $curr_list_info)
{
  if ($list_id == $curr_list_info["list_id"])
    continue;

  $list_names[] = "\"" . htmlspecialchars($curr_list_info["option_list_name"]) . "\"";
}

$list_names = implode(",", $list_names);

$existing_option_list_names_js = "page_ns.option_list_names = [$list_names];";

// ------------------------------------------------------------------------------------------------


// compile template info
$page_vars["list_info"] = $list_info;
$page_vars["text_option_list_used_by_fields"] = ft_eval_smarty_string($LANG["text_option_list_used_by_fields"], $placeholders);
$page_vars["tabs"] = $tabs;
$page_vars["page_url"] = ft_get_page_url("edit_option_list");
$page_vars["head_title"] = $LANG["phrase_edit_option_list"];
$page_vars["num_fields_using_option_list"] = $num_fields;
$page_vars["total_options"] = $total_options;
$page_vars["sortable_id"] = $sortable_id;
$page_vars["js_messages"] = array("word_delete", "validation_no_smart_fill_values", "validation_invalid_url",
  "validation_smart_fill_no_field_found", "validation_smart_fill_cannot_fill", "validation_smart_fill_invalid_field_type",
  "validation_smart_fill_upload_all_pages", "validation_upload_html_files_only", "validation_smart_fill_no_page",
  "validation_no_option_list_name", "validation_option_list_name_taken", "validation_num_rows_to_add", "word_error",
  "word_okay", "phrase_please_confirm", "word_yes", "word_no", "confirm_delete_group", "phrase_create_group", "word_cancel",
  "notify_field_options_smart_filled"
);
$page_vars["head_string"] =<<< END
  <script src="$g_root_url/global/scripts/manage_option_lists.js"></script>
  <script src="$g_root_url/global/scripts/sortable.js?v=2"></script>
END;

$page_vars["head_js"] =<<< END
var page_ns = {};
page_ns.page_initialized = false;
page_ns.ungroup_options_dialog = $("<div></div>");

$existing_option_list_names_js

$(function() {
  // the main add rows button for ungrouped option lists
  sf_ns.num_rows = $("#num_rows").val();

  $("#add_rows_button").bind("click", function(e) {
    sf_ns.add_field_options($("#num_rows_to_add").val(), $(".rows:first"));
    e.preventDefault();
  });

  $("#option_list_form").bind("submit", function() {
    return sf_ns.submit_update_field_option_group_page();
  });

  $("input[name=is_grouped]").bind("change", function() {
    var selected = $(this).val();

    // the user wants to group the options. No problem: just enable the grouping
    if (selected == "yes") {
      $(".sortable_group_header").removeClass("hidden");
      $(".sortable_group_footer").removeClass("hidden");
      $(".add_group_section").removeClass("hidden");
      $(".add_ungrouped_rows").hide();

    // otherwise, it's a bit more fussy. Warn the user they're about to lose data, and reload the page
    } else {
      ft.create_dialog({
        dialog:     page_ns.ungroup_options_dialog,
        title:      "{$LANG["phrase_please_confirm"]}",
        content:    "{$LANG["confirm_ungroup_option_list"]}",
        popup_type: "warning",
        buttons: [
          {
            text: "{$LANG["word_yes"]}",
            click: function() {
              ft.dialog_activity_icon(this, "show");
              $("#option_list_form").trigger("submit");
            }
          },
          {
            text: "{$LANG["word_no"]}",
            click: function() {
              $("#go1").attr("checked", "checked");
              $(this).dialog("close");
            }
          }
        ]
      });
    }
  });

  $(".num_rows_to_add_to_group").live("keypress", function(e) {
    if (e.keyCode == 13) {
      var rows_section = $(this).closest(".sortable_group").find(".rows");
      sf_ns.add_field_options($(this).val(), rows_section);
      return false;
    }
  });

  $(".add_rows_to_group_button").live("click", function() {
    var num_rows = $(this).parent().find(".num_rows_to_add_to_group").val();
    var rows_section = $(this).closest(".sortable_group").find(".rows");
    sf_ns.add_field_options(num_rows, rows_section);
  });

  $("#option_lists_advanced_settings_link").bind("click", function() { return sf_ns.toggle_advanced_settings(); });
});
END;


ft_display_page("admin/forms/option_lists/edit.tpl", $page_vars);