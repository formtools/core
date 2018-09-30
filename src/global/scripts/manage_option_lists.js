/**
 * This code is used for managing the Edit Option List pages; most notably, it contains the Smart
 * Fill code for parsing HTML files (and uploading them, if need be).
 */

sf_ns = {}; // Smart Fill namespace... (old naming scheme)
sf_ns.num_rows = null; // set by onload function
sf_ns.tmp_deleted_field_option_rows = [];
sf_ns.manual_file_upload_attempted  = false;
sf_ns.error_dialog = $("<div></div>");
sf_ns.delete_option_list_dialog = $("<div></div>");
sf_ns.delete_group_dialog = $("<div></div>");
sf_ns.num_new_groups = 0; // keeps track of the number of new groups added to the page


/**
 * Deletes an Option List. The second "may_delete" boolean parameter is determined by whether this
 * field option group is used by any fields. If it is, they can't delete it: they need to re-assign the fields
 * to other option groups or change the field types.
 *
 * @param integer list_id
 * @param boolean may_delete
 */
sf_ns.delete_option_list = function(list_id, may_delete) {
  var content = g.messages["confirm_delete_option_list"];
  if (!may_delete) {
    content = "<div class=\"red\">" + g.messages["validation_delete_non_empty_option_list"] + "</div>" + content;
  }

  ft.create_dialog({
  dialog:     sf_ns.delete_option_list_dialog,
    popup_type: "warning",
    title:      g.messages["phrase_please_confirm"],
    content:    content,
    buttons: [{
      "text":  g.messages["word_yes"],
      "click": function() {
        ft.dialog_activity_icon(this, "show");
        window.location = "index.php?delete=" + list_id;
      }
    },
    {
      "text":  g.messages["word_no"],
      "click": function() {
        $(this).dialog("close");
      }
    }]
  });

  return false;
}


/**
 * Called during Smart Fill. It removes the entire content of the page.
 */
sf_ns.delete_all_rows = function() {
  $(".sortable_group").remove();
  sf_ns.num_rows = 0;
}


/**
 * Adds a field option for the currently selected field (dropdown, radio or checkbox).
 */
sf_ns.add_field_options = function(num_rows, target_el) {
  var num_rows = $.trim(num_rows);

  // check num_rows is an integer
  if (num_rows.match(/\D/) || num_rows == 0 || num_rows == "") {
    ft.create_dialog({
      dialog:     sf_ns.error_dialog,
      popup_type: "error",
      title:      g.messages["word_error"],
      content:    g.messages["validation_num_rows_to_add"],
      buttons: [{
        "text":  g.messages["word_okay"],
        "click": function() {
          $(this).dialog("close");
          $("#num_rows_to_add").focus().select();
        }
      }]
    });
    $("#num_rows_to_add").focus().select();
    return false;
  }

  var num_rows = parseInt(num_rows);

  for (var i=0; i<num_rows; i++) {
    var curr_row = ++sf_ns.num_rows;

    var li0 = $("<li class=\"col0\"></li>");
    var li1 = $("<li class=\"col1 sort_col\">" + curr_row + "</li>");
    var li2 = $("<li class=\"col2\"><input type=\"text\" name=\"field_option_value_" + curr_row + "\" /></li>");
    var li3 = $("<li class=\"col3\"><input type=\"text\" name=\"field_option_text_" + curr_row + "\" /></li>");
    var li4 = $("<li class=\"col4 colN del\"></li>");

    var ul = $("<ul></ul>");
    ul.append(li0);
    ul.append(li1);
    ul.append(li2);
    ul.append(li3);
    ul.append(li4);

    var main_div = $("<div class=\"row_group\"><input type=\"hidden\" class=\"sr_order\" value=\"" + curr_row + "\" /></div>");
    main_div.append(ul);
    main_div.append("<div class=\"clear\"></div>");

    $(target_el).append(sortable_ns.get_sortable_row_markup({ row_group: main_div }));
    sortable_ns.reorder_rows(target_el, false);
  }

  // always hide the empty group item
  $(target_el).find(".empty_group").hide();

  return false;
}


/**
 * This attempts to smart fill a single field, based on a form field name and a website page. It
 * assumes the page_ns.scrape_page value has been set ("file_get_contents", "curl" or "redirect") to
 * indicate the method by which the Smart Fill will be used. This is determined automatically by
 * system compatibility.
 */
