/**
 * Smart Fill Fields
 * -----------------
 *
 * This function handles the "Smart Filling" of the form fields in the Add Form process. It works
 * by having the form page(s) loaded in hidden iframes in the page, which is then available for parsing.
 * For forms that are off-server, copies are made and created locally for the duration of the
 * Add Form, allowing the JS access to read them.
 */

var sf_ns = {
  is_busy:                       true,
  smart_filled:                  false,
  itemize_form_fields_complete:  false,
  itemized_fields:               [], // temporary storage for the raw data
  fields:                        [], // final location of "clean" data
  current_field_id:              null,
  current_page_id:               "main_field_table",
  all_fields_complete:           false,
  tmp_deleted_field_option_rows: [],
  duplicate_fields:              {},
  log_files_as_uploaded_page_loaded: false,
  manual_file_upload_attempted:      false,
  refresh_page_dialog: $("<div></div>")
}


/**
 * This function smart fills ALL fields in the form - even for multi-page forms. It relies on the form data
 * already having been loaded into sf_ns.itemized_fields. This function cleans up the info and stores the
 * fields info that the user is interested in in the sf_ns.fields object. Each field contains two special
 * properties:
 *      status:      a flag string, indicating where the field setup is at: "found", "not_found", "duplicate_found",
 *                   "resolved"
 *      is_complete: a boolean. This is either set automatically to TRUE if there were no problems & no further
 *                   attention is needed on behalf of the user (e.g. with textboxes), or FALSE. For elements like
 *                   radios, checkboxes & dropdowns, once the user approves the dropdown lists this setting is
 *                   set to TRUE. For problems that can't be resolved, the user still has the option to manually
 *                   set it to TRUE, on the understanding that they can always edit the field later.
 */
