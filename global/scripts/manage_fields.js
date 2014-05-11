$(function() {
  var scroll_panes   = $(".scroll-pane");
  var scroll_content = $(".scroll-content");

  // update slider to slide all elements with .scroll-content class currently on page
  fields_ns.init_sliders({
    scroll_panes:   scroll_panes,
    scroll_content: scroll_content
  });

  // append icon to handles
  var handleHelper = fields_ns.scrollbars.find(".ui-slider-handle").mousedown(function() {
    fields_ns.scrollbars.width(handleHelper.width());
  })
  .mouseup(function() {
    fields_ns.scrollbars.width("100%");
  })
  .append("<span class='ui-icon ui-icon-grip-dotted-vertical'></span>").wrap("<div class='ui-handle-helper-parent'></div>").parent();

  // change overflow to hidden now that sliders handles the scrolling
  scroll_panes.css("overflow", "hidden");

  // size scrollbars and handle proportionally to scroll distance
  function size_scrollbars() {
    var remainder   = scroll_content.width() - scroll_panes.width();
    var proportion  = remainder / scroll_content.width();
    var handle_size = scroll_panes.width() - (proportion * scroll_panes.width());
    fields_ns.scrollbars.find(".ui-slider-handle").css({
      width:         handle_size,
      "margin-left": -handle_size / 2
    });
    handleHelper.width("").width(fields_ns.scrollbars.width() - handle_size);
  }

  // init scrollbar size
  setTimeout(size_scrollbars, 10); // safari wants a timeout

  $("#display_form").bind("submit", function(e) {
  var rules = [];
  rules.push("function,fields_ns.check_fields");
  fields_ns.__disable_focusin = true;
    if (!rsv.validate(this, rules)) {
      e.preventDefault();
      fields_ns.__disable_focusin = false;
    }
  });

  // add some event-delegated logic to only bother updating the fields dropdown with the latest content
  // after a field title actually changes
  $(".rows").bind("focusin", function(e) {
    if (fields_ns.__disable_focusin) {
      return;
    }
    var currentField = e.target;
    if ($(currentField).hasClass("display_text")) {
      fields_ns.__onfocus_display_text_value = $(currentField).val();
    }
    var parent_scrollable = $(currentField).closest(".scrollable");
    if (parent_scrollable.hasClass("col3")) {
      var scrollLeft = parent_scrollable.attr("scrollLeft");
      var marginLeft = parseInt($(currentField).closest(".scroll-content").css("marginLeft"));
      if (scrollLeft != 0) {
        var left_percentage = (marginLeft <= 0 && marginLeft > -238) ? 50 : 100;
        fields_ns.change_scroll_area(left_percentage);
      }
    }
  });

  $(".rows").bind("focusout", function(e) {
    if (!$(e.target).hasClass("display_text")) {
      return;
    }
    if ($(e.target).val() != fields_ns.__onfocus_display_text_value) {
      fields_ns.update_fields_dropdown();
    }
  });

  $(".rows").live("click", function(e) {
    if ($(e.target).hasClass("edit")) {
      fields_ns.edit_field($(e.target).closest(".row_group"));
    }
  });

  $("#edit_field_template .prev_field").bind("click", function(e) { fields_ns.edit_prev_field(); });
  $("#edit_field_template .next_field").bind("click", function(e) { fields_ns.edit_next_field(); });

  // this handles all the "Use Default Value?" checkboxes. It enables/disables the custom setting for the row
  // and places focus on the customizable option, if Use Default Value is disabled
  $(".use_default").live("click", function() {
    fields_ns.click_use_default(this);
  });

  // called whenever the user changes a field type in the Edit Field popup
  $("#edit_field__field_type").live("change keyup", function() {
    ft.update_field_size_dropdown(this, $("#edit_field__field_size_div"), {
      id:       "edit_field__field_size",
      name:     "edit_field__field_size",
      selected: $('#edit_field__field_size').val()
    });

    var field_type_id = $(this).val();
    var field_settings = page_ns.field_settings["field_type_" + field_type_id];
    var field_type_label = $("#edit_field__field_type option[value=" + field_type_id + "]").text();
    if (field_settings == undefined) {
      $("#edit_field_template .inner_tab2").html(field_type_label + " " + g.messages["word_settings"] + " (0)");
      $("#edit_field__field_settings").html("<div class=\"notify\"><div style=\"padding:6px\">" + g.messages["notify_no_field_settings"] + "</div></div>");
    } else {
      $("#edit_field_template .inner_tab2").html(field_type_label + " " + g.messages["word_settings"] + " (" + field_settings.length + ")");
      var html = fields_ns.generate_field_type_markup(fields_ns.__current_field_id, field_type_id, field_settings);
      $("#edit_field__field_settings").html(html);
    }
    $("#edit_field__field_settings_loading").hide();
    $(".use_default").each(function() {
      $(this).attr("disabled", "");
    });

    // recreate the validation tab
    var html = fields_ns.generate_field_type_validation_table(field_type_id);
    fields_ns.update_validation_tab_label(field_type_id);
    $("#validation_table").html(html);
  });

  $("#edit_field__field_type").live("blur", function() {
    fields_ns.__last_field_type_id    = fields_ns.__current_field_type_id;
    fields_ns.__current_field_type_id = this.value;
    fields_ns.__check_shared_characteristics_cond1 = true;
    fields_ns.check_shared_characteristics();

    // here we empty the memory cache for tab 2. This ensures that when the user clicks Save Changes, any orphaned
    // field settings aren't incorrectly passed along to the server
    delete fields_ns.memory["field_" + fields_ns.__current_field_id].tab2;
  });


  // any time the user changes a value in any of the field in the Edit Field dialog, it stores the changes in
  // fields_ns.memory. When they click Save Changes all changes are sent to the server for updating. This
  // was simplified in 2.1.4 so that any change ANYWHERE, resubmits everything. It was getting absurdly complicated.
  $(".inner_tab_content").bind("change", function(e) {
    if (typeof fields_ns.memory["field_" + fields_ns.__current_field_id] == 'undefined') {
      fields_ns.memory["field_" + fields_ns.__current_field_id] = { tab1: null, tab2: null, tab3: null };
      fields_ns.memory.changed_field_ids.push(fields_ns.__current_field_id);
    }
    fields_ns.memory["field_" + fields_ns.__current_field_id].tab1 = $("#edit_field_form_tab1").serializeArray();
    fields_ns.memory["field_" + fields_ns.__current_field_id].tab2 = $("#edit_field_form_tab2").serializeArray();
    fields_ns.memory["field_" + fields_ns.__current_field_id].tab3 = $("#edit_field_form_tab3").serializeArray();
  });

  // this updates the list of field sizes in the page whenever the user changes the type
  $(".field_types").live("change keyup", function(e) {
    var tmp = $(this).attr("name").match(/field_(.+)_type_id/);
    var curr_field_id = tmp[1];
    var field_sizes_div = $(this).closest(".scroll-content").find(".field_sizes_div");
    var curr_value      = field_sizes_div.find("[name=field_" + curr_field_id + "_size]").val();
    ft.update_field_size_dropdown(this, field_sizes_div, {
      name:       "field_" + curr_field_id + "_size",
      html_class: "field_sizes",
      selected:   curr_value
    });
  });

  $(".option_list_or_form_field").live("change", function() {
    if ($(this).find(":selected").closest("optgroup").is("#edit_field__option_lists")) {
      $("#edit_field__option_list_options").show();
      $("#edit_field__form_options").hide();
    } else if ($(this).find(":selected").closest("optgroup").is("#edit_field__forms")) {
      var form_id = this.value.replace(/^ft/, "");
      $("#edit_field__option_list_options").hide();
      $("#edit_field__form_options").show();

      if (fields_ns.form_fields["form_" + form_id] == undefined) {
        fields_ns.load_form_fields({ form_id: form_id });
      } else {
        fields_ns.generate_form_fields_section({ form_id: form_id });
      }
    }
  });

  $(".check_areas").live("click", function(e) {
    if (!$(e.target).hasClass("check_area")) {
      return;
    }
    var field = $(e.target).find("input")[0];
    fields_ns.click_use_default(field);
  });

  // load all option lists into memory
  $.ajax({
    url:      g.root_url + "/global/code/actions.php",
    type:     "POST",
    dataType: "json",
    data:     { action: "get_option_lists" },
    success:  fields_ns.get_option_lists_response,
    error:    ft.error_handler
  });

  // load all forms into memory
  $.ajax({
    url:      g.root_url + "/global/code/actions.php",
    type:     "POST",
    dataType: "json",
    data:     { action: "get_form_list" },
    success:  fields_ns.get_form_list_response,
    error:    ft.error_handler
  });

  // for the Edit Field validation tab
  $("#validation_table").bind("change", function(e) {
    if (!$(e.target).hasClass("v_checked")) {
      return;
    }
    var error_str_field = $(e.target).closest("tr").find(".validation_error_message");
    if (e.target.checked) {
      var rule_id = $(e.target).attr("id").replace(/edit_field__v_/, "");
      error_str_field.removeClass("light_grey").attr("disabled", "");
      if (error_str_field.val() == "") {
        error_str_field.val(fields_ns._get_default_error_message(fields_ns.__current_field_type_id, rule_id));
      }
    } else {
      error_str_field.addClass("light_grey").attr("disabled", "disabled");
    }
  });
});


