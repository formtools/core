/**
 * File:        manage_views.js
 * Abstract:    contains all JS for the Views and Edit View pages.
 * Assumptions: g.root_url is defined
 */

var view_ns = {};
view_ns.num_standard_filter_rows   = 0; // the number of standard filters currently displayed (overwritten by calling page)
view_ns.num_client_map_filter_rows = 0; // the number of client map filters currently displayed (overwritten by calling page)
view_ns.field_ids       = []; // stores the fields IDs currently in the view
view_ns.all_form_fields = []; // all form fields
view_ns.view_tabs       = []; // the view tabs. This is change dynamically in the page
view_ns.tabindex_increment = 1000;
view_ns.delete_view_dialog = $("<div id=\"delete_view_dialog\"></div>");


// used internally to create a new group ID for dynamically inserted groups. This is used temporarily
// in the page. When submitted, the server code does the job of assigned appropriate group IDs from the DB
view_ns.curr_new_group_id = 1;

// initialized on page load
view_ns.num_new_submission_default_values = 0;
view_ns.num_view_columns = 0;


$(function() {
  $(".add_field_link").live("click", function() {
    var group_id = $(this).closest(".sortable_group").find(".group_order").val();
    ft.create_dialog({
      dialog:  $("#new_view_dialog"),
      title:   g.messages["phrase_create_new_view"],
      buttons: [{
        text:  g.messages["phrase_create_new_view"],
        click: function() {
          ft.dialog_activity_icon($("#new_view_dialog"), "show");
          var form_id = $("#form_id").val();
          var data_type = (g.js_debug) ? "html" : "json";
          $.ajax({
            url:      g.root_url + "/global/code/actions.php",
            type:     "POST",
            dataType: data_type,
            data:     {
              form_id:                  form_id,
              group_id:                 group_id,
              action:                   "create_new_view",
              view_name:                $("#new_view_name").val(),
              create_view_from_view_id: $("#create_view_from_view_id").val()
            },
            success: function(data) {
              // check the user wasn't logged out / denied permissions
              if (!ft.check_ajax_response_permissions(data)) {
                return;
              }

              if (g.js_debug) {
                console.log(data);
                return;
              }
              window.location = "edit.php?page=edit_view&edit_view_tab=1&form_id=" + form_id + "&view_id=" + data.view_id;
            },
            error: function(jqXHR, textStatus, errorThrown) {
              $("#new_view_dialog").dialog("close");
              ft.error_handler(jqXHR, textStatus, errorThrown);
            }
          });
        }
      },
      {
        text:  g.messages["word_cancel"],
        click: function() {
          $(this).dialog("close");
        }
      }]
    });
  });
});


/**
 * Creates a new View group.
 */
view_ns.create_new_group = function() {
  ft.dialog_activity_icon($("#add_group_popup"), "show");
  $.ajax({
    url:      g.root_url + "/global/code/actions.php",
    type:     "POST",
    dataType: "json",
    data:     { group_name: $("#add_group_popup").find(".new_group_name").val(), action: "create_new_view_group" },
    success:  view_ns.create_new_group_response,
    error:    function(xhr, text_status, error_thrown) {
      ft.dialog_activity_icon($("#add_group_popup"), "hide");
      $("#add_group_popup").dialog("close");
      ft.error_handler(xhr, text_status, error_thrown);
    }
  });
  return false;
}


/**
 * Creates a new View group (on the main Views page).
 */
view_ns.create_new_group_response = function(data) {
  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  ft.dialog_activity_icon($("#add_group_popup"), "hide");
  $("#add_group_popup").dialog("close");
  sortable_ns.insert_new_group({
    group_id:     data.group_id,
    group_name:   data.group_name,
    is_groupable: true
  });
}


view_ns.delete_view = function(el) {
  var view_id = $(el).closest(".row_group").find(".sr_order").val();

  ft.create_dialog({
    dialog:     view_ns.delete_view_dialog,
    title:      g.messages["phrase_delete_view"],
    content:    g.messages["confirm_delete_view"],
    popup_type: "warning",
    buttons: [{
      text: g.messages["word_yes"],
      click: function() {
        ft.dialog_activity_icon($("#delete_view_dialog"), "show");
        $.ajax({
          url:      g.root_url + "/global/code/actions.php",
          type:     "POST",
          dataType: "json",
          data:     { view_id: view_id, action: "delete_view" },
          success:  view_ns.delete_view_response,
          error:    function(xhr, text_status, error_thrown) {
            ft.dialog_activity_icon($("#add_group_popup"), "hide");
            $("#delete_view_dialog").dialog("close");
            ft.error_handler(xhr, text_status, error_thrown);
          }
        });
      }
    },
    {
      text: g.messages["word_no"],
      click: function() {
        $(this).dialog("close");
      }
    }]
  });
}