sf_ns.smart_fill = function() {
  if (sf_ns.check_all_pages_loaded() && sf_ns.itemize_form_fields_complete) {
    return false;
  }

  sf_ns.log_activity(true);

  // now loop through each and every form field and see if we can identify it in the forms
  var num_not_found_elements = 0;

  for (var i=0; i<page_ns.field_ids.length; i++) {
    var field_id = page_ns.field_ids[i];
    var field_name = $("#field_" + field_id + "_name").val();

    sf_ns.fields["f" + field_id] = {
      field_name:  field_name,
      status:      null,
      is_complete: false
    };

    var found_element_index = null;
    for (var j=0; j<sf_ns.itemized_fields.length; j++) {
      if (sf_ns.itemized_fields[j].element_name == field_name || sf_ns.itemized_fields[j].element_name == field_name + "[]") {
        found_element_index = j;
        break;
      }
    }

    // no field was found with this field name
    if (found_element_index == null) {
      sf_ns.fields["f" + field_id].status = "not_found";
      $("#field_" + field_id + "_action").html("<a href=\"#\" onclick=\"return sf_ns.show_page('not_found', '" + field_id + "')\" class=\"red\">" + g.messages["phrase_not_found"] + "</a>");
      $("#field_" + field_id + "_options").html("");
      num_not_found_elements++;
    } else {
      var curr_field_info = sf_ns.itemized_fields[found_element_index];
      var field_type = curr_field_info.field_type;
      sf_ns.fields["f" + field_id].status = "found";
      sf_ns.fields["f" + field_id].field_type = field_type;

      // display the field type dropdown with whatever custom fields are mapped to this raw field type
      sf_ns.update_field_type(field_id, field_type);

      switch (field_type) {
        case "file":
        case "textbox":
        case "password":
        case "textarea":
          sf_ns.fields["f" + field_id].is_complete = true;
          $("#field_" + field_id + "_action").html("<span class=\"light_grey\">" + g.messages["word_none"] + "</span>");
          $("#field_" + field_id + "_options").html("<span class=\"light_grey\">" + g.messages["word_na"] + "</span>");
          break;

        // note that these aren't marked as complete - the user needs to manally approve the field option
        // display values
        case "radio-buttons":
        case "checkboxes":
          sf_ns.fields["f" + field_id].status = "add_display_values";
          var num_options = curr_field_info.option_list.length;
          sf_ns.fields["f" + field_id].option_list = curr_field_info.option_list;
          $("#field_" + field_id + "_action").html("<a href=\"#\" class=\"orange\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["notify_add_display_values"] + "</span>");
          $("#field_" + field_id + "_options").html("<a href=\"#\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["word_options"] + " (" + num_options + ")</span>");
          break;

        case "multi-select":
        case "select":
          sf_ns.fields["f" + field_id].is_complete = true;
          var num_options = curr_field_info.option_list.length;
          sf_ns.fields["f" + field_id].option_list = curr_field_info.option_list;
          $("#field_" + field_id + "_action").html("<span class=\"light_grey\">" + g.messages["word_none"] + "</span>");
          $("#field_" + field_id + "_options").html("<a href=\"#\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["word_options"] + " (" + num_options + ")</span>");
          break;

        // here, more than one field with this name was found (and it wasn't a series of radio-buttons or checkboxes)
        case "mixed":
          sf_ns.fields["f" + field_id].status = "multiple_fields_found";
          $("#field_" + field_id + "_action").html("<a href=\"#\" onclick=\"return sf_ns.show_page('multiple_fields_found', " + field_id + ")\" class=\"red\">" + g.messages["phrase_multiple_fields_found"] + "</a></span>");
          $("#field_" + field_id + "_options").html("");
          break;
      }
    }
  }

  sf_ns.log_activity(false);

  // disable the Smart Fill button. This is done because if the user is wanting to re-Smart fill the fields,
  // chances are they've tweaked one of their form pages - and in that case, re-Smart filling won't help: the iframe
  // contents need to be refreshed, which requires them to have clicked the "Refresh Page" button.
  $("#smart_fill_button").attr("disabled", true).removeClass("blue").addClass("light_grey");
  sf_ns.smart_filled = true;

  // if there were more than one fields not found, display the appropriate error message
  if (num_not_found_elements > 1) {
    var message = (page_ns.num_pages == 1) ? $("#multiple_fields_not_found_single_page_form").html() : $("#multiple_fields_not_found_multi_page_form").html();
    ft.display_message("ft_message", false, message);
  }

  sf_ns.check_all_fields_complete();
}


/**
 * Called by smart_fill() above. This is called on every field found; it updates the Field Type dropdown
 * to show an appropriate list of field types (those mapped to the raw field type) and updates the Field Size
 * dropdown to show all appropriate field sizes.
 *
 * @param integer field_id
 * @param string field_type
 */
sf_ns.update_field_type = function(field_id, field_type) {
  var found = [];
  $.each(page_ns.raw_field_types, function(raw_field_type, field_types) {
    if (raw_field_type != field_type) {
      return;
    }
    found = field_types;
  });

  // if none were found, this probably means that the user was messing around with the Custom Fields module
  // and deleted the field types. Ignore the row.
  if (found.length == 0) {

  } else if (found.length == 1) {
    var item = found[0];
    html = "<input type=\"hidden\" name=\"field_" + field_id + "_type\" id=\"field_" + field_id + "_type\" value=\"" + item.field_type_id + "\">"
       + item.field_type_name;
    $("#field_" + field_id + "_type_div").removeClass("light_grey").html(html);

    // update the field size column for this field. This assumes that the custom fields module requires the user
    // to select at least one field size
    sf_ns.create_field_size_dropdown(field_id, item.compatible_field_sizes);
  } else {
    var field_type_html = "<select name=\"field_" + field_id + "_type\" id=\"field_" + field_id + "_type\" class=\"multiple_field_types\">";
    for (var i=0; i<found.length; i++) {
      field_type_html += "<option value=\"" + found[i].field_type_id + "\">" + found[i].field_type_name + "</option>";
    }
    field_type_html += "</select>";
    $("#field_" + field_id + "_type_div").html(field_type_html);

    sf_ns.create_field_size_dropdown(field_id, found[0].compatible_field_sizes);
  }
}


sf_ns.create_field_size_dropdown = function(field_id, compatible_field_sizes) {
  var ordered_defaults = ["medium", "small", "tiny"];
  var sizes = compatible_field_sizes.split(",");
  if (sizes.length == 1) {
    var size_html = "<input type=\"hidden\" name=\"field_" + field_id + "_size\" id=\"field_" + field_id + "_size\" value=\"" + sizes[0] + "\">"
       + page_ns.field_sizes[sizes[0]];
    $("#field_" + field_id + "_size_div").removeClass("light_grey").html(size_html);
  } else {
    var default_value = null;
    for (var i=0; i<ordered_defaults.length; i++) {
      for (var j=0; j<sizes.length; j++) {
        if (ordered_defaults[i] == sizes[j]) {
          default_value = sizes[j];
          break;
        }
      }
      if (default_value) {
        break;
      }
    }

    var size_html = "<select name=\"field_" + field_id + "_size\" id=\"field_" + field_id + "_size\">";
    for (var i=0; i<sizes.length; i++) {
      size_html += "<option value=\"" + sizes[i] + "\"" + ((sizes[i] == default_value) ? " selected" : "")
        + ">" + page_ns.field_sizes[sizes[i]] + "</option>";
    }
    size_html += "</select>";
    $("#field_" + field_id + "_size_div").html(size_html);
  }
}


/**
 * Displays any of the JS "pages".
 */
sf_ns.show_page = function(page_id, field_id) {
  // reset the temporary page values, just in case
  sf_ns.tmp_deleted_field_option_rows = [];
  $("#" + sf_ns.current_page_id).fadeOut(400);

  switch (page_id) {
    case "review_field_options":
      sf_ns.log_activity(true); // closed in sf_ns.load_field_options_page
      sf_ns.generate_next_prev_links(field_id,
        ["review_field_options_previous_field_link", "review_field_options_previous_field_link2"],
        ["review_field_options_next_field_link", "review_field_options_next_field_link2"]);

      setTimeout(function() { sf_ns.load_field_options_page(field_id) }, 400);
      break;

    case "multiple_fields_found":
      sf_ns.generate_next_prev_links(field_id,
        ["multiple_fields_found_previous_field_link", "multiple_fields_found_previous_field_link2"],
        ["multiple_fields_found_next_field_link", "multiple_fields_found_next_field_link2"]);

      // if the previous page was ALSO a field option page, load the new page content in a timeout so it
      // doesn't get set before the old page has fully faded out
      setTimeout(function() { sf_ns.load_multiple_fields_found_page(field_id); }, 400);
      break;

    case "not_found":
      sf_ns.generate_next_prev_links(field_id,
        ["not_found_previous_field_link", "not_found_previous_field_link2"],
        ["not_found_next_field_link", "not_found_next_field_link2"]);

      // if the previous page was ALSO a field option page, load the new page content in a timeout so it
      // doesn't get set before the old page has fully faded out
      setTimeout(function() { sf_ns.load_not_found_page(field_id); }, 400);
      break;

    case "main_field_table":
      setTimeout(function() { $("#main_field_table").fadeIn(400); }, 400);
      break;
  }

  sf_ns.current_page_id = page_id;

  return false;
}


/**
 * When the contents of the iframe is fully loaded (and, namely, the content is now available to JS), this
 * function is called. It logs the page as loaded. Once all form pages are loaded, it loads all the form
 * field information into memory for analyzing by the Smart Fill function.
 */
sf_ns.log_form_page_as_loaded = function(page) {
  page_ns["form_" + page + "_loaded"] = true;

  if (sf_ns.check_all_pages_loaded()) {
    sf_ns.log_activity(false);
    $("#smart_fill_button").attr("disabled", false)
    $("#smart_fill_button").addClass("blue");
    $("#smart_fill_button").removeClass("light_grey");
    sf_ns.itemize_form_fields();

    // if a manual file upload was just attempted, inform the user that they can now try to Smart Fill
    // the fields, based on the uploaded files
    if (sf_ns.manual_file_upload_attempted) {
      ft.display_message("ft_message", 1, g.messages["notify_smart_fill_files_uploaded_successfully"]);
    }
  }
}


/**
 * This is called whenever the user explicitly clicks the "Refresh Page" button. It reloads the contents
 * of the iframes and empties the.
 */
sf_ns.refresh_page = function() {
  ft.create_dialog({
    dialog:     sf_ns.refresh_page_dialog,
    title:      g.messages["phrase_please_confirm"],
    popup_type: "warning",
    content:    g.messages["confirm_refresh_page"],
    buttons: [{
      text:  g.messages["word_yes"],
      click: function() {
        sf_ns._confirm_refresh_page();
        $(this).dialog("close");
      }
    },
    {
      text:  g.messages["word_no"],
      click: function() {
        $(this).dialog("close");
      }
    }]
  });
}

sf_ns._confirm_refresh_page = function() {
  for (var i=0; i<page_ns.field_ids.length; i++) {
    var field_id = page_ns.field_ids[i];
    $("#field_" + field_id + "_type").val("");
    $("#field_" + field_id + "_action").html("&#8212;");
    $("#field_" + field_id + "_options").html("&#8212;");
  }

  // reset all the field values
  sf_ns.itemized_fields = [];
  sf_ns.itemize_form_fields_complete = false;
  sf_ns.fields = [];
  sf_ns.current_field_id = null;
  sf_ns.current_page_id = "main_field_table";
  sf_ns.all_fields_complete = false;
  sf_ns.tmp_deleted_field_option_rows = [];

  // reload the iframe contents
  for (var i=1; i<=page_ns.num_pages; i++) {
    page_ns["form_" + i + "_loaded"] = false;
    $("#form_" + i + "_iframe").attr("src", $("#form_" + i + "_iframe").attr("src"));
  }

  $("#smart_fill_button").attr("disabled", false).addClass("blue").removeClass("light_grey");
}


/**
 * This is called whenever starting or ending any potentially lengthy JS operation. It hides/shows the
 * ajax loading icon.
 */
sf_ns.log_activity = function(is_busy) {
  sf_ns.is_busy = is_busy;

  if (is_busy) {
    $("#ajax_activity").show();
    $("#ajax_no_activity").hide();
  } else {
    $("#ajax_activity").hide();
    $("#ajax_no_activity").show();
  }
}


sf_ns.load_multiple_fields_found_page = function(field_id) {
  var field_name = sf_ns.fields["f" + field_id].field_name;
  var field_title = $("#field_" + field_id + "_title").html();
  $("#multiple_fields_found_field_title").html(field_title);
  setTimeout(function() { $("#multiple_fields_found").fadeIn(400); }, 400);

  $("#multiple_fields_found_action_needed").addClass("error");
  var notify_multiple_fields_found = g.messages["notify_multiple_fields_found"].replace(/\{\$field_name\}/, field_name);
  $("#multiple_fields_found_action_needed").html("<div style=\"padding:6px\">" + notify_multiple_fields_found + "</div>");

  // create and populate the table listing the fields found
  var row = $("<tr></tr>");
  var th1 = $("<th width=\"50\"></th>");
  var th2 = $("<th width=\"150\">" + g.messages["phrase_field_type"] + "</th>");
  var th3 = $("<th>" + g.messages["phrase_form_page"] + "</th>");
  var th4 = $("<th>" + g.messages["word_options"] + "</th>");
  var th5 = $("<th width=\"80\">" + g.messages["word_select"].toUpperCase() + "</th>");

  row.append(th1);
  row.append(th2);
  row.append(th3);
  row.append(th4);
  row.append(th5);

  $("#multiple_fields_found_table tbody").html(row);

  // find the duplicate elements for this field
  var field_name_index = null;
  for (var i=0; i<sf_ns.itemized_fields.length; i++) {
    if (sf_ns.itemized_fields[i].element_name == field_name) {
      field_name_index = i;
    }
  }

  for (var i=0; i<sf_ns.itemized_fields[field_name_index].elements.length; i++) {
    var field_info = sf_ns.itemized_fields[field_name_index].elements[i];
    var field_type = field_info.element_type;
    var page_num   = field_info.form_page;
    var row_number = i+1;

    var row = document.createElement("tr");
    var td1 = document.createElement("td");
    td1.setAttribute("width", "50");
    td1.setAttribute("align", "center");
    td1.appendChild(document.createTextNode(row_number));

    var td2 = document.createElement("td");

    var field_type_string = "";
    switch (field_type)
    {
      case "textbox":
        field_type_string = g.messages["word_textbox"];
        break;
      case "password":
        field_type_string = g.messages["word_password"];
        break;
      case "file":
        field_type_string = g.messages["word_file"];
        break;
      case "radio-buttons":
        field_type_string = g.messages["phrase_radio_buttons"];
        break;
      case "checkboxes":
        field_type_string = g.messages["word_checkboxes"];
        break;
      case "textarea":
      field_type_string = g.messages["word_textarea"];
        break;
      case "select":
        field_type_string = g.messages["word_select"];
        break;
      case "multi-select":
        field_type_string = g.messages["phrase_multi_select"];
        break;
    }

    td2.appendChild(document.createTextNode(field_type_string));
    td2.setAttribute("width", "150");
    $(td2).addClass("pad_left_small");

    var td3 = document.createElement("td");
    var a = document.createElement("a");
    a.setAttribute("href", page_ns["form_" + page_num + "_url"]);
    a.setAttribute("target", "_blank");
    a.appendChild(document.createTextNode(g.messages["word_page"] + " " + page_num));
    td3.appendChild(a);

    var td4 = document.createElement("td");
    $(td4).addClass("pad_left_small");

    if (field_info.option_list) {
      td4.appendChild(document.createTextNode(field_info.option_list.toString().truncate(40)));
    } else {
      td4.appendChild(document.createTextNode(g.messages["word_na"]));
    }

    var td5 = document.createElement("td");
    td5.setAttribute("align", "center");
    var a = document.createElement("a");
    a.setAttribute("href", "#");
    $(a).bind("click", { field_id: field_id, info: sf_ns.itemized_fields[field_name_index].elements[i] }, function(e) {
      sf_ns.choose_field(e.data.field_id, e.data.info);
    });

    a.appendChild(document.createTextNode(g.messages["word_select"].toUpperCase()));
    td5.appendChild(a);

    row.appendChild(td1);
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);
    row.appendChild(td5);

    $("#multiple_fields_found_table tbody").append(row);
  }
}


sf_ns.load_not_found_page = function(field_id) {
  sf_ns.current_field_id = field_id;

  var field_title = $("#field_" + field_id + "_title").html();
  var field_name = sf_ns.fields["f" + field_id].field_name;
  $("#not_found_field_type").val("");
  $("#not_found_field_title").html(field_title);
  setTimeout(function() { $("#not_found").fadeIn(400); }, 400);

  $("#not_found_action_needed").removeClass("notify");
  $("#not_found_action_needed").addClass("error");

  var notify_smart_fill_field_not_found = g.messages["notify_smart_fill_field_not_found"].replace(/\{\$field_name\}/, field_name);
  $("#not_found_action_needed").html("<div style=\"padding:6px\">" +  notify_smart_fill_field_not_found + "</div>");
}


/**
 * Called on the Not Found page after the user selects a form type. It saves the value & displays the
 * appropriate message.
 */
sf_ns.choose_field_type = function() {
  var selected_field_type = $("#not_found_field_type").val();
  if (!selected_field_type) {
    alert(g.messages["validation_select_field_type"]);
    return;
  }

  sf_ns.fields["f" + sf_ns.current_field_id].is_complete = true;
  sf_ns.fields["f" + sf_ns.current_field_id].status = "resolved";
  sf_ns.fields["f" + sf_ns.current_field_id].field_type = selected_field_type;

  $("#field_" + sf_ns.current_field_id + "_action").html("<span class=\"medium_grey\">" + g.messages["word_resolved"] + "</span>");
  $("#field_" + sf_ns.current_field_id + "_options").html("");

  $("#not_found_action_needed").removeClass("error");
  $("#not_found_action_needed").addClass("notify");

  if (selected_field_type == "radio-buttons" || selected_field_type == "checkboxes" ||
      selected_field_type == "select" || selected_field_type == "multi-select") {
    $("not_found_action_needed").html("<div style=\"padding:6px;\">" + g.messages["notify_multi_field_updated"] + "</div>");
  } else {
    $("not_found_action_needed").html("<div style=\"padding:6px;\">" + g.messages["notify_field_updated"] + "</div>");
  }

  sf_ns.check_all_fields_complete();
}


/**
 * This can be called on the Not Found page, letting a user skip setting it up for now.
 */
sf_ns.skip_field = function() {
  sf_ns.fields["f" + sf_ns.current_field_id].is_complete = true;
  sf_ns.fields["f" + sf_ns.current_field_id].status = "skipped";

  $("#field_" + sf_ns.current_field_id + "_action").html("<span class=\"medium_grey\">" + g.messages["word_skipped"] + "</span>");
  $("#field_" + sf_ns.current_field_id + "_options").html("");

  $("#not_found_action_needed").removeClass("error").addClass("notify").html("<div style=\"padding:6px;\">" + g.messages["phrase_field_skipped"] + "</div>");

  sf_ns.check_all_fields_complete();
}


/**
 * This is called by the user after clicking on one of the SELECT links on the "Multiple fields found" page.
 * It updates the info stored in memory and displays the appropriate message on the page.
 */
sf_ns.choose_field = function(field_id, itemized_field_info) {
  sf_ns.fields["f" + field_id].field_type = itemized_field_info.element_type;
  $("#field_" + field_id + "_type").val(itemized_field_info.element_type);

  sf_ns.fields["f" + field_id].status = "resolved";
  sf_ns.fields["f" + field_id].is_complete = true;
  $("#field_" + field_id + "_action").html("<span class=\"light_grey\">" + g.messages["word_none"] + "</span>");

  // if this chosen field has an option list, copy that over too
  if (itemized_field_info.option_list) {
    sf_ns.fields["f" + field_id].option_list = itemized_field_info.option_list;
    var num_options = sf_ns.fields["f" + field_id].option_list.length;
    $("#field_" + field_id + "_options").html("<a href=\"#\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["word_options"] + " (" + num_options + ")</span>");
  } else {
    $("#field_" + field_id + "_options").html("<span class=\"light_grey\">" + g.messages["word_na"] + "</span>");
  }

  $("#multiple_fields_found_action_needed").removeClass("error");
  $("#multiple_fields_found_action_needed").addClass("notify");

  if (itemized_field_info.option_list) {
    var notify_multi_field_selected = g.messages["notify_multi_field_selected"].replace(/\{\$onclick\}/, "return sf_ns.show_page('review_field_options', '" + field_id + "')");
    $("#multiple_fields_found_action_needed").html("<div style=\"padding:6px\">" + notify_multi_field_selected + "</div>");
  } else{
    var notify_field_selected = g.messages["notify_field_selected"].replace(/\{\$onclick\}/, "return sf_ns.show_page('main_field_table', null)");
    $("#multiple_fields_found_action_needed").html("<div style=\"padding:6px\">" + notify_field_selected + "</div>");
  }

  sf_ns.check_all_fields_complete();
}


/**
 * This populates the Field Option page. This page is used for radio buttons, checkboxes, select and
 * multi-select elements to display the option list and notify the user of any actions that need to be
 * performed on it.
 *
 * This was updated a little in 2.1.0 to use a sortable list and to drop the orientation requirement, but
 * it still doesn't support optgroups.
 *
 * @param integer the unique field ID
 */
sf_ns.load_field_options_page = function(field_id) {
  sf_ns.current_field_id = field_id;

  // display the appropriate "action needed" message for this item (there can be only one)
  sf_ns.display_action_needed_message(field_id, "review_field_options_action_needed");

  var field_info = sf_ns.fields["f" + field_id];
  var field_type = sf_ns.fields["f" + field_id].field_type;
  var field_title = $("#field_" + field_id + "_title").html();
  $("#review_field_options_field_title").html(field_title);

  // clear our any options for previous fields
  $(".review_field_options .rows").html("");

  // now add the custom options
  var num_fields = sf_ns.fields["f" + field_id].option_list.length;
  for (var i=0; i<num_fields; i++) {
    var option_info = sf_ns.fields["f" + field_id].option_list[i];
    sf_ns.add_field_option({
      default_value: option_info[0],
      default_text:  option_info[1],
      reorder: false
    });
  }

  // only bother ordering the rows after adding them all
  sortable_ns.reorder_rows($(".review_field_options"));

  // when the last row that has been inserted is now accessible by JS, that means it's visible in the page
  $("#review_field_options").fadeIn(400);
  sf_ns.log_activity(false);
}


sf_ns.set_display_values_from_field_values = function() {
  $(".review_field_options .sortable_row").each(function() {
  var curr_display_value = $(this).find(".field_option_value").val();
  $(this).find(".field_option_text").val(curr_display_value);
  });
}


/**
 * This is called on all the pages that focus on an individual field. It generates an appropriate
 * message, informing the user of the status of the field.
 */
sf_ns.display_action_needed_message = function(field_id, target_id) {
  var field_info = sf_ns.fields["f" + field_id];

  if (field_info.is_complete) {
    $("#" + target_id).removeClass("error notify").html("<span class=\"light_grey\">" + g.messages["word_none"] + "</span>");
  } else {
    switch (field_info.status) {
      case "add_display_values":
        $("#" + target_id).addClass("error margin_bottom_large").html("<div style=\"padding:6px\">" + g.messages["notify_add_display_values"] + "</div>");
        break;
    }
  }
}


/**
 * Confirms that all pages are loaded.
 */
sf_ns.check_all_pages_loaded = function() {
  var all_pages_loaded = true;
  for (var i=1; i<=page_ns.num_pages; i++) {
    if (!page_ns["form_" + i + "_loaded"]) {
      all_pages_loaded = false;
    }
  }

  return all_pages_loaded;
}


/**
 * Our storage function. This examines all the forms and itemizes all the form fields for examination
 * by the Smart Fill function. The field data is organized in the sf_ns.itemized_fields array.
 *
 * Each index is an object containing information about the field. It has the following properties:
 *     info.element_name: the name of the form field. Note: for groups of checkboxes using the same
 *                        name and multi-select fields, this value contains the [] suffix (which is
 *                        NOT stored in the FT database - it gets dropped in transit to the server).
 *     info.elements:     an array of all elements with this name attribute. Each element has the
 *                        following info stored:
 *         element_type:  "textbox", "file", "password", "radio-buttons", "checkboxes",
 *                        "multi-select", "select"
 *
 *         option_list:   this is filled with option lists for multi-select options. The content is
 *                        slightly different based on the type.
 *                        1. checkbox groups / radio buttons
 *                        - an array of values (since we won't hazard a guess at the (possibly
 *                        non-existent) label for these field types.
 *
 *                        2. dropdowns / multi-select dropdowns
 *                        - an array of objects. For <option>'s, the object
 *                            { value: X , display_text: Y }
 *                          For optgroups, the object will contain a single property:
 *                            { group_name: X }
 *
 * After locating & processing the fields, it also sets two extra (seemingly duplicate) properties:
 *
 *     info.element_type: This is set to the same type as the info.elements[X].element_type iff (if
 *                        and only if) all the fields are the same. Otherwise it's set to "mixed".
 *
 *     info.option_list:  again, if all the fields are of the same type and either radios, checkboxes
 *                        or a SINGLE select or multi-select field, this contains the (single) group
 *                        of option lists.
 *
 * Once it has finished processing, it registers complete by setting:
 *   sf_ns.itemize_form_fields_complete = true;
 */
sf_ns.itemize_form_fields = function() {

  // process the forms, logging information about all the form fields.
  for (var form_num=1; form_num<=page_ns.num_pages; form_num++) {
    var form_iframe = window["form_" + form_num + "_iframe"].window.document;
    var num_forms  = form_iframe.forms.length;

    for (var j=0; j<num_forms; j++) {
      // loop through every element in this form
      var elements = sf_ns.get_form_elements(form_iframe.forms[j]);

//      console.log(elements);

      for (var k=0; k<elements.length; k++) {
        var el = elements[k];

        // if this element doesn't have a name, skip it - we're not interested in it
        if (!el.name) {
          continue;
        }

        var info = {};
        var element_name = el.name;
        info.element_type = null;
        info.form_page = form_num;

        // see if a field with this name has already been added
        var existing_field_name_index = null;
        for (var p=0; p<sf_ns.itemized_fields.length; p++) {
          if (sf_ns.itemized_fields[p].element_name == element_name) {
            existing_field_name_index = p;
            break;
          }
        }

        switch (el.tagName) {
          case "INPUT":
            switch (el.type) {
              case "text":
                info.element_type = "textbox";
                break;
              case "file":
                info.element_type = "file";
                break;
              case "password":
                info.element_type = "password";
                break;

              case "radio":
                info.element_type = "radio-buttons";
                info.option_list = [];
                if (form_iframe.forms[j][el.name].length != undefined) {
                  var num_options = form_iframe.forms[j][el.name].length;
                  for (var n=0; n<num_options; n++) {
                    info.option_list.push([form_iframe.forms[j][el.name][n].value, ""]);
                  }
                } else {
                  info.option_list.push([form_iframe.forms[j][el.name].value, ""]);
                }
                break;

              case "checkbox":
                info.element_type = "checkboxes";
                info.option_list = [];
                if (form_iframe.forms[j][el.name].length != undefined) {
                  var num_options = form_iframe.forms[j][el.name].length;
                  for (var n=0; n<num_options; n++) {
                    info.option_list.push([form_iframe.forms[j][el.name][n].value, ""]);
                  }
                } else {
                  info.option_list.push([form_iframe.forms[j][el.name].value, ""]);
                }
                break;

              // explicitly ignore buttons and reset fields
              //case "image":
              case "button":
              case "reset":
                break;

              // just interpret other input types as a plain textbox
              default:
                info.element_type = "textbox";
                break;
            }
            break;

          case "SELECT":
            if (el.multiple) {
              info.element_type = "multi-select";
            } else {
              info.element_type = "select";
            }
            var num_options = el.options.length;
            info.option_list = [];
            for (var n=0; n<num_options; n++) {
              var val = el.options[n].value;
              var txt = el.options[n].text;
              info.option_list.push([val, txt]);
            }
            break;

          case "TEXTAREA":
            info.element_type = "textarea";
            break;
        }

        if (info.element_type != null) {
          if (existing_field_name_index == null) {
            var field_info = {
              element_name: element_name,
              elements: [info]
            }
            sf_ns.itemized_fields.push(field_info);
          } else {
            // if this isn't a radio or checkbox (which have already been added), add it to the end of the list
            if (info.element_type != "radio-buttons" && info.element_type != "checkboxes") {
              sf_ns.itemized_fields[existing_field_name_index].elements.push(info);
            }
          }
        }
      }
    }
  }

  for (var i=0; i<sf_ns.itemized_fields.length; i++) {
    // find out the field type(s) that have this field name

    // case 1: there's just a single field
    if (sf_ns.itemized_fields[i].elements.length == 1) {
      sf_ns.itemized_fields[i].field_type = sf_ns.itemized_fields[i].elements[0].element_type;
      sf_ns.itemized_fields[i].option_list = sf_ns.itemized_fields[i].elements[0].option_list;
    }

    // case 2: there were multiple fields with this field name. This should only happen for radio buttons
    // and checkboxes, but it's possible the user incorrectly entered the same field name more than once
    // for other field types. This section examines the fields and makes a note of potential problems
    else {
      var full_option_list = [];
      var all_field_types = [];
      for (var j=0; j<sf_ns.itemized_fields[i].elements.length; j++) {
        all_field_types.push(sf_ns.itemized_fields[i].elements[j].element_type);
        full_option_list.push(sf_ns.itemized_fields[i].elements[j].option_list);
      }

      all_unique_field_types = $.unique(all_field_types);

      // if there were more than one field type with this name, make a note of the error
      if (all_unique_field_types.length > 1) {
        sf_ns.itemized_fields[i].field_type = "mixed";
      }

      // if this field type wasn't a set of radios or checkboxes, make a note of the error
      else if (all_unique_field_types[0] != "radio-buttons" && all_unique_field_types[0] != "checkboxes") {
        sf_ns.itemized_fields[i].field_type = "mixed";
      }

      // otherwise all is well!
      else {
        sf_ns.itemized_fields[i].field_type = all_unique_field_types[0];
        sf_ns.itemized_fields[i].option_list = full_option_list;
      }
    }
  }
}


sf_ns.get_form_elements = function(f) {
  var form_elements = [];
  for (var i=0; i<f.elements.length; i++) {
    form_elements.push(f.elements[i]);
  }

  return form_elements;
}


/**
 * Adds a field option for the currently selected field (dropdown, radio or checkbox).
 */
sf_ns.add_field_option = function(data) {

  var data = $.extend({
    default_value: "",
    default_text: "",
    reorder: true
  }, data);

  var num_rows = sf_ns.fields["f" + sf_ns.current_field_id].option_list.length;
  var next_id = num_rows;

  // generate the field option table
  var row_html = "<li class=\"sortable_row\">"
        + "<div class=\"row_content\">"
          + "<div class=\"row_group\">"
            + "<input type=\"hidden\" class=\"sr_order\" value=\"" + next_id + "\" />"
            + "<ul>"
              + "<li class=\"col1 sort_col\"></li>"
              + "<li class=\"col2\"><input type=\"text\" name=\"field_option_value_" + next_id + "\" value=\"" + data.default_value + "\" class=\"field_option_value\" /></li>"
              + "<li class=\"col3\"><input type=\"text\" name=\"field_option_text_" + next_id + "\" value=\"" + data.default_text + "\" class=\"field_option_text\" /></li>"
              + "<li class=\"col4 colN del\"></li>"
            + "</ul>"
            + "<div class=\"clear\"></div>"
          + "</div>"
        + "</div>"
        + "<div class=\"clear\"></div>"
        + "</li>";

  // now a null placeholder entry to the end of the field option list. It's not used, and will be
  // automatically overwritten when the user approves the field or leaves the page (why?!)
  //sf_ns.fields["f" + sf_ns.current_field_id].option_list.push(null);

  $(".review_field_options .rows").append(row_html);

  if (data.reorder) {
    sortable_ns.reorder_rows($(".review_field_options"));
  }
}


/**
 * This is called when the user explicitly updates a field.
 */
sf_ns.update_field = function(message_target_id) {

  // mark this field as approved and update the various page elements + data structures
  sf_ns.fields["f" + sf_ns.current_field_id].is_complete = true;
  sf_ns.display_action_needed_message(sf_ns.current_field_id, message_target_id);
  sf_ns._update_current_field_settings();

  // marked as skipped
  if (sf_ns.fields["f" + sf_ns.current_field_id].status == "skipped") {
    sf_ns.fields["f" + sf_ns.current_field_id].status = "found";
    $("#field_" + sf_ns.current_field_id + "_action").html("<span class=\"light_grey\">" + g.messages["word_none"] + "</span>");
  }

  $("#" + message_target_id).removeClass("error notify").html("<div class=\"notify\"><div style=\"padding:6px;\">" + g.messages["notify_field_updated"] + "</div></div>");
  sf_ns.check_all_fields_complete();
}


/**
 * This is called after a field is approved or marked as "will resolve later" (or something). It checks to
 * see if all the fields have been marked as complete. If so, enables the "next step >>" link.
 */
sf_ns.check_all_fields_complete = function() {
  var all_fields_complete = true;
  for (var i=0; i<page_ns.field_ids.length; i++) {
    var field_id = page_ns.field_ids[i];
    if (!sf_ns.fields["f" + field_id].is_complete) {
      all_fields_complete = false;
    }
  }

  sf_ns.all_fields_complete = all_fields_complete;

  if (all_fields_complete) {
    $("#next_step").removeClass("light_grey").addClass("next_step").attr("disabled", false);
  }
}


/**
 * This function is called whenever the user LEAVES a fields options page. It saves the current
 * values for the field - options and the field orientation.
 */
sf_ns._update_current_field_settings = function() {
  var new_option_list = [];

  $(".review_field_options .sortable_row").each(function() {
  var curr_display_value = $(this).find(".field_option_value").val();
  var curr_display_text = $(this).find(".field_option_text").val();
    new_option_list.push([curr_display_value, curr_display_text]);
  });

  sf_ns.fields["f" + sf_ns.current_field_id].option_list = new_option_list;
  sf_ns._update_field_main_table_settings(sf_ns.current_field_id);
}


// update the columns on the main table.
sf_ns._update_field_main_table_settings = function(field_id) {
  if (sf_ns.fields["f" + field_id].status == "skipped") {
    $("#field_" + field_id + "_action").html("<span class=\"medium_grey\">" + g.messages["word_skipped"] + "</span>");
  } else {
    $("#field_" + field_id + "_action").html("<span class=\"light_grey\">" + g.messages["word_none"] + "</span>");
  }

  var num_options = sf_ns.fields["f" + field_id].option_list.length;
  $("#field_" + field_id + "_options").html("<a href=\"#\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["word_options"] + " (" + num_options + ")</span>");
}


/**
 * This function generates and inserts the << previous field + next field >> links at the appropriate
 * spots.
 *
 * @param field_id the current field ID
 * @param prev_link_id an array of IDs of the page element(s) where the previous links should be inserted
 * @param next_link_id an array of IDs of the page element(s) where the next link should be inserted
 */
sf_ns.generate_next_prev_links = function(field_id, prev_link_ids, next_link_ids) {
  var links = sf_ns.get_next_prev_field_ids(field_id);
  previous_field_id = links[0];
  next_field_id = links[1];

  // PREVIOUS link
  var prev_link_html = "";
  if (previous_field_id == null) {
    prev_link_html = "<span class=\"light_grey\">" + g.messages["phrase_previous_field"] + "</span>";
  } else {
    var previous_field_type = sf_ns.fields["f" + previous_field_id].field_type;

    var page = "review_field_options";
    if (previous_field_type == "mixed") {
      page = "multiple_fields_found";
    }
    if (sf_ns.fields["f" + previous_field_id].status == "not_found") {
      page = "not_found";
    }
    prev_link_html = "<a href=\"#\" onclick=\"return sf_ns.show_page('" + page + "', " + previous_field_id + ")\">" + g.messages["phrase_previous_field"] + "</a>";
  }

  for (var i=0; i<prev_link_ids.length; i++) {
    $("#" + prev_link_ids[i]).html(prev_link_html);
  }

  var next_link_html = "";
  if (next_field_id == null) {
    next_link_html = "<span class=\"light_grey\">" + g.messages["phrase_next_field"] + "</span>";
  } else {
    var next_field_type = sf_ns.fields["f" + next_field_id].field_type;
    var page = "review_field_options";
    if (next_field_type == "mixed") {
      page = "multiple_fields_found";
    }
    if (sf_ns.fields["f" + next_field_id].status == "not_found") {
      page = "not_found";
    }
    next_link_html = "<a href=\"#\" onclick=\"return sf_ns.show_page('" + page + "', " + next_field_id + ")\">" + g.messages["phrase_next_field"] + "</a>";
  }

  for (var i=0; i<next_link_ids.length; i++) {
    $("#" + next_link_ids[i]).html(next_link_html);
  }
}


/**
 * Figures out what IDs should be linked to in the << previous and next >> links. This is "smart" in that
 * it only links to fields that need the attention of the user.
 */
sf_ns.get_next_prev_field_ids = function(field_id) {
  var previous_field_id = null;
  var next_field_id     = null;

  var field_index = null;
  for (var i=0; i<page_ns.field_ids.length; i++) {
    if (page_ns.field_ids[i] == field_id) {
      field_index = i;
    }
  }

  if (field_index > 0) {
    for (var i=field_index-1; i>=0; i--) {
      var curr_field_id = page_ns.field_ids[i];
      if (!sf_ns.fields["f" + curr_field_id].is_complete) {
        previous_field_id = curr_field_id;
        break;
      }
    }
  }

  if (field_index < page_ns.field_ids.length-1) {
    for (var i=field_index+1; i<page_ns.field_ids.length; i++) {
      var curr_field_id = page_ns.field_ids[i];
      if (!sf_ns.fields["f" + curr_field_id].is_complete) {
        next_field_id = curr_field_id;
        break;
      }
    }
  }

  return [previous_field_id, next_field_id];
}


/**
 * This function overrides any problems that arose and/or fields that haven't had their action
 * resolved.
 */
sf_ns.skip_step = function() {
  // loop through all incomplete fields and mark them as complete
  for (var i=0; i<page_ns.field_ids.length; i++) {
    var field_id = page_ns.field_ids[i];

    if (!sf_ns.smart_filled) {
      sf_ns.fields["f" + field_id] = { status: "skipped" }
      $("#field_" + field_id + "_action").html("<span class=\"medium_grey\">" + g.messages["word_skipped"] + "</span>");
      sf_ns.fields["f" + field_id].is_complete = true;
    } else if (!sf_ns.fields["f" + field_id].is_complete) {
      sf_ns.fields["f" + field_id].status = "skipped";
      $("#field_" + field_id + "_action").html("<span class=\"medium_grey\">" + g.messages["word_skipped"] + "</span>");
      sf_ns.fields["f" + field_id].is_complete = true;
    }
  }

  sf_ns.check_all_fields_complete();
}


/**
 * This function can be optionally called by the user if the Smart Fill couldn't read the web pages. It
 * uploads copies of the forms to the server. The
 */
sf_ns.validate_upload_files = function(f) {
  var error = null;
  for (var i=1; i<=page_ns.num_pages; i++) {
    // check all the fields have been entered
    if (!f["form_page_" + i].value) {
      error = g.messages["validation_smart_fill_upload_all_pages"];
      break;
    }
    // check it has a .html or .htm file
    if (!f["form_page_" + i].value.match(/(\.htm|\.html)$/)) {
      error = g.messages["validation_upload_html_files_only"];
      break;
    }
  }

  if (error != null) {
    ft.create_dialog({
      title:      g.messages["word_error"],
      popup_type: "warning",
      content:    error,
      buttons:    [
       {
         text: g.messages["word_okay"],
         click: function() {
           $(this).dialog("close")
         }
       }
      ]
    });
    return false;
  }
  // show the processing icon; this is turned off when the custom onload handler is executed for the
  // upload_files_iframe
  sf_ns.log_activity(true);

  return true;
}


/**
 * This is called when the files have been uploaded to the server to be Smart Filled. If all
 * went well, the iframe should now contain a JSON string listing the files (and paths).
 */
sf_ns.log_files_as_uploaded = function() {
  // this prevents anything being (unnecessarily) fired on the initial page load
  if (!sf_ns.log_files_as_uploaded_page_loaded) {
    sf_ns.log_files_as_uploaded_page_loaded = true;
    return;
  }

  sf_ns.log_activity(false);

  var response = $("#upload_files_iframe")[0].contentWindow.document.body.innerHTML;
  try {
    var info = eval("(" + response + ")");

    if (info.success) {
      // so far so good! Here, the files have been uploaded to the server and should be accessible
      // to the JS. Get the file URLs from response and load the iframes. When that's done, a message
      // is displayed to the user telling them to try Smart Filling now. Since the
      sf_ns.manual_file_upload_attempted = true;
      for (var i=1; i<=page_ns.num_pages; i++) {
        page_ns["form_" + i + "_loaded"] = false;
        $("#form_" + i + "_iframe").attr("src", info["url_" + i]);
      }
    } else {
      ft.display_message("ft_message", false, info.message);
    }
  }
  // should never occur, but...
  catch (err) {
    ft.display_message("ft_message", false, g.messages["notify_smart_fill_upload_fields_fail"]);
  }
}


/**
 * Helper function to determine if a field type has been mapped to an option list. This is a little kludgy...
 */
sf_ns.is_option_list_field = function(field_type_id) {

  var is_option_list_field = false;
  $.each(page_ns.raw_field_types, function(key, info) {
    for (var i=0; i<info.length; i++) {
      if (parseInt(info[i].field_type_id) != parseInt(field_type_id)) {
        continue;
      }

      if (info[i].raw_field_type_map_multi_select_id != "") {
      is_option_list_field = true;
      return false;
      }
    }
  });

  return is_option_list_field;
}


/**
 * This function handles the all-important form submit. It sends all the form data to the server via
 * an Ajax call, and redirects to the final page when complete.
 */
sf_ns.submit_form = function() {
  var params = {
    action: "process_smart_fill_contents"
  };

  for (var i=0; i<page_ns.field_ids.length; i++) {
    var field_id   = page_ns.field_ids[i];
    var field_info    = sf_ns.fields["f" + field_id];
    var field_type_id = $("#field_" + field_id + "_type").val();
    params["field_" + field_id + "_type"] = field_type_id;
    params["field_" + field_id + "_size"] = $("#field_" + field_id + "_size").val();

    if (sf_ns.is_option_list_field(field_type_id)) {
      var num_options = field_info.option_list.length;
      params["field_" + field_id + "_num_options"] = num_options;
      for (var j=0; j<num_options; j++) {
        var row_num = j+1;
        params["field_" + field_id + "_opt" + row_num + "_val"] = field_info.option_list[j][0];
        params["field_" + field_id + "_opt" + row_num + "_txt"] = field_info.option_list[j][1];
      }
    }
  }

  // display the "please be patient" message
  ft.display_message("next_step_message", 1, g.messages["notify_smart_fill_submitted"]);
  $("#next_step").attr("disabled", true);
  $("#ajax_activity_bottom").show();
  $("#ajax_no_activity_bottom").hide();

  $.ajax({
    url: g.root_url + "/global/code/actions.php",
    type:     "POST",
    dataType: "json",
    data:     params,
    success:  sf_ns.submit_form_response,
    error:    ft.error_handler
  });
}


sf_ns.submit_form_response = function(info) {
  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(info)) {
    return;
  }

  try {
    if (info.success == 1) {
      window.location = "step6.php";
    } else {
      // the most likely reason we'd get here is with very large forms & the SQL query to create the table failed
      $("#next_step").attr("disabled", false);
      $("#ajax_activity_bottom").hide();
      $("#ajax_no_activity_bottom").show();

      var message = info.message;
      if (info.sql_error) {
        message += "<br /><br />SQL Error: <b>" + info.sql_error + "</b>";
      }
      ft.display_message("next_step_message", false, message);
    }
  } catch (err) {
    ft.display_message("next_step_message", false, info.responseText);
  }
}