// sadly, this fires multiple times when resizing by dragging the mouse
$(window).resize(function() {
  fields_ns.reinit_sliders();
});


var fields_ns = {
  num_new_rows:      0,
  add_field_dialog:  $("<div></div>"),
  smart_fill_dialog: $("<div></div>"),
  confirm_redirect_dialog: $("<div id=\"confirm_before_redirect_dialog\"></div>"),

  edit_field_dialog_opened: false,
  cached_field_settings_markup: [],
  cached_loaded_extended_field: [],
  limit_fields_enabled: false,
  max_fields: null,
  num_fields: null, // initiated on page load, and contains the TOTAL number of fields in the form, regardless of the current page

  // this is used to store the contents of the Edit Field popup as the user navigates from field to field. It
  // works by keeping track of ALL changed fields, and only when the user clicks the "Save Changes" field
  // does it actually do an Ajax request to save the contents & update the main page
  memory: { changed_field_ids: [] },

  // used to solve a nagging asynchronous problem on page load: when a user clicks from the Option Lists page (or other pages)
  // to edit the field immediately on page load. In that scenario, the extended field setting info can load before to the
  // Option List and form requests having been loaded. This variable is used to store the values after they've been initially
  // returned from the server so the fields are displayed & populated properly
  onload_field_setting_values: [],

  // these are loaded dynamically on page load. The option lists is just the list of available option lists
  // that the user can choose from; the forms are all forms OTHER than the current one
  option_lists: null,
  forms:        null,
  form_fields: {}, //  stores form_X: [fields]

  __current_field_id:      null,
  __current_field_type_id: null,
  __last_field_type_id:    null,
  __disable_focusin:       false,

  // when the user changes a field type in the Edit Field dialog, we check to see if any of the old
  // settings should be copied across. These two conditions both need to be met in order for that code to
  // run
  __check_shared_characteristics_cond1: false, // the onblur event of the field type
  __check_shared_characteristics_cond2: false  // the settings page is actually loaded
};


fields_ns.reinit_sliders = function() {
  fields_ns.init_sliders({
    scroll_panes:   $(".scroll-pane"),
    scroll_content: $(".scroll-content")
  });
}


fields_ns.get_option_lists_response = function(data) {
  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }
  fields_ns.option_lists = data;
}

fields_ns.get_form_list_response = function(data) {
  fields_ns.forms = data;
}


/**
 * Called on page load, window resize and after adding rows to (re)initialize the top and bottom sliders.
 */
fields_ns.init_sliders = function(info) {
  var max_rows_on_page = fields_ns._get_max_rows_on_page();
  var top_rows_to_scroll    = info.scroll_content.slice(0, max_rows_on_page);
  var bottom_rows_to_scroll = info.scroll_content.slice(-max_rows_on_page);

  fields_ns.scrollbars = $(".scroll-bar-top, .scroll-bar-bottom").slider({
    slide: function(event, ui) {
      var rows_to_scroll = null;
      var other_scrollbar = null;
      if ($(this).hasClass("scroll-bar-top")) {
        rows_to_scroll = top_rows_to_scroll;
        other_scrollbar = ".scroll-bar-bottom";
      } else {
        rows_to_scroll = bottom_rows_to_scroll;
        other_scrollbar = ".scroll-bar-top";
      }

      var rows_to_scroll_width = rows_to_scroll.width();
      if (rows_to_scroll_width > info.scroll_panes.width()) {
        rows_to_scroll.css("margin-left", Math.round(ui.value / 100 * (info.scroll_panes.width() - rows_to_scroll_width)) + "px");
      } else {
        rows_to_scroll.css("margin-left", 0);
      }

      // always update the other scrollbar, too (even if it's off screen)
      $(other_scrollbar + " .ui-slider-handle").css("left", Math.round(ui.value) + "%");
    },

    // this is called after the user finishes dragging the slider. It gives the appropriate margin-left to
    // all off-screen items that weren't just dragged
    change: function(event, ui) {
      var margin_left = null;
      var rows_to_update = null;
      var max_rows_on_page = fields_ns._get_max_rows_on_page();
      if ($(this).hasClass("scroll-bar-top")) {
        margin_left = $(".scroll-content:first").css("marginLeft");
        rows_to_update = $(".scroll-content").slice(max_rows_on_page);
      } else {
        margin_left = $(".scroll-content:last").css("marginLeft");
        rows_to_update = $(".scroll-content").slice(0, $(".scroll-content").length - max_rows_on_page);
      }
      rows_to_update.each(function() {
        $(this).css("marginLeft", margin_left);
      });
    }
  });
}


fields_ns.add_fields = function(e) {
  var num_fields = $.trim($("#add_num_fields").val());

  // check num_rows is an integer
  if (num_fields.match(/\D/) || num_fields == 0 || num_fields == "") {
    ft.create_dialog({
      dialog:     fields_ns.add_field_dialog,
      popup_type: "error",
      title:      g.messages["word_error"],
      content:    g.messages["validation_num_rows_to_add"],
      buttons: [{
        "text":  g.messages["word_okay"],
        "click": function() {
          $(this).dialog("close");
          $("#add_num_fields").focus().select();
        }
      }]
    });
    $("#add_num_fields").focus().select();
    return false;
  }

  // if the max number of fields is in place, check they're not adding more than they're allowed
  if (fields_ns.limit_fields_enabled) {
	var num_fields_on_page = fields_ns.get_num_fields_on_page();
    var remaining_num_fields = fields_ns.max_fields - num_fields_on_page;
    fields_ns.update_max_field_count();
    if (remaining_num_fields <= 0) {
      return false;
    }

    if (num_fields > remaining_num_fields) {
      num_fields = remaining_num_fields;
    }
  }

  var group = $("#group_new_fields").attr("checked");
  var new_row_template = $("#new_row_template").html();
  var fragment = document.createDocumentFragment();
  var html     = "";

  for (var i=0; i<num_fields; i++) {
    fields_ns.num_new_rows++;
    var field = "NEW" + fields_ns.num_new_rows;
    var new_row_html = new_row_template.replace(/%%ROW%%/g, field);

    if (group) {
      html += new_row_html;
    } else {
      var new_row_node = sortable_ns.get_sortable_row_markup({ row_group: new_row_html });
      fragment.appendChild(new_row_node[0]);
    }
  }

  if (group) {
    var grouped_row_nodes = sortable_ns.get_sortable_row_markup({row_group: html });
    fragment = grouped_row_nodes[0];
  }

  var new_field_position = $("#new_field_position").val();

  // jQuery's append and prepend are too slow for this situation, hence the DOM scripting
  if (new_field_position == "start") {
    var rows = $("#rows")[0];
    var first_row = null;
    for (var i=0; i<rows.childNodes.length; i++) {
      if (rows.childNodes[i].nodeType == Node.ELEMENT_NODE) {
        first_row = rows.childNodes[i];
        break;
      }
    }
    if (first_row == null) {
      document.getElementById("rows").appendChild(fragment);
    } else {
      rows.insertBefore(fragment, first_row);
    }
  } else if (new_field_position == "end") {
    document.getElementById("rows").appendChild(fragment);
  } else {
    var row_group = $(".row_group").has("input.sr_order[value=" + new_field_position + "]");

    // if there are later .row_group siblings, separate them into a new group
    var has_next_grouped_row = row_group.next().length != 0;
    if (has_next_grouped_row) {
      sortable_ns._disconnect_row(row_group);
    }
    $(row_group).closest(".sortable_row").after(fragment);
  }

  sortable_ns.reorder_rows($("#rows"), true);
  fields_ns.update_fields_dropdown();

  // update slider to slide all elements with .scroll-content class currently on page
  fields_ns.init_sliders({
    scroll_panes:   $(".scroll-pane"),
    scroll_content: $(".scroll-content")
  });

  fields_ns.num_fields++;
  fields_ns.update_max_field_count();

  return false;
}


/**
 * Helper function to find out the maximum number of rows that can be visible on the page, as determined
 * by the user's window height. This is used for a little visual trickery: scrolling the field columns actually
 * only scrolls the visible ones - not others. This significantly improves the responsiveness of the UI for
 * forms with large numbers of fields.
 */
fields_ns._get_max_rows_on_page = function() {
  var max_row_height = 23;
  return parseInt($(window).height() / 23);
}


/**
 * Any time the field content changes (fields are added, removed or re-sorted), the fields dropdown
 * at the bottom right needs to be updated.
 */
