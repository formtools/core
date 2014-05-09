// our namespace for the Add Fields functions and vars
var add_fields_ns = {}
add_fields_ns.num_rows = 0; // the total number of rows
add_fields_ns.tabindex_increment = 1000;


/**
 * Used to add fields to the Add Fields form.
 */
add_fields_ns.add_fields = function(num_rows)
{
  // check num_rows is an integer
  if (num_rows.match(/\D/) || num_rows == 0 || num_rows == "")
  {
    alert(g.messages["validation_num_rows_to_add"]);
    document.add_fields_form.num_fields.focus();
    return;
  }

  // get the current table
  var tbody = $("add_fields_table").getElementsByTagName("tbody")[0];

  for (var i=1; i<=num_rows; i++)
  {
    var currRow = ++add_fields_ns.num_rows;

    var row = document.createElement("tr");
    row.setAttribute("id", "row_" + currRow);

    // [1] Pass On column
    var td1 = document.createElement("td");
    td1.setAttribute("align", "center");
    var inp = document.createElement("input");
    inp.setAttribute("type", "checkbox");
    inp.setAttribute("name", "include_on_redirect_" + currRow);
    inp.setAttribute("id", "include_on_redirect_" + currRow);
    inp.setAttribute("tabindex", add_fields_ns.tabindex_increment + currRow);
    td1.appendChild(inp);

    // [2] Form Field Name column
    var td2 = document.createElement("td");
    var inp = document.createElement("input");
    inp.setAttribute("type", "text");
    inp.setAttribute("name", "field_name_" + currRow);
    inp.setAttribute("id", "field_name_" + currRow);
    inp.setAttribute("tabindex", (add_fields_ns.tabindex_increment * 2) + currRow);
    inp.style.cssText = "width: 90px";
    td2.appendChild(inp);

    // [3] Display Text column
    var td3 = document.createElement("td");
    var inp = document.createElement("input");
    inp.setAttribute("type", "text");
    inp.setAttribute("name", "field_title_" + currRow);
    inp.setAttribute("id", "field_title_" + currRow);
    inp.setAttribute("tabindex", (add_fields_ns.tabindex_increment * 3) + currRow);
    inp.style.cssText = "width: 85px";
    td3.appendChild(inp);

    // [4] Field Size column
    var td4 = document.createElement("td");
    var dd1 = document.createElement("select");
    dd1.setAttribute("name", "field_size_" + currRow);
    dd1.setAttribute("tabindex", (add_fields_ns.tabindex_increment * 4) + currRow);
    var option1 = document.createElement("option");
    option1.setAttribute("value", "tiny");
    option1.appendChild(document.createTextNode(g.messages["phrase_size_tiny"]));
    var option2 = document.createElement("option");
    option2.setAttribute("value", "small");
    option2.appendChild(document.createTextNode(g.messages["phrase_size_small"]));
    var option3 = document.createElement("option");
    option3.setAttribute("value", "medium");
    option3.appendChild(document.createTextNode(g.messages["phrase_size_medium"]));
    var option4 = document.createElement("option");
    option4.setAttribute("value", "large");
    option4.appendChild(document.createTextNode(g.messages["phrase_size_large"]));
    var option5 = document.createElement("option");
    option5.setAttribute("value", "very_large");
    option5.appendChild(document.createTextNode(g.messages["phrase_size_very_large"]));
    dd1.appendChild(option1);
    dd1.appendChild(option2);
    dd1.appendChild(option3);
    dd1.appendChild(option4);
    dd1.appendChild(option5);
    td4.appendChild(dd1);

    // [5] Data Type column
    var td5 = document.createElement("td");
    var dd2 = document.createElement("select");
    dd2.setAttribute("name", "data_type_" + currRow);
    dd2.setAttribute("tabindex", (add_fields_ns.tabindex_increment * 5) + currRow);
    var option1 = document.createElement("option");
    option1.setAttribute("value", "string");
    option1.appendChild(document.createTextNode(g.messages["word_string"]));
    var option2 = document.createElement("option");
    option2.setAttribute("value", "number");
    option2.appendChild(document.createTextNode(g.messages["word_number"]));
    dd2.appendChild(option1);
    dd2.appendChild(option2);
    td5.appendChild(dd2);

    // [6] Database Column
    var td6 = document.createElement("td");
    var inp = document.createElement("input");
    inp.setAttribute("type", "text");
    inp.setAttribute("name", "col_name_" + currRow);
    inp.setAttribute("id", "col_name_" + currRow);
    inp.setAttribute("tabindex", (add_fields_ns.tabindex_increment * 6) + currRow);

    if ($("auto_generate_col_names").checked)
      inp.setAttribute("disabled", "true");

    inp.style.cssText = "width: 100px";
    td6.appendChild(inp);

    // [7] Delete column
    var td7 = document.createElement("td");
    td7.setAttribute("align", "center");
    td7.className = "del";
    var delete_link = document.createElement("a");
    delete_link.setAttribute("href", "#");
    delete_link.onclick = add_fields_ns.delete_row.bind(this, currRow);
    delete_link.appendChild(document.createTextNode(g.messages["word_delete"].toUpperCase()));

    td7.appendChild(delete_link);

    // add the table data cells to the row
    row.appendChild(td1);
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);
    row.appendChild(td5);
    row.appendChild(td6);
    row.appendChild(td7);

    // add the row to the table
    tbody.appendChild(row);
  }

  // update the number of columns
  document.add_fields_form.num_fields.value = add_fields_ns.num_rows;
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
add_fields_ns.delete_row = function(row)
{
  // get the current table
  var tbody = $("add_fields_table").getElementsByTagName("tbody")[0];

  for (i=tbody.childNodes.length-1; i>0; i--)
  {
    if (tbody.childNodes[i].id == "row_" + row)
      tbody.removeChild(tbody.childNodes[i]);
  }
}