sf_ns.smart_fill_field = function() {
  var smart_fill_source_form_field = $("#smart_fill_source_form_field").val();
  var smart_fill_source_url        = $("#smart_fill_source_url").val();

  if (smart_fill_source_form_field == "" || smart_fill_source_url == "") {
    ft.display_message("smart_fill_messages", false, g.messages["validation_no_smart_fill_values"]);
    return false;
  }

  if (!ft.is_valid_url(smart_fill_source_url)) {
    ft.display_message("smart_fill_messages", false, g.messages["validation_invalid_url"]);
    return false;
  }

  // find out the appropriate scrape method to extract this field's contents
  sf_ns.log_activity(true);
  $.ajax({
    url:      g.root_url + "/global/code/actions.php",
    data:     { action: "get_js_webpage_parse_method", url: smart_fill_source_url },
    type:     'POST',
    dataType: "json",
    success:  sf_ns.process_js_webpage_parse_method_response,
    error:    ft.error_handler
  });
}


sf_ns.process_js_webpage_parse_method_response = function(data) {

  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  // if their server doesn't support any of the page scraping methods, offer them the option
  // of uploading the file manually
  if (data.scrape_method == "") {
    ft.display_message("smart_fill_messages", false, $("#upload_files_text").html());
    sf_ns.log_activity(false);
  }
  else
  {
    var page_url = g.root_url + "/global/code/actions.php?action=smart_fill&scrape_method=" + data.scrape_method
      + "&url=" + $("#smart_fill_source_url").val();
    $("#hidden_iframe").attr("src", page_url);
  }
}


/**
 * When the contents of the iframe are fully loaded (and, namely, the content is now available to JS), this
 * function is called. It logs the page as loaded. Once all form pages are loaded, it loads all the form
 * field information into memory for analyzing by the Smart Fill function.
 */
