/**
 * File: manage_field_options.js
 */

if (typeof fo_ns == 'undefined')
  fo_ns = {};

fo_ns.page_loaded = 0;
fo_ns.field_type = null; // this should ALWAYS be set on page load
fo_ns.current_field_type = null;


/**
 * This function is called whenever the user changes the field type. It hides all the fields, forcing them to
 * update the field type before editing the values.
 */
fo_ns.select_field_type = function(field_type)
{
  if (fo_ns.current_field_type == field_type)
    return;

  if (field_type == fo_ns.field_type)
  {
	  Effect.Fade($("changed_field_settings"), { duration: 0.4 });
	  Effect.Appear($("field_settings"), { duration: 0.4, delay: 0.4 });
	  $("update_field_type").disabled = true;
  }
  else
  {
    // if the previous field type was the original field type (i.e. the one when first arriving at the page)
    // then hide the field settings. Otherwise do nothing: they're just going from one non-selected field type
    // to another
    if (fo_ns.current_field_type == fo_ns.field_type);
    {
		  Effect.Fade($("field_settings"), { duration: 0.4 });
		  Effect.Appear($("changed_field_settings"), { duration: 0.4, delay: 0.4 });
    }

    $("update_field_type").disabled = false;
  }

  fo_ns.current_field_type = field_type;
}



/**
 * Called by user to automatically fill the field options. Works best for single and multi-dropdowns,
 * since both the value and display text can be accessed and populated consistently through the DOM.
 * For checkboxes and radio buttons, only the values are populated.
 */
fo_ns.smart_fill = function()
{
  if (fo_ns.page_loaded == 0)
    return;

  // locate this element in the page
  var formIframe = hidden_form.window.document;
  var num_forms  = formIframe.forms.length;
  var form_index = null;
  for (var i=0; i<num_forms; i++)
  {
    if (formIframe.forms[i][fo_ns.field_name] || formIframe.forms[i][fo_ns.field_name + "[]"])
      form_index = i;
  }

  if (form_index == null)
  {
    fo_ns.smart_fill_err();
    return;
  }

  fo_ns.empty_rows();

  // get our field (checks for myField[] syntax, too)
  if (formIframe.forms[form_index][fo_ns.field_name])
    var field = formIframe.forms[form_index][fo_ns.field_name];
  else
    var field = formIframe.forms[form_index][fo_ns.field_name + "[]"];

  // if field.type is undefined, it's probably an array
  if (field.type == undefined)
    field.type = field[0].type;

  // if field.type is still undefined, Smart Fill won't work. Alert the user
  if (field.type == undefined)
    fo_ns.smart_fill_err();


  switch (field.type)
  {
    case "select-one":
    case "select-multiple":
      fo_ns.add_rows(field.length.toString());

      for (var i=0; i<field.length; i++)
      {
        document.field_options["value_" + (i+1)].value = field[i].value;
        document.field_options["text_" + (i+1)].value = field[i].text;
      }
      break;

    case "checkbox":
    case "radio":
      is_single = true;
      if (field.length != undefined)
        is_single = false;

      // Single Checkbox
      if (is_single)
      {
        add_rows("1");
        document.field_options["value_1"].value = field.value;
      }

      // multiple checkboxes
      else
      {
        rows = field.length.toString();
        fo_ns.add_rows(rows);
        for (var i=0; i<field.length; i++)
          document.field_options["value_" + (i+1)].value = field[i].value;
      }
      break;

    default:
      alert(g.messages["phrase_unknown_field_type_c"] + " " + field.type);
      break;
  }
}


/**
 * Adds one or more rows to the fields options page for a multi-select element (radio buttons, checkboxes,
 * select or multi-select).
 */
fo_ns.add_rows = function(num_rows)
{
  // check num_rows is an integer
  if (num_rows.match(/\D/) || num_rows == 0 || num_rows == "")
  {
    alert(g.messages["validation_num_rows_to_add"]);
    document.num_options.num_rows.focus();
    return;
  }

  // get the current table
  var tbody = document.getElementById("options_table").getElementsByTagName("tbody")[0];

  for (var i=1; i<=num_rows; i++)
  {
    var currRow = ++fo_ns.num_rows;
    var row = document.createElement("tr");

    // [1] first cell: row number
    var td1 = document.createElement("td");
    td1.setAttribute("align", "center");
    td1.appendChild(document.createTextNode(currRow));

    // [2] second <td> cell: "value" field
    var td2 = document.createElement("td");
    var title = document.createElement("input");
    title.setAttribute("type", "text");
    title.setAttribute("name", "value_" + currRow);
    title.setAttribute("style", "width: 150px;");
    td2.appendChild(title);

    // [3] second <td> cell: "display text" field
    var td3 = document.createElement("td");
    var title = document.createElement("input");
    title.setAttribute("type", "text");
    title.setAttribute("name", "text_" + currRow);
    title.setAttribute("style", "width: 150px;");
    td3.appendChild(title);

    // add the table data cells to the row
    row.appendChild(td1);
    row.appendChild(td2);
    row.appendChild(td3);

    // add the row to the table
    tbody.appendChild(row);
  }

  // update the number of columns
  document.field_options.num_options.value = fo_ns.num_rows;
}


/**
 * Remove all but the heading row from the Options table
 */
fo_ns.empty_rows = function()
{
  // get the current table
  var tbody = document.getElementById("options_table").getElementsByTagName("tbody")[0];

  for (var i=tbody.childNodes.length-1; i>0; i--)
    tbody.removeChild(tbody.childNodes[i]);

  fo_ns.num_rows = 0;
}


/**
 * Helper function for checkboxes and radio button fields, which copies the option values over to
 * the text as well.
 */
fo_ns.copy_values_to_text = function(num_rows)
{
  for (var i=0; i<fo_ns.num_rows; i++)
    document.field_options["text_" + (i+1)].value = document.field_options["value_" + (i+1)].value;
}


/**
 * Display a simple error message if unable to Smart Fill.
 */
fo_ns.smart_fill_err = function()
{
  alert(g.messages["notify_smart_fill_failure"]);
  return;
}


/**
 * This is called from the edit field option page, it links to the Edit Field Option Group page where
 * a new field option group is created and assigned to the current field.
 */
fo_ns.create_new_field_option_group = function(field_id)
{
  window.location = "field_option_groups/index.php?add_field_option_group=1&field_id=" + field_id;
}


fo_ns.edit_field_option_group = function(group_id)
{
  if (!group_id)
  {
    ft.display_message("ft_message", false, g.messages["validation_no_field_group_selected"]);
    return false;
  }

  window.location = "field_option_groups/edit.php?page=main&group_id=" + group_id;
}