view_ns.delete_view_response = function(data) {
  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  ft.dialog_activity_icon($("#delete_view_dialog"), "hide");
  $("#delete_view_dialog").dialog("close");
  sortable_ns.delete_row("view_list", $(".sr_order[value=" + data.view_id + "]"));
  ft.display_message("ft_message", 1, g.messages["notify_view_deleted"]);

  // one final thing: delete the now-orphaned reference to this deleted View in the Add View dialog source
  $("#create_view_from_view_id option[value=" + data.view_id + "]").remove();
}


/**
 * Hides/shows the custom client list.
 */
view_ns.toggle_custom_client_list = function(val) {
  if (val == "yes") {
    $("#custom_clients").hide();
  } else {
    $("#custom_clients").show();
  }
}


/**
 * Enables / disables all fields in the View.
 */
view_ns.toggle_editable_fields = function(may_edit) {
  if (may_edit) {
    $(".editable_fields").attr({ disabled: "" });
  } else {
    $(".editable_fields").attr({ disabled: "disabled" });
  }
}


/**
 * Called after the user changes the tab content.
 */
view_ns.update_tab_dropdowns = function() {
  var tabs = view_ns.get_tab_dropdown_contents();
  $(".tabs_dropdown").each(function() {
    var curr_dropdown_selection = this.value;
    if (tabs.length == 0) {
      $(this).html("<option value=\"\">" + g.messages["validation_no_tabs_defined"] + "</option>");
    } else {
      var options = [];
      for (var i=0; i<tabs.length; i++) {
      var is_selected = (tabs[i].tab == curr_dropdown_selection) ? " selected" : "";
        options.push($("<option value=\"" + tabs[i].tab + "\"" + is_selected + ">" + tabs[i].label + "</option>"));
      }
      var optgroup = $("<optgroup label=\"" + g.messages["phrase_available_tabs"] + "\"></optgroup>").append(ft.group_nodes(options));
      $(this).html(optgroup);
    }
  });
}


/**
 * Looks at the contents of the tabs and constructs and returns
 */
view_ns.get_tab_dropdown_contents = function() {
  var tabs = [];
  $(".tab_label").each(function(i) {
    var curr_tab_label = $.trim($(this).val());
    if (curr_tab_label == "") {
      return;
    }
    var tab_num = i + 1;
    tabs.push({ tab: tab_num, label: curr_tab_label});
  });

  return tabs;
}


/**
 * Hides / shows the sortable checkbox for a particular field. Called whenever a user clicks a
 * "column" checkbox.
 *
 * @param integer field_id
 * @param bool show hide/show the field
 */
view_ns.toggle_sortable_field = function(field_id, show) {
  $("#sortable_" + field_id).css("display", ((show) ? "block" : "none"));
}


/**
 * Helper function to get the field name out of the view_ns.all_form_fields array.
 *
 * @param integer field_id
 */
view_ns.get_field_name = function(field_id) {
  var field_name = null;
  for (var i=0; i<view_ns.all_form_fields.length; i++) {
    if (view_ns.all_form_fields[i].field_id == field_id) {
      field_name = view_ns.all_form_fields[i].display_name;
      break;
    }
  }
  return field_name;
}


/**
 * Removes the view field from the view field table, on the Edit View page and decrements the order
 * values for all fields found after this item.
 *
 * @param integer field_id the unique field ID
 */
view_ns.remove_view_field = function(field_id) {
  var tbody = $("#view_fields_table").getElementsByTagName("tbody")[0];

  for (var i=tbody.childNodes.length-1; i>0; i--) {
    // ignore any whitespace "nodes"
    if (tbody.childNodes[i].nodeName == '#text') {
      continue;
    }

    if (tbody.childNodes[i].id == "field_row_" + field_id) {
      tbody.removeChild(tbody.childNodes[i]);
      break;
    }

    var curr_field_id = tbody.childNodes[i].id.replace(/field_row_/, "");
    var curr_order    = parseFloat($("field_" + curr_field_id + "_order").value);
    $("field_" + curr_field_id + "_order").value = curr_order-1;
  }

  // remove it from view_ns.field_ids
  view_ns.field_ids = $(view_ns.field_ids).without(field_id);

  // update the available field dropdown
  view_ns.update_available_view_fields();

  return false;
}


/**
 * Used to add rows to the standard filters table on the Edit View page (the first table). This is separated from the
 * client map filter table because the content is sufficiently different.
 *
 * @param integer num_rows the number of rows to add
 */