fields_ns.update_fields_dropdown = function() {
  var curr_value = $("#new_field_position").val();
  var options = "";
  $(".sortable .display_text").each(function() {
    var field_id     = $(this).closest(".row_group").find(".sr_order").val();
    var display_text = $(this).val();
    if (display_text == "") {
      display_text = "[" + g.messages["word_row"] + " " + $(this).closest(".row_group").find(".col1").text() + "]";
    }
    var selected = (field_id == curr_value) ? " selected" : "";
    options += "<option value=\"" + field_id + "\"" + selected + ">" + display_text + "</option>\n";
  });

  $("#add_fields_list").html(options);
}


/**
 * This smart-fills the DB Column field, by converting the display text to a simplified DB-friendly
 * string.
 */
fields_ns.smart_fill = function() {
  ft.create_dialog({
    dialog:     fields_ns.smart_fill_dialog,
    popup_type: "warning",
    min_width:  450,
    title:      g.messages["phrase_smart_fill"],
    content:    "<div class=\"desc\">" + g.messages["confirm_smart_fill_db_column_fields_desc"] + "</div>" +
                g.messages["confirm_smart_fill_db_column_fields"],
    buttons: [{
      "text":  g.messages["word_okay"],
      "click": function() {
        var inserted_db_col_names = [];
        $(".sortable .db_column").each(function() {
          // the field ID is either a single integer, or an integer with the NEW prefix, to signify the
          // user just added the field
          var field_id_info = $(this).attr("id").match(/col_(NEW)?(\d+)_name/);
          if (field_id_info[1] != undefined) {
            field_id = "NEW" + field_id_info[2];
          } else {
            field_id = field_id_info[2];
          }

          var display_text = $.trim($("#field_" + field_id + "_display_name").val());
          var new_db_col_name = display_text.replace(/\s+/g, "_");
          new_db_col_name = new_db_col_name.replace(/[^a-zA-Z0-9_]/g, "").toLowerCase();

          // if it's a reserved word, or already taken, add an numeric suffix
          var is_invalid = $.inArray(new_db_col_name.toUpperCase(), page_ns.reserved_words) != -1 ||
                           $.inArray(new_db_col_name, inserted_db_col_names) != -1;
          var suffix = 2;
          var test_name = null;
          while (is_invalid) {
            test_name = new_db_col_name + suffix;
            is_invalid = $.inArray(test_name.toUpperCase(), page_ns.reserved_words) != -1 ||
                         $.inArray(test_name, inserted_db_col_names) != -1;
            if (!is_invalid) {
              break;
            }
            suffix++;
          };

          if (test_name != null) {
            new_db_col_name = test_name;
          }

          // lastly, if the display name consisted entirely of non a-Z0-9 chars, it'll be empty at this point.
          // just set it to a default col_X value - it's all we can do
          if (new_db_col_name == "") {
            var prefix = "col_";
            var suffix = 1;
            is_invalid = true;
            var test_name = null;
            while (is_invalid) {
              test_name = prefix + suffix;
              is_invalid = $.inArray(test_name, inserted_db_col_names) != -1;
              if (!is_invalid) {
                break;
              }
              suffix++;
            };
            new_db_col_name = test_name;
          }

          $(this).val(new_db_col_name);

          // make a note of this database column name, so we don't generate another column with the same name
          inserted_db_col_names.push(new_db_col_name);
        });
        $(this).dialog("close");
      }
    },
    {
      "text":  g.messages["word_cancel"],
      "click": function() { $(this).dialog("close"); }
    }]
  });

  return false;
};


/*
 * Called on form submit: validates the entire page.
 *
 * @param object the form
 */
fields_ns.check_fields = function(f) {
  var errors = [];
  var field_name_list = [];
  var col_name_list   = [];
  var errors_by_col   = [0, 0, 0];

  $(".sortable .display_text").each(function() {
    var row_group = $(this).closest(".row_group");
    var field_id  = row_group.find(".sr_order").val();
    var is_new_row = /^NEW/.test(field_id);
    var display_text_field = row_group.find(".display_text");
    var display_text = $.trim(display_text_field.val());

    if (display_text == "") {
      // if it's a NEW row, ignore the entire row
      if (is_new_row) {
        return;
      }
      errors.push([this, g.messages["validation_no_display_text"]]);
      errors_by_col[0]++;
    } else {
      $(display_text_field).removeClass("rsvErrorField");
    }

    // check the form name field isn't empty or invalid
    var field_name_field = row_group.find(".field_names");
    if (field_name_field.length) {
      var field_name = $.trim(field_name_field.val());
      if (!field_name) {
        errors.push([$(field_name_field)[0], g.messages["validation_no_form_field_name"]]);
        errors_by_col[0]++;
      } else if (field_name.match(/[^0-9a-zA-Z_]/)) {
        errors.push([$(field_name_field)[0], g.messages["validation_invalid_form_field_names"]]);
        errors_by_col[0]++;
      } else if ($.inArray(field_name, field_name_list) != -1) {
        errors.push([$(field_name_field)[0], g.messages["validation_duplicate_form_field_name"]]);
        errors_by_col[0]++;
      } else {
        field_name_field.removeClass("rsvErrorField");
      }

      field_name_list.push(field_name);
    }

    // check the db_column
    var db_column_field = row_group.find(".db_column");
    if (db_column_field.length) {
      var db_column_name = $.trim(db_column_field.val());
      var return_info = fields_ns._is_valid_db_column_name(db_column_name, col_name_list);
      if (!return_info.success) {
      errors.push([$(db_column_field)[0], return_info.error]);
        errors_by_col[2]++;
      } else {
        db_column_field.removeClass("rsvErrorField");
      }

      col_name_list.push(db_column_name);
    }
  });

  // now scroll the region to the appropriate section, based on what cols had the errors
  if (errors_by_col[0] > 0) {
    fields_ns.change_scroll_area(0);
  } else if (errors_by_col[1] > 0) {
    fields_ns.change_scroll_area(50);
  } else if (errors_by_col[2] > 0) {
    fields_ns.change_scroll_area(100);
  }

  return errors;
}


/**
 * Helper function to determine if a database column name is valid or not. If success,
 *
 * @param col_name the column name to test
 * @param col_name_list an optional param containing a list of existing columns. If this is
 *        defined, it will throw an error if the column name is already taken
 * @return object { success: true/false, error: "string" }
 */
fields_ns._is_valid_db_column_name = function(col_name, col_name_list) {

  // tack on the core fields
  col_name_list.push("submission_id");
  col_name_list.push("submission_date");
  col_name_list.push("last_submission_date");
  col_name_list.push("ip_address");
  col_name_list.push("is_finalized");

  var return_info = { success: true, error: "" }
  var db_column_name = $.trim(col_name);
  if (db_column_name == "") {
    return_info = { success: false, error: g.messages["validation_no_column_name"] };
  } else if (db_column_name.match(/\W/)) {
    return_info = { success: false, error: g.messages["validation_invalid_column_name"] };
  } else if ($.inArray(db_column_name.toUpperCase(), page_ns.reserved_words) != -1) {
    return_info = { success: false, error: g.messages["validation_col_name_is_reserved_word"] };
  } else if ($.inArray(db_column_name, col_name_list) != -1) {
    return_info = { success: false, error: g.messages["validation_no_two_column_names"] };
  }

  return return_info;
}


fields_ns.change_scroll_area = function(percentage, ignore) {
  var new_margin_left = 0;
  if (percentage == 50) {
    new_margin_left = -238;
  } else if (percentage == 100) {
    new_margin_left = -477;
  }
  $(".scrollable").attr("scrollLeft", 0);
  $(".ui-slider-handle").css("left", percentage + "%");
  $(".sortable").find(".scrollable").each(function() {
    $(this).find(".scroll-content").css("marginLeft", new_margin_left);
  });
}


/**
 * Called whenever the user edits a field. This opens a dialog window containing all appropriate options
 * for this field type. Note: the field type is found by looking at the current value in the page - not
 * from the server. This ensures it gets the latest appropriate value.
 *
 * @param node row_group - the row_group node, containing the entire row of data already in the page.
 */
