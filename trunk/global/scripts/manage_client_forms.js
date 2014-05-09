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
cf_ns.add_form_row = function()
{
  // get the current table
  var tbody = $("client_forms_table").getElementsByTagName("tbody")[0];

  var curr_row = ++cf_ns.num_rows;
  var row = document.createElement("tr");
  row.setAttribute("id", "row_" + curr_row);

  var td1 = document.createElement("td");
  td1.setAttribute("valign", "top");
  var sel = document.createElement("select");
  sel.onchange = function(evt) { cf_ns.select_form(curr_row, this.value); };
  sel.setAttribute("name", "form_row_" + curr_row);
  sel.style.cssText = "width:100%";
  sel.appendChild(new Option(g.messages["phrase_please_select"], ""));

  for (var i=0; i<page_ns.forms.length; i++)
  {
    var form_id   = page_ns.forms[i][0];
    var form_name = page_ns.forms[i][1];

    var new_option = new Option(form_name, form_id);
    sel.appendChild(new_option);
  }
  td1.appendChild(sel);

  var td2 = document.createElement("td");
  var span = document.createElement("span");
  span.setAttribute("id", "row_" + curr_row + "_available_views_span");
  $(span).addClassName("medium_grey");
  $(span).addClassName("pad_left");
  span.appendChild(document.createTextNode(g.messages["phrase_please_select_form"]));
  td2.appendChild(span);

  var td3 = document.createElement("td");
  td3.setAttribute("align", "center");
  td3.setAttribute("valign", "center");
  var span = document.createElement("span");
  span.setAttribute("id", "row_" + curr_row + "_actions");
  td3.appendChild(span);

  var td4 = document.createElement("td");
  var span = document.createElement("span");
  span.setAttribute("id", "row_" + curr_row + "_selected_views_span");
  $(span).addClassName("medium_grey");
  $(span).addClassName("pad_left_small");
  td4.appendChild(span);

  var td5 = document.createElement("td");
  td5.setAttribute("align", "center");
  $(td5).addClassName("del");
  var delete_link = document.createElement("a");
  delete_link.setAttribute("href", "#");
  delete_link.onclick = function(evt) { cf_ns.delete_row(curr_row); };
  delete_link.appendChild(document.createTextNode(g.messages["word_delete"].toUpperCase()));
  td5.appendChild(delete_link);

  // add the table data cells to the row
  row.appendChild(td1);
  row.appendChild(td2);
  row.appendChild(td3);
  row.appendChild(td4);
  row.appendChild(td5);

  // add the row to the table
  tbody.appendChild(row);

  // update the form count
  document.client_forms.num_forms.value = cf_ns.num_rows;

  return false;
}


/**
 * Called when the user selects a form from one of the dropdowns in the first column. It shows
 * the appropriate View content in the second column.
 */
cf_ns.select_form = function(row, form_id)
{
  // if the user just selected the default option,
  if (form_id == "")
  {
    $("row_" + row + "_available_views_span").innerHTML = "<span class=\"pad_left medium_grey\">"
      + g.messages["phrase_please_select_form"] + "</span>";
    $("row_" + row + "_actions").innerHTML = "";
    $("row_" + row + "_selected_views_span").innerHTML = "";
    return;
  }

  // if the form has already been selected
  if (cf_ns.form_is_selected(form_id))
  {
    $("row_" + row + "_available_views_span").innerHTML = "<span class=\"pad_left medium_grey\">"
      + g.messages["phrase_form_already_selected"] + "</span>";
    $("row_" + row + "_actions").innerHTML = "";
    $("row_" + row + "_selected_views_span").innerHTML = "";
    return;
  }

  // build and add the available Views
  var available_dd = document.createElement("select");
  available_dd.setAttribute("name", "row_" + row + "_available_views[]");
  available_dd.setAttribute("id", "row_" + row + "_available_views");
  available_dd.setAttribute("multiple", true);
  available_dd.setAttribute("size", "4");
  available_dd.style.cssText = "width:100%"

  var form_index = null;
  for (var i=0; i<page_ns.form_views.length; i++)
  {
    if (form_id == page_ns.form_views[i][0])
      form_index = i;
  }

  for (var i=0; i<page_ns.form_views[form_index][1].length; i++)
  {
    var view_id   = page_ns.form_views[form_index][1][i][0];
    var view_name = page_ns.form_views[form_index][1][i][1];

    available_dd.appendChild(new Option(view_name, view_id));
  }
  $("row_" + row + "_available_views_span").innerHTML = "";
  $("row_" + row + "_available_views_span").removeClassName("medium_grey");
  $("row_" + row + "_available_views_span").removeClassName("pad_left");
  $("row_" + row + "_available_views_span").appendChild(available_dd);


  // add the << >> nav for the row
  var add_button = document.createElement("input");
  add_button.setAttribute("type", "button");
  add_button.setAttribute("value", g.messages["word_add_uc_rightarrow"].unescapeHTML());
  add_button.onclick = function(evt) { ft.move_options($("row_" + row + "_available_views"), $("row_" + row + "_selected_views")); };

  var remove_button = document.createElement("input");
  remove_button.setAttribute("type", "button");
  remove_button.setAttribute("value", g.messages["word_remove_uc_leftarrow"].unescapeHTML());
  remove_button.onclick = function(evt) { ft.move_options($("row_" + row + "_selected_views"), $("row_" + row + "_available_views")); };

  $("row_" + row + "_actions").innerHTML = "";
  $("row_" + row + "_actions").appendChild(add_button);
  $("row_" + row + "_actions").appendChild(remove_button);


  var selected_dd = document.createElement("select");
  selected_dd.setAttribute("name", "row_" + row + "_selected_views[]");
  selected_dd.setAttribute("id", "row_" + row + "_selected_views");
  selected_dd.setAttribute("multiple", true);
  selected_dd.setAttribute("size", "4");
  selected_dd.style.cssText = "width:100%"

  $("row_" + row + "_selected_views_span").innerHTML = "";
  $("row_" + row + "_selected_views_span").removeClassName("medium_grey");
  $("row_" + row + "_selected_views_span").removeClassName("pad_left");
  $("row_" + row + "_selected_views_span").appendChild(selected_dd);

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
cf_ns.delete_row = function(row)
{
  // get the current table
  var tbody = $("client_forms_table").getElementsByTagName("tbody")[0];

  for (i=tbody.childNodes.length-1; i>0; i--)
  {
    if (tbody.childNodes[i].id == "row_" + row)
      tbody.removeChild(tbody.childNodes[i]);
  }
}


/**
 * Called on form submit.
 *
 * @param object the form
 */
cf_ns.check_fields = function(f)
{
  // ensures that everything is selected in the Selected Views column to pas salong to the server
  for (var i=1; i<=cf_ns.num_rows; i++)
  {
    if ($("row_" + i + "_selected_views"))
    {
      ft.select_all($("row_" + i + "_selected_views"));
    }
  }

  $("num_forms").value = cf_ns.num_rows;

  return true;
}


/**
 * A helper function to find if a form has already been selected on the page
 *
 * @return boolean
 */
cf_ns.form_is_selected = function(form_id)
{
  var is_selected = false;

  for (var i=1; i<=cf_ns.num_rows; i++)
  {
    if ($("form_row_" + i) && $("form_row_" + i).value == form_id)
    {
      is_selected = true;
      break;
    }
  }

  return is_selected;
}