view_ns.add_standard_filters = function(num_rows) {
  var num_rows = num_rows.toString();
  if (num_rows.match(/\D/) || num_rows == 0 || num_rows == "") {
    ft.display_message("ft_message", false, g.messages["validation_num_rows_to_add"]);
    $("#num_standard_filter_rows").focus();
    return;
  }

  for (var i=1; i<=num_rows; i++) {
    var curr_row = ++view_ns.num_standard_filter_rows;

    var row = document.createElement("tr");
    row.setAttribute("id", "standard_row_" + curr_row);

    // [1] first <td> cell: form field dropdown (defaulted to "please select")
    var td2 = document.createElement("td");
    var dd2 = document.createElement("select");
    dd2.setAttribute("name", "standard_filter_" + curr_row + "_field_id");
    dd2.setAttribute("id", "standard_filter_" + curr_row + "_field_id");
    $(dd2).bind("change", { curr_row: curr_row }, function(e) {
      view_ns.change_standard_filter_field(e.data.curr_row);
    });

    var default_option = document.createElement("option");
    default_option.setAttribute("value", "");
    default_option.appendChild(document.createTextNode(g.messages["phrase_please_select"]));
    dd2.appendChild(default_option);

    // now add all form fields (even if they're not included in the View)
    for (var j=0; j<view_ns.all_form_fields.length; j++) {
      field_id   = view_ns.all_form_fields[j].field_id;
      field_name = view_ns.all_form_fields[j].display_name;
      dd2.options[j+1] = new Option(field_name, field_id);
    }
    td2.appendChild(dd2);

    // [3] third <td> cell: operator dropdown

    // TODO What if a date field is first?
    var td3 = document.createElement("td");

    // -- first section: DATE operators
    var first_div = document.createElement("div");
    first_div.setAttribute("id", "standard_filter_" + curr_row + "_operators_dates_div");
    first_div.style.cssText = "display: none";
    var operator_dd = document.createElement("select");
    operator_dd.setAttribute("name", "standard_filter_" + curr_row + "_operator_date");
    var option1 = document.createElement("option");
    option1.setAttribute("value", "before");
    option1.appendChild(document.createTextNode(g.messages["word_before"]));
    var option2 = document.createElement("option");
    option2.setAttribute("value", "after");
    option2.appendChild(document.createTextNode(g.messages["word_after"]));
    operator_dd.appendChild(option1);
    operator_dd.appendChild(option2);
    first_div.appendChild(operator_dd);

    // -- second section: REGULAR operators
    var second_div = document.createElement("div");
    second_div.setAttribute("id", "standard_filter_" + curr_row + "_operators_div");
    var operator_dd = document.createElement("select");
    operator_dd.setAttribute("name", "standard_filter_" + curr_row + "_operator");
    var option1 = document.createElement("option");
    option1.setAttribute("value", "equals");
    option1.appendChild(document.createTextNode(g.messages["word_equals"]));
    var option2 = document.createElement("option");
    option2.setAttribute("value", "not_equals");
    option2.appendChild(document.createTextNode(g.messages["phrase_not_equal"]));
    var option3 = document.createElement("option");
    option3.setAttribute("value", "like");
    option3.appendChild(document.createTextNode(g.messages["word_like"]));
    var option4 = document.createElement("option");
    option4.setAttribute("value", "not_like");
    option4.appendChild(document.createTextNode(g.messages["phrase_not_like"]));
    operator_dd.appendChild(option1);
    operator_dd.appendChild(option2);
    operator_dd.appendChild(option3);
    operator_dd.appendChild(option4);
    second_div.appendChild(operator_dd);
    td3.appendChild(first_div);
    td3.appendChild(second_div);

    // [4] fourth <td> cell: user defined values textbox
    var td4 = document.createElement("td");

    // -- first section: DATE textbox & select image
    var first_div = document.createElement("div");
    first_div.setAttribute("id", "standard_filter_" + curr_row + "_values_dates_div");
    first_div.style.cssText = "display: none";
    $(first_div).addClass("cf_date_group");

    var inp = document.createElement("input");
    inp.setAttribute("type", "text");
    inp.setAttribute("name", "standard_filter_" + curr_row + "_filter_date_values");
    inp.setAttribute("id", "standard_date_" + curr_row);

    var img = document.createElement("img");
    img.setAttribute("src", g.root_url + "/global/images/calendar.png");
    img.setAttribute("id", "standard_date_image_" + curr_row);
    img.setAttribute("border", "0");
    $(img).addClass("ui-datepicker-trigger");

    first_div.appendChild(inp);
    first_div.appendChild(img);

    // -- second section: REGULAR textbox
    var second_div = document.createElement("div");
    second_div.setAttribute("id", "standard_filter_" + curr_row + "_values_div");
    var inp2 = document.createElement("input");
    inp2.setAttribute("type", "text");
    inp2.setAttribute("name", "standard_filter_" + curr_row + "_filter_values");
    inp2.setAttribute("style", "width: 144px;");
    second_div.appendChild(inp2);
    td4.appendChild(first_div);
    td4.appendChild(second_div);

    // [5] a delete column
    var td5 = document.createElement("td");
    td5.setAttribute("align", "center");
    td5.setAttribute("class", "del"); // for Mozilla
    td5.className = "del"; // for IE
    var delete_link = document.createElement("a");
    delete_link.setAttribute("href", "#");
    $(delete_link).bind("click", { curr_row: curr_row }, function(e) {
      return view_ns.delete_filter_row("standard", e.data.curr_row);
    });
    td5.appendChild(delete_link);

    // add the table data cells to the row
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);
    row.appendChild(td5);

    // add the row to the table
    $("#standard_filters_table tbody").append(row);
    $("#standard_date_" + curr_row).datetimepicker({
      showSecond: true,
      timeFormat: "hh:mm:ss",
      dateFormat: "yy-mm-dd"
    });
  }

  // update the filter count
  $("#num_standard_filters").val(view_ns.num_standard_filter_rows);
}