fields_ns.edit_field = function(row_group) {
  $("#edit_field_template_message").addClass("hidden");
  var is_system_field = $(row_group).hasClass("system_field");

  // keep track of the row group currently being edited
  fields_ns.__current_editing_row_group = row_group;
  var field_id = $(row_group).find(".sr_order").val();
  var is_new_field = (field_id.match(/^NEW/) != null) ? true : false;

  // if we're here, then we just started editing a field: the user hasn't changed the field type ID
  fields_ns.__last_field_type_id = null;
  fields_ns.__check_shared_characteristics = false;

  // find out if this field has already been edited and has values in memory
  var field_values_cached = ($.inArray(field_id, fields_ns.memory.changed_field_ids) != -1) ? true : false;

  var tab1_fields = null;
  if (field_values_cached) {
    if (typeof fields_ns.memory["field_" + field_id].tab1 != "undefined") {
      tab1_fields = fields_ns.memory["field_" + field_id].tab1;
    }
  }

  // if this field hasn't been added to the database yet, force it to show the first page
  if (is_new_field) {
    $("#edit_field_template_new_field").removeClass("hidden");
    $(".tab_row").hide();
    ft.change_inner_tab(1, "edit_field");
  } else {
    $("#edit_field_template_new_field").addClass("hidden");
    $(".tab_row").show();
  }

  var display_text = "";
  if (tab1_fields) {
    display_text = ft._extract_array_val(tab1_fields, "edit_field__display_text");
  } else {
    display_text = row_group.find(".display_text").val();
  }
  $("#edit_field__display_text").val(display_text);

  var field_type_id = null;
  fields_ns.__current_field_id = field_id;
  fields_ns.__current_field_is_system_field = is_system_field;

  var field_type_str = null;

  // system fields only allow the display text and pass-on fields to be edited
  if (is_system_field) {
    $(".edit_field__non_system").hide();
    $(".edit_field__system").show();
    field_type_id = row_group.find(".system_field_type_id").val();
    $("#edit_field__field_type_system").html(row_group.find(".system_field_type_label").html());
    $("#edit_field__db_column_div_system").html(row_group.find(".system_field_db_column").html());
    $("#edit_field__pass_on").attr("checked", row_group.find(".pass_on").attr("checked"));
    field_type_str = $("#edit_field__field_type option[value=" + field_type_id + "]").text();
  } else {
    $(".edit_field__non_system").show();
    $(".edit_field__system").hide();
    var field_type = row_group.find(".field_types");
    field_type_id = field_type.val();

    // display all the various values for this field
    if (tab1_fields) {
      $("#edit_field__field_name").val(ft._extract_array_val(tab1_fields, "edit_field__field_name"));
      $("#edit_field__field_type").val(ft._extract_array_val(tab1_fields, "edit_field__field_type"));

      ft.update_field_size_dropdown(field_type, $("#edit_field__field_size_div"), {
        selected: ft._extract_array_val(tab1_fields, "edit_field__field_size"),
        name:     "edit_field__field_size",
        id:       "edit_field__field_size"
      });

      var pass_on = ft._extract_array_val(tab1_fields, "edit_field__pass_on");
      $("#edit_field__pass_on").attr("checked", ((pass_on == "") ? "" : "checked"));
      $("#edit_field__data_type").val(ft._extract_array_val(tab1_fields, "edit_field__data_type"));
      $("#edit_field__db_column").val(ft._extract_array_val(tab1_fields, "edit_field__db_column"));
    } else {
      $("#edit_field__field_name").val(row_group.find(".field_names").val());
      $("#edit_field__field_type").val(field_type_id);

      ft.update_field_size_dropdown(field_type, $("#edit_field__field_size_div"), {
        selected: row_group.find(".field_sizes").val(),
        name:     "edit_field__field_size",
        id:       "edit_field__field_size"
      });

      $("#edit_field__pass_on").attr("checked", row_group.find(".pass_on").attr("checked"));
      $("#edit_field__data_type").val(row_group.find(".data_types").val());
      $("#edit_field__db_column").val(row_group.find(".db_column").val());
    }

    // the string version of the field type
    field_type_str = $("#edit_field__field_type :selected").text();
  }

  fields_ns.__current_field_type_id = field_type_id;
  fields_ns.__last_field_type_id    = field_type_id;

  fields_ns.update_validation_tab_label(field_type_id);

  // load the Field-Specific Settings tab
  var has_field_settings = fields_ns.init_field_settings_tab(field_type_id, field_id);

  // update the previous/next links (grey them out if one or both aren't relevant)
  if (sortable_ns._has_previous_field(row_group)) {
    $("#edit_field_template .prev_field").removeClass("disabled");
  } else {
    $("#edit_field_template .prev_field").addClass("disabled");
  }
  if (sortable_ns._has_next_field(row_group)) {
    $("#edit_field_template .next_field").removeClass("disabled");
  } else {
    $("#edit_field_template .next_field").addClass("disabled");
  }

  var title = display_text + "<span class=\"edit_field_title_field_type\">(" + field_type_str + ")</span>";

  // finally, if the Edit Field dialog isn't already open, open it
  if (!fields_ns.edit_field_dialog_opened) {
    ft.create_dialog({
      dialog:    $("#edit_field_template"),
      title:     title,
      min_width: 720,

      // this fires the first time the dialog is opened - not when the user clicks from field to field
      open: function() { fields_ns.edit_field_dialog_opened = true; },
      close: function() { fields_ns.edit_field_dialog_opened = false; },

      buttons: [
        {
          text: g.messages["phrase_save_changes"],
          click: function() {
            fields_ns.save_changes();
          }
        },
        {
          text:  g.messages["word_close"],
          click: function() {
            fields_ns.memory = {};
            fields_ns.memory.changed_field_ids = [];
            $(this).dialog("close");
          }
        }
      ]
    });
  } else {
    $("#edit_field_template").dialog({ title: title });
  }
}


/**
 * Called whenever a field is loaded in the Edit Field dialog: regardless of what tab is currently being
 * displayed. This constructs the appropriate markup on the Field-Specific Settings tab (i.e. the setting
 * fields for the particular field type) and loads it in the page - but HIDDEN. It then does an Ajax
 * call to retrieve the extended field info. When that's completed the settings are displayed in the page.
 *
 * The other scenario is when a user is editing one field to the next, then returns to a field that they've
 * already edited. In that case, the updated values are stored in fields_ns.memory["field_X"].tab2. If that
 * contains values, the field settings are loaded from there. Lastly: the Field Type field on the first tab
 * changes the contents of the field settings tab. As such...? TODO
 *
 * @return boolean true if the field type has settings, false otherwise
 */
fields_ns.init_field_settings_tab = function(field_type_id, field_id) {
  var field_settings = page_ns.field_settings["field_type_" + field_type_id];

  // get the string name of the second tab. To make it totally clear, we give the second tab a label like
  // "Textbox Settings" or "Phone Number Settings"
  var field_type_label = $("#edit_field__field_type option[value=" + field_type_id + "]").text();

  // no extended settings
  if (field_settings == undefined) {
    $("#edit_field_template .inner_tab2").html(field_type_label + " " + g.messages["word_settings"] + " (0)");
    $("#edit_field__field_settings").html("<div class=\"notify\"><div style=\"padding:6px\">" + g.messages["notify_no_field_settings"] + "</div></div>");
    $("#edit_field__field_settings_loading").hide();
    return false;
  } else {
    $("#edit_field_template .inner_tab2").html(field_type_label + " " + g.messages["word_settings"] + " (" + field_settings.length + ")");
  }

  // generate the field settings markup and embed it in the page
  var html = fields_ns.generate_field_type_markup(field_id, field_type_id, field_settings);
  $("#edit_field__field_settings").html(html);

  // now load the actual settings for this field from the server. If this information is already loaded (or is
  // currently loading), use that. Otherwise do a fresh Ajax request
  if (fields_ns.cached_loaded_extended_field["field_id_" + field_id] == undefined) {
	fields_ns.cached_loaded_extended_field["field_id_" + field_id] = {
      loaded:     false,
      settings:   [],
      validation: []
    };
    $("#edit_field__field_settings_loading").show();
    $("#edit_field__field_settings").hide();
    ft.dialog_activity_icon($("#edit_field_template"), "show");

    if (!/^NEW/.test(field_id)) {
      $.ajax({
        url:      g.root_url + "/global/code/actions.php",
        type:     "POST",
        dataType: "json",
        data:     { field_id: field_id, field_type_id: field_type_id, action: "get_extended_field_settings" },
        success:  fields_ns.load_field_settings_response,
        error:    ft.error_handler
      });
    }
  }
  else
  {
    // if the request has already been returned from the server, display the data. Otherwise,
    // the Ajax response method will handle the loading of the data appropriately
    if (fields_ns.cached_loaded_extended_field["field_id_" + field_id].loaded) {
      var data = fields_ns.cached_loaded_extended_field["field_id_" + field_id];
      fields_ns.display_field_settings(field_type_id, data.settings, data.validation);
    }
  }

  return true;
}


/**
 * This is called whenever the user opens the Edit Field dialog on a field that has a field type with
 * extended settings - that includes going from field to field with the "prev/next" nav. It constructs the
 * markup and if all the markup was available, stores it in cache (fields_ns.cached_field_setting_markup).
 * This function also checks shared characteristics to map over as much shared data as possible.
 *
 * @param integer field_id
 * @param integer field_type_id
 * @param array settings
 */
