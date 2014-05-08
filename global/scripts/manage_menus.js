/**
 * File: manage_menus.js
 *
 * Used for managing both the administrator and client menus pages.
 */

// our namespace for manage submission functions
if (typeof mm == 'undefined')
  mm = {};

mm.num_rows = 0;


/**
 * Adds a new menu item row.
 */
mm.add_menu_item_row = function()
{
  var currRow = ++mm.num_rows;

  // get the current table
  var tbody = $("menu_table").getElementsByTagName("tbody")[0];

  var row = document.createElement("tr");
  row.setAttribute("id", "row_" + currRow);

  // [1] Order column
  var td1 = document.createElement("td");
  td1.className = "greyCell";
  td1.setAttribute("align", "center");
  var inp = document.createElement("input");
  inp.setAttribute("type", "text");
  inp.style.cssText = "width:30px";
  inp.setAttribute("value", currRow);
  inp.setAttribute("name", "menu_row_" + currRow + "_order");
  inp.setAttribute("id", "menu_row_" + currRow + "_order");
  td1.appendChild(inp);

  // [2] Page
  var td2 = document.createElement("td");
  var pages_dd = $("menu_options").innerHTML;
  pages_dd = pages_dd.replace(/%%X%%/gi, currRow);
  var div = document.createElement("div");
  div.innerHTML = pages_dd;

  // now add the onchange handler to the select field
  for (var i=0; i<div.childNodes.length; i++)
  {
    if (div.childNodes[i].nodeName == "SELECT")
      div.childNodes[i].onchange = function (evt) { mm.change_page(currRow, this.value); }
  }
  td2.appendChild(div);

  // [3] Display column
  var td3 = document.createElement("td");
  var inp = document.createElement("input");
  inp.style.cssText = "width:120px";
  inp.setAttribute("type", "text");
  inp.setAttribute("name", "display_text_" + currRow);
  inp.setAttribute("id", "display_text_" + currRow);
  td3.appendChild(inp);

  // [4] Options column [empty by default]
  var td4 = document.createElement("td");
  var div = document.createElement("div");
  div.setAttribute("id", "row_" + currRow + "_options");
  $(div).addClassName("pad_left_small nowrap");
  var span = document.createElement("span");
  $(span).addClassName("medium_grey");
  span.appendChild(document.createTextNode(g.messages["word_na"]));
  div.appendChild(span);
  td4.appendChild(div);

  // [5] Is Sub-option
  var td5 = document.createElement("td");
  td5.setAttribute("align", "center");
  var inp = document.createElement("input");
  inp.setAttribute("type", "checkbox");
  inp.setAttribute("name", "submenu_" + currRow);
  inp.setAttribute("id", "submenu_" + currRow);
  td5.appendChild(inp);

  // [6] Delete column
  var td6 = document.createElement("td");
  td6.setAttribute("align", "center");
  $(td6).addClassName("del");
  var delete_link = document.createElement("a");
  delete_link.setAttribute("href", "#");
  delete_link.onclick = function (evt) { return mm.remove_menu_item_row(currRow); };
  delete_link.appendChild(document.createTextNode(g.messages["word_remove"].toUpperCase()));
  td6.appendChild(delete_link);

  row.appendChild(td1);
  row.appendChild(td2);
  row.appendChild(td3);
  row.appendChild(td4);
  row.appendChild(td5);
  row.appendChild(td6);

  tbody.appendChild(row);

  return false;
}


/**
 * Removes a menu item row.
 */
mm.remove_menu_item_row = function(row)
{
  // get the current table
  var tbody = $("menu_table").getElementsByTagName("tbody")[0];

  for (i=tbody.childNodes.length-1; i>0; i--)
  {
    if (tbody.childNodes[i].id == "row_" + row)
      tbody.removeChild(tbody.childNodes[i]);
  }

  return false;
}


mm.change_page = function(row, page)
{
  // first, if the Display Text field is empty, set its value to the same as the display text
  // in the dropdown menu
  for (var i=0; i<$("page_identifier_" + row).options.length; i++)
  {
    if ($("page_identifier_" + row).options[i].value == page)
    {
      if ($("page_identifier_" + row).options[i].value)
        $("display_text_" + row).value = $("page_identifier_" + row).options[i].text;
      else
        $("display_text_" + row).value = "";
      break;
    }
  }

  // show / hide the appropriate options for this page
  switch (page)
  {
    case "custom_url":
      var html = g.messages["word_url_c"] + "&nbsp;<input type=\"text\" name=\"custom_options_" + row + "\" style=\"width:160px\" />";
      $("row_" + row + "_options").innerHTML = html;
      break;

    case "client_form_submissions":
    case "form_submissions":
    case "edit_form_main":
    case "edit_form_fields":
    case "edit_form_views":
    case "edit_form_emails":
    case "edit_form_database":
    case "edit_form_add_fields":
      var form_dd = $("form_dropdown_template").innerHTML.replace(/%%X%%/gi, row);
      var html = g.messages["word_form_c"] + "&nbsp;" + form_dd;
      $("row_" + row + "_options").innerHTML = html;
      break;

    case "edit_client":
      var client_dd = $("client_dropdown_template").innerHTML.replace(/%%X%%/gi, row);
      var html = g.messages["word_client_c"] + "&nbsp;" + client_dd;
      $("row_" + row + "_options").innerHTML = html;
      break;

    default:
      $("row_" + row + "_options").innerHTML = "<span class=\"medium_grey\">" + g.messages["word_na"] + "</span>";
      break;
  }
}


/**
 * The onsubmit handler for the update admin menu form. This runs a little validation on the
 * values to confirm all required fields are entered, and there's nothing missing.
 */
mm.update_admin_menu_submit = function(f)
{
  // [page identifier, page label, is included]
  var required_pages = [
    ["admin_forms", g.messages["word_forms"], false],
    ["clients", g.messages["word_clients"], false],
    ["your_account", g.messages["phrase_your_account"], false],
    ["modules", g.messages["word_modules"], false],
    ["settings", g.messages["word_settings"], false],
    ["logout", g.messages["word_logout"], false]
  ];

  for (var i=1; i<=mm.num_rows; i++)
  {
    for (var j=0; j<required_pages.length; j++)
    {
      var curr_page_identifier = required_pages[j][0];
      if ($("page_identifier_" + i) && $("page_identifier_" + i).value == curr_page_identifier)
        required_pages[j][2] = true;
    }
  }

  // now check to see if all required pages were included
  var pages = [];
	for (var j=0; j<required_pages.length; j++)
  {
    if (!required_pages[j][2])
      pages.push(required_pages[j][1]);
  }

  if (pages.length)
  {
    var pages_str = pages.join(", ");
    var message = g.messages["notify_required_admin_pages"].replace(/\{\$remaining_pages\}/, pages_str);
    ft.display_message("ft_message", false, message);
    return false;
  }

  $("num_rows").value = mm.num_rows;

  return true;
}


/**
 * The onsubmit handler for the update client menu form.
 */
mm.update_client_menu_submit = function(f)
{
  $("num_rows").value = mm.num_rows;

  // check the menu name isn't already taken
  var menu_name = $("menu_name").value;
  for (var i=0; i<page_ns.menu_names.length; i++)
  {
    if (menu_name == page_ns.menu_names[i])
    {
      ft.display_message("ft_message", false, g.messages["validation_menu_name_taken"]);
      return false;
    }
  }

  return true;
}