/**
 * Used to add rows to the client map filters table on the Edit View page.
 *
 * @param integer num_rows the number of rows to add
 */
view_ns.add_client_map_filters = function(num_rows) {
  if (num_rows.match(/\D/) || num_rows == 0 || num_rows == "") {
    ft.display_message("ft_message", false, g.messages["validation_num_rows_to_add"]);
    $("#num_client_map_filter_rows").focus();
    return;
  }

  var tbody = $("#client_map_filters_table tbody")[0];
  for (var i=1; i<=num_rows; i++) {
    var curr_row = ++view_ns.num_client_map_filter_rows;
    var row = document.createElement("tr");
    row.setAttribute("id", "client_map_row_" + curr_row);

    // [1] first <td> cell: form field dropdown (defaulted to "please select")
    var td2 = document.createElement("td");
    var dd2 = document.createElement("select");
    dd2.setAttribute("name", "client_map_filter_" + curr_row + "_field_id");
    dd2.setAttribute("id", "client_map_filter_" + curr_row + "_field_id");

    // now add all form fields (even if they're not included in the View)
    var options = "<option value=\"\">" + g.messages["phrase_please_select"] + "</option>";
    for (var j=0; j<view_ns.all_form_fields.length; j++) {
      var field_id   = view_ns.all_form_fields[j].field_id;
      var field_name = view_ns.all_form_fields[j].display_name;
      options += "<option value=\"" + field_id + "\">" + field_name + "</option>";
    }
    $(dd2).append(options);
    td2.appendChild(dd2);

    // [3] third <td> cell: operator dropdown

    // TODO What if a date field is first?
    var td3 = document.createElement("td");

    // -- second section: REGULAR operators
    var div = document.createElement("div");
    div.setAttribute("id", "client_map_filter_" + curr_row + "_operators_div");
    var operator_dd = document.createElement("select");
    operator_dd.setAttribute("name", "client_map_filter_" + curr_row + "_operator");
    var option1 = document.createElement("option");
    option1.setAttribute("value", "equals");
    option1.appendChild(document.createTextNode(g.messages["word_equals"]));
    var option2 = document.createElement("option");
    option2.setAttribute("value", "not_equals");
    option2.appendChild(document.createTextNode(g.messages["phrase_not_equal"]));
    var option3 = document.createElement("option");
    option3.setAttribute("value", "like");
    option3.appendChild(document.createTextNode(g.messages["word_like"]));
    var option4 = document.createElement("option");
    option4.setAttribute("value", "not_like");
    option4.appendChild(document.createTextNode(g.messages["phrase_not_like"]));
    operator_dd.appendChild(option1);
    operator_dd.appendChild(option2);
    operator_dd.appendChild(option3);
    operator_dd.appendChild(option4);
    div.appendChild(operator_dd);
    td3.appendChild(div);

    // [4] fourth <td> cell: select dropdown
    var td4 = document.createElement("td");
    var dd = document.createElement("select");
    dd.setAttribute("style", "width: 160px;");
    dd.setAttribute("name", "client_map_filter_" + curr_row + "_client_field");

    var default_option = document.createElement("option");
    default_option.setAttribute("value", "");
    default_option.appendChild(document.createTextNode(g.messages["phrase_please_select"]));
    dd.appendChild(default_option);

    // add in the contents of the page_ns.clientFields array. For extensibility with
    // other modules, the options are grouped in optgroups.
    var current_section = null;
    var optgroup = null;
    for (var j=0; j<page_ns.clientFields.length; j++) {
      if (page_ns.clientFields[j].section != current_section) {
        if (current_section != null) {
          dd.appendChild(optgroup);
        }

        optgroup = document.createElement("optgroup");
        current_section = page_ns.clientFields[j].section;
        optgroup.setAttribute("label", current_section);
      }

      var option = document.createElement("option");
      option.setAttribute("value", page_ns.clientFields[j].val);
      option.appendChild(document.createTextNode(page_ns.clientFields[j].text));
      optgroup.appendChild(option);
    }

    dd.appendChild(optgroup);

    // add any additional fields defined in the page (from the Extended Client Fields module)
    td4.appendChild(dd);

    // [5] a delete column
    var td5 = document.createElement("td");
    td5.setAttribute("align", "center");
    td5.setAttribute("class", "del"); // for Mozilla
    td5.className = "del"; // for IE
    var delete_link = document.createElement("a");
    delete_link.setAttribute("href", "#");
    $(delete_link).bind("click", { curr_row: curr_row }, function(e) {
      return view_ns.delete_filter_row("client_map", e.data.curr_row);
    });
    td5.appendChild(delete_link);

    // add the table data cells to the row
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);
    row.appendChild(td5);

    // add the row to the table
    tbody.appendChild(row);
  }

  // update the filter count
  $("#num_client_map_filters").val(view_ns.num_client_map_filter_rows);
}


