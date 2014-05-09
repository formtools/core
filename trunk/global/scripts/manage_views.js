/**
 * File:        manage_views.js
 * Abstract:    contains all JS for adding/editing Views. Note: this file also contains the JS that
 *              used to be in the filters.js file, since filters are now associated with Views, not
 *              individual clients.
 * Assumptions: g.root_url is defined
 */

// the Views JS namespace
var view_ns = {};

view_ns.num_standard_filter_rows   = 0; // the number of standard filters currently displayed (overwritten by calling page)
view_ns.num_client_map_filter_rows = 0; // the number of client map filters currently displayed (overwritten by calling page)
view_ns.field_ids       = []; // stores the fields IDs currently in the view
view_ns.all_form_fields = []; // all form fields
view_ns.view_tabs       = []; // the view tabs. This is change dynamically in the page
view_ns.enable_editable_fields = false;
view_ns.tabindex_increment = 1000;


/**
 * Hides/shows the custom client list.
 */
view_ns.toggle_custom_client_list = function(val)
{
  if (val == "yes")
    $("custom_clients").hide();
  else
    $("custom_clients").show();
}


/**
 * Enables / disables all fields in the View.
 */
view_ns.toggle_editable_fields = function(enable_fields)
{
  view_ns.enable_editable_fields = enable_fields;

  // loop through all selected form fields and if the
  for (var i=0; i<view_ns.field_ids.length; i++)
  {
    field_id = view_ns.field_ids[i];
    if ($("field_" + field_id + "_is_editable"))
      $("field_" + field_id + "_is_editable").disabled = !enable_fields;
  }
}


/**
 * Hides / shows the sortable checkbox for a particular field. Called whenever a user clicks a
 * "column" checkbox.
 *
 * @param integer field_id
 * @param bool show hide/show the field
 */
view_ns.toggle_sortable_field = function(field_id, show)
{
  var display = (show) ? "block" : "none";
  $("sortable_" + field_id).style.display = display;
}


/**
 * Adds one or more fields to the current View's field list. This calls the
 * view_ns.add_view_field function on each selected row.
 */
view_ns.add_view_fields = function(dd_field_id)
{
  var selected_field_ids = $F(dd_field_id);
  if (!selected_field_ids.length)
  {
    ft.display_message("ft_message", false, g.messages["validation_no_view_fields_selected"]);
    return;
  }

  for (var i=0; i<selected_field_ids.length; i++)
    view_ns.add_view_field(selected_field_ids[i]);
}


/**
 * Adds a field to the current View's field list and removes it from the
 * "available fields" dropdown.
 *
 * @param integer field_id the unique field ID.
 */
