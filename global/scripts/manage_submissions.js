/**
 * File: manage_submissions.js
 *
 * Contains assorted javascript functions used in the administrator and client Submission Listing
 * and edit submission pages.
 */

// our namespace for manage submission functions
if (typeof ms == "undefined")
  var ms = {};


/**
 * Validation function called when the user clicks the "download" or "view" buttons for the Excel
 * download and Printer-Friendly page. If the "selected" option is selected, it checks that the
 * user has selected at least one submission.
 *
 * @param string action what to do: "print_preview" / ""
 * @param string select_option
 * @return boolean
 */
ms.check_selected = function(action, select_option)
{
  var selected_ids = ms.get_selected_submissions();

  if (select_option == "all")
    return true;

  if (!selected_ids.length)
  {
    if (action == "print_preview")
      alert(g.messages["validation_select_rows_to_view"]);
    else
      alert(g.messages["validation_select_rows_to_download"]);

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
ms.get_selected_submissions = function()
{
  var selected_ids = [];
  for (var i=0; i<ms.page_submission_ids.length; i++)
  {
    var curr_id = ms.page_submission_ids[i];
    if ($('submission_cb_' + curr_id).checked)
      selected_ids.push(curr_id);
  }

  return selected_ids;
}


/**
 * Checks that the user has selected at least one submission and confirms they really want
 * to delete them.
 *
 * @param string page
 */
ms.delete_submissions = function(page)
{
  var selected_ids = ms.get_selected_submissions();

  // if empty, alert the user and return
  if (!ms.selected_submission_ids.length)
  {
    ft.display_message("ft_message", false, g.messages["validation_select_submissions_to_delete"]);
    return;
  }

  if (ms.selected_submission_ids.length == 1)
    var answer = confirm(g.messages["confirm_delete_submission"]);
  else
    var answer = confirm(g.messages["confirm_delete_submissions"]);

  var submission_ids = selected_ids.join(",");

  if (answer)
    window.location = '?delete';
}


/**
 * Called on the edit submission page; lets a user delete the submission.
 *
 * @param integer submission_id
 */
ms.delete_submission = function(submission_id)
{
  if (confirm(g.messages["confirm_delete_submission"]))
    window.location = "submissions.php?delete=" + submission_id;

  return false;
}


/**
 * Called on page load, it ensures that the selected / unselected rows have the appropriate class.
 */
ms.init_page = function()
{
  var all_checked = true;

  if (ms.page_submission_ids.length == 0)
    return;

  ms.change_search_field($("search_field").value);

  // check the selected rows and make sure they have the appropriate class
  for (var i=0; i<ms.page_submission_ids.length; i++)
  {
    var curr_id = ms.page_submission_ids[i];

    if ($("submission_cb_" + curr_id).checked)
      $("submission_row_" + curr_id).className = "selected_row_color";
    else
      all_checked = false;
  }

  // if all the rows are selected
  if (all_checked)
  {
    if (ms.all_submissions_in_result_set_selected)
    {
      if (ms.all_submissions_selected_omit_list.length == 0)
        ms._update_select_all_button("all_in_result_set_selected");
      else
        ms._update_select_all_button("all_on_page_selected");
    }
    else
    {
      if (ms.selected_submission_ids.length == ms.search_num_results)
        ms._update_select_all_button("all_in_result_set_selected");
      else
        ms._update_select_all_button("all_on_page_selected");
    }
  }

  ms.update_display_row_count();
}


/**
 * Selects / unselects an individual submission row, and changes the row colour to clearly show
 * what's selected and what isn't. It keep a log of all selected rows - REGARDLESS OF PAGE -
 * in the ms.selected_submission_ids array. It also updates the "X row(s) selected" display text.
 *
 * @param integer id the unique submission ID
 * @param integer results_per_page the number of results in the page
 */
ms.select_row = function(id, results_per_page)
{
  if (document.current_form['submission_cb_' + id].checked)
  {
    ft.toggle_unique_class($("submission_row_" + id), "selected_row_color", ["selected_row_color","unselected_row_color"]);

    if (ms.all_submissions_in_result_set_selected)
      ms.all_submissions_selected_omit_list = $(ms.all_submissions_selected_omit_list).without(id);
    else
      ms.selected_submission_ids.push(id);

    var selected_ids = ms.get_selected_submissions();

    // all submissions in result set are selected
    if (ms.all_submissions_in_result_set_selected)
    {
      // if the user just selected the only row that wasn't selected on the page, update the select all button
      // based on whether all results are now selected or not
      if (ms.all_submissions_selected_omit_list.length == 0)
        ms._update_select_all_button("all_in_result_set_selected");
      else
      {
        // case 1: multiple pages, and all on page are selected
        if (ms.page_submission_ids.length == selected_ids.length)
          ms._update_select_all_button("all_on_page_selected");

        // case 2: single page (total results <= max num on page) and all are selected
        else if (ms.search_num_results == selected_ids.length)
          ms._update_select_all_button("all_on_page_selected");
      }
    }
    else
    {
      if (ms.selected_submission_ids.length == ms.search_num_results)
        ms._update_select_all_button("all_in_result_set_selected");

      // case 2: multiple pages, and all on page are selected
      else if (ms.page_submission_ids.length == selected_ids.length)
        ms._update_select_all_button("all_on_page_selected");

      // case 3: single page (total results <= max num on page) and all are selected
      else if (ms.search_num_results == selected_ids.length)
        ms._update_select_all_button("all_on_page_selected");
    }

    // pass this individual submission ID to the server for storage
    var page_url = g.root_url + "/global/code/actions.php?action=select_submission&form_id=" + ms.form_id + "&submission_id=" + id;
    new Ajax.Request(page_url, {
      method: 'get',
      onFailure: function() { alert("Couldn't load page: " + page_url); }
    });
  }
  else
  {
    ft.toggle_unique_class($("submission_row_" + id), "unselected_row_color", ["selected_row_color","unselected_row_color"]);

    if (ms.all_submissions_in_result_set_selected)
    {
      if (!ms.all_submissions_selected_omit_list.include(id))
        ms.all_submissions_selected_omit_list.push(id);
    }
    else
      ms.selected_submission_ids = $(ms.selected_submission_ids).without(id);

    ms._update_select_all_button("all_on_page_not_selected");

    // remove this row from sessions
    var page_url = g.root_url + "/global/code/actions.php?action=unselect_submission&form_id=" + ms.form_id + "&submission_id=" + id;
    new Ajax.Request(page_url, {
      method: 'get',
      onFailure: function() { alert("Couldn't load page: " + page_url); }
    });
  }

  ms.update_display_row_count();
}


/**
 * Updated the "X row(s) displayed" text.
 *
 * @return num_selected returns the number of currently selected rows.
 */
ms.update_display_row_count = function()
{
  var num_selected = 0;
  if (ms.all_submissions_in_result_set_selected)
    num_selected = ms.search_num_results - ms.all_submissions_selected_omit_list.length;
  else
    num_selected = ms.selected_submission_ids.length;

  if (num_selected == 0)
  {
    ft.toggle_unique_class($("display_num_selected_rows"), "light_grey", ["green","light_grey"]);
    $("display_num_selected_rows").innerHTML = g.messages["phrase_rows_selected"].replace(/\{\$num_rows\}/, "0");
  }
  else if (ms.selected_submission_ids.length == 1 && !ms.all_submissions_in_result_set_selected)
  {
    ft.toggle_unique_class($("display_num_selected_rows"), "green", ["green","light_grey"]);
    $("display_num_selected_rows").innerHTML = g.messages["phrase_row_selected"].replace(/\{\$num_rows\}/, "1");
  }
  else
  {
    ft.toggle_unique_class($("display_num_selected_rows"), "green", ["green","light_grey"]);
    $("display_num_selected_rows").innerHTML = g.messages["phrase_rows_selected"].replace(/\{\$num_rows\}/, num_selected);
  }

  return num_selected;
}


/**
 * Selects all submission rows on the page. This is called ONLY when the user clicks on the "Select All" button.
 */
ms.select_all_on_page = function()
{
  // check all submissions on the page
  for (var i=0; i<ms.page_submission_ids.length; i++)
  {
    var curr_id = ms.page_submission_ids[i];
    ft.toggle_unique_class($("submission_row_" + curr_id), "selected_row_color", ["unselected_row_color", "selected_row_color"]);
    $('submission_cb_' + curr_id).checked = true;
    ms.all_submissions_selected_omit_list = $(ms.all_submissions_selected_omit_list).without(curr_id);

    if (!$(ms.selected_submission_ids).include(curr_id))
      ms.selected_submission_ids.push(curr_id);
  }

  if (ms.all_submissions_in_result_set_selected)
  {
    // if the user just selected the only row that wasn't selected on the page, update the select all button
    // based on whether all results are now selected or not
    if (ms.all_submissions_selected_omit_list.length == 0)
      ms._update_select_all_button("all_in_result_set_selected");
    else
      ms._update_select_all_button("all_on_page_selected");
  }
  else
  {
    if (ms.selected_submission_ids.length == ms.search_num_results)
      ms._update_select_all_button("all_in_result_set_selected");
    else
      ms._update_select_all_button("all_on_page_selected");
  }

  // pass these submission IDs to the server to store in sessions
  var page_url = g.root_url + "/global/code/actions.php?action=select_submissions&form_id=" + ms.form_id +
    "&submission_ids=" + ms.page_submission_ids.join(",");

  new Ajax.Request(page_url, {
    method: 'get',
    onFailure: function() { alert("Couldn't load page: " + page_url); }
  });

  ms.update_display_row_count();
}


/**
 * Selects all submissions in the current result set.
 */
ms.select_all_in_result_set = function()
{
  ms._update_select_all_button("all_in_result_set_selected");
  ms.all_submissions_selected_omit_list = [];

  // pass these submission IDs to the server to store in sessions
  var page_url = g.root_url + "/global/code/actions.php?action=select_all_submissions&form_id=" + ms.form_id;

  new Ajax.Request(page_url, {
    method: 'get',
    onFailure: function () { alert("Couldn't load page: " + page_url); }
  });

  ms.update_display_row_count();
}


/**
 * Unselects all submission rows on the page.
 */
ms.unselect_all = function()
{
  for (var i=0; i<ms.page_submission_ids.length; i++)
  {
    var curr_id = ms.page_submission_ids[i];
    ft.toggle_unique_class($("submission_row_" + curr_id), "unselected_row_color", ["unselected_row_color","selected_row_color"]);
    $('submission_cb_' + curr_id).checked = false;
  }

  ms._update_select_all_button("unselect_all");
  ms.all_submissions_in_result_set_selected = false;

  // pass this to the server to store in sessions
  var page_url = g.root_url + "/global/code/actions.php?action=unselect_all_submissions&form_id=" + ms.form_id;
  new Ajax.Request(page_url, { method: 'get' });

  ms.selected_submission_ids = [];
  ms.update_display_row_count();
}


/**
 * Used to hide/show the additional date search options.
 *
 * @param string choice the column name
 */
ms.change_search_field = function(choice)
{
  if (choice == "submission_date" || choice == "last_modified_date")
    $("search_dropdown_section").style.display = "block";
  else
    $("search_dropdown_section").style.display = "none";
}


/**
 * Called internally. This updates the appearance and functionality of the "select all" button, which
 * acts differently based on what rows are selected at any give time.
 */
ms._update_select_all_button = function(flag)
{
  if (flag == "all_on_page_selected")
  {
    $("select_button").value = g.messages["phrase_select_all_X_results"].replace(/\{\$numresults\}/, ms.search_num_results);
    $("select_button").onclick = function() { ms.select_all_in_result_set(); }
    $("select_button").disabled = false;
    ft.toggle_unique_class($("select_button"), "blue", ["blue","light_grey","black"]);
  }
  if (flag == "all_on_page_not_selected")
  {
    $("select_button").value = g.messages["phrase_select_all_on_page"];
    ft.toggle_unique_class($("select_button"), "black", ["blue","light_grey","black"]);
    $("select_button").disabled = false;
    $("select_button").onclick = function() { ms.select_all_on_page(); }
  }

  // only ever gets called when the user selected the "Select All X results"
  if (flag == "all_in_result_set_selected")
  {
    $("select_button").value = g.messages["phrase_all_X_results_selected"].replace(/\{\$numresults\}/, ms.search_num_results);
    ft.toggle_unique_class($("select_button"), "light_grey", ["blue","light_grey","black"]);
    $("select_button").disabled = true;
    ms.all_submissions_in_result_set_selected = true;
  }

  // only ever called when the user clicks "Unselect All" button
  if (flag == "unselect_all")
  {
    ms.selected_submission_ids = [];
    ms.all_submissions_in_result_set_selected = false;
    $("select_button").value = g.messages["phrase_select_all_on_page"];
    ft.toggle_unique_class($("select_button"), "black", ["blue","light_grey","black"]);
    $("select_button").disabled = false;
    $("select_button").onclick = function() { ms.select_all_on_page(); }
  }
}


/**
 * Deletes a submission file or image.
 *
 * TODO make this support Image Manager
 *
 * @param field_id
 * @param file_type "file" or "image"
 */
ms.delete_submission_file = function(field_id, file_type, force_delete)
{
  if (file_type == "file")
  {
    var page_url = g.root_url + "/global/code/actions.php?action=delete_submission_file&field_id=" + field_id
      + "&return_vals[]=target_message_id:file_field_" + field_id + "_message_id"
      + "&return_vals[]=field_id:" + field_id
      + "&force_delete=" + force_delete;
  }

  new Ajax.Request(page_url, {
    method: 'get',
    onSuccess: ms.delete_submission_file_response,
    onFailure: function() { alert("Couldn't load page: " + page_url); }
  });
}


/**
 * Handles the successful responses for the delete file feature. Whether or not the file was *actually*
 * deleted is a separate matter. If the file couldn't be delete, the user is provided the option of deleting
 * the database record anyway.
 */
ms.delete_submission_file_response = function(transport)
{
  var info = transport.responseText.evalJSON();

  // if it was a success, remove the link from the page
  if (info.success == 1)
  {
    var field_id = info.field_id;
    $("field_" + field_id + "_link").innerHTML = "";
    $("field_" + field_id + "_upload_field").show();
  }

  ft.display_message(info.target_message_id, info.success, info.message);
}


/**
 * Called from the edit submission page: sends an email.<b>
 *
 * @param integer submission_id
 * @param integer email_id
 */
ms.edit_submission_page_send_email = function(submission_id)
{
  var email_id = $("form_tools_email_template_id").value;
  var page_url = g.root_url + "/global/code/actions.php?action=edit_submission_send_email&submission_id="
    + submission_id + "&email_id=" + email_id + "&form_id=" + $("form_id").value;

  if (!email_id)
  {
    ft.display_message("ft_message", false, g.messages["notify_no_email_template_selected"]);
    return;
  }

  new Ajax.Request(page_url, {
    method: 'get',
    onSuccess: ms.email_sent,
    onFailure: function() { alert("Couldn't load page: " + page_url); }
  });
}


/**
 * Called after an email has been successfully
 */
ms.email_sent = function(transport)
{
  var info = transport.responseText.evalJSON();
  ft.display_message("ft_message", info.success, info.message);
}