/**
 * Called by the form field dropdown onchange handler on the standard View filters table; shows the appropriate
 * operators and values section.
 *
 * @param integer row the row number
 * @param integer field_id the unique field ID
 */
view_ns.change_standard_filter_field = function(row) {
  var field_id = $("#standard_filter_" + row + "_field_id").val();

  // find out if this field is the submission date or not
  var is_date_field = false;
  for (var i=0; i<view_ns.all_form_fields.length; i++) {
    var curr_field_id = view_ns.all_form_fields[i].field_id;
    if (curr_field_id == field_id) {
      is_date_field = view_ns.all_form_fields[i].is_date_field;
    }
  }

  if (is_date_field) {
    $("#standard_filter_" + row + "_operators_div, #standard_filter_" + row + "_values_div").hide();
    $("#standard_filter_" + row + "_operators_dates_div, #standard_filter_" + row + "_values_dates_div").show();
  } else {
    $("#standard_filter_" + row + "_operators_div, #standard_filter_" + row + "_values_div").show();
    $("#standard_filter_" + row + "_operators_dates_div, #standard_filter_" + row + "_values_dates_div").hide();
  }
}


/**
 * This function deletes an individual row. Note that this does NOT re-id all the other fields (e.g.
 * after deleting row 5, row 6 still has an id of row_6) nor does it decrement the filter row
 * counter "view_ns.num_filter_rows". This is done for simplicity. The PHP function that handles the
 * update discards any rows without a FORM specified, so the absent row(s) are not important. The
 * num_filters hidden field (which stores the view_ns.num_filter_rows value) IS important, though -
 * that lets the PHP function know how the MAX rows to loop over. It ignored the empty rows.
 * So again, it's fine that the actual number of rows passed is less.
 *
 * @param string table "standard", "client_map"
 * @param integer row the row number
 */
view_ns.delete_filter_row = function(table, row) {
  // get the current table
  var table_id = null;
  if (table == "standard") {
    table_id = "standard_filters_table";
    row_id_prefix = "standard_";
  } else {
    table_id = "client_map_filters_table";
    row_id_prefix = "client_map_";
  }

  $("#" + row_id_prefix + "row_" + row).remove();

  return false;
}


/**
 * Called whenever the user clicks the "Update View" button. It does some elementary field
 * validation and ensures the data is in a state ready for the PHP.
 *
 * @param the form element
 */
view_ns.process_form = function(f) {
  var rules = [];
  rules.push("required,view_name," + g.messages['validation_no_view_name']);
  rules.push("required,num_submissions_per_page," + g.messages['validation_no_num_submissions_per_page']);
  if (!rsv.validate(f, rules)) {
    return ft.change_inner_tab(1, "edit_view"); // this always returns false
  }

  // select all clients
  ft.select_all("selected_user_ids");

  return true;
}


view_ns.toggle_filter_section = function(section) {
  var section_id = null;
  if (section == "client_map") {
    section_id = "client_map_filters";
  } else {
    section_id = "standard_filters";
  }

  var display_setting = $("#" + section_id).css("display");
  if (display_setting == 'none') {
    $("#" + section_id).show("blind");
  } else {
    $("#" + section_id).hide("blind");
  }

  return false;
}


/**
 * Open the Add Fields dialog after the user clicks on the Add Fields(s) >> link for a specific group.
 */