fields_ns.generate_field_type_markup = function(field_id, field_type_id, field_settings) {
  var html = "";
  if (fields_ns.cached_field_settings_markup["field_type_" + field_type_id] !== undefined) {
    html = fields_ns.cached_field_settings_markup["field_type_" + field_type_id];
    fields_ns.__check_shared_characteristics_cond2 = true;
    fields_ns.check_shared_characteristics();
  } else {
    // the assumption is that we can cache the markup we're about to generate. However, some field types rely on
    // other requests: namely, option lists and form fields. At the time this function is called, those requests
    // may not have returned. In those instances, we don't want to cache the markup since it would be incomplete
    var may_cache = true;

    // all fields are disabled by default. Once the Ajax call to load the values for the actual field
    // has completed, they are enabled & the Ajax loading icon is hidden to signify readiness
    html = "<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\" class=\"check_areas\">"
         + "<tr>"
           + "<th width=\"200\" class=\"underline medium_grey\">" + g.messages["word_setting"] + "</th>"
           + "<th width=\"150\" align=\"center\" class=\"underline medium_grey\">" + g.messages["phrase_use_default_value_q"] + "</th>"
           + "<th class=\"underline medium_grey\">" + g.messages["word_value"] + "</th>"
         + "</tr>";

    for (var i=0; i<field_settings.length; i++) {
      var info = field_settings[i];
      html += "<tr>"
            + "<td>" + info.field_label + "</td>"
            + "<td class=\"check_area\" align=\"center\">";

      var display_use_default_checkbox = true;
      if (info.field_type == "option_list_or_form_field") {
        display_use_default_checkbox = false;
      }

      // Note we don't add a name attribute to the checkbox. The server-side code doesn't actually need that info: in the
      // absence of a custom value, it will use the default
      if (display_use_default_checkbox) {
        html += "<input type=\"checkbox\" class=\"use_default\" "
              + "id=\"edit_field__use_default_value_" + info.setting_id + "\" checked />";
      } else {
        html += "<span class=\"light_grey\">&#8212;</span>";
      }

      html += "</td><td>";

      switch (info.field_type) {
        case "textbox":
          html += "<input type=\"text\" name=\"edit_field__setting_" + info.setting_id + "\" id=\"edit_field__setting_" + info.setting_id + "\" value=\"" + info.default_value + "\" "
                + "disabled style=\"width: 100%\" class=\"light_grey\" />";
          break;

        case "textarea":
          html += "<textarea name=\"edit_field__setting_" + info.setting_id + "\" id=\"edit_field__setting_" + info.setting_id + "\" disabled style=\"width:100%; height: 80px\" class=\"light_grey\">"
                + info.default_value + "</textarea>";
          break;

        case "select":
          html += "<select name=\"edit_field__setting_" + info.setting_id + "\" id=\"edit_field__setting_" + info.setting_id + "\" class=\"light_grey\" disabled>";
          var options = info.options;
          for (var j=0; j<options.length; j++) {
            var opt = options[j];
            var selected = (opt.value == info.default_value) ? " selected" : "";
            html += "<option value=\"" + opt.value + "\"" + selected + ">" + opt.text + "</option>";
          }
          html += "</select>";
          break;

        case "checkboxes":
          for (var j=0; j<info.options.length; j++) {
            if (j > 0) {
              html += (info.field_orientation == "vertical") ? "<br />" : "&nbsp;";
            }
            var opt = info.options[j];
            var checked = (opt.value == info.default_value) ? "checked" : "";
            html += "<input type=\"checkbox\" name=\"edit_field__setting_" + info.setting_id + "\" id=\"edit_field__setting_" + info.setting_id + "_" + j + "\" "
                 + "name=\"edit_field__setting_" + info.setting_id + "\" class=\"light_grey\" value=\"" + opt.value + "\" " + checked + " disabled>"
                 + "<label for=\"edit_field__setting_" + info.setting_id + "_" + j + "\">" + opt.text + "</label>";
          }
          break;

        case "radios":
          for (var j=0; j<info.options.length; j++) {
            if (j > 0) {
              html += (info.field_orientation == "vertical") ? "<br />" : "&nbsp;";
            }
            var opt = info.options[j];
            var checked = (opt.value == info.default_value) ? "checked" : "";
            html += "<input type=\"radio\" name=\"edit_field__setting_" + info.setting_id + "\" id=\"edit_field__setting_" + info.setting_id + "_" + j + "\" "
                 + "name=\"edit_field__setting_" + info.setting_id + "\" class=\"light_grey\" value=\"" + opt.value + "\" " + checked + " disabled>"
                 + "<label for=\"edit_field__setting_" + info.setting_id + "_" + j + "\">" + opt.text + "</label>";
          }
          break;

        case "multi-select":
          html += "<select name=\"edit_field__setting_" + info.setting_id + "\" id=\"edit_field__setting_" + info.setting_id + "\" class=\"light_grey\" multiple size=\"5\" disabled>";
          for (var j=0; j<info.options.length; j++) {
          var opt = info.options[j];
            var selected = (opt.value == info.default_value) ? " selected" : "";
            html += "<option value=\"" + opt.value + "\"" + selected + ">" + opt.text + "</option>";
          }
          html += "</select>";
          break;

        case "option_list_or_form_field":
          html += "<div id=\"option_list_div_" + info.setting_id + "\">";

          // if either of the requests for the option lists or forms haven't returned yet, queue the job of updating
          // the markup in this page. That will fire once that data has become available. Also, we make a note NOT to
          // cache this field's markup. N.B. if the user has gone to a new page, nothing happens
          if (fields_ns.option_lists == null || fields_ns.forms == null) {
            may_cache = false;
            (function(setting_id, default_value) {
              ft.queue.push([
                function() { },
                function() {
                  var option_lists_ready = (fields_ns.option_lists != null);
                  var forms_ready = (fields_ns.forms != null);
                  if (option_lists_ready && forms_ready) {
                    $("#option_list_div_" + setting_id).html(fields_ns._generate_option_list_markup(setting_id, default_value));
                    fields_ns.__check_shared_characteristics_cond2 = true;
                    fields_ns.check_shared_characteristics();
                  }

                  // here, the Option List markup has been loaded, but the value hasn't been set. This can occur because at
                  // the time that this function was called, the extended fields weren't loaded. See comment above for the
                  // definition of onload_field_setting_values
                  for (var j=0; j<fields_ns.onload_field_setting_values.length; j++) {
                    if (fields_ns.onload_field_setting_values[j].setting_id == setting_id) {
                      $("#option_list_div_" + setting_id).html(fields_ns._generate_option_list_markup(setting_id, fields_ns.onload_field_setting_values[j].setting_value));
                    }
                  }

                  // no emptying of onload_field_setting_values?

                  return option_lists_ready;
                }
              ]);
              ft.process_queue();
            })(info.setting_id, info.default_value);
          } else {
            html += fields_ns._generate_option_list_markup(info.setting_id, info.default_value);
          }

          html += "</div>";
          break;
      }
      html += "</td></tr>";
    }
    html += "</table>";

    // if we can cache it, we do
    if (may_cache) {
      fields_ns.cached_field_settings_markup["field_type_" + field_type_id] = html;
      fields_ns.__check_shared_characteristics_cond2 = true;
      fields_ns.check_shared_characteristics();
    }
  }

  return html;
}


/**
 * This is called once fields_ns.option_lists AND fields_ns.forms are populated with the appropriate info.
 * It generates the markup for the main select box, which the user can choose to pick an Option List or map the
 * field to another form's field.
 *
 * Kind of a klutzy function. Only ever used to load the Option List markup on page load - not the Form Field markup.
 */
fields_ns._generate_option_list_markup = function(setting_id, default_value) {
  var option_list_selected = false;
  var option_list_html = "";
  $.each(fields_ns.option_lists, function(value, text) {
    var selected = "";
    if (value == default_value) {
      selected = " selected";
      option_list_selected = true;
    }
    option_list_html += "<option value=\"" + value + "\"" + selected + ">" + text + "</option>";
  });

  var forms_selected = false;
  var forms_html = "";
  $.each(fields_ns.forms, function(value, text) {
    // never output the CURRENT form
    if (value == page_ns.form_id) {
      return;
    }
    // note the "ft" field type prefix!
    forms_html += "<option value=\"ft" + value + "\">" + text + "</option>";
  });

  if (option_list_html == "" && forms_html == "")
  {
    return g.messages["phrase_no_option_lists_available"]
        + "<div><a href=\"#\" onclick=\"return fields_ns.create_new_option_list()\">" + g.messages["phrase_create_new_option_list"] + "</a></div>";
  }

  var html = "<select class=\"option_list_or_form_field\" name=\"edit_field__setting_" + setting_id + "\" id=\"edit_field__setting_" + setting_id + "\">"
    + "<option value=\"\">" + g.messages["phrase_please_select"] + "</option>\n";

  if (option_list_html) {
    html += "<optgroup id=\"edit_field__option_lists\" label=\"" + g.messages["phrase_available_option_lists"] + "\">" + option_list_html + "</optgroup>";
  }
  if (forms_html) {
    html += "<optgroup id=\"edit_field__forms\" label=\"" + g.messages["phrase_form_field_contents"] + "\">" + forms_html + "</optgroup>";
  }

  var option_list_options_hidden = (option_list_selected) ? "" : " style=\"display:none\"";
  html += "</select>"
       + "<div id=\"edit_field__option_list_options\"" + option_list_options_hidden + ">"
         + "<a href=\"#\" onclick=\"return fields_ns.edit_option_list(" + setting_id + ")\">" + g.messages["phrase_edit_option_list"] + "</a> | "
         + "<a href=\"#\" onclick=\"return fields_ns.create_new_option_list()\">" + g.messages["phrase_create_new_option_list"] + "</a>"
       + "</div>";

  var forms_hidden = (forms_selected) ? "" : " style=\"display:none\"";

  // the contents of this section (the form field dropdown & order options) are loaded dynamically
  html += "<div id=\"edit_field__form_options\"></div>";

  return html;
}