sf_ns.log_form_page_as_loaded = function() {
  sf_ns.log_activity(false);
  if (!page_ns.page_initialized) {
    page_ns.page_initialized = true;
    return;
  }

  // try to smart fill the field requested
  var iframe_element = hidden_iframe.window.document;
  var field_name = $("#smart_fill_source_form_field").val();
  var num_forms  = iframe_element.forms.length;

  var form_index = null;
  for (var i=0; i<num_forms; i++) {
    if (iframe_element.forms[i][field_name] || iframe_element.forms[i][field_name + "[]"]) {
      form_index = i;
    }
  }

  if (form_index == null) {
    ft.display_message("smart_fill_messages", false, g.messages["validation_smart_fill_no_field_found"]);
    return;
  }

  // if this was the result of manually uploading a file, be optimistic and tell them it worked (this is
  // overwritten if anything fails)
  if (sf_ns.manual_file_upload_attempted) {
    ft.display_message("smart_fill_messages", true, g.messages["notify_field_options_smart_filled"]);
  }

  sf_ns.delete_all_rows();


  // get our field (checks for myField[] syntax, too)
  if (iframe_element.forms[form_index][field_name]) {
    var field = iframe_element.forms[form_index][field_name];
  } else {
    var field = iframe_element.forms[form_index][field_name + "[]"];
  }

  // if field.type is undefined, it's probably an array
  if (field.type == undefined) {
    field.type = field[0].type;
  }

  // if field.type is still undefined, Smart Fill won't work. Alert the user
  if (field.type == undefined) {
    ft.display_message("smart_fill_messages", false, g.messages["validation_smart_fill_cannot_fill"]);
    return;
  }

  // okay! We're about to add in all the default items. Tot them all up here. We'll add them to the page
  // at the end
  var has_option_lists = false;
  var grouped_fields = [];

  switch (field.type) {
    case "select-one":
    case "select-multiple":
      has_option_lists = ($(field).find("optgroup").length > 0) ? true : false;
      if (has_option_lists) {
        $(field).find("optgroup").each(function() {
          var group_name = $(this).attr("label");
          var fields = [];
          $(this).find("option").each(function() {
            fields.push({ "value": this.value, "label": $(this).html() });
          });
          grouped_fields.push({
            "group_name": group_name,
            "fields":     fields
          });
        });
      } else {
        var fields = [];
        for (var i=0; i<field.length; i++) {
          fields.push({ "value": field[i].value, "label": field[i].text });
        }
        grouped_fields.push({
          "group_name": "",
          "fields":     fields
        });
      }
      break;

    case "checkbox":
    case "radio":
      is_single = true;
      if (field.length != undefined) {
        is_single = false;
      }

      // single checkbox
      if (is_single) {
        grouped_fields.push({
          "group_name": "",
          "fields":     [{ "value": field.value, "label": "" }]
      });

      // multiple checkboxes
      } else {
      var fields = [];
        for (var i=0; i<field.length; i++) {
          fields.push({ "value": field[i].value, "label": "" });
        }
        grouped_fields.push({
          "group_name": "",
          "fields":     fields
      });
      }
      break;

    default:
      ft.display_message("smart_fill_messages", false, g.messages["validation_smart_fill_invalid_field_type"]);
      break;
  }

  if (grouped_fields[0].fields.length == 0) {
    alert("no fields found!"); // ...!
  } else {
    // create the groups
    // *** bit concerned about timing issues here, but haven't encountered problems in FF 3.6 ***
    var row_count = 1;
    for (var i=0; i<grouped_fields.length; i++) {
      sf_ns.num_new_groups++;
      var info = {
        group_id:   "NEW" + sf_ns.num_new_groups,
        group_name: grouped_fields[i].group_name
      }
      sortable_ns.insert_new_group(info);

      // add the right number of blank rows
      sf_ns.add_field_options(grouped_fields[i].fields.length, $(".sortable_group:last .rows"));

      // now add the actual items. This relies on sf_ns.num_rows having being reset to 0 earlier on
      for (var j=0; j<grouped_fields[i].fields.length; j++) {
        $("input[name=field_option_value_" + row_count + "]").val(grouped_fields[i].fields[j].value);
        $("input[name=field_option_text_" + row_count + "]").val(grouped_fields[i].fields[j].label);
        row_count++;
      }
    }

    if (has_option_lists) {
      $(".add_ungrouped_rows").hide();
      $(".sortable_group_header, .sortable_group_footer").removeClass("hidden");
      $(".add_group_section").removeClass("hidden");
      $("#go1").attr("checked", "checked");
    } else {
      $(".add_ungrouped_rows").show();
      $(".sortable_group_header, .sortable_group_footer").addClass("hidden");
      $(".add_group_section").removeClass("hidden");
      $("#go2").attr("checked", "checked");
    }
  }
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


/**
 * This function can be optionally called by the user if the Smart Fill couldn't read the web pages. It
 * uploads copies of the forms to the server.
 */
sf_ns.validate_upload_file = function(f) {
  // check all the fields have been entered
  if (!f["form_page_1"].value) {
    alert(g.messages["validation_smart_fill_no_page"]);
    return false;
  }

  // check it's a .html or .htm file
  if (!f["form_page_1"].value.match(/(\.htm|\.html)$/)) {
    alert(g.messages["validation_upload_html_files_only"]);
    return false;
  }

  // show the processing icon; this is turned off when the custom onload handler is executed for the
  // upload_files_iframe
  sf_ns.log_activity(true);
  sf_ns.manual_file_upload_attempted = true;

  return true;
}


/**
 * Called when submitting the Edit Field Option Group main tab. It just
 * ensures the option list name is valid.
 */
sf_ns.submit_update_field_option_group_page = function() {
  var option_list_field = $("#option_list_name");
  var group_name = $.trim(option_list_field.val());
  if (group_name == "") {
    $("#delete_group_dialog").dialog("close"); // just in case
    ft.create_dialog({
      dialog:     sf_ns.error_dialog,
      popup_type: "error",
      title:      g.messages["word_error"],
      content:    g.messages["validation_no_option_list_name"],
      buttons: [{
        "text":  g.messages["word_okay"],
        "click": function() {
          $(this).dialog("close");
          option_list_field.focus().select();
        }
      }]
    });
    option_list_field.focus().select();
    return false;
  }

  // check to see if the list group name is already taken
  for (var i=0; i<page_ns.option_list_names.length; i++) {
    if (group_name == page_ns.option_list_names[i]) {
      $("#delete_group_dialog").dialog("close"); // just in case
      ft.create_dialog({
        dialog:     sf_ns.error_dialog,
        popup_type: "error",
        title:      g.messages["word_error"],
        content:    g.messages["validation_option_list_name_taken"],
        buttons: [{
          "text":  g.messages["word_okay"],
          "click": function() {
            $(this).dialog("close");
            option_list_field.focus().select();
          }
        }]
      });
      option_list_field.focus().select();
      return false;
    }
  }

  return true;
}


sf_ns.add_group = function() {
  sf_ns.num_new_groups++;
  var info = {
    group_id:   "NEW" + sf_ns.num_new_groups,
    group_name: $(".new_group_name").val()
  }
  sortable_ns.insert_new_group(info);
  $("#add_group_popup").dialog("close");
}


/**
 * For option lists, it's very possible that the user wants to delete an entire option list - including
 * the contents - in one click. The default behaviour of the grouped sortables is to only permit it
 * if the group is empty. This custom delete handler overrides the default behaviour.
 *
 * @param node the delete group icon that was clicked
 */
sf_ns.delete_group = function(el) {
  $(el).closest(".sortable_group").remove();
}

sf_ns.toggle_advanced_settings = function() {
  var display_setting = $("#option_lists_advanced_settings").css("display");
  if (display_setting == "none") {
    $("#option_lists_advanced_settings").slideDown(200);
    $("#smart_fill_source_form_field").focus();
  } else {
    $("#option_lists_advanced_settings").slideUp(200);
  }
  return false;
}