view_ns.add_fields_dialog = function() {
  view_ns.curr_group = $(this).closest(".sortable_group");

  $("#add_fields_popup .error").addClass("hidden");

  // figure out what fields haven't already been added and add the HTML into the dialog
  var available_field_info = view_ns.get_available_view_fields();
  $("#add_fields_popup_available_fields").html(available_field_info.html);

  var buttons = [{
    text: g.messages["word_close"],
    click: function() {
    view_ns._close_add_fields_dialog(this);
    }
  }];

  if (available_field_info.num_fields > 0) {
    $("#add_fields_popup .two_buttons input").attr("disabled", "");
    buttons.unshift({
      text: g.messages["phrase_add_fields"],
      click: function() {
        var field_ids = [];
        $('#add_fields_popup .adding_field_ids').each(function() {
          if (!this.checked) {
            return;
          }
          field_ids.push(this.value);
        });

        if (!field_ids.length) {
          $("#add_fields_popup .error div").html(g.messages["validation_no_view_fields_selected"]).parent().removeClass("hidden");
        } else {
          view_ns.add_view_fields(field_ids);
          view_ns._close_add_fields_dialog(this);
        }
      }
    });
  } else {
    $("#add_fields_popup .two_buttons input").attr("disabled", "disabled");
  }

  ft.create_dialog({
    dialog:     $("#add_fields_popup"),
    title:      g.messages["phrase_add_fields"],
    min_width:  500,
    min_height: 360,
    buttons:    buttons
  });

  return false;
}


view_ns._close_add_fields_dialog = function(dialog) {
  $("#add_fields_popup_available_fields").html("");
  $(dialog).dialog("close");
}


/**
 * Figures out how many fields are left that aren't currently used in the View. This function
 * return an object with the following keys:
 *
 * @return object {
 *                  "num_fields": the number of rows available
 *                  "html":       the HTML of the rows to insert in the dialog
 *                }
 */
view_ns.get_available_view_fields = function() {
  var selected_field_ids = [];
  $(".inner_tab_content3 .sr_order").each(function() { selected_field_ids.push(parseInt(this.value)); });

  var html = "";
  var num_fields = 0;
  if (selected_field_ids.length < view_ns.all_form_fields.length) {
    html = "<ul>";
    for (var i=0; i<view_ns.all_form_fields.length; i++) {
      if ($.inArray(view_ns.all_form_fields[i].field_id, selected_field_ids) != -1) {
        continue;
      }
      html += "<li><input type=\"checkbox\" class=\"adding_field_ids\" value=\"" + view_ns.all_form_fields[i].field_id + "\" "
            + "id=\"r" + i + "\" /><label for=\"r" + i + "\">" + view_ns.all_form_fields[i].display_name + "</label></li>";
      num_fields++;
    }
    html += "</ul>";
  } else {
    html += "<div class=\"light_grey margin_left_small\">" + g.messages["phrase_all_fields_displayed"] + "</div>";
  }

  return {
    num_fields: num_fields,
    html:       html
  };
}


/**
 * Adds one or more fields to the appropriate View group.
 */
view_ns.add_view_fields = function(field_ids) {
  var rows = [];

  for (var i=0; i<field_ids.length; i++) {
    var field_id   = field_ids[i];
    var field_name = view_ns.get_field_name(field_id);

    // a little irksome. All fields may be editable except Last Modified and Submission ID
    var field_info = {};
    for (var j=0; j<view_ns.all_form_fields.length; j++) {
      if (view_ns.all_form_fields[j].field_id != field_id) {
        continue;
      }
      field_info = view_ns.all_form_fields[j];
    }
    var is_editable = true;
    if ($.inArray(field_info.col_name, ["submission_id", "last_modified_date"]) != -1) {
      is_editable = false;
    }

    var row_html = '<div class="row_group">'
      + '<input type="hidden" value="' + field_id + '" class="sr_order">'
      + '<ul>'
        + '<li class="col0"></li>'
        + '<li class="col1 sort_col"></li>'
        + '<li class="col2">' + field_name + '</li>'
        + '<li class="col3 medium_grey">' + view_ns.field_type_map["ft" + field_info.field_type_id] + '</li>'

    if (is_editable) {
      row_html += '<li class="col4 check_area"><input type="checkbox" checked="checked" class="editable_fields" value="' + field_id + '" name="editable_fields[]" /></li>';
    } else {
      row_html += '<li class="col4"></li>';
    }

    row_html += '<li class="col5 check_area"><input type="checkbox" checked="checked" value="' + field_id + '" name="searchable_fields[]" /></li>'
        + '<li class="col6 colN del"><a onclick="return view_ns.remove_view_field(' + field_id + ')" href="#"></a></li>'
      + '</ul>'
      + '<div class="clear"></div>'
      + '</div>';

    rows.push(sortable_ns.get_sortable_row_markup({row_group: row_html, is_grouped: true}));
  }

  view_ns.curr_group.find(".rows").append(ft.group_nodes(rows));
  sortable_ns.reorder_rows(view_ns.curr_group, false);
}


/**
 * This opens the Add Group dialog window, allowing users to create a new group and assign fields in one
 * go. Note that this does NOT use the default add group functionality defined in sortables.js - we needed
 * more control, hence the customized version.
 */
