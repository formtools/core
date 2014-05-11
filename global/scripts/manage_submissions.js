/**
 * File: manage_submissions.js
 *
 * Contains assorted javascript functions used in the administrator and client Submission Listing
 * and edit submission pages.
 */

// our namespace for manage submission functions
if (typeof ms == "undefined") {
  var ms = {};
}


/**
 * Validation function called when the user clicks the "download" or "view" buttons for the Excel
 * download and Printer-Friendly page. If the "selected" option is selected, it checks that the
 * user has selected at least one submission.
 *
 * TODO
 *
 * @param string action what to do: "print_preview" / ""
 * @param string select_option
 * @return boolean
 */
ms.check_selected = function(action, select_option) {
  var selected_ids = ms.get_selected_submissions();
  if (select_option == "all") {
    return true;
  }
  if (!selected_ids.length) {
    if (action == "print_preview") {
      alert(g.messages["validation_select_rows_to_view"]);
    } else {
      alert(g.messages["validation_select_rows_to_download"]);
    }
    return false;
  }

  return true;
}


/**
 * Helper function which returns an array of the submission rows that have been selected ON
 * THE PAGE. All submission IDs, regardless of page are stored in ms.selected_submission_ids
 *
 * @return array selected_ids
 */
ms.get_selected_submissions = function() {
  var selected_ids = [];
  $(".select_row_cb:checked").each(function() { selected_ids.push(parseInt(this.value)); });
  return selected_ids;
}


/**
 * Checks that the user has selected at least one submission and confirms they really want
 * to delete them.
 *
 * @param string page
 */