/**
 * For now, this just does a quick check to confirm that the user doesn't have unsaved changes, then
 * redirects to the Option List page. It would be very nice, however, to integrate the Option Lists better
 * so that the user can edit the option list details right in the dialog.
 */
fields_ns.edit_option_list = function(setting_id) {
  var list_id = $("#edit_field__setting_" + setting_id).val();

  if (!list_id) {
    $("#edit_field_template_message").removeClass("hidden");
    ft.display_message("edit_field_template_message", 0, g.messages["notify_edit_option_list_after_save"]);
    return false;
  }

  var edit_option_list_url = g.root_url + "/admin/forms/option_lists/edit.php?page=main&list_id=" + list_id;

  if (fields_ns.memory.changed_field_ids.length == 0) {
    window.location = edit_option_list_url;
  } else {
   // hide the main parent dialog. We'll re-show it if the user cancels
   $("#edit_field_template").closest(".ui-dialog").hide();
   ft.create_dialog({
      dialog:     fields_ns.confirm_redirect_dialog,
      popup_type: "warning",
      content:    g.messages["confirm_save_change_before_redirect"],
      title:      g.messages["phrase_please_confirm"],
      buttons: [
        {
          text:  g.messages["word_yes"],
          click: function() {
            $.ajax({
              url:      g.root_url + "/global/code/actions.php",
              data:     {
                form_id:     page_ns.form_id,
                action:      "update_form_fields",
                data:        fields_ns.memory,
                return_vars: { url: edit_option_list_url }
              },
              type:     "POST",
              dataType: "json",
              success:  function(data) {
                if (data.success == 1) {
                  window.location = data.url;
                }
              },
              error:    function(xhr, text_status, error_thrown) {
                ft.dialog_activity_icon($("#confirm_before_redirect_dialog"), "hide");
                $("#confirm_before_redirect_dialog").dialog("close");
                $("#edit_field_template").dialog("close");
                ft.error_handler(xhr, text_status, error_thrown);
              }
            });

            ft.dialog_activity_icon($("#confirm_before_redirect_dialog"), "show");
          }
        },
        {
          text:  g.messages["word_no"],
          click: function() {
          ft.dialog_activity_icon($("#confirm_before_redirect_dialog"), "show");
            window.location = edit_option_list_url;
          }
        },
        {
          text:  g.messages["word_cancel"],
          click: function() {
          $(this).dialog("close");
            $("#edit_field_template").closest(".ui-dialog").show();
          }
        }
      ]
    });
  }
  return false;
}


/**
 * Called when the user clicks on the "Create New Option List" link. All it does is redirect to the Option List page
 * and tell it to create a new Option List & assign it to the current field ID.
 */
fields_ns.create_new_option_list = function() {
  window.location = "./option_lists/index.php?add_option_list=1&field_id=" + fields_ns.__current_field_id;
  return false;
}


/**
 * This is called for fields that have types with one or more (possible) extended fields.
 */
fields_ns.load_field_settings_response = function(data) {
  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  // store the info in memory. This memory is invalidated anytime the user changes the field type
  // for the field
  fields_ns.cached_loaded_extended_field["field_id_" + data.field_id] = {
    loaded:        true,
    field_type_id: data.field_type_id,
    settings:      data.settings,
    validation:    data.validation
  };

  // if the user is currently editing the same field as the info returned in this request,
  // display the data
  if (data.field_id == fields_ns.__current_field_id) {
    fields_ns.display_field_settings(data.field_type_id, data.settings, data.validation);
  }

  // check to see if there are any open requests to the server. If none, hide the loading icon
  var has_open_thread = false;
  for (var item in fields_ns.cached_loaded_extended_field) {
    if (fields_ns.cached_loaded_extended_field[item].loading === false) {
      has_open_thread = true;
      break;
    }
  }
  if (!has_open_thread) {
    ft.dialog_activity_icon($("#edit_field_template"), "hide");
  }
}


/**
 * This updates the HTML to show a field's settings. This is loaded after the settings have become available.
 * Note: it's possible that the option list or forms request hasn't been returned yet.
 */
fields_ns.display_field_settings = function(field_type_id, settings, validation) {

  // see if there's already some values stashed in memory for this field. If there IS, we use those
  // values rather that what's being passed via the settings param. This is klutzy.
  var load_validation_from_memory = false;
  if (typeof fields_ns.memory["field_" + fields_ns.__current_field_id] != 'undefined') {

    // tab 2
    if (typeof fields_ns.memory["field_" + fields_ns.__current_field_id].tab2 != 'undefined' &&
    		fields_ns.memory["field_" + fields_ns.__current_field_id].tab2 != null) {
      var unsaved_changes = fields_ns.memory["field_" + fields_ns.__current_field_id].tab2;
      var updated_settings = [];
      for (var i=0; i<settings.length; i++) {
        var curr_setting_id = settings[i].setting_id;
        var curr_setting = settings[i];
        for (var j=0; j<unsaved_changes.length; j++) {
          var setting_name = "edit_field__setting_" + curr_setting_id;
          if (unsaved_changes[j].name == setting_name) {
            curr_setting.setting_value = unsaved_changes[j].value;
            curr_setting.uses_default = false;
          }
        }
        updated_settings.push(curr_setting);
      }
      settings = updated_settings;
    }

    // tab 3
    if (typeof fields_ns.memory["field_" + fields_ns.__current_field_id].tab3 != 'undefined') {
      load_validation_from_memory = true;
    }
  }

  for (var i=0, j=settings.length; i<j; i++) {
    var curr_setting_id = settings[i].setting_id;
    $("#edit_field__use_default_value_" + curr_setting_id).attr("disabled", "");
    $("#edit_field__use_default_value_" + curr_setting_id).attr("checked", (settings[i].uses_default) ? "checked" : "");

    var field_type_info = {};
    var field_settings = page_ns.field_settings["field_type_" + field_type_id];
    for (var k=0; k<field_settings.length; k++) {
      if (field_settings[k].setting_id == curr_setting_id) {
        field_type_info = field_settings[k];
        break;
      }
    }

    switch (field_type_info.field_type) {
      case "textbox":
      case "textarea":
      case "select":
        $("#edit_field__setting_" + curr_setting_id).val(settings[i].setting_value);
        if (!settings[i].uses_default) {
          $("#edit_field__setting_" + curr_setting_id).removeClass("light_grey").attr("disabled", "");
        }
        break;
      case "radios":
      var radios = $("input[name=edit_field__setting_" + curr_setting_id + "]");
        radios.filter("[value=" + settings[i].setting_value + "]").attr("checked", "checked");
        if (!settings[i].uses_default) {
          radios.removeClass("light_grey").attr("disabled", "");
        }
        break;
      case "option_list_or_form_field":
        var is_form_field = settings[i].setting_value.toString().match(/^form_field/);

        // we can't just set the form field values because we have a few asyncronous dependencies: we need
        // to ensure that both the Option Lists and the form list has been loaded
        if (is_form_field) {
          (function(setting_id, setting_value) {
            ft.queue.push([
              function() { },
              function() {
                var option_lists_ready = (fields_ns.option_lists != null);
                var forms_ready = (fields_ns.forms != null);
                if (option_lists_ready && forms_ready) {
                  var parts = setting_value.replace(/form_field:/, "").split("|");
                  var form_id     = parts[0];
                  var field_id    = parts[1];
                  var field_order = parts[2];
                  $("#edit_field__setting_" + setting_id).val("ft" + parts[0]);

                  // now request the form fields
                  if (fields_ns.form_fields["form_" + form_id] == undefined) {
                    fields_ns.load_form_fields({ form_id: form_id, field_id: field_id, field_order: field_order });
                  } else {
                    fields_ns.generate_form_fields_section({ form_id: form_id, field_id: field_id, field_order: field_order });
                  }
                }
                return (option_lists_ready && forms_ready);
              }
            ]);
            ft.process_queue();
          })(curr_setting_id, settings[i].setting_value);
        } else {
          if (fields_ns.option_lists == null) {

          } else {
            $("#edit_field__setting_" + curr_setting_id).val(settings[i].setting_value);
            $("#edit_field__option_list_options").show();
            $("#edit_field__form_options").hide();

            // in case the Option List section wasn't loaded yet, store it in memory
            fields_ns.onload_field_setting_values.push({
              setting_id:    curr_setting_id,
              setting_value: settings[i].setting_value
            });
          }
        }
        break;

      default:
        //console.log(field_type_info);
        break;
    }
  }

  // now create the validation table
  var html = fields_ns.generate_field_type_validation_table(field_type_id);
  $("#validation_table").html(html);

  // now display the validation rules
  var num_rules = 0;
  if (load_validation_from_memory) {
    var rules = fields_ns.memory["field_" + fields_ns.__current_field_id].tab3;
    for (var i=0; i<rules.length; i++) {
      var match = rules[i].name.match(/^edit_field__v_(.*)_message$/);
      if (match) {
        var rule_id = match[1];
        $("#edit_field__v_" + rule_id).attr("checked", "checked");
        $("#edit_field__v_" + rule_id + "_message").removeClass("light_grey").attr("disabled", "").val(rules[i].value);
      }
    }
  } else {
    for (var i=0; i<validation.length; i++) {
      var rule_id = validation[i].rule_id;
      var message = validation[i].error_message.replace(/\\/g, "");
      $("#edit_field__v_" + rule_id).attr("checked", "checked");
      $("#edit_field__v_" + rule_id + "_message").removeClass("light_grey").attr("disabled", "").val(message);
    }
  }

  fields_ns.update_validation_tab_label(field_type_id);

  // now show the markup
  $("#edit_field__field_settings_loading").hide();
  $("#edit_field__field_settings").show();
}