view_ns.add_field_group = function() {
  var available_field_info = view_ns.get_available_view_fields();
  $("#add_group_popup_available_fields").html(available_field_info.html);
  $(".new_group_name").val("");

  ft.create_dialog({
    dialog:     $("#add_group_popup"),
    title:      $(".add_group_popup_title").val(),
    min_width:  500,
    buttons:    [{
      "text":  g.messages["phrase_create_group"],
      "click": function() {
        // note we use a custom insert_new_group function, defined on the view_ns namespace
        view_ns.insert_new_group({
          group_id:   "NEW" + view_ns.curr_new_group_id,
          group_name: $(".new_group_name").val()
        });
        view_ns.curr_new_group_id++;

        $("#no_view_fields_defined").hide();
        $("#allow_editable_fields_toggle").show();

        // now insert all selected rows
        var field_ids = [];
        $('#add_group_popup .adding_field_ids').each(function() {
          if (!this.checked) {
            return;
          }
          field_ids.push(this.value);
        });
        view_ns.curr_group = $(".sortable_group:last");
        view_ns.add_view_fields(field_ids);

      // we clear the markup because both the add group and fields popup add the same markup with
      // the same IDs for the labels
      $("#add_group_popup_available_fields").html("");
        $(this).dialog("close");
      }
    },
    {
      "text":  g.messages["word_cancel"],
      "click": function() {
      $("#add_group_popup_available_fields").html("");
        $(this).dialog("close");
      }
    }]
  });

  return false;
}


/**
 * Our custom insert_new_group function. Normally you'd use sortable_ns.insert_new_group, but the
 * View field groups contain custom header: i.e. the tabs dropdown.
 */
