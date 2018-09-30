/**
 * manage_client_forms.js
 *
 * This file handles all functions for the Forms tab of the Edit Client administration page. It
 * provides the functionality for adding and removing forms and form Views to a client account.
 */


var cf_ns = {}
cf_ns.num_rows = 0; // the total number of forms (overridden by calling page)


/**
 * Adds a new row to the forms table, letting the administrator control what forms & what form Views
 * the client has access to.
 *
 * Assumption: the calling page has defined the data structures for the form list and form Views.
 */
cf_ns.add_form_row = function() {
  var curr_row = ++cf_ns.num_rows;

  var row = $("<tr id=\"row_" + curr_row + "\"></tr>");
  var td1 = $("<td valign=\"top\"></td>");

  var sel = $("<select name=\"form_row_" + curr_row + "\" class=\"selected_form\"></select>");
  sel.bind("change", { curr_row: curr_row }, function(e) { cf_ns.select_form(e.data.curr_row, $(this).val()); });
  sel.append("<option value=\"\">" + g.messages["phrase_please_select"] + "</option>");

  for (var i=0, j=page_ns.forms.length; i<j; i++) {
    var form_id   = page_ns.forms[i][0];
    var form_name = page_ns.forms[i][1];
    sel.append("<option value=\"" + form_id + "\">" + form_name + "</option>");
  }
  td1.append(sel);

  var td2 = $("<td><span id=\"row_" + curr_row + "_available_views_span\" class=\"medium_grey pad_left\">" + g.messages["phrase_please_select_form"] + "</span></td>");
  var td3 = $("<td align=\"center\" valign=\"center\"><span id=\"row_" + curr_row + "_actions\"></span></td>");
  var td4 = $("<td><span id=\"row_" + curr_row + "_selected_views_span\" class=\"medium_grey\"></span></td>");
  var td5 = $("<td align=\"center\" class=\"del\"></td>");
  td5.bind("click", { curr_row: curr_row }, function(e) { cf_ns.delete_row(e.data.curr_row); });

  // add the table data cells to the row
  var cells = ft.group_nodes([td1, td2, td3, td4, td5]);
  row.append(cells);

  // add the row to the table
  $("#client_forms_table tbody").append(row);

  // update the form count
  document.client_forms.num_forms.value = cf_ns.num_rows;

  return false;
}


/**
 * Called when the user selects a form from one of the dropdowns in the first column. It shows
 * the appropriate View content in the second column.
 */
cf_ns.select_form = function(row, form_id) {

  // if the user just selected the default option
  if (form_id == "") {
    $("#row_" + row + "_available_views_span").html("<span class=\"pad_left medium_grey\">" + g.messages["phrase_please_select_form"] + "</span>");
    $("#row_" + row + "_actions, #row_" + row + "_selected_views_span").html("");
    return;
  }

  // if the form has already been selected
  if (cf_ns.form_is_selected(form_id)) {
    $("#row_" + row + "_available_views_span").html("<span class=\"medium_grey\">" + g.messages["phrase_form_already_selected"] + "</span>");
    $("#row_" + row + "_actions, #row_" + row + "_selected_views_span").html("");
    return;
  }

  // build and add the available Views
  var available_dd = $("<select name=\"row_" + row + "_available_views[]\" id=\"row_" + row + "_available_views\" multiple size=\"4\"></select>");
  var form_index = null;
  for (var i=0; i<page_ns.form_views.length; i++) {
    if (form_id == page_ns.form_views[i][0]) {
      form_index = i;
      break;
    }
  }

  for (var i=0, j=page_ns.form_views[form_index][1].length; i<j; i++) {
    var view_id   = page_ns.form_views[form_index][1][i][0];
    var view_name = page_ns.form_views[form_index][1][i][1];
    available_dd.append($("<option value=\"" + view_id + "\">" + view_name + "</option>"));
  }
  $("#row_" + row + "_available_views_span").html("");
  $("#row_" + row + "_available_views_span").removeClass("medium_grey pad_left");
  $("#row_" + row + "_available_views_span").append(available_dd);


  // add the << >> nav for the row
  var add = $("<input type=\"button\" value=\"" + g.messages["word_add_uc_rightarrow"] + "\" />");
  add.bind("click", function(e) { ft.move_options("row_" + row + "_available_views", "row_" + row + "_selected_views"); });
  var remove = $("<input type=\"button\" value=\"" + g.messages["word_remove_uc_leftarrow"] + "\" />");
  add.bind("click", function(e) { ft.move_options("row_" + row + "_selected_views", "row_" + row + "_available_views"); });

  $("#row_" + row + "_actions").html(ft.group_nodes([add, remove]));

  var selected_dd = $("<select name=\"row_" + row + "_selected_views[]\" id=\"row_" + row + "_selected_views\" multiple size=\"4\"></select>");

  $("#row_" + row + "_selected_views_span").html("");
  $("#row_" + row + "_selected_views_span").removeClass("medium_grey pad_left");
  $("#row_" + row + "_selected_views_span").append(selected_dd);

  return false;
}


/**
 * Deletes an individual row. Note that this does NOT re-id all the other fields (e.g. after deleting
 * row 5, row 6 still has an id of row_6) nor does it decrement the global row counter "g_num_rows".
 * This is done for simplicity. The PHP function that handles the update discards any rows without a
 * FORM specified, so the absent row is not important. The num_filters hidden field (which is based on
 * the g_num_rows value) IS important, though - that lets the PHP function know how many rows (or the
 * MAX rows) that the form is sending. So again, it's fine that the actual number of rows passed is less.
 *
 * @param integer row
 */
cf_ns.delete_row = function(row) {
  $("#client_forms_table tbody #row_" + row).fadeOut(400);
  setTimeout(function() { $("#client_forms_table tbody #row_" + row).remove(); }, 450);
}


/**
 * Called on form submit.
 *
 * @param object the form
 */
cf_ns.check_fields = function(f) {
  // ensures that everything is selected in the Selected Views column to pas salong to the server
  for (var i=1; i<=cf_ns.num_rows; i++) {
    if ($("#row_" + i + "_selected_views").length > 0) {
      ft.select_all("row_" + i + "_selected_views");
    }
  }
  $("#num_forms").val(cf_ns.num_rows);
  return true;
}


/**
 * A helper function to find if a form has already been selected on the page. It's a bit weird since it detects for
 * > 1 items (since the newly selected row will have the same form_id value as an existing field).
 *
 * @return boolean
 */
cf_ns.form_is_selected = function(form_id) {
  var select_count = 0;

  $(".selected_form").each(function() {
    if (this.value == form_id) {
      select_count++;
    }
  });

  return (select_count > 1);
}