fields_ns.update_validation_tab_label = function(field_type_id) {
  var num_rules = 0;
  if (typeof page_ns.field_validation["field_type_" + field_type_id] != "undefined") {
    num_rules = page_ns.field_validation["field_type_" + field_type_id].length;
  }
  if (fields_ns.__current_field_is_system_field) {
    num_rules = 0;
  }
  $("#edit_field_template .inner_tab3").html(g.messages["word_validation"] + " (" + num_rules + ")");
}


/**
 * Called when the user clicks on the "<< previous field" link in the Edit Field dialog.
 */
fields_ns.edit_prev_field = function() {
  if ($("#edit_field_template .prev_field").hasClass("disabled")) {
    return false;
  }

  // only let the user change fields if this page passes validation
  if (!fields_ns.validate_field()) {
    return false;
  }

  // get the previous field and update the Edit Field dialog
  var previous_row_group = sortable_ns._get_previous_row_group(fields_ns.__current_editing_row_group);
  fields_ns.edit_field(previous_row_group);
}


/**
 * Called when the user clicks on the "next field >>" link in the Edit Field dialog.
 */
fields_ns.edit_next_field = function() {
  if ($("#edit_field_template .next_field").hasClass("disabled")) {
    return false;
  }

  // only let the user change fields if this page passes validation
  if (!fields_ns.validate_field()) {
    return false;
  }

  // get the next field and update the Edit Field dialog
  var next_row_group = sortable_ns._get_next_row_group(fields_ns.__current_editing_row_group);
  fields_ns.edit_field(next_row_group);
}


fields_ns.save_changes = function() {
  // 1. call Ajax query to save it
  // 2. if successful, update the parent page
  // 3. display message in page saying "saved!"

  // if nothing's been changed, just close the popup. We also show a "saved!" message because it feels
  // more natural to the user that way
  if (fields_ns.memory.changed_field_ids.length == 0) {
    $("#edit_field_template").dialog("close");
    ft.display_message("ft_message", 1, g.messages["notify_field_changes_saved"]);
    return;
  }

  // only let the user change fields if this page passes validation
  if (!fields_ns.validate_field()) {
    return false;
  }

  $.ajax({
    url:      g.root_url + "/global/code/actions.php",
    data:     { form_id: page_ns.form_id, action: "update_form_fields", data: fields_ns.memory },
    type:     "POST",
    dataType: "json",
    success:  fields_ns.save_changes_response,
    error:    function(xhr, text_status, error_thrown) {
      ft.dialog_activity_icon($("#edit_field_template"), "hide");
      $("#edit_field_template").dialog("close");
      ft.error_handler(xhr, text_status, error_thrown);
    }
  });

  ft.dialog_activity_icon($("#edit_field_template"), "show");
}


fields_ns.save_changes_response = function(data) {
  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  // if there were no problems updating the fields, we just update the parent page with the latest values
  // and close the dialog
  if (data.success == 1) {
    for (var i=0; i<fields_ns.memory.changed_field_ids.length; i++) {
      var field_id = fields_ns.memory.changed_field_ids[i];

      if (typeof fields_ns.memory["field_" + field_id].tab1 != "undefined" && fields_ns.memory["field_" + field_id].tab1 != null) {
        $("#field_" + field_id + "_include_on_redirect").attr("checked", "");
        $.each(fields_ns.memory["field_" + field_id].tab1, function(index, info) {
          switch (info.name) {
            case "edit_field__display_text":
              $("#field_" + field_id + "_display_name").val(info.value);
              break;
            case "edit_field__field_name":
              $("#field_" + field_id + "_name").val(info.value);
              break;
            case "edit_field__field_type":
              $("#field_" + field_id + "_type_id").val(info.value);
              // we also update the old field_type_id value. So, if the user ends up clicking "save" from the main
              // page, the server doesn't try to do any extra clean-up work to delete the old field type-dependent settings
              $("#old_field_" + field_id + "_type_id").val(info.value);
              break;
            case "edit_field__pass_on":
              $("#field_" + field_id + "_include_on_redirect").attr("checked", "checked");
              break;
            case "edit_field__field_size":
              $("#old_field_" + field_id + "_size").val(info.value);
              $("#field_" + field_id + "_size").val(info.value);
              break;
            case "edit_field__data_type":
              $("#field_" + field_id + "_data_type").val(info.value);
              break;
            case "edit_field__db_column":
              $("#old_col_" + field_id + "_name").val(info.value);
              $("#col_" + field_id + "_name").val(info.value);
              break;
          }
        });
      }
    }

    ft.dialog_activity_icon($("#edit_field_template"), "hide");
    $("#edit_field_template").dialog("close");
    ft.display_message("ft_message", 1, g.messages["notify_field_changes_saved"]);
    fields_ns.memory = {};
    fields_ns.memory.changed_field_ids = [];

    // we ALSO empty the cache here. Strictly speaking, this isn't necessary: we could just be smart and update the
    // cache with the updated values, but (a) this keeps it simple and (b) ensures that one re-editing we get
    // the latest content
    fields_ns.cached_loaded_extended_field = [];
  } else {
    ft.display_message("ft_message", 0, g.messages["notify_error_saving_fields"]);
  }
}


/**
 * This is called any time a user clicks from one field to the next in the Edit Field dialog, or clicks
 * Save Changes. It checks that the values entered in the current field are valid. Namely:
 *   - all required fields are filled in (tab 1)
 *   - if an option list or form field setting is defined, it's also filled in
 *
 * @return boolean passes validation or not
 */
fields_ns.validate_field = function() {
  var errors = [];
  var first_tab_with_error = 1;

  var is_system_field = $(".sr_order[value=" + fields_ns.__current_field_id + "]").closest(".row_group").hasClass("system_field");
  var display_text = $.trim($("#edit_field__display_text").val());
  if (display_text == "") {
    errors.push(["#edit_field__display_text", "validation_no_display_text_single"]);
  }

  if (!is_system_field) {
    var form_field = $.trim($("#edit_field__field_name").val());
    if (form_field == "") {
      errors.push(["#edit_field__field_name", "validation_no_form_field_single"]);
    }
    var db_column  = $.trim($("#edit_field__db_column").val());
    if (db_column == "") {
      errors.push(["#edit_field__db_column", "validation_no_db_column_single"]);
    }

    // TODO: intelligently validate database column name here
  }

  if (errors.length > 0) {
    $("#edit_field_template_message").removeClass("hidden");
    ft.change_inner_tab(first_tab_with_error, "edit_field");
    $(errors[0][0]).focus();

    var error_str = "";
    if (errors.length == 1) {
      error_str = g.messages[errors[0][1]];
    } else {
      for (var i=0; i<errors.length; i++) {
        error_str += "&bull; " + g.messages[errors[i][1]] + "<br />";
      }
    }
    ft.display_message("edit_field_template_message", 0, error_str);
  }

  return errors.length == 0;
}


fields_ns.load_form_fields = function(params) {
  var params = $.extend({
    // required
    form_id: null,

    // optional
    field_id:    null,
    field_order: null
  }, params);

  // always remove the ft prefix
  params.form_id = params.form_id.replace(/^ft/, "");

  $("#edit_field__form_options").html("<div class=\"loading_small\"></div>");
  $.ajax({
    url:      g.root_url + "/global/code/actions.php",
    type:     "POST",
    dataType: "json",
    data: {
      action:      "get_form_fields",
      form_id:     params.form_id,
      field_id:    params.field_id,
      field_order: params.field_order
    },
    success:  fields_ns.get_form_fields_response,
    error:    ft.error_handler
  });
}


fields_ns.get_form_fields_response = function(data) {
  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  fields_ns.form_fields["form_" + data.form_id] = data.fields;

  // if the user still has this form selected in the Edit Field dialog, load the info into a dropdown
  if ($(".option_list_or_form_field :selected").val() == "ft" + data.form_id.toString()) {
    fields_ns.generate_form_fields_section({ form_id: data.form_id, field_id: data.field_id, field_order: data.field_order });
  }
}