view_ns.insert_new_group = function(info) {
  var new_group_name = info.group_name;
  var new_group_id   = info.group_id;

  var group_label    = $(".sortable__new_group_name").val();
  var sortable_class = $(".sortable__class").val();

  // for the tab dropdown, see if it's already defined in the page. If so, just copy the contents - otherwise
  // we need to manually construct it [TODO: check this on IE... I think the selected value of the first group's
  // tab will be passed over]
  var tabs_dropdown = $(".tabs_dropdown");
  var tab_options   = "";
  if (tabs_dropdown.length) {
    tab_options = $(".tabs_dropdown:first").html();
  } else {
    var tabs = view_ns.get_tab_dropdown_contents();
    if (tabs.length == 0) {
      tab_options = "<option value=\"\">" + g.messages["validation_no_tabs_defined"] + "</option>";
    } else {
      tab_options = "<optgroup label=\"" + g.messages["phrase_available_tabs"] + "\">";
      for (var i=0; i<tabs.length; i++) {
        tab_options += "<option value=\"" + tabs[i].tab + "\">" + tabs[i].label + "</option>";
      }
      tab_options += "</optgroup>";
    }
  }

  var html = "<div class=\"sortable_group\">\n"
        + "<div class=\"sortable_group_header\">\n"
          + "<div class=\"sort\"></div>\n"
          + "<label>" + group_label + "</label>\n"
          + "<input type=\"text\" name=\"group_name_" + new_group_id + "\" class=\"group_name\" value=\""
            + new_group_name.replace(/"/, "&quot;") + "\" />\n"
          + "<select name=\"group_tab_" + new_group_id + "\" class=\"tabs_dropdown\">"
            + tab_options
          + "</select>"
          + "<div class=\"delete_group\"></div>\n"
          + "<input type=\"hidden\" class=\"group_order\" value=\"" + new_group_id + "\" />\n"
          + "<div class=\"clear\"></div>\n"
        + "</div>\n"
        + "<div class=\"sortable " + sortable_class + "\">\n";

  html += $("#sortable__new_group_header").html();

  html += "<div class=\"clear\"></div>\n"
          + "<ul class=\"rows connected_sortable\">\n"
            + "<li class=\"sortable_row rowN empty_group\"><div class=\"clear\"></div></li>"
          + "</ul>\n"
        + "</div>\n"
        + "<div class=\"clear\"></div>\n";

  if ($("#sortable__new_group_footer").length) {
    html += $("#sortable__new_group_footer").html();
  }

  html += "</div>\n";

  sortable_ns.append_new_sortable_group(html);
}

view_ns.add_fields_select_all = function() {
  $('.adding_field_ids').each(function() {
    this.checked = true;
    $(this).closest("li").addClass("selected_row");
  });
}
view_ns.add_fields_unselect_all = function() {
  $('.adding_field_ids').each(function() {
    this.checked = false;
    $(this).closest("li").removeClass("selected_row");
  });
}


/**
 * We keep the delete View group really simple: just remove it from the DOM. I don't want to
 * pester the user with confirmation requests for *anything* on the Edit View fields page: it should
 * be as speedy as possible.
 *
 * @param node the delete_group link node.
 */
view_ns.delete_field_group = function(el) {
  var sortable_group = $(el).closest(".sortable_group");
  var group_id = sortable_group.find(".group_order").val();
  sortable_group.remove();

  var deleted_groups_str = $("#deleted_groups").val();
  var updated_str = (deleted_groups_str == "") ? group_id : deleted_groups_str + "," + group_id;
  $("#deleted_groups").val(updated_str);

  // if there are no other groups in the page, show the default message
  if ($(".sortable_group").length == 0) {
    $("#no_view_fields_defined").show();
    $("#allow_editable_fields_toggle").hide();
  }
}


view_ns.add_default_values_for_submission = function() {
  var row_html = "<tr><td><select name=\"new_submissions[]\" class=\"new_submission_default_val_fields\">"
      + "<option value=\"\">" + g.messages["phrase_please_select"] + "</option>";
  for (var i=0; i<view_ns.all_form_fields.length; i++) {
    if (view_ns.all_form_fields[i].is_system_field) {
      continue;
  }
    row_html += "<option value=\"" + view_ns.all_form_fields[i].field_id + "\">" + view_ns.all_form_fields[i].display_name + "</option>";
  }
  row_html += "</select></td><td><input type=\"text\" name=\"new_submissions_vals[]\" class=\"new_submission_default_vals\" /></td>"
    + "<td class=\"del\"><a href=\"#\" onclick=\"return view_ns.delete_new_view_submission_vals(this)\"></a></td></tr>";

  $("#new_view_default_submission_vals tbody").append(row_html);
  return false;
}


view_ns.delete_new_view_submission_vals = function(el)
{
  $(el).closest("tr").remove();

  // if there are no more rows, hide the entire section (the first row is the heading)
  if ($("#new_view_default_submission_vals tr").length <= 1) {
    $("#no_new_submission_default_values").removeClass("hidden");
    $("#new_submission_default_values").addClass("hidden");
  }

  return false;
}


/**
 * Helper function to empty the 6 tab label fields (with "tab_label" class).
 */
view_ns.remove_tabs = function() {
  $(".tab_label").val("");
  view_ns.update_tab_dropdowns();
}


/* Submission Listing tab code */


/**
 * Adds a new row to the Submission List tab so that it will appear as a column on the Submission Listing page.
 */
view_ns.add_view_column = function() {
  $("#no_view_columns_defined").hide();
  $("#submission_list").show();

  var row_num = ++view_ns.num_view_columns;
  var row_html = '<div class="row_group">'
    + '<input type="hidden" value="' + row_num + '" class="sr_order">'
    + '<ul>'
      + '<li class="col1 sort_col"></li>'
      + '<li class="col2">'
        + '<select name="field_id_' + row_num + '">'
          + '<option value="">' + g.messages["phrase_please_select"] + '</option>';

  // now add all form fields (even if they're not included in the View)
  for (var j=0; j<view_ns.all_form_fields.length; j++) {
    field_id   = view_ns.all_form_fields[j].field_id;
    field_name = view_ns.all_form_fields[j].display_name;
    row_html += '<option value="' + field_id + '">' + field_name + '</option>';
  }

  row_html += '</select>'
      + '</li>'
      + '<li class="col3 check_area"><input type="checkbox" name="is_sortable_' + row_num + '" checked /></li>'
      + '<li class="col4 light_grey">'
        + '<input type="checkbox" name="auto_size_' + row_num + '" id="auto_size_' + row_num + '" class="auto_size" checked />'
        + '<label for="auto_size_' + row_num + '" class="black">' + g.messages["phrase_auto_size"] + '</label>'
        + ' &#8212; ' + g.messages["word_width_c"] + ' '
        + '<input type="text" name="custom_width_' + row_num + '" class="custom_width" disabled />px'
      + '</li>'
      + '<li class="col5">'
        + '<select name="truncate_' + row_num + '">'
          + '<option value="truncate">' + g.messages["word_yes"] + '</option>'
          + '<option value="no_truncate">' + g.messages["word_no"] + '</option>'
        + '</select>'
      + '</li>'
      + '<li class="col6 colN del"></li>'
    + '</ul>'
    + '<div class="clear"></div>'
    + '</div>';

  var new_row = sortable_ns.get_sortable_row_markup({row_group: row_html, is_grouped: false });

  $("#submission_list").find(".rows").append(new_row);
  sortable_ns.reorder_rows($("#submission_list"), false);

  return false;
}


/**
 * A custom delete View column handler, added to detect if there are any columns left. If not, it shows the sortable
 * section altogether and shows a message telling them to add. It uses the Core delete row function.
 */
view_ns.delete_view_column = function(el) {

  // if there are no other groups in the page, show the default message
  if ($(".submission_list .rows .sortable_row").length == 0) {
    $("#no_view_columns_defined").show();
    $("#submission_list").hide();
  }

  sortable_ns.delete_row("submission_list", el);
}

