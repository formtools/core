/**
 * This code is used for managing the Eedit Field Option Group page; most notably, it contains the Smart
 * Fill code for parsing HTML files (and uploading them, if need be).
 */

sf_ns = {}; // Smart Fill namespace... (old naming scheme)
sf_ns.num_rows = null; // set by onload function
sf_ns.tmp_deleted_field_option_rows = [];
sf_ns.manual_file_upload_attempted = false;


/**
 * This deletes a field option. Note: it doesn't remove the option in memory; that is handled by
 * _update_current_field_settings(), which is called when the user leaves the page & when
 */
sf_ns.delete_field_option = function(row)
{
  // before we delete the row, get it's Order column value
  var order = parseInt($("field_option_" + row + "_order").innerHTML);

  $("row_" + row).remove();
  sf_ns.tmp_deleted_field_option_rows.push(row);

  // update the order of all subsequent rows
  for (var i=row+1; i<=sf_ns.num_rows; i++)
  {
    // if the row has already been deleted, just ignore it
    if (!$("field_option_" + i + "_order"))
      continue;

    $("field_option_" + i + "_order").innerHTML = order;
    order++;
  }

  return false;
}


sf_ns.delete_all_rows = function()
{
  for (var i=1; i<=sf_ns.num_rows; i++)
  {
    // if the row has already been deleted, just ignore it
    if (!$("field_option_" + i + "_order"))
      continue;

    $("row_" + i).remove();
  }

  sf_ns.num_rows = 0;
}


/**
 * Adds a field option for the currently selected field (dropdown, radio or checkbox).
 */
sf_ns.add_field_option = function(default_val, default_txt)
{
  // find out how many rows there already is
  var next_id = ++sf_ns.num_rows;

	var row = document.createElement("tr");
	row.setAttribute("id", "row_" + next_id);

	// [1] first cell: row number
	var td1 = document.createElement("td");
	td1.setAttribute("align", "center");
	$(td1).addClassName("medium_grey");
	td1.setAttribute("id", "field_option_" + next_id + "_order");
  var num_deleted_rows = sf_ns.tmp_deleted_field_option_rows.length;
	var row_num_label = next_id - num_deleted_rows;
	td1.appendChild(document.createTextNode(row_num_label));

	// [2] second <td> cell: "value" field
	var td2 = document.createElement("td");
	var title = document.createElement("input");
	title.setAttribute("type", "text");
	title.setAttribute("name", "field_option_value_" + next_id);
	title.setAttribute("id", "field_option_value_" + next_id);
	title.style.cssText = "width: 98%";
	if (default_val != null)
	  title.setAttribute("value", default_val);
	td2.appendChild(title);

	// [3] second <td> cell: "display text" field
	var td3 = document.createElement("td");
	var title = document.createElement("input");
	title.setAttribute("type", "text");
	title.setAttribute("name", "field_option_text_" + next_id);
	title.setAttribute("id", "field_option_text_" + next_id);
	title.style.cssText = "width: 98%";
	if (default_txt != null)
	  title.setAttribute("value", default_txt);
	td3.appendChild(title);

	// [4] delete column
	var td4 = document.createElement("td");
	td4.setAttribute("align", "center");
	td4.className = "del";
	var del_link = document.createElement("a");
	del_link.setAttribute("href", "#");
	del_link.onclick = sf_ns.delete_field_option.bind(this, next_id);
	del_link.appendChild(document.createTextNode(g.messages["word_delete"].toUpperCase()));
	td4.appendChild(del_link);

	// add the table data cells to the row
	row.appendChild(td1);
	row.appendChild(td2);
	row.appendChild(td3);
	row.appendChild(td4);

	// add the row to the table
	var tbody = $("field_options_table").getElementsByTagName("tbody")[0];
	tbody.appendChild(row);

	$("num_rows").value = sf_ns.num_rows;
}


/**
 * This attempts to smart fill a single field, based on a form field name and a website page. It
 * assumes the page_ns.scrape_page value has been set ("file_get_contents", "curl", "redirect") to
 * indicate the method by which the Smart Fill will be used.
 */
sf_ns.smart_fill_field = function()
{
  var smart_fill_source_form_field = $("smart_fill_source_form_field").value;
  var smart_fill_source_url        = $("smart_fill_source_url").value;

  if (smart_fill_source_form_field == "" || smart_fill_source_url == "")
  {
    ft.display_message("smart_fill_messages", false, g.messages["validation_no_smart_fill_values"]);
    return false;
  }

  // first, find out the appropriate scrape method to extract this field's contents.
  if (!ft.is_valid_url(smart_fill_source_url))
  {
    ft.display_message("smart_fill_messages", false, g.messages["validation_invalid_url"]);
    return false;
  }

  sf_ns.log_activity(true);
  var page_url = g.root_url + "/global/code/actions.php";
	new Ajax.Request(page_url, {
	  parameters: { action: "get_js_webpage_parse_method", url: smart_fill_source_url },
	  method: 'post',
	  onSuccess: sf_ns.process_js_webpage_parse_method_response
	    });
}