// this is called once we know that a form's fields have been loaded. It populates the section in the
// Edit Field dialog that allows the user to select a form field to be the source data of a dropdown
fields_ns.generate_form_fields_section = function(params) {
  params = $.extend({
    // required
    form_id:     null,

    // optional
    field_id:    "",
    field_order: ""
  }, params);

  var fields = fields_ns.form_fields["form_" + params.form_id];

  // to create the name attribute of these fields, we do something a little fussy. The name is requires so that
  // this value will be stored by the automatic serializer which fires whenever the tab content changes. Since
  // (in theory) a field type can have any number of Option Lists, we want to give each form field dropdown +
  // form field order dropdown their own unique name so that the server-side code can figure out what's stored
  // by what field. But we only have the current field ID + form_id at our disposal. So, we find it by parsing
  // the DOM.
  var setting_id = null;
  $("#edit_field__form_options").parent().find(".option_list_or_form_field").each(function() {
    setting_id = $(this).attr("id").replace(/edit_field__setting_/, "");
  });

  var field_dd = "<select name=\"edit_field__setting_" + setting_id + "_field_id\">"
    + "<option value=\"\">" + g.messages["phrase_please_select"] + "</option>";

  $.each(fields, function(value, text) {
    var selected = (value.toString() == params.field_id.toString()) ? " selected" : "";
    field_dd += "<option value=\"" + value + "\"" + selected + ">" + text + "</option>";
  });
  field_dd += "</select>";

  var html = "<table class=\"grey_box\">"
    + "<tr>"
      + "<td width=\"100\">" + g.messages["phrase_select_field"] + "</td>"
      + "<td>" + field_dd + "</td>"
    + "</tr>"
    + "<tr>"
      + "<td>" + g.messages["word_order"] + "</td>"
      + "<td><select name=\"edit_field__setting_" + setting_id + "_field_order\">"
        + "<option value=\"ASC\"" + ((params.field_order == "ASC") ? " selected" : "") + ">ASC</option>"
        + "<option value=\"DESC\"" + ((params.field_order == "DESC") ? " selected" : "") + ">DESC</option>"
      + "</select></td>"
    + "</tr>";

  $("#edit_field__form_options").html(html);
}


fields_ns.click_use_default = function(el) {
  var is_checked = $(el).attr("checked");
  var setting_id = $(el).attr("id").replace(/^edit_field__use_default_value_/, "");
  var settings_selectors = '#edit_field__setting_' + setting_id + ',[id^="edit_field__setting_' + setting_id + '_"]';
  if (is_checked) {
    $(settings_selectors).attr("disabled", "disabled").addClass("light_grey");
  } else {
    $(settings_selectors).attr("disabled", "").removeClass("light_grey");
    $("#edit_field__setting_" + setting_id).focus();
  }
  $(el).trigger("change");
}


/**
 * This is called onblur after the user changed a field type AND after the actual field settings are returned and loaded
 * for a field. It checks for both conditions; when they're both completed, it
 */
fields_ns.check_shared_characteristics = function() {
  var curr_ft_id = fields_ns.__current_field_type_id;
  var last_ft_id = fields_ns.__last_field_type_id;

  if (curr_ft_id == last_ft_id) {
    return;
  }
  if (!fields_ns.__check_shared_characteristics_cond1 || !fields_ns.__check_shared_characteristics_cond2) {
    return;
  }
  if (typeof page_ns.field_settings["field_type_" + curr_ft_id] == "undefined" ||
      typeof page_ns.field_settings["field_type_" + last_ft_id] == "undefined") {
    return;
  }

  var curr_setting_ids = [];
  for (var i=0; i<page_ns.field_settings["field_type_" + curr_ft_id].length; i++) {
    var curr_setting_id = page_ns.field_settings["field_type_" + curr_ft_id][i].setting_id;
    if (page_ns.shared_characteristics["s" + curr_setting_id] != undefined) {
      curr_setting_ids.push(curr_setting_id);
    }
  }
  var last_setting_ids = [];
  for (var i=0; i<page_ns.field_settings["field_type_" + last_ft_id].length; i++) {
    var last_setting_id = page_ns.field_settings["field_type_" + last_ft_id][i].setting_id;
    if (page_ns.shared_characteristics["s" + last_setting_id] != undefined) {
      last_setting_ids.push(last_setting_id);
    }
  }

  if (!curr_setting_ids.length || !last_setting_ids.length) {
    return;
  }

  // So. We have the subset of all setting IDs of both the PREVIOUS and CURRENT field types that have
  // associated shared characteristics. Now we need to determine which are actually shared so we can copy over the old
  // setting values

  var found_map = [];
  for (var i=0; i<last_setting_ids.length; i++) {
    var last_shared_characteristic_ids = page_ns.shared_characteristics["s" + last_setting_ids[i]];

    for (var j=0; j<curr_setting_ids.length; j++) {
      var curr_shared_characteristic_ids = page_ns.shared_characteristics["s" + curr_setting_ids[j]];
      for (var k=0; k<curr_shared_characteristic_ids.length; k++) {
        // FINALLY, we know there's a map. Make a note of the old & new setting IDs
        if ($.inArray(curr_shared_characteristic_ids[k], last_shared_characteristic_ids) != -1) {
          found_map.push([last_setting_ids[i], curr_setting_ids[j]]);
        }
      }
    }
  }

  var old_values = fields_ns.memory["field_" + fields_ns.__current_field_id].tab2;
  for (var i=0; i<found_map.length; i++) {
    var old_setting_id = found_map[i][0];
    var new_setting_id = found_map[i][1];

    for (var j=0; j<old_values.length; j++) {
      if (old_values[j].name == undefined || old_values[j].value == undefined) {
        continue;
      }
      if (old_values[j].name != "edit_field__setting_" + old_setting_id) {
        continue;
      }
      $("#edit_field__use_default_value_" + new_setting_id).attr("checked", "");
      $("#edit_field__setting_" + new_setting_id).val(old_values[j].value).removeClass("light_grey").attr("disabled", "");
    }
  }

  fields_ns.__check_shared_characteristics_cond1 = false;
  fields_ns.__check_shared_characteristics_cond2 = false;
}


fields_ns.generate_field_type_validation_table = function(field_type_id) {
  var html = "";
  if (typeof page_ns.field_validation["field_type_" + field_type_id] == "undefined") {
    html = "<span class=\"medium_grey\"><i>" + g.messages["phrase_field_type_no_validation"] + "</span>";
  } else {
    html += "<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\">"
            + "<tr>"
              + "<td width=\"20\"></td>"
              + "<td width=\"180\" class=\"medium_grey\"><i>" + g.messages["phrase_validation_rule"] + "</i></td>"
              + "<td class=\"medium_grey\"><i>" + g.messages["text_error_message_to_show"] + "</i></td>"
            + "</tr>";

    var rules = page_ns.field_validation["field_type_" + field_type_id];
    for (var i=0; i<rules.length; i++) {
      var rule_id = rules[i].rule_id;
      var label   = rules[i].label;
      html += "<tr>"
              + "<td><input type=\"checkbox\" name=\"edit_field__v_" + rule_id + "\" id=\"edit_field__v_" + rule_id + "\" class=\"v_checked\" /></td>"
              + "<td><label for=\"edit_field__v_" + rule_id + "\">" + label + "</label></td>"
              + "<td><input type=\"text\" id=\"edit_field__v_" + rule_id + "_message\" name=\"edit_field__v_" + rule_id + "_message\" class=\"validation_error_message light_grey\" disabled=\"disabled\" /></td>"
            + "</tr>"
    }

    html += "</table>";
  }

  return html;
}


fields_ns._get_default_error_message = function(field_type_id, rule_id) {
  var error = "";
  var rules = page_ns.field_validation["field_type_" + field_type_id];
  for (var i=0; i<rules.length; i++) {
    if (rules[i].rule_id == rule_id) {
      error = rules[i].error;
      break;
    }
  }
  return error;
}


/**
 * Helper function to return the number of fields on the page.
 */
fields_ns.get_num_fields_on_page = function() {
  return $("#rows .row_group").length;
}


/**
 * Added in 2.2.3. This is used when the $g_max_ft_form_fields global is set, which limits the number of
 * fields that a form may contain. This is called on page load and any time a user adds/deletes a field.
 */
fields_ns.update_max_field_count = function() {
  if (!fields_ns.limit_fields_enabled) {
    return;
  }

  var remaining_fields = fields_ns.max_fields - fields_ns.num_fields;
  $("#max_field_count").html(fields_ns.max_fields);
  $("#curr_field_count").html(fields_ns.num_fields);

  if (remaining_fields <= 0) {
    $("#add_num_fields,#new_field_position,#group_new_fields,#add_field").attr("disabled", "disabled");
  } else {
    $("#add_num_fields,#new_field_position,#group_new_fields,#add_field").removeAttr("disabled");
  }
}


fields_ns.delete_field = function(el) {
  sortable_ns.delete_row("edit_fields", el);
  fields_ns.num_fields--;
  setTimeout(function() { fields_ns.update_max_field_count(); }, 500);
}