view_ns.add_view_field = function(field_id)
{
  // get the field display text
  var field_display_text = "";
  for (i=0; i<view_ns.all_form_fields.length; i++)
  {
    if (view_ns.all_form_fields[i][0] == field_id)
    {
      field_display_text = view_ns.all_form_fields[i][1];
      break;
    }
  }

  var tbody = $("view_fields_table").getElementsByTagName("tbody")[0];
  var row = document.createElement("tr");
  row.setAttribute("id", "field_row_" + field_id);
  var num_fields = view_ns.field_ids.length;

  // [1] Order column
  var td1 = document.createElement("td");
  td1.setAttribute("align", "center");
  var inp = document.createElement("input");
  inp.setAttribute("type", "text");
  inp.setAttribute("name", "field_" + field_id + "_order");
  inp.setAttribute("id", "field_" + field_id + "_order");
  inp.setAttribute("tabindex", (view_ns.tabindex_increment * 1) + num_fields);

  // find the next available number
  var highest_num = 0;
  for (i=0; i<view_ns.field_ids.length; i++)
  {
    curr_num = parseInt($("field_" + view_ns.field_ids[i] + "_order").value);
    if (curr_num > highest_num)
      highest_num = curr_num;
  }
  next_num = highest_num + 1;
  inp.setAttribute("value", next_num);
  inp.style.cssText = "width: 30px";
  td1.appendChild(inp);

  // [2] Is Column
  var td2 = document.createElement("td");
  td2.setAttribute("align", "center");
  var inp = document.createElement("input");
  inp.setAttribute("type", "checkbox");
  inp.setAttribute("name", "field_" + field_id + "_is_column");
  inp.setAttribute("id", "field_" + field_id + "_is_column");
  inp.setAttribute("tabindex", (view_ns.tabindex_increment * 2) + num_fields);
  inp.onclick = function(evt) { view_ns.toggle_sortable_field(field_id, this.checked) }
  td2.appendChild(inp);

  // [3] Sortable
  var td3 = document.createElement("td");
  td3.setAttribute("align", "center");
  var inp = document.createElement("input");
  inp.setAttribute("type", "checkbox");
  inp.setAttribute("name", "field_" + field_id + "_is_sortable");
  inp.setAttribute("checked", true);
  inp.setAttribute("tabindex", (view_ns.tabindex_increment * 3) + num_fields);
  var div = document.createElement("div");
  div.setAttribute("id", "sortable_" + field_id);
  div.style.cssText = "display:none";
  div.appendChild(inp);
  td3.appendChild(div);

  // [4] Editable
  var td4 = document.createElement("td");
  td4.setAttribute("align", "center");

  // the Submission ID and Last Modified fields CANNOT be edited, so check it's another field
  if (field_id != view_ns.submission_id_field_id && field_id != view_ns.last_modified_date_field_id)
  {
    var may_edit_submissions = $("cmes").checked;

    var inp = document.createElement("input");
    inp.setAttribute("type", "checkbox");
    inp.setAttribute("name", "field_" + field_id + "_is_editable");
    inp.setAttribute("id", "field_" + field_id + "_is_editable");
    inp.setAttribute("tabindex", (view_ns.tabindex_increment * 4) + num_fields);

    if (may_edit_submissions)
      inp.setAttribute("checked", true);
    else
      inp.setAttribute("disabled", true);

    td4.appendChild(inp);
  }

  // [5] Searchable
  var td5 = document.createElement("td");
  td5.setAttribute("align", "center");
  var inp = document.createElement("input");
  inp.setAttribute("type", "checkbox");
  inp.setAttribute("name", "field_" + field_id + "_is_searchable");
  inp.setAttribute("checked", true);
  inp.setAttribute("tabindex", (view_ns.tabindex_increment * 5) + num_fields);
  var div = document.createElement("div");
  div.setAttribute("id", "searchable_" + field_id);
  div.appendChild(inp);
  td5.appendChild(div);

  // [6] Display text column
  var td6 = document.createElement("td");
  td6.className = "pad_left_small";
  var textNode = document.createTextNode(field_display_text);
  td6.appendChild(textNode);

  // [7] Tab column
  var td7 = document.createElement("td");
  var sel = document.createElement("select");
  sel.setAttribute("name", "field_" + field_id + "_tab");
  sel.setAttribute("id", "field_" + field_id + "_tab");
  sel.setAttribute("tabindex", (view_ns.tabindex_increment * 6) + num_fields);
  for (i=0; i<view_ns.view_tabs.length; i++)
    sel.options[i] = new Option(view_ns.view_tabs[i][1], view_ns.view_tabs[i][0]);
  if (view_ns.view_tabs.length == 0)
    sel.options[0] = new Option(g.messages["validation_no_tabs_defined"], "");
  td7.appendChild(sel);

  // [8] Remove column
  var td8 = document.createElement("td");
  td8.setAttribute("align", "center");
  td8.className = "del";
  var delete_link = document.createElement("a");
  delete_link.setAttribute("href", "#");
  delete_link.onclick = function (evt) { return view_ns.remove_view_field(field_id); };
  delete_link.appendChild(document.createTextNode(g.messages["word_remove"].toUpperCase()));
  td8.appendChild(delete_link);

  // add the table data cells to the row
  row.appendChild(td1);
  row.appendChild(td2);
  row.appendChild(td3);
  row.appendChild(td4);
  row.appendChild(td5);
  row.appendChild(td6);
  row.appendChild(td7);
  row.appendChild(td8);

  // add the row to the table
  tbody.appendChild(row);

  // add this field to view_ns.field_ids and update the available view field list
  view_ns.field_ids.push(field_id);
  view_ns.update_available_view_fields();
}