sf_ns.process_js_webpage_parse_method_response = function(transport)
{
  var info = transport.responseText.evalJSON();

  // if their server doesn't support any of the page scraping methods, offer them the option
  // of uploading the file manually
  if (info.scrape_method == "")
  {
    ft.display_message("smart_fill_messages", false, $("upload_files_text").innerHTML);
    sf_ns.log_activity(false);
  }
  else
  {
	  var page_url = g.root_url + "/global/code/actions.php?action=smart_fill&scrape_method=" + info.scrape_method
	    + "&url=" + $("smart_fill_source_url").value;
	  $("hidden_iframe").src = page_url;
  }
}


/**
 * When the contents of the iframe are fully loaded (and, namely, the content is now available to JS), this
 * function is called. It logs the page as loaded. Once all form pages are loaded, it loads all the form
 * field information into memory for analyzing by the Smart Fill function.
 */
sf_ns.log_form_page_as_loaded = function()
{
  sf_ns.log_activity(false);

  if (!page_ns.page_initialized)
  {
    page_ns.page_initialized = true;
    return;
  }

  // try to smart fill the field requested
  var iframe_element = hidden_iframe.window.document;
  var field_name = $("smart_fill_source_form_field").value;
  var num_forms  = iframe_element.forms.length;


  var form_index = null;
  for (i=0; i<num_forms; i++)
  {
    if (iframe_element.forms[i][field_name] || iframe_element.forms[i][field_name + "[]"])
      form_index = i;
  }

  if (form_index == null)
  {
    ft.display_message("smart_fill_messages", false, g.messages["validation_smart_fill_no_field_found"]);
    return;
  }

  // if this was the result of manually uploading a file, be optimistic and tell them it worked (this is
  // overwritten if anything fails).
  if (sf_ns.manual_file_upload_attempted)
    ft.display_message("smart_fill_messages", true, "Your field options have been Smart Filled");

  sf_ns.delete_all_rows();


  // get our field (checks for myField[] syntax, too)
  if (iframe_element.forms[form_index][field_name])
    var field = iframe_element.forms[form_index][field_name];
  else
    var field = iframe_element.forms[form_index][field_name + "[]"];

  // if field.type is undefined, it's probably an array
  if (field.type == undefined)
    field.type = field[0].type;

  // if field.type is still undefined, Smart Fill won't work. Alert the user
  if (field.type == undefined)
  {
    ft.display_message("smart_fill_messages", false, g.messages["validation_smart_fill_cannot_fill"]);
    return;
  }


  switch (field.type)
  {
    case "select-one":
    case "select-multiple":
      for (i=0; i<field.length; i++)
        sf_ns.add_field_option(field[i].value, field[i].text);
      break;

    case "checkbox":
    case "radio":
      is_single = true;
      if (field.length != undefined)
        is_single = false;

      // single checkbox
      if (is_single)
        sf_ns.add_field_option(field.value, null);

      // multiple checkboxes
      else
      {
	      for (i=0; i<field.length; i++)
	        sf_ns.add_field_option(field[i].value, null);
      }
      break;

    default:
      ft.display_message("smart_fill_messages", false, g.messages["validation_smart_fill_invalid_field_type"]);
      break;
  }
}

/**
 * This is called whenever starting or ending any potentially lengthy JS operation. It hides/shows the
 * ajax loading icon.
 */
sf_ns.log_activity = function(is_busy)
{
  sf_ns.is_busy = is_busy;

  if (is_busy)
  {
    $("ajax_activity").show();
    $("ajax_no_activity").hide();
  }
  else
  {
    $("ajax_activity").hide();
    $("ajax_no_activity").show();
  }
}


/**
 * This function can be optionally called by the user if the Smart Fill couldn't read the web pages. It
 * uploads copies of the forms to the server. The
 */
sf_ns.validate_upload_file = function(f)
{
  // check all the fields have been entered
  if (!f["form_page_1"].value)
  {
    alert(g.messages["validation_smart_fill_no_page"]);
    return false;
  }

  // check it's a .html or .htm file
  if (!f["form_page_1"].value.match(/(\.htm|\.html)$/))
  {
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
 * Called when submitting the Edit Field Option Group main tab. It just ensures the group name is valid.
 */
sf_ns.submit_update_field_option_group_page = function(f)
{
  var group_name = $("group_name").value.strip();
  if (group_name == "")
  {
    ft.display_message("ft_message", false, g.messages["validation_no_field_option_group_name"]);
    $("group_name").focus();
    return false;
  }

  // see if the field option group is already taken
  for (var i=0; i<page_ns.group_names.length; i++)
  {
    if (group_name == page_ns.group_names[i])
    {
      ft.display_message("ft_message", false, g.messages["validation_field_option_group_name_taken"]);
      return false;
    }
  }

  return true;
}