ms.delete_submissions = function(page) {
  // find out if there are submissions selected on another page
  var num_selected_on_other_pages = 0;
  var selected_ids_on_page = ms.get_selected_submissions();

  if (ms.all_submissions_in_result_set_selected) {
    // find out how many submissions in the ms.all_submissions_selected_omit_list are found
    // on other pages
    var other_pages_omit_list_count = 0;
    for (var i=0; i<ms.all_submissions_selected_omit_list.length; i++) {
      if ($.inArray(ms.all_submissions_selected_omit_list[i], ms.page_submission_ids) == -1) {
        other_pages_omit_list_count++;
      }
    }

    // now calculate the total selected on other pages
    num_selected_on_other_pages = ms.search_num_results - (other_pages_omit_list_count + ms.num_results_per_page);
  } else {
    for (var i=0; i<ms.selected_submission_ids.length; i++) {
      if ($.inArray(parseInt(ms.selected_submission_ids[i]), selected_ids_on_page) == -1) {
        num_selected_on_other_pages++;
      }
    }
  }


  // if there are none selected, alert the user and return
  if (!ms.selected_submission_ids.length && !num_selected_on_other_pages) {
    ft.display_message("ft_message", false, g.messages["validation_select_submissions_to_delete"]);
    return;
  }

  if (num_selected_on_other_pages > 0) {
    // if the user has submissions selected on this page AND other pages, give them
    // the option to either delete ALL submissions or just those selected on this page.
    if (selected_ids_on_page.length) {
      var message = g.messages["confirm_delete_submissions_on_other_pages"].replace(/\{\$num_selected_on_page\}/, selected_ids_on_page.length);
      message = message.replace(/\{\$num_selected_on_other_pages\}/, num_selected_on_other_pages);
      message = message.replace(/\{\$delete_all_submissions_onclick\}/, "onclick=\"window.location='?delete'\"");
      var id_string = selected_ids_on_page.join(",");
      message = message.replace(/\{\$delete_submissions_on_page_onclick\}/, "onclick=\"window.location='?delete=" + id_string + "'\"");
      ft.display_message("ft_message", false, message);
      return;
    } else {
      var message = g.messages["confirm_delete_submissions_on_other_pages2"].replace(/\{\$num_selected_on_page\}/, selected_ids_on_page.length);
      message = message.replace(/\{\$num_selected_on_other_pages\}/, num_selected_on_other_pages);
      message = message.replace(/\{\$delete_all_submissions_onclick\}/, "onclick=\"window.location='?delete'\"");
      ft.display_message("ft_message", false, message);
      return;
    }
  }

  var message = "";
  if (ms.selected_submission_ids.length == 1) {
    message = g.messages["confirm_delete_submission"];
  } else {
    message = g.messages["confirm_delete_submissions"];
  }

  ft.create_dialog({
    dialog:    ft.check_url_dialog,
    title:     g.messages["phrase_please_confirm"],
    content:   message,
    popup_type: "warning",
    buttons: [{
      text: g.messages["word_yes"],
      click: function() {
        window.location = "?delete";
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


/**
 * Called on the edit submission page; lets a user delete the submission.
 *
 * @param integer submission_id
 * @param string target_webpage where to link to after deleting the submission
 */
ms.delete_submission = function(submission_id, target_webpage) {
  ft.create_dialog({
    dialog:    ft.check_url_dialog,
    title:     g.messages["phrase_please_confirm"],
    content:   g.messages["confirm_delete_submission"],
    popup_type: "warning",
    buttons: [{
      text: g.messages["word_yes"],
      click: function() {
        window.location = target_webpage + "?delete=" + submission_id;
      }
    },
    {
      text: g.messages["word_no"],
      click: function() {
        $(this).dialog("close");
      }
    }]
  });

  return false;
}


/**
 * Called on page load, it ensures that the selected / unselected rows have the appropriate class,
 * and adds the appropriate event handlers to the rows.
 */
ms.init_submissions_page = function() {
  if (ms.page_submission_ids.length == 0) {
    return;
  }

  if ($("#search_field").length) {
    ms.change_search_field($("#search_field").val());
  }

  // check the selected rows and make sure they have the appropriate class
  var all_checked = true;
  $(".select_row_cb").each(function() {
    if (this.checked) {
      $(this).closest("tr").addClass("selected_row_color").removeClass("unselected_row_color");
    } else {
      all_checked = false;
    }
  });

  // if all the rows are selected
  if (all_checked) {
    if (ms.all_submissions_in_result_set_selected) {
      if (ms.all_submissions_selected_omit_list.length == 0) {
        ms._update_select_all_button("all_in_result_set_selected");
      } else {
        ms._update_select_all_button("all_on_page_selected");
      }
    } else {
      if (ms.selected_submission_ids.length == ms.search_num_results) {
        ms._update_select_all_button("all_in_result_set_selected");
      } else {
        ms._update_select_all_button("all_on_page_selected");
      }
    }
  }

  ms.update_display_row_count();


  $(".select_row_cb").bind("click", function() {
    ms.select_row(this, ms.num_results_per_page);
  });

  $("#submissions_table tr:gt(0)").bind("click", function(e) {
    if (e.target.nodeName == "INPUT" || e.target.nodeName == "A")
      return;

    var select_row_cb = $(this).find(".select_row_cb");
    select_row_cb.attr("checked", select_row_cb.attr("checked") ? false : true);
    ms.select_row(select_row_cb[0], ms.num_results_per_page);
  });
}


/**
 * Selects / unselects an individual submission row, and changes the row colour to clearly show
 * what's selected and what isn't. It keep a log of all selected rows - REGARDLESS OF PAGE -
 * in the ms.selected_submission_ids array. It also updates the "X row(s) selected" display text.
 *
 * @param element the checkbox
 * @param integer results_per_page the number of results in the page
 */
ms.select_row = function(cb, results_per_page) {
  var submission_id = cb.value;
  if (cb.checked) {
    ft.toggle_unique_class($(cb).closest("tr")[0], "selected_row_color", ["selected_row_color", "unselected_row_color"]);

    if (ms.all_submissions_in_result_set_selected) {
      ms.all_submissions_selected_omit_list = $.grep(ms.all_submissions_selected_omit_list, function(i) { return i != submission_id; });
    } else {
      ms.selected_submission_ids.push(submission_id);
    }
    var selected_ids = ms.get_selected_submissions();

    // all submissions in result set are selected
    if (ms.all_submissions_in_result_set_selected) {
      // if the user just selected the only row that wasn't selected on the page, update the select all button
      // based on whether all results are now selected or not
      if (ms.all_submissions_selected_omit_list.length == 0) {
        ms._update_select_all_button("all_in_result_set_selected");
      } else {
        // case 1: multiple pages, and all on page are selected
        if (ms.page_submission_ids.length == selected_ids.length) {
          ms._update_select_all_button("all_on_page_selected");
        // case 2: single page (total results <= max num on page) and all are selected
        } else if (ms.search_num_results == selected_ids.length) {
          ms._update_select_all_button("all_on_page_selected");
        }
      }
    } else {
      if (ms.selected_submission_ids.length == ms.search_num_results) {
        ms._update_select_all_button("all_in_result_set_selected");
      // case 2: multiple pages, and all on page are selected
      } else if (ms.page_submission_ids.length == selected_ids.length) {
        ms._update_select_all_button("all_on_page_selected");
      // case 3: single page (total results <= max num on page) and all are selected
      } else if (ms.search_num_results == selected_ids.length) {
        ms._update_select_all_button("all_on_page_selected");
      }
    }

    // pass this individual submission ID to the server for storage
    $.ajax({
      url:   g.root_url + "/global/code/actions.php?action=select_submission&form_id=" + ms.form_id + "&submission_id=" + submission_id,
      type:  "GET",
      error: function() { alert("Couldn't load page: " + page_url); }
    });
  }
  else
  {
    ft.toggle_unique_class($(cb).closest("tr")[0], "unselected_row_color", ["selected_row_color","unselected_row_color"]);

    if (ms.all_submissions_in_result_set_selected) {
      if ($.inArray(submission_id, ms.all_submissions_selected_omit_list) == -1) {
        ms.all_submissions_selected_omit_list.push(submission_id);
      }
    } else {
      ms.selected_submission_ids = $.grep(ms.selected_submission_ids, function(i) { return i != submission_id; });
    }

    ms._update_select_all_button("all_on_page_not_selected");

    // remove this row from sessions
    $.ajax({
      url:   g.root_url + "/global/code/actions.php?action=unselect_submission&form_id=" + ms.form_id + "&submission_id=" + submission_id,
      type:  "GET",
      error: function() { alert("Couldn't load page: " + page_url); }
    });
  }

  ms.update_display_row_count();
}


/**
 * Updated the "X row(s) displayed" text.
 *
 * @return num_selected returns the number of currently selected rows.
 */
ms.update_display_row_count = function() {
  var num_selected = 0;
  if (ms.all_submissions_in_result_set_selected) {
    num_selected = ms.search_num_results - ms.all_submissions_selected_omit_list.length;
  } else {
    num_selected = ms.selected_submission_ids.length;
  }

  if (num_selected == 0) {
    ft.toggle_unique_class($("#display_num_selected_rows")[0], "light_grey", ["green", "light_grey"]);
    $("#display_num_selected_rows").html(g.messages["phrase_rows_selected"].replace(/\{\$num_rows\}/, "0"));
  } else if (ms.selected_submission_ids.length == 1 && !ms.all_submissions_in_result_set_selected) {
    ft.toggle_unique_class($("#display_num_selected_rows")[0], "green", ["green", "light_grey"]);
    $("#display_num_selected_rows").html(g.messages["phrase_row_selected"].replace(/\{\$num_rows\}/, "1"));
  } else {
    ft.toggle_unique_class($("#display_num_selected_rows")[0], "green", ["green", "light_grey"]);
    $("#display_num_selected_rows").html(g.messages["phrase_rows_selected"].replace(/\{\$num_rows\}/, num_selected));
  }

  return num_selected;
}


/**
 * Selects all submission rows on the page. This is called ONLY when the user clicks on the "Select All" button.
 */
ms.select_all_on_page = function() {
  $(".select_row_cb").each(function() {
    $(this).attr("checked", "checked").closest("tr").addClass("selected_row_color").removeClass("unselected_row_color");

    var curr_id = this.value;
    ms.all_submissions_selected_omit_list = $.grep(ms.all_submissions_selected_omit_list, function(i) { return i != curr_id; });
    if ($.inArray(curr_id, ms.selected_submission_ids) == -1) {
      ms.selected_submission_ids.push(curr_id);
    }
  });

  if (ms.all_submissions_in_result_set_selected) {
    // if the user just selected the only row that wasn't selected on the page, update the select all button
    // based on whether all results are now selected or not
    if (ms.all_submissions_selected_omit_list.length == 0) {
      ms._update_select_all_button("all_in_result_set_selected");
    } else {
      ms._update_select_all_button("all_on_page_selected");
    }
  } else {
    if (ms.selected_submission_ids.length == ms.search_num_results) {
      ms._update_select_all_button("all_in_result_set_selected");
    } else {
      ms._update_select_all_button("all_on_page_selected");
    }
  }

  // pass these submission IDs to the server to store in sessions
  $.ajax({
    url:   g.root_url + "/global/code/actions.php?action=select_submissions&form_id=" + ms.form_id
             + "&submission_ids=" + ms.page_submission_ids.join(","),
    type:  "GET",
    error: function() { alert("Couldn't load page: " + page_url); }
  });

  ms.update_display_row_count();
}


/**
 * Selects all submissions in the current result set.
 */
ms.select_all_in_result_set = function() {
  ms._update_select_all_button("all_in_result_set_selected");
  ms.all_submissions_selected_omit_list = [];

  // pass these submission IDs to the server to store in sessions
  $.ajax({
    url:   g.root_url + "/global/code/actions.php?action=select_all_submissions&form_id=" + ms.form_id,
    type:  "GET",
    error: function () { alert("Couldn't load page: " + page_url); }
  });

  ms.update_display_row_count();
}


/**
 * Unselects all submission rows on the page.
 */
ms.unselect_all = function() {
  $(".select_row_cb").each(function() {
    $(this).attr("checked", "").closest("tr").addClass("unselected_row_color").removeClass("selected_row_color");
  });

  ms._update_select_all_button("unselect_all");
  ms.all_submissions_in_result_set_selected = false;

  // pass this to the server to store in sessions
  $.ajax({
    url:  g.root_url + "/global/code/actions.php?action=unselect_all_submissions&form_id=" + ms.form_id,
    type: "GET"
  });

  ms.selected_submission_ids = [];
  ms.update_display_row_count();
}


/**
 * Used to hide/show the additional date search options. With 2.1.0, any field can be a date field. We identify
 * them via a "date" class on the <option> element.
 *
 * @param string choice the selected column name
 */
ms.change_search_field = function(val) {
  if (val.match(/\|date$/)) {
    $("#search_dropdown_section").show();
  } else {
    $("#search_dropdown_section").hide();
  }
}


/**
 * Called internally. This updates the appearance and functionality of the "select all" button, which
 * acts differently based on what rows are selected at any give time.
 */
ms._update_select_all_button = function(flag) {
  if (flag == "all_on_page_selected") {
    $("#select_button").val(g.messages["phrase_select_all_X_results"].replace(/\{\$numresults\}/, ms.search_num_results))
      .attr("disabled", "")
      .removeAttr("onclick")
      .unbind("click")
      .bind("click", function() { ms.select_all_in_result_set(); });
    ft.toggle_unique_class($("#select_button")[0], "blue", ["blue","light_grey","black"]);
  }
  if (flag == "all_on_page_not_selected") {
    $("#select_button").val(g.messages["phrase_select_all_on_page"])
      .attr("disabled", "")
      .removeAttr("onclick")
      .unbind("click")
      .bind("click", function() { ms.select_all_on_page(); });
    ft.toggle_unique_class($("#select_button")[0], "black", ["blue","light_grey","black"]);
  }

  // only ever gets called when the user selected the "Select All X results"
  if (flag == "all_in_result_set_selected") {
    $("#select_button").val(g.messages["phrase_all_X_results_selected"].replace(/\{\$numresults\}/, ms.search_num_results))
      .attr("disabled", "disabled");
    ms.all_submissions_in_result_set_selected = true;
    ft.toggle_unique_class($("#select_button")[0], "light_grey", ["blue","light_grey","black"]);
  }

  // only ever called when the user clicks "Unselect All" button
  if (flag == "unselect_all") {
    ms.selected_submission_ids = [];
    ms.all_submissions_in_result_set_selected = false;
    $("#select_button").val(g.messages["phrase_select_all_on_page"])
      .attr("disabled", "")
      .removeAttr("onclick")
      .unbind("click")
      .bind("click", function() { ms.select_all_on_page(); });
    ft.toggle_unique_class($("#select_button")[0], "black", ["blue","light_grey","black"]);
  }
}


/**
 * Called from the edit submission page: sends an email.<b>
 *
 * @param integer submission_id
 * @param integer email_id
 */
ms.edit_submission_page_send_email = function(submission_id) {
  var email_id = $("#form_tools_email_template_id").val();

  if (!email_id) {
    ft.display_message("ft_message", false, g.messages["notify_no_email_template_selected"]);
    return;
  }
  var page_url = g.root_url + "/global/code/actions.php?action=edit_submission_send_email&submission_id="
    + submission_id + "&email_id=" + email_id + "&form_id=" + $("#form_id").val();

  $.ajax({
    url:      page_url,
    type:     "GET",
    dataType: "json",
    success:  ms.email_sent,
    error:    function() { alert("Couldn't load page: " + page_url); }
  });
}


/**
 * Called after an email has been sent, or failed to be sent due to session timeout.
 */
ms.email_sent = function(data) {

  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  ft.display_message("ft_message", data.success, data.message);
}


// ensures that if the user's doing a search on a non-date field, they have to have entered in a search string
ms.check_search_keyword = function() {
  var search_keyword_field = $("#search_keyword");
  var curr_value = $("#search_field").val();
  if (!curr_value.match(/\|date$/) && !search_keyword_field.val()) {
    return [[search_keyword_field[0], g.messages["validation_please_enter_search_keyword"]]];
  } else {
    return true;
  }
}

// checks that if a user is doing a search on a date field, there's a valid date or date range in the date field
ms.check_valid_date = function() {
  var curr_value = $("#search_field").val();
  if (!curr_value.match(/\|date$/)) {
    return true;
  }

  var date_field = $("#search_date");
  var val = date_field.val();
  if (!val.match(/^\d{1,2}?\/\d{1,2}\/\d{4}$/) &&
      !val.match(/^\d{1,2}?\/\d{1,2}\/\d{4}\s-\s\d{1,2}?\/\d{1,2}\/\d{4}$/)) {
    return [[date_field[0], g.messages["notify_invalid_search_dates"]]];
  } else {
    return true;
  }
}


ms.submit_form = function(f, error_info) {
  if (!error_info.length) {
    return true;
  }
  var first_el = null;
  var error_str = "<ul>";
  for (var i=0; i<error_info.length; i++) {
    error_str += "<li>" + error_info[i][1] + "</li>";
    if (first_el == null) {
      first_el = error_info[i][0];
    }
  }
  error_str += "</ul>";

  ft.create_dialog({
	title:      g.messages["phrase_validation_error"],
    popup_type: "error",
    width:      450,
    content:    error_str,
    buttons:    [{
      text:  g.messages["word_close"],
      click: function() {
        $(this).dialog("close");
        $(first_el).focus().select();
      }
    }]
  })

  return false;
}