/**
 * Enables/disables the fields in the database column. Also hides/shows the "existing column names"
 * section, since it's not relevant if the column names are being auto-generated. Also, since the
 * "auto-generate" option is enabled by default, if the user disables it, showing the existing
 * column names dropdown draws attention to it.
 *
 * @param boolean is_checked
 */
add_fields_ns.toggle_db_column_fields = function(is_checked)
{
  show_field = false;

  if (is_checked)
  {
    $("existing_column_name_info").style.display = "none";
    show_field = true;
  }
  else
    $("existing_column_name_info").style.display = "block";

  // now enable / disable all the columns
  for (var i=1; i<=add_fields_ns.num_rows; i++)
  {
    if ($("col_name_" + i))
      $("col_name_" + i).disabled = show_field;
  }
}


/**
 * Called on form submit. Checks that (a) if the form field name is included, all other fields have
 * been entered; (b) if the user isn't auto-generating the database columns, check they've been entered,
 * alphanumeric, not already used and unique.
 *
 * @param object the form
 */
add_fields_ns.check_fields = function(f)
{
  for (var i=1; i<=add_fields_ns.num_rows; i++)
  {
    // if this row has been deleted or doesn't have a form field name, just ignore it
    if (!f["field_name_" + i] || !f["field_name_" + i].value)
      continue;

    // check the Display Text has been included
    if (!f["field_title_" + i].value)
    {
      var message = g.messages["validation_no_display_text"].replace(/\{\$fieldname\}/, "'" + f["field_name_" + i].value + "'");
      ft.display_message("ft_message", false, message);
      f["field_title_" + i].focus();
      f["field_title_" + i].style.backgroundColor = rsv.offendingFieldStyle;
      return false;
    }

    if (!f.auto_generate_col_names.checked)
    {
      // check it's entered
      if (!f["col_name_" + i].value)
      {
        var message = g.messages["validation_no_column_name"].replace(/{\$fieldname\}/, "'" + f["field_name_" + i].value + "'");
        ft.display_message("ft_message", false, message);
        f["col_name_" + i].focus();
        f["col_name_" + i].style.backgroundColor = rsv.offendingFieldStyle;
        return false;
      }

      // check the column name is alphanumeric
      if (f["col_name_" + i].value.match(/\W/))
      {
        var message = g.messages["validation_invalid_column_name"].replace(/{\$fieldname\}/, "'" + f["field_name_" + i].value + "'");
        ft.display_message("ft_message", false, message);
        f["col_name_" + i].focus();
        f["col_name_" + i].style.backgroundColor = rsv.offendingFieldStyle;
        return false;
      }

      // check the field doesn't already exist in the database
      for (var j=0; j<f.existing_columns.length; j++)
      {
        if (f["col_name_" + i].value == f.existing_columns[j].value)
        {
          var message = g.messages["validation_db_column_name_exists"].replace(/{\$name\}/, "'" + f["col_name_" + i].value + "'");
          ft.display_message("ft_message", false, message);
          f["col_name_" + i].focus();
          f["col_name_" + i].style.backgroundColor = rsv.offendingFieldStyle;
          return false;
        }
      }

      // check the field isn't already entered
      for (var k=1; k<=add_fields_ns.num_rows; k++)
      {
        // ignore the empty fields
        if (!f["field_name_" + k] || !f["field_name_" + k].value)
          continue;

        // if it's the same row, ignore it too
        if (k == i)
          continue;

        if (f["col_name_" + i].value == f["col_name_" + k].value)
        {
          ft.display_message("ft_message", false, g.messages["validation_no_two_column_names"]);
          f["col_name_" + i].focus();
          f["col_name_" + i].style.backgroundColor = rsv.offendingFieldStyle;
          return false;
        }
      }
    }
  }

  return true;
}