/**
 * Removes the view field from the view field table, on the Edit View page and decrements the order
 * values for all fields found after this item.
 *
 * @param integer field_id the unique field ID
 */
view_ns.remove_view_field = function(field_id)
{
  // get the current table
  var tbody = $("view_fields_table").getElementsByTagName("tbody")[0];

  for (i=tbody.childNodes.length-1; i>0; i--)
  {
    // ignore any whitespace "nodes"
    if (tbody.childNodes[i].nodeName == '#text')
      continue;

    if (tbody.childNodes[i].id == "field_row_" + field_id)
    {
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
 * Called whenever a user adds or removes a field in the View. This updates the "available field"
 * dropdown and whether or not the Add Field button is enabled or not.
 */
view_ns.update_available_view_fields = function()
{
  var available_field_list = [];

  // loop through view_ns.all_form_fields hash and if the key (field_id) isn't in view_ns.field_ids,
  // keep track of it. We'll use that info to populate the available fields dropdown
  for (j=0; j<view_ns.all_form_fields.length; j++)
  {
    field_id = view_ns.all_form_fields[j][0];

    if (!$(view_ns.field_ids).include(field_id))
      available_field_list.push(view_ns.all_form_fields[j]);
  }

  if (available_field_list.length == 0)
  {
    $("available_fields").options.length = 0;
    $("available_fields").options[0] = new Option(g.messages["phrase_all_fields_displayed"], "");
    $("add_field_button").disabled = true;
    $("add_field_button").style.color = '#999999';
  }
  else
  {
    $("available_fields").options.length = 0;
    $("add_field_button").disabled = false;
    $("add_field_button").style.color = '#000000';

    // now add the new list
    for (i=0; i<available_field_list.length; i++)
    {
      new_option = new Option(available_field_list[i][1], available_field_list[i][0]);
      $("available_fields").options[i] = new_option;
    }
  }
}


/**
 * Changes the tab choice of multiple View fields at once, saving the user have to do them all
 * manually.
 */
view_ns.assign_field_tabs = function()
{
  var from_row    = parseInt($("tab_row_from").value);
  var to_row      = parseInt($("tab_row_to").value);
  var new_tab_num = $("assign_fields_tab_selection").value;

  if (from_row.toString() == "NaN" || to_row.toString() == "NaN")
  {
    ft.display_message("ft_message", false, g.messages["validation_invalid_tab_assign_values"]);
    return;
  }

  var tbody = $("view_fields_table").getElementsByTagName("tbody")[0];
  var count = 1;

  for (var i=0; i<tbody.childNodes.length; i++)
  {
    if (!tbody.childNodes[i].id || !tbody.childNodes[i].id.match(/field_row_/))
      continue;

    var curr_field_id = tbody.childNodes[i].id.replace(/field_row_/, "");

    if (count >= from_row && count <= to_row)
      $("field_" + curr_field_id + "_tab").value = new_tab_num;

    count++;
  }
}


/**
 * Helper function to empty the 6 tab label fields (with "tab_label" class).
 */
view_ns.remove_tabs = function()
{
  $$('.tab_label').each(function(Element) { Element.value = ""; } );
  view_ns.update_field_tabs();
}


/**
 * This function is called whenever the user's focus leaves any of the the tab name fields. It
 * updates the contents of the tab dropdown column in the fields section - for each and every
 * field.
 */
view_ns.update_field_tabs = function()
{
  // get the tab labels
  var tab_labels = [
    $("tab_label1").value,
    $("tab_label2").value,
    $("tab_label3").value,
    $("tab_label4").value,
    $("tab_label5").value,
    $("tab_label6").value
      ];

  // update the view_tabs array
  view_ns.view_tabs = [];
  for (var i=0; i<tab_labels.length; i++)
  {
    if (tab_labels[i] != "")
      view_ns.view_tabs.push([i+1, tab_labels[i]]);
  }
  if (view_ns.view_tabs.length == 0)
    view_ns.view_tabs.push(["", g.messages["validation_no_tabs_defined"]]);

  // update all the tab dropdowns for each of the View fields
  for (var i=0; i<view_ns.field_ids.length; i++)
  {
    var field_id = view_ns.field_ids[i];
    var tab_number = $("field_" + field_id + "_tab").value;
    $("field_" + field_id + "_tab").options.length = 0;

    for (var j=0; j<view_ns.view_tabs.length; j++)
    {
      var is_selected = ((j+1) == tab_number) ? true : false;
      $("field_" + field_id + "_tab").options[j] = new Option(view_ns.view_tabs[j][1], view_ns.view_tabs[j][0], is_selected);
    }
  }

  // lastly, update the assign multiple tabs dropdown
  $("assign_fields_tab_selection").options.length = 0;
   for (var i=0; i<view_ns.view_tabs.length; i++)
  {
    var is_selected = ((i+1) == tab_number) ? true : false;
    $("assign_fields_tab_selection").options[i] = new Option(view_ns.view_tabs[i][1], view_ns.view_tabs[i][0], is_selected);
  }
}


/**
 * Used to add rows to the standard filters table on the Edit View page (the first table). This is separated from the
 * client map filter table because the content is sufficiently different.
 *
 * @param integer num_rows the number of rows to add
 */
view_ns.add_standard_filters = function(num_rows)
{
  // check num_rows is an integer
  if (num_rows.match(/\D/) || num_rows == 0 || num_rows == "")
  {
    ft.display_message("ft_message", false, g.messages["validation_num_rows_to_add"]);
    $("num_standard_filter_rows").focus();
    return;
  }

  var tbody = $("standard_filters_table").getElementsByTagName("tbody")[0];

  for (var i=1; i<=num_rows; i++)
  {
    var currRow = ++view_ns.num_standard_filter_rows;

    var row = document.createElement("tr");
    row.setAttribute("id", "standard_row_" + currRow);

    // [1] first <td> cell: form field dropdown (defaulted to "please select")
    var td2 = document.createElement("td");
    var dd2 = document.createElement("select");
    dd2.setAttribute("name", "standard_filter_" + currRow + "_field_id");
    dd2.setAttribute("id", "standard_filter_" + currRow + "_field_id");
    dd2.onchange = view_ns.change_standard_filter_field.bind(this, currRow);

    var default_option = document.createElement("option");
    default_option.setAttribute("value", "");
    default_option.appendChild(document.createTextNode(g.messages["phrase_please_select"]));
    dd2.appendChild(default_option);

    // now add all form fields (even if they're not included in the View)
    for (j=0; j<view_ns.all_form_fields.length; j++)
    {
      field_id   = view_ns.all_form_fields[j][0];
      field_name = view_ns.all_form_fields[j][1];

      dd2.options[j+1] = new Option(field_name, field_id);
    }
    td2.appendChild(dd2);

    // [3] third <td> cell: operator dropdown

    // TODO What if submission_date is first?
    var td3 = document.createElement("td");

    // -- first section: DATE operators
    var first_div = document.createElement("div");
    first_div.setAttribute("id", "standard_filter_" + currRow + "_operators_dates_div");
    first_div.style.cssText = "display: none";
    var operator_dd = document.createElement("select");
    operator_dd.setAttribute("name", "standard_filter_" + currRow + "_operator_date");
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
    second_div.setAttribute("id", "standard_filter_" + currRow + "_operators_div");
    var operator_dd = document.createElement("select");
    operator_dd.setAttribute("name", "standard_filter_" + currRow + "_operator");
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
    first_div.setAttribute("id", "standard_filter_" + currRow + "_values_dates_div");
    first_div.style.cssText = "display: none";

    var table = document.createElement("table");
    table.setAttribute("cellspacing", "0");
    table.setAttribute("cellpadding", "0");
    table.setAttribute("border", "0");
    var tr = document.createElement("tr");
    var tr_td1 = document.createElement("td");

     var inp = document.createElement("input");
    inp.setAttribute("type", "text");
    inp.setAttribute("name", "standard_filter_" + currRow + "_filter_date_values");
    inp.setAttribute("id", "standard_date_" + currRow);
    inp.style.cssText = "width: 120px";
    tr_td1.appendChild(inp);

    var tr_td2 = document.createElement("td");
    var img = document.createElement("img");
    img.setAttribute("src", g.root_url + "/themes/" + g.theme_folder + "/images/calendar_icon.gif");
    img.setAttribute("id", "standard_date_image_" + currRow);
    img.setAttribute("border", "0");
    tr_td2.appendChild(img);

    tr.appendChild(tr_td1);
    tr.appendChild(tr_td2);
    table.appendChild(tr);

    first_div.appendChild(table);

    // -- second section: REGULAR textbox
    var second_div = document.createElement("div");
    second_div.setAttribute("id", "standard_filter_" + currRow + "_values_div");
    var inp2 = document.createElement("input");
    inp2.setAttribute("type", "text");
    inp2.setAttribute("name", "standard_filter_" + currRow + "_filter_values");
    inp2.setAttribute("style", "width: 120px;");
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
    delete_link.onclick = view_ns.delete_filter_row.bind(this, "standard", currRow);

    delete_link.appendChild(document.createTextNode(g.messages["word_remove"].toUpperCase()));
    td5.appendChild(delete_link);

    // add the table data cells to the row
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);
    row.appendChild(td5);

    // add the row to the table
    tbody.appendChild(row);

    // init a Calendar for this row
    Calendar.setup({
       inputField     :    "standard_date_" + currRow,
       showsTime      :    true,
       timeFormat     :    "24",
       ifFormat       :    "%Y-%m-%d %H:%M:00",
       button         :    "standard_date_image_" + currRow,
       align          :    "Bl",
       singleClick    :    true
    });
  }

  // update the filter count
  $("num_standard_filters").value = view_ns.num_standard_filter_rows;
}


/**
 * Used to add rows to the client map filters table on the Edit View page.
 * 
 * @param integer num_rows the number of rows to add
 */
view_ns.add_client_map_filters = function(num_rows)
{
  // check num_rows is an integer
  if (num_rows.match(/\D/) || num_rows == 0 || num_rows == "")
  {
    ft.display_message("ft_message", false, g.messages["validation_num_rows_to_add"]);
    $("num_client_map_filter_rows").focus();
    return;
  }

  var tbody = $("client_map_filters_table").getElementsByTagName("tbody")[0];

  for (var i=1; i<=num_rows; i++)
  {
    var currRow = ++view_ns.num_client_map_filter_rows;

    var row = document.createElement("tr");
    row.setAttribute("id", "client_map_row_" + currRow);

    // [1] first <td> cell: form field dropdown (defaulted to "please select")
    var td2 = document.createElement("td");
    var dd2 = document.createElement("select");
    dd2.setAttribute("name", "client_map_filter_" + currRow + "_field_id");
    dd2.setAttribute("id", "client_map_filter_" + currRow + "_field_id");

    var default_option = document.createElement("option");
    default_option.setAttribute("value", "");
    default_option.appendChild(document.createTextNode(g.messages["phrase_please_select"]));
    dd2.appendChild(default_option);

    // now add all form fields (even if they're not included in the View)
    for (j=0; j<view_ns.all_form_fields.length; j++)
    {
      field_id   = view_ns.all_form_fields[j][0];
      field_name = view_ns.all_form_fields[j][1];
      dd2.options[j+1] = new Option(field_name, field_id);
    }
    td2.appendChild(dd2);

    // [3] third <td> cell: operator dropdown

    // TODO What if submission_date is first?
    var td3 = document.createElement("td");

    // -- second section: REGULAR operators
    var div = document.createElement("div");
    div.setAttribute("id", "client_map_filter_" + currRow + "_operators_div");
    var operator_dd = document.createElement("select");
    operator_dd.setAttribute("name", "client_map_filter_" + currRow + "_operator");
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
    dd.setAttribute("name", "client_map_filter_" + currRow + "_client_field");
    dd.setAttribute("style", "width: 150px;");
    var default_option = document.createElement("option");
    default_option.setAttribute("value", "");
    default_option.appendChild(document.createTextNode(g.messages["phrase_please_select"]));
    dd.appendChild(default_option);

		// add in the contents of the page_ns.clientFields array. For extensibility with
		// other modules, the options are grouped in optgroups.
	  var current_section = null;
		var optgroup = null;
    for (var j=0; j<page_ns.clientFields.length; j++)
    {
		  if (page_ns.clientFields[j].section != current_section)
      {
			  if (current_section != null)
				  dd.appendChild(optgroup);

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
    delete_link.onclick = view_ns.delete_filter_row.bind(this, "client_map", currRow);

    delete_link.appendChild(document.createTextNode(g.messages["word_remove"].toUpperCase()));
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
  $("num_client_map_filters").value = view_ns.num_client_map_filter_rows;
}


/**
 * Called by the form field dropdown onchange handler on the standard View filters table; shows the appropriate
 * operators and values section.
 *
 * @param integer row the row number
 * @param integer field_id the unique field ID
 */
view_ns.change_standard_filter_field = function(row)
{
  var field_id = $("standard_filter_" + row + "_field_id").value;

  // find out if this field is the submission date or not
  var is_date_field = false;
  for (var i=0; i<view_ns.all_form_fields.length; i++)
  {
    curr_field_id = view_ns.all_form_fields[i][0];
    curr_col_name = view_ns.all_form_fields[i][2];

    if (curr_field_id == field_id && (curr_col_name.match("submission_date") || curr_col_name.match("last_modified_date")))
      is_date_field = true;
  }

  if (is_date_field)
  {
    $("standard_filter_" + row + "_operators_div").style.display = "none";
    $("standard_filter_" + row + "_operators_dates_div").style.display = "block";
    $("standard_filter_" + row + "_values_div").style.display = "none";
    $("standard_filter_" + row + "_values_dates_div").style.display = "block";
  }
  else
  {
    $("standard_filter_" + row + "_operators_div").style.display = "block";
    $("standard_filter_" + row + "_operators_dates_div").style.display = "none";
    $("standard_filter_" + row + "_values_div").style.display = "block";
    $("standard_filter_" + row + "_values_dates_div").style.display = "none";
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
view_ns.delete_filter_row = function(table, row)
{
  // get the current table
  var table_id = null;
  if (table == "standard")
  {
    table_id = "standard_filters_table";
    row_id_prefix = "standard_";
  }
  else
  {
    table_id = "client_map_filters_table";
    row_id_prefix = "client_map_";
  }

  var tbody = $(table_id).getElementsByTagName("tbody")[0];
  for (var i=tbody.childNodes.length-1; i>0; i--)
  {
    if (tbody.childNodes[i].id == row_id_prefix + "row_" + row)
      tbody.removeChild(tbody.childNodes[i]);
  }

  return false;
}


/**
 * Called whenever the user clicks the "Update View" button. It does some elementary field
 * validation and ensures the data is in a state ready for the PHP.
 *
 * @param the form element
 */
view_ns.process_form = function(f)
{
  // 1. Main tab
  var rules = [];
  rules.push("required,view_name," + g.messages['validation_no_view_name']);
  rules.push("required,num_submissions_per_page," + g.messages['validation_no_num_submissions_per_page']);

  if (!rsv.validate(f, rules))
    return ft.change_inner_tab(1, 4, "edit_view_tab"); // this always returns false

  // 2. Fields tab
  if (view_ns.field_ids.length == 0)
  {
    ft.display_message("ft_message", false, g.messages["validation_no_view_fields"]);
    return ft.change_inner_tab(2, 4, "edit_view_tab");
  }

  // check that at least one field is marked as a column
  var has_column_checked = false;
  for (i=0; i<view_ns.field_ids.length; i++)
  {
    if ($("field_" + view_ns.field_ids[i] + "_is_column").checked)
      has_column_checked = true;
  }
  if (!has_column_checked)
  {
    ft.display_message("ft_message", false, g.messages["validation_no_column_selected"]);
    return ft.change_inner_tab(2, 4, "edit_view_tab"); // this always returns false
  }

  // select all clients
  ft.select_all(f["selected_user_ids[]"]);

  // store the field_ids in a hidden field to pass along with the update request
  $("field_ids").value = view_ns.field_ids.join(",");

  return true;
}


view_ns.toggle_filter_section = function(section)
{
  if (section == "client_map")
    var section_id = "client_map_filters"; 
  else
    var section_id = "standard_filters"; 
		  
  var display_setting = $(section_id).getStyle('display');
  var is_visible = false;

  if (display_setting == 'none')
  {
    Effect.BlindDown($(section_id));
    is_visible = true;
  }
  else
    Effect.BlindUp($(section_id));

  return false;
}