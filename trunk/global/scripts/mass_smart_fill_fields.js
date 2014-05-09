/**
 * Smart Fill Fields
 * -----------------
 *
 * This function handles the "Smart Filling" of the form fields in the Add Form process. It works
 * by having the form page(s) loaded in hidden iframes in the page, which can then be interpreted by this
 * javascript. For forms that are off-server, copies are made and created locally for the duration of the
 * Add Form, allowing the JS access to read them.
 */

var sf_ns = {};
sf_ns.is_busy = true;
sf_ns.smart_filled = false;
sf_ns.itemize_form_fields_complete = false;
sf_ns.itemized_fields = []; // temporary storage for the raw data
sf_ns.fields = []; // final location of "clean" data
sf_ns.current_field_id = null;
sf_ns.current_page_id = "main_field_table";
sf_ns.all_fields_complete = false;
sf_ns.tmp_deleted_field_option_rows = [];
sf_ns.duplicate_fields = {};
sf_ns.log_files_as_uploaded_page_loaded = false;
sf_ns.manual_file_upload_attempted = false;


/**
 * This function smart fills ALL fields in the form - even for multi-page forms. It relies on the form data
 * already having been loaded into sf_ns.itemized_fields. This function cleans up the info and stores the
 * fields info that the user is interested in in the sf_ns.fields object. Each field contains two special
 * properties:
 *      status:      a flag string, indicating where the field setup is at: "found", "not_found", "duplicate_found",
 *                   "resolved"
 *      is_complete: a boolean. This is either set automatically to TRUE if there were no problems & no further
 *                   attention is needed on behalf of the user (e.g. with textboxes), or FALSE. For elements like
 *                   radios, checkboxes & dropdowns, once the user approves the dropdown lists this setting is
 *                   set to TRUE. For problems that can't be resolved, the user still has the option to manually
 *                   set it to TRUE, on the understanding that they can always edit the field later.
 */
sf_ns.smart_fill = function()
{
  if (sf_ns.check_all_pages_loaded() && sf_ns.itemize_form_fields_complete)
    return false;

  sf_ns.log_activity(true);

  // now loop through each and every form field and see if we can identify it in the forms
  var num_not_found_elements = 0;
  for (var i=0; i<page_ns.field_ids.length; i++)
  {
    var field_id = page_ns.field_ids[i];
    var field_name = $("field_" + field_id + "_name").value;

    sf_ns.fields["f" + field_id] = {};
    sf_ns.fields["f" + field_id].field_name = field_name;
    sf_ns.fields["f" + field_id].status = null;
    sf_ns.fields["f" + field_id].is_complete = false;


    var found_element_index = null;
    for (var j=0; j<sf_ns.itemized_fields.length; j++)
    {
      if (sf_ns.itemized_fields[j].element_name == field_name || sf_ns.itemized_fields[j].element_name == field_name + "[]")
      {
        found_element_index = j;
        break;
      }
    }

    // no field was found with this field name
    if (found_element_index == null)
    {
      sf_ns.fields["f" + field_id].status = "not_found";
      $("field_" + field_id + "_action").innerHTML   = "<a href=\"#\" onclick=\"return sf_ns.show_page('not_found', '" + field_id + "')\" class=\"red\">" + g.messages["phrase_not_found"] + "</a>";
      $("field_" + field_id + "_options").innerHTML  = "";
      num_not_found_elements++;
    }
    else
    {
      var curr_field_info = sf_ns.itemized_fields[found_element_index];

      var field_type = curr_field_info.field_type;
      sf_ns.fields["f" + field_id].status = "found";
      sf_ns.fields["f" + field_id].field_type = field_type;

      // display the field type
      $("field_" + field_id + "_type").value = field_type;

      switch (field_type)
      {
        case "file":
        case "textbox":
        case "password":
        case "textarea":
          sf_ns.fields["f" + field_id].is_complete = true;
          $("field_" + field_id + "_action").innerHTML = "<span class=\"light_grey\">" + g.messages["word_none"] + "</span>";
          $("field_" + field_id + "_options").innerHTML = "<span class=\"light_grey\">" + g.messages["word_na"] + "</span>";
          break;

        // note that these aren't marked as complete - the user needs to manally approve the field option
        // display values
        case "radio-buttons":
        case "checkboxes":
          sf_ns.fields["f" + field_id].status = "add_field_values";
          sf_ns.fields["f" + field_id].orientation = "horizontal";
          var num_options = curr_field_info.option_list.length;
          sf_ns.fields["f" + field_id].option_list = curr_field_info.option_list;
          $("field_" + field_id + "_action").innerHTML = "<a href=\"#\" class=\"orange\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["phrase_add_field_values"] + "</span>";
          $("field_" + field_id + "_options").innerHTML = "<a href=\"#\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["word_options"] + " (" + num_options + ")</span>";
          break;

        case "multi-select":
        case "select":
          sf_ns.fields["f" + field_id].is_complete = true;
          var num_options = curr_field_info.option_list.length;
          sf_ns.fields["f" + field_id].option_list = curr_field_info.option_list;
          $("field_" + field_id + "_action").innerHTML = "<span class=\"light_grey\">" + g.messages["word_none"] + "</span>";
          $("field_" + field_id + "_options").innerHTML = "<a href=\"#\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["word_options"] + " (" + num_options + ")</span>";
          break;

        // here, more than one field with this name was found (and it wasn't a series of radio-buttons or checkboxes)
        case "mixed":
          sf_ns.fields["f" + field_id].status = "multiple_fields_found";
           $("field_" + field_id + "_action").innerHTML   = "<a href=\"#\" onclick=\"return sf_ns.show_page('multiple_fields_found', " + field_id + ")\" class=\"red\">" + g.messages["phrase_multiple_fields_found"] + "</a></span>";
          $("field_" + field_id + "_options").innerHTML  = "";
          break;
      }
    }
  }

  sf_ns.log_activity(false);

  // disable the Smart Fill button. This is done because if the user is wanting to re-Smart fill the fields,
  // chances are they've tweaked one of their form pages - and in that case, re-Smart filling won't help: the iframe
  // contents need to be refreshed, which requires them to have clicked the "Refresh Page" button.
  $("smart_fill_button").disabled = true;
  $("smart_fill_button").removeClassName("blue");
  $("smart_fill_button").addClassName("light_grey");

  sf_ns.smart_filled = true;

  // if there were more than one fields not found, display the appropriate error message
  if (num_not_found_elements > 1)
  {
    var message = (page_ns.num_pages == 1) ? $("multiple_fields_not_found_single_page_form").innerHTML : $("multiple_fields_not_found_multi_page_form").innerHTML;
    ft.display_message("ft_message", false, message);
  }

  sf_ns.check_all_fields_complete();
}


/**
 * Displays any of the JS "pages".
 */
sf_ns.show_page = function(page_id, field_id)
{
// reset the temporary page values, just in case
  sf_ns.tmp_deleted_field_option_rows = [];

  Effect.Fade(sf_ns.current_page_id, { duration: 0.4 });
  switch (page_id)
  {
    case "review_field_options":
      sf_ns.log_activity(true); // closed in sf_ns.load_field_options_page
      sf_ns.generate_next_prev_links(field_id,
        ["review_field_options_previous_field_link", "review_field_options_previous_field_link2"],
        ["review_field_options_next_field_link", "review_field_options_next_field_link2"]);

      setTimeout(function() { sf_ns.load_field_options_page(field_id) }, 400);
      break;

    case "multiple_fields_found":
      sf_ns.generate_next_prev_links(field_id,
        ["multiple_fields_found_previous_field_link", "multiple_fields_found_previous_field_link2"],
        ["multiple_fields_found_next_field_link", "multiple_fields_found_next_field_link2"]);

      // if the previous page was ALSO a field option page, load the new page content in a timeout so it
      // doesn't get set before the old page has fully faded out
      setTimeout(function() { sf_ns.load_multiple_fields_found_page(field_id); }, 400);
      break;

    case "not_found":
      sf_ns.generate_next_prev_links(field_id,
        ["not_found_previous_field_link", "not_found_previous_field_link2"],
        ["not_found_next_field_link", "not_found_next_field_link2"]);

      // if the previous page was ALSO a field option page, load the new page content in a timeout so it
      // doesn't get set before the old page has fully faded out
      setTimeout(function() { sf_ns.load_not_found_page(field_id); }, 400);
      break;

    case "main_field_table":
      Effect.Appear($("main_field_table"), { duration: 0.4, delay: 0.4 });
      break;
  }

  sf_ns.current_page_id = page_id;

  return false;
}


/**
 * When the contents of the iframe is fully loaded (and, namely, the content is now available to JS), this
 * function is called. It logs the page as loaded. Once all form pages are loaded, it loads all the form
 * field information into memory for analyzing by the Smart Fill function.
 */
sf_ns.log_form_page_as_loaded = function(page)
{
  page_ns["form_" + page + "_loaded"] = true;

  if (sf_ns.check_all_pages_loaded())
  {
    sf_ns.log_activity(false);
    $("smart_fill_button").disabled = false;
    $("smart_fill_button").addClassName("blue");
    $("smart_fill_button").removeClassName("light_grey");
    sf_ns.itemize_form_fields();

    // if a manual file upload was just attempted, inform the user that they can now try to Smart Fill
    // the fields, based on the uploaded files
    if (sf_ns.manual_file_upload_attempted)
      ft.display_message("ft_message", true, g.messages["notify_smart_fill_files_uploaded_successfully"]);
  }
}


/**
 * This is called whenever the user explicitly clicks the "Refresh Page" button. It reloads the contents
 * of the iframes and empties the.
 */
sf_ns.refresh_page = function()
{
  if (!confirm("Are you sure you want to refresh the page? This will lose any changes you have made."))
    return false;

  for (var i=0; i<page_ns.field_ids.length; i++)
  {
    var field_id = page_ns.field_ids[i];
    $("field_" + field_id + "_type").value = "";
    $("field_" + field_id + "_action").innerHTML = "&#8212;";
    $("field_" + field_id + "_options").innerHTML = "&#8212;";
  }

  // reset all the field values
  sf_ns.itemized_fields = [];
  sf_ns.itemize_form_fields_complete = false;
  sf_ns.fields = [];
  sf_ns.current_field_id = null;
  sf_ns.current_page_id = "main_field_table";
  sf_ns.all_fields_complete = false;

  sf_ns.tmp_deleted_field_option_rows = [];

  // reload all the iframe contents
  for (var i=1; i<=page_ns.num_pages; i++)
  {
    page_ns["form_" + i + "_loaded"] = false;
    $("form_" + i + "_iframe").src = $("form_" + i + "_iframe").src;
  }

  $("smart_fill_button").disabled = false;
  $("smart_fill_button").addClassName("blue");
  $("smart_fill_button").removeClassName("light_grey");
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


sf_ns.load_multiple_fields_found_page = function(field_id)
{
  var field_name = sf_ns.fields["f" + field_id].field_name;
  var field_title = $("field_" + field_id + "_title").innerHTML;
  $("multiple_fields_found_field_title").innerHTML = field_title;
  Effect.Appear($("multiple_fields_found"), { delay: 0.4, duration: 0.4 });

  $("multiple_fields_found_action_needed").addClassName("error");
  var notify_multiple_fields_found = g.messages["notify_multiple_fields_found"].replace(/\{\$field_name\}/, field_name);
  $("multiple_fields_found_action_needed").innerHTML = "<div style=\"padding:6px\">" + notify_multiple_fields_found + "</div>";

  // create and populate the table listing the fields found
  var row = document.createElement("tr");
  var th1 = document.createElement("th");
  th1.setAttribute("width", "50");
  var th2 = document.createElement("th");
  th2.appendChild(document.createTextNode(g.messages["phrase_field_type"]));
  th2.setAttribute("width", "150");
  var th3 = document.createElement("th");
  th3.appendChild(document.createTextNode(g.messages["phrase_form_page"]));
  var th4 = document.createElement("th");
  th4.appendChild(document.createTextNode(g.messages["word_options"]));
  var th5 = document.createElement("th");
  th5.setAttribute("width", "80");
  th5.appendChild(document.createTextNode(g.messages["word_select"].toUpperCase()));

  row.appendChild(th1);
  row.appendChild(th2);
  row.appendChild(th3);
  row.appendChild(th4);
  row.appendChild(th5);

  $("multiple_fields_found_table").getElementsByTagName("tbody")[0].innerHTML = "";
  var tbody = $("multiple_fields_found_table").getElementsByTagName("tbody")[0];
  tbody.appendChild(row);

  // find the duplicate elements for this field
  var field_name_index = null;
  for (var i=0; i<sf_ns.itemized_fields.length; i++)
  {
    if (sf_ns.itemized_fields[i].element_name == field_name)
      field_name_index = i;
  }

  for (var i=0; i<sf_ns.itemized_fields[field_name_index].elements.length; i++)
  {
    var field_info = sf_ns.itemized_fields[field_name_index].elements[i];
    var field_type = field_info.element_type;
    var page_num   = field_info.form_page;
    var row_number = i+1;

    var row = document.createElement("tr");
    var td1 = document.createElement("td");
    td1.setAttribute("width", "50");
    td1.setAttribute("align", "center");
    td1.appendChild(document.createTextNode(row_number));

    var td2 = document.createElement("td");

    var field_type_string = "";
    switch (field_type)
    {
      case "textbox":
        field_type_string = g.messages["word_textbox"];
        break;
      case "password":
        field_type_string = g.messages["word_password"];
        break;
      case "file":
        field_type_string = g.messages["word_file"];
        break;
      case "radio-buttons":
        field_type_string = g.messages["phrase_radio_buttons"];
        break;
      case "checkboxes":
        field_type_string = g.messages["word_checkboxes"];
        break;
      case "textarea":
        field_type_string = g.messages["word_textarea"];
        break;
      case "select":
        field_type_string = g.messages["word_select"];
        break;
      case "multi-select":
        field_type_string = g.messages["phrase_multi_select"];
        break;
    }

    td2.appendChild(document.createTextNode(field_type_string));
    td2.setAttribute("width", "150");
    $(td2).addClassName("pad_left_small");

    var td3 = document.createElement("td");
    var a = document.createElement("a");
    a.setAttribute("href", page_ns["form_" + page_num + "_url"]);
    a.setAttribute("target", "_blank");
    a.appendChild(document.createTextNode(g.messages["word_page"] + " " + page_num));
    td3.appendChild(a);

    var td4 = document.createElement("td");
    $(td4).addClassName("pad_left_small");


    if (field_info.option_list)
      td4.appendChild(document.createTextNode(field_info.option_list.toString().truncate(40)));
    else
      td4.appendChild(document.createTextNode(g.messages["word_na"]));

    var td5 = document.createElement("td");
    td5.setAttribute("align", "center");
    var a = document.createElement("a");
    a.setAttribute("href", "#");
    a.onclick = sf_ns.choose_field.bind(this, field_id, sf_ns.itemized_fields[field_name_index].elements[i]);
    a.appendChild(document.createTextNode(g.messages["word_select"].toUpperCase()));
    td5.appendChild(a);

    row.appendChild(td1);
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);
    row.appendChild(td5);

    tbody.appendChild(row);
  }
}


sf_ns.load_not_found_page = function(field_id)
{
  sf_ns.current_field_id = field_id;

  var field_title = $("field_" + field_id + "_title").innerHTML;
  var field_name = sf_ns.fields["f" + field_id].field_name;
  $("not_found_field_type").value = "";

  $("not_found_field_title").innerHTML = field_title;
  Effect.Appear($("not_found"), { delay: 0.4, duration: 0.4 });

  $("not_found_action_needed").removeClassName("notify");
  $("not_found_action_needed").addClassName("error");

  var notify_smart_fill_field_not_found = g.messages["notify_smart_fill_field_not_found"].replace(/\{\$field_name\}/, field_name);
  $("not_found_action_needed").innerHTML = "<div style=\"padding:6px\">" +  notify_smart_fill_field_not_found + "</div>";
}


/**
 * Called on the Not Found page after the user selects a form type. It saves the value & displays the
 * appropriate message.
 */
sf_ns.choose_field_type = function()
{
  var selected_field_type = $("not_found_field_type").value;
  if (!selected_field_type)
  {
    alert(g.messages["validation_select_field_type"]);
    return;
  }

  sf_ns.fields["f" + sf_ns.current_field_id].is_complete = true;
  sf_ns.fields["f" + sf_ns.current_field_id].status = "resolved";
  sf_ns.fields["f" + sf_ns.current_field_id].field_type = selected_field_type;

  $("field_" + sf_ns.current_field_id + "_action").innerHTML = "<span class=\"medium_grey\">" + g.messages["word_resolved"] + "</span>";
  $("field_" + sf_ns.current_field_id + "_options").innerHTML = "";

  $("not_found_action_needed").removeClassName("error");
  $("not_found_action_needed").addClassName("notify");

  if (selected_field_type == "radio-buttons" || selected_field_type == "checkboxes" ||
      selected_field_type == "select" || selected_field_type == "multi-select")
  {
    $("not_found_action_needed").innerHTML = "<div style=\"padding:6px;\">" + g.messages["notify_multi_field_updated"] + "</div>";
  }
  else
  {
    $("not_found_action_needed").innerHTML = "<div style=\"padding:6px;\">" + g.messages["notify_field_updated"] + "</div>";
  }

  sf_ns.check_all_fields_complete();
}


/**
 * This can be called on the Not Found page, letting a user skip setting it up for now.
 */
sf_ns.skip_field = function()
{
  sf_ns.fields["f" + sf_ns.current_field_id].is_complete = true;
  sf_ns.fields["f" + sf_ns.current_field_id].status = "skipped";

  $("field_" + sf_ns.current_field_id + "_action").innerHTML = "<span class=\"medium_grey\">" + g.messages["word_skipped"] + "</span>";
  $("field_" + sf_ns.current_field_id + "_options").innerHTML = "";

  $("not_found_action_needed").removeClassName("error");
  $("not_found_action_needed").addClassName("notify");
  $("not_found_action_needed").innerHTML = "<div style=\"padding:6px;\">" + g.messages["phrase_field_skipped"] + "</div>";

  sf_ns.check_all_fields_complete();
}


/**
 * This is called by the user after clicking on one of the SELECT links on the "Multiple fields found" page.
 * It updates the info stored in memory and displays the appropriate message on the page.
 */
sf_ns.choose_field = function(field_id, itemized_field_info)
{
  sf_ns.fields["f" + field_id].field_type = itemized_field_info.element_type;
  $("field_" + field_id + "_type").value = itemized_field_info.element_type;

  sf_ns.fields["f" + field_id].status = "resolved";
  sf_ns.fields["f" + field_id].is_complete = true;
  $("field_" + field_id + "_action").innerHTML = "<span class=\"light_grey\">" + g.messages["word_none"] + "</span>";

  // if this chosen field has an option list, copy that over too
  if (itemized_field_info.option_list)
  {
    sf_ns.fields["f" + field_id].option_list = itemized_field_info.option_list;
    var num_options = sf_ns.fields["f" + field_id].option_list.length;
    $("field_" + field_id + "_options").innerHTML = "<a href=\"#\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["word_options"] + " (" + num_options + ")</span>";
  }
  else
    $("field_" + field_id + "_options").innerHTML = "<span class=\"light_grey\">" + g.messages["word_na"] + "</span>";

  $("multiple_fields_found_action_needed").removeClassName("error");
  $("multiple_fields_found_action_needed").addClassName("notify");

  if (itemized_field_info.option_list)
  {
    var notify_multi_field_selected = g.messages["notify_multi_field_selected"].replace(/\{\$onclick\}/, "return sf_ns.show_page('review_field_options', '" + field_id + "')");
    $("multiple_fields_found_action_needed").innerHTML = "<div style=\"padding:6px\">" + notify_multi_field_selected + "</div>";
  }
  else
  {
    var notify_field_selected = g.messages["notify_field_selected"].replace(/\{\$onclick\}/, "return sf_ns.show_page('main_field_table', null)");
    $("multiple_fields_found_action_needed").innerHTML = "<div style=\"padding:6px\">" + notify_field_selected + "</div>";
  }

  sf_ns.check_all_fields_complete();
}


/**
 * This populates the Field Option page. This page is used for radio buttons, checkboxes, select and
 * multi-select elements to display the option list, orientation (if radios or checkboxes) and notify
 * the user of any actions that need to be performed on it.
 */
sf_ns.load_field_options_page = function(field_id)
{
  sf_ns.current_field_id = field_id;

  // display the appropriate "action needed" message for this item (there can be only one)
  sf_ns.display_action_needed_message(field_id, "review_field_options_action_needed");

  var field_info = sf_ns.fields["f" + field_id];
  var field_type = sf_ns.fields["f" + field_id].field_type;
  var field_title = $("field_" + field_id + "_title").innerHTML;
  $("review_field_options_field_title").innerHTML = field_title;

  // if this is a checkbox or radio buttons, display the orientation option
  var html = "";
  if (field_type == "radio-buttons" || field_type == "checkboxes")
  {
    var orientation = sf_ns.fields["f" + field_id].orientation;

    html = "<input type=\"radio\" name=\"orientation\" id=\"orientation1\" value=\"horizontal\" ";
    if (orientation == "horizontal")
      html += "checked";
    html += " /> <label for=\"orientation1\">" + g.messages["word_horizontal"] + "</label> &nbsp;";

    html += "<input type=\"radio\" name=\"orientation\" id=\"orientation2\" value=\"vertical\" ";
    if (orientation == "vertical")
      html += "checked";
    html += " /> <label for=\"orientation2\">" + g.messages["word_vertical"] + "</label>";

    $("review_options_values_to_text").show();
  }
  else
  {
    html = "<span class=\"light_grey\">" + g.messages["word_na"] + "</span>";
    $("review_options_values_to_text").hide();
  }

  $("review_field_options_field_orientation").innerHTML = html;


  // generate the field option table
  $("review_field_options_table").getElementsByTagName("tbody")[0].innerHTML = "";
  var tbody = $("review_field_options_table").getElementsByTagName("tbody")[0];

  // add the table heading row
  var row = document.createElement("tr");

  // [1] first cell: row number
  var th1 = document.createElement("th");
  th1.setAttribute("width", "45");
  th1.appendChild(document.createTextNode(g.messages["word_order"]));

  // [2] second <td> cell: "value" field
  var th2 = document.createElement("th");
  th2.appendChild(document.createTextNode(g.messages["phrase_field_value"]));

  // [3] second <td> cell: "display text" field
  var th3 = document.createElement("th");
  th3.appendChild(document.createTextNode(g.messages["phrase_display_value"]));

  // [4] delete column
  var th4 = document.createElement("th");
  th4.className = "del";
  th4.appendChild(document.createTextNode(g.messages["word_delete"].toUpperCase()));

  row.appendChild(th1);
  row.appendChild(th2);
  row.appendChild(th3);
  row.appendChild(th4);
  tbody.appendChild(row);

  // now add the custom options
  var num_fields = sf_ns.fields["f" + field_id].option_list.length;
  for (var i=0; i<num_fields; i++)
  {
    var currRow = i;
    var option_info = sf_ns.fields["f" + field_id].option_list[i];

    var row = document.createElement("tr");
    row.setAttribute("id", "field_option_row" + currRow);

    // [1] first cell: row number
    var td1 = document.createElement("td");
    td1.setAttribute("align", "center");
    td1.setAttribute("id", "field_option_" + currRow + "_order");
    var row_num_label = currRow+1;
    td1.appendChild(document.createTextNode(row_num_label));

    // [2] second <td> cell: "value" field
    var td2 = document.createElement("td");
    var title = document.createElement("input");
    title.setAttribute("type", "text");
    title.setAttribute("name", "field_option_value_" + currRow);
    title.setAttribute("id", "field_option_value_" + currRow);
    title.style.cssText = "width: 98%";
    title.setAttribute("value", option_info[0]);
    td2.appendChild(title);

    // [3] second <td> cell: "display text" field
    var td3 = document.createElement("td");
    var title = document.createElement("input");
    title.setAttribute("type", "text");
    title.setAttribute("name", "field_option_text_" + currRow);
    title.setAttribute("id", "field_option_text_" + currRow);
    title.style.cssText = "width: 98%";
    title.setAttribute("value", option_info[1]);

    td3.appendChild(title);

    // [4] delete column
    var td4 = document.createElement("td");
    td4.setAttribute("align", "center");
    td4.className = "del";
    var del_link = document.createElement("a");
    del_link.setAttribute("href", "#");
    del_link.onclick = sf_ns.delete_field_option.bind(this, currRow);
    del_link.appendChild(document.createTextNode(g.messages["word_delete"].toUpperCase()));
    td4.appendChild(del_link);

    // add the table data cells to the row
    row.appendChild(td1);
    row.appendChild(td2);
    row.appendChild(td3);
    row.appendChild(td4);

    // add the row to the table
    tbody.appendChild(row);
  }

  // when the last row that has been inserted is accessible by JS, that means it's visible in the page.
  var last_row = num_fields - 1;

  ft.queue.push([
    function() {
      Effect.Appear($("review_field_options"), { duration: 0.4, delay: 0.4 });
      setTimeout(function() { sf_ns.log_activity(false) }, 400);
    },
    function() {
      return $("field_option_text_" + last_row);
    }
  ]);
  ft.process_queue();
}


sf_ns.set_display_values_from_field_values = function()
{
  for (var i=0; i<sf_ns.fields["f" + sf_ns.current_field_id].option_list.length; i++)
  {
    if ($("field_option_value_" + i))
      $("field_option_text_" + i).value = $("field_option_value_" + i).value;
  }
}


/**
 * This is called on all the pages that focus on an individual field. It generates an appropriate
 * message, informing the user of the status of the field.
 */
sf_ns.display_action_needed_message = function(field_id, target_id)
{
  var field_info = sf_ns.fields["f" + field_id];

  if (field_info.is_complete)
  {
    $(target_id).removeClassName("error");
    $(target_id).removeClassName("notify");
    $(target_id).innerHTML = "<span class=\"light_grey\">" + g.messages["word_none"] + "</span>";
  }
  else
  {
    switch (field_info.status)
    {
      case "add_field_values":
        $(target_id).addClassName("error");
        $(target_id).innerHTML = "<div style=\"padding:6px\">" + g.messages["notify_add_display_values"] + "</div>";
        break;
    }
  }
}


/**
 * Confirms that all pages are loaded.
 */
sf_ns.check_all_pages_loaded = function()
{
  var all_pages_loaded = true;
  for (var i=1; i<=page_ns.num_pages; i++)
  {
    if (!page_ns["form_" + i + "_loaded"])
      all_pages_loaded = false;
  }

  return all_pages_loaded;
}


/**
 * Our storage function. This examines all the forms and itemizes all the form fields for examination
 * by the Smart Fill function. The field data is organized in the sf_ns.itemized_fields array.
 *
 * Each index is an object containing information about the field. It has the following properties:
 *     info.element_name: the name of the form field. Note: for groups of checkboxes using the same
 *                        name and multi-select fields, this value contains the [] suffix (which is
 *                        NOT stored in the FT database - it gets lost in transit).
 *     info.elements:     an array of all elements with this name attribute. Each element has the
 *                        following info stored:
 *         element_type:  "textbox", "file", "password", "radio-buttons", "checkboxes",
 *                        "multi-select", "select"
 *         option_list:   for options with field lists (dropdowns, multi-select dropdowns, checkbox
 *                        groups, radio buttons), this stores the list of options in the fields. For
 *                        select and multi-select fields, this is an array of arrays: each subarray
 *                        storing both the value and display text of the option nodes. For the other
 *                        types, it just stores an array of values (since we won't hazard a guess at
 *                        the (possibly non-existent) label.
 *
 * After locating & processing the fields, it also sets two extra (seemingly duplicate) properties:
 *
 *     info.element_type: This is set to the same type as the info.elements[X].element_type IFF (if
 *                        and only if) all the fields are the same. Otherwise it's set to "mixed".
 *     info.option_list:  again, if all the fields are of the same type and either radios, checkboxes
 *                        or a SINGLE select or multi-select field, this contains the (single) group
 *                        of option lists.
 *
 * Once it has finished processing, it registers complete by setting:
 *   sf_ns.itemize_form_fields_complete = true;
 */
sf_ns.itemize_form_fields = function()
{
  // process the forms, logging information about all the form fields.
  for (var form_num=1; form_num<=page_ns.num_pages; form_num++)
  {
    var form_iframe = window["form_" + form_num + "_iframe"].window.document;
    var num_forms  = form_iframe.forms.length;
    for (var j=0; j<num_forms; j++)
    {
      // loop through every element in this form
      var elements = sf_ns.get_form_elements(form_iframe.forms[j]);

      for (var k=0; k<elements.length; k++)
      {
        var el = elements[k];

        // if this element doesn't have a name, skip it - we're not interested in it
        if (!el.name)
          continue;

        var info = {};
        var element_name = el.name;
        info.element_type = null;
        info.form_page = form_num;


        // see if a field with this name has already been added
        var existing_field_name_index = null;
        for (var p=0; p<sf_ns.itemized_fields.length; p++)
        {
          if (sf_ns.itemized_fields[p].element_name == element_name)
          {
            existing_field_name_index = p;
            break;
          }
        }

        switch (el.tagName)
        {
          case "INPUT":
            switch (el.type)
            {
              case "text":
                info.element_type = "textbox";
                break;
              case "file":
                info.element_type = "file";
                break;
              case "password":
                info.element_type = "password";
                break;

              case "radio":
                info.element_type = "radio-buttons";
                info.option_list = [];
                if (form_iframe.forms[j][el.name].length != undefined)
                {
                  var num_options = form_iframe.forms[j][el.name].length;
                  for (var n=0; n<num_options; n++)
                  {
                    info.option_list.push([form_iframe.forms[j][el.name][n].value, null]);
                  }
                }
                else
                  info.option_list.push([form_iframe.forms[j][el.name].value, ""]);
                break;

              case "checkbox":
                info.element_type = "checkboxes";
                info.option_list = [];
                if (form_iframe.forms[j][el.name].length != undefined)
                {
                  var num_options = form_iframe.forms[j][el.name].length;
                  for (var n=0; n<num_options; n++)
                    info.option_list.push([form_iframe.forms[j][el.name][n].value, null]);
                }
                else
                  info.option_list.push([form_iframe.forms[j][el.name].value, ""]);
                break;

              // this may seem odd, but for lack of anything better we store these two form field types
              // as textboxes. Neither would make sense as any other types of field & in most cases, they
              // won't be stored
              case "submit":
              case "hidden":
                info.element_type = "textbox";
                break;

              // ignore buttons and reset fields
            }
            break;

          case "SELECT":
            if (el.multiple)
              info.element_type = "multi-select";
            else
              info.element_type = "select";

            //var num_options = form_iframe.forms[j][el.name].options.length;
            var num_options = el.options.length;
            info.option_list = [];
            for (var n=0; n<num_options; n++)
            {
              var val = el.options[n].value;
              var txt = el.options[n].text;
              info.option_list.push([val, txt]);
            }
            break;

          case "TEXTAREA":
            info.element_type = "textarea";
            break;
        }

        if (info.element_type != null)
        {
          if (existing_field_name_index == null)
          {
            var field_info = {
              element_name: element_name,
              elements: [info]
            }
            sf_ns.itemized_fields.push(field_info);
          }
          else
          {
            // if this isn't a radio or checkbox (which have already been added), add it to the end of the list
            if (info.element_type != "radio-buttons" && info.element_type != "checkboxes")
            {
              sf_ns.itemized_fields[existing_field_name_index].elements.push(info);
            }
          }
        }
      }
    }
  }

  for (var i=0; i<sf_ns.itemized_fields.length; i++)
  {
    // find out the field type(s) that have this field name

    // case 1: there's just a single field
    if (sf_ns.itemized_fields[i].elements.length == 1)
    {
      sf_ns.itemized_fields[i].field_type = sf_ns.itemized_fields[i].elements[0].element_type;
      sf_ns.itemized_fields[i].option_list = sf_ns.itemized_fields[i].elements[0].option_list;
    }

    // case 2: there were multiple fields with this field name. This should only happen for radio buttons
    // and checkboxes, but it's possible the user incorrectly entered the same field name more than once
    // for other field types. This section examines the fields and makes a note of potential problems
    else
    {
      var full_option_list = [];
      var all_field_types = [];
      for (var j=0; j<sf_ns.itemized_fields[i].elements.length; j++)
      {
        all_field_types.push(sf_ns.itemized_fields[i].elements[j].element_type);
        full_option_list.push(sf_ns.itemized_fields[i].elements[j].option_list);
      }

      all_unique_field_types = all_field_types.uniq();

      // if there were more than one field type with this name, make a note of the error
      if (all_unique_field_types.length > 1)
        sf_ns.itemized_fields[i].field_type = "mixed";

      // if this field type wasn't a set of radios or checkboxes, make a note of the error
      else if (all_unique_field_types[0] != "radio-buttons" && all_unique_field_types[0] != "checkboxes")
        sf_ns.itemized_fields[i].field_type = "mixed";

      // otherwise all is well!
      else
      {
        sf_ns.itemized_fields[i].field_type = all_unique_field_types[0];
        sf_ns.itemized_fields[i].option_list = full_option_list;
      }
    }
  }
}


sf_ns.get_form_elements = function(f)
{
  var form_elements = [];
  for (var i=0; i<f.elements.length; i++)
  {
    form_elements.push(f.elements[i]);
  }

  return form_elements;
}


/**
 * This deletes a field option. Note: it doesn't remove the option in memory; that is handled by
 * _update_current_field_settings(), which is called when the user leaves the page & when
 */
sf_ns.delete_field_option = function(row)
{
  // before we delete the row, get it's Order column value
  var order = parseInt($("field_option_" + row + "_order").innerHTML);

  $("field_option_row" + row).remove();
  sf_ns.tmp_deleted_field_option_rows.push(row);

  // update the order of all subsequent rows
  for (var i=row+1; i<sf_ns.fields["f" + sf_ns.current_field_id].option_list.length; i++)
  {
    // if the row has already been deleted, just ignore it
    if (!$("field_option_" + i + "_order"))
      continue;

    $("field_option_" + i + "_order").innerHTML = order;
    order++;
  }

  return false;
}


/**
 * Adds a field option for the currently selected field (dropdown, radio or checkbox).
 */
sf_ns.add_field_option = function()
{
  // find out how many rows there already is
  var num_rows = sf_ns.fields["f" + sf_ns.current_field_id].option_list.length;
  var next_id = num_rows;

  // now a null placeholder entry to the end of the field option list. It's not used, and will be
  // automatically overwritten when the user approves the field or leaves the page
  sf_ns.fields["f" + sf_ns.current_field_id].option_list.push(null);

  var row = document.createElement("tr");
  row.setAttribute("id", "field_option_row" + next_id);

  // [1] first cell: row number
  var td1 = document.createElement("td");
  td1.setAttribute("align", "center");
  td1.setAttribute("id", "field_option_" + next_id + "_order");
  var num_deleted_rows = sf_ns.tmp_deleted_field_option_rows.length;
  var row_num_label = (next_id + 1) - num_deleted_rows;
  td1.appendChild(document.createTextNode(row_num_label));

  // [2] second <td> cell: "value" field
  var td2 = document.createElement("td");
  var title = document.createElement("input");
  title.setAttribute("type", "text");
  title.setAttribute("name", "field_option_value_" + next_id);
  title.setAttribute("id", "field_option_value_" + next_id);
  title.style.cssText = "width: 98%";
  td2.appendChild(title);

  // [3] second <td> cell: "display text" field
  var td3 = document.createElement("td");
  var title = document.createElement("input");
  title.setAttribute("type", "text");
  title.setAttribute("name", "field_option_text_" + next_id);
  title.setAttribute("id", "field_option_text_" + next_id);
  title.style.cssText = "width: 98%";
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
  var tbody = $("review_field_options_table").getElementsByTagName("tbody")[0];
  tbody.appendChild(row);
}


/**
 * This is called when the user updates any particular field.
 */
sf_ns.update_field = function(message_target_id)
{
  // mark this field as approved and update the various page elements + data structures
  sf_ns.fields["f" + sf_ns.current_field_id].is_complete = true;
  sf_ns.display_action_needed_message(sf_ns.current_field_id, message_target_id);
  sf_ns._update_current_field_settings();

  // if this field had been marked as skipped, it's just been
  if (sf_ns.fields["f" + sf_ns.current_field_id].status == "skipped")
  {
    sf_ns.fields["f" + sf_ns.current_field_id].status = "found";
    $("field_" + sf_ns.current_field_id + "_action").innerHTML = "<span class=\"light_grey\">" + g.messages["word_none"] + "</span>";
  }

  $(message_target_id).removeClassName("error");
  $(message_target_id).removeClassName("notify");
  $(message_target_id).innerHTML = "<div class=\"notify\"><div style=\"padding:6px;\">" + g.messages["notify_field_updated"] + "</div></div>";

  sf_ns.check_all_fields_complete();
}


/**
 * This is called after a field is approved or marked as "will resolve later" (or something). It checks to
 * see if all the fields have been marked as complete. If so, displays the "next step >>" link.
 */
sf_ns.check_all_fields_complete = function()
{
  var all_fields_complete = true;
  for (var i=0; i<page_ns.field_ids.length; i++)
  {
    var field_id = page_ns.field_ids[i];
    if (!sf_ns.fields["f" + field_id].is_complete)
      all_fields_complete = false;
  }

  sf_ns.all_fields_complete = all_fields_complete;

  if (all_fields_complete)
  {
    $("next_step").removeClassName("light_grey");
    $("next_step").addClassName("next_step");
    $("next_step").disabled = false;
  }
}


/**
 * This function is called whenever the user LEAVES a fields options page. It saves the current
 * values for the field - options and the field orientation.
 */
sf_ns._update_current_field_settings = function()
{
  var new_option_list = [];
  var max_num_options = sf_ns.fields["f" + sf_ns.current_field_id].option_list.length;
  for (var i=0; i<max_num_options; i++)
  {
    if ($(sf_ns.tmp_deleted_field_option_rows).include(i))
      continue;

    if ($("field_option_" + i + "_order"))
      new_option_list.push([$("field_option_value_" + i).value, $("field_option_text_" + i).value]);
  }

  sf_ns.fields["f" + sf_ns.current_field_id].option_list = new_option_list;
  sf_ns._update_field_main_table_settings(sf_ns.current_field_id);

  if (sf_ns.fields["f" + sf_ns.current_field_id].field_type == "radio-buttons" ||
      sf_ns.fields["f" + sf_ns.current_field_id].field_type == "checkboxes")
  {
    var orientation = ($("orientation1").checked) ? "horizontal" : "vertical";
    sf_ns.fields["f" + sf_ns.current_field_id].orientation = orientation;
  }
}


// update the columns on the main table.
sf_ns._update_field_main_table_settings = function(field_id)
{
  if (sf_ns.fields["f" + field_id].status == "skipped")
    $("field_" + field_id + "_action").innerHTML = "<span class=\"medium_grey\">" + g.messages["word_skipped"] + "</span>";
  else
    $("field_" + field_id + "_action").innerHTML = "<span class=\"light_grey\">" + g.messages["word_none"] + "</span>";

  var num_options = sf_ns.fields["f" + field_id].option_list.length;
  $("field_" + field_id + "_options").innerHTML = "<a href=\"#\" onclick=\"return sf_ns.show_page('review_field_options', '" + field_id + "')\">" + g.messages["word_options"] + " (" + num_options + ")</span>";
}


/**
 * This function generates and inserts the << previous field + next field >> links at the appropriate
 * spots.
 *
 * @param field_id the current field ID
 * @param prev_link_id an array of IDs of the page element(s) where the previous links should be inserted
 * @param next_link_id an array of IDs of the page element(s) where the next link should be inserted
 */
sf_ns.generate_next_prev_links = function(field_id, prev_link_ids, next_link_ids)
{
  var links = sf_ns.get_next_prev_field_ids(field_id);
  previous_field_id = links[0];
  next_field_id = links[1];


  // PREVIOUS link
  var prev_link_html = "";
  if (previous_field_id == null)
    prev_link_html = "<span class=\"light_grey\">" + g.messages["phrase_previous_field"] + "</span>";
  else
  {
    var previous_field_type = sf_ns.fields["f" + previous_field_id].field_type;

    var page = "review_field_options";
    if (previous_field_type == "mixed")
      page = "multiple_fields_found";
    if (sf_ns.fields["f" + previous_field_id].status == "not_found")
      page = "not_found";
    prev_link_html = "<a href=\"#\" onclick=\"return sf_ns.show_page('" + page + "', " + previous_field_id + ")\">" + g.messages["phrase_previous_field"] + "</a>";
  }

  for (var i=0; i<prev_link_ids.length; i++)
    $(prev_link_ids[i]).innerHTML = prev_link_html;

  var next_link_html = "";
  if (next_field_id == null)
    next_link_html = "<span class=\"light_grey\">" + g.messages["phrase_next_field"] + "</span>";
  else
  {
    var next_field_type = sf_ns.fields["f" + next_field_id].field_type;
    var page = "review_field_options";
    if (next_field_type == "mixed")
      page = "multiple_fields_found";
    if (sf_ns.fields["f" + next_field_id].status == "not_found")
      page = "not_found";
    next_link_html = "<a href=\"#\" onclick=\"return sf_ns.show_page('" + page + "', " + next_field_id + ")\">" + g.messages["phrase_next_field"] + "</a>";
  }

  for (var i=0; i<next_link_ids.length; i++)
    $(next_link_ids[i]).innerHTML = next_link_html;
}


/**
 * Figures out what IDs should be linked to in the << previous and next >> links. This is "smart" in that
 * it only links to fields that need the attention of the user.
 */
sf_ns.get_next_prev_field_ids = function(field_id)
{
  var previous_field_id = null;
  var next_field_id     = null;

  var field_index = null;
  for (var i=0; i<page_ns.field_ids.length; i++)
  {
    if (page_ns.field_ids[i] == field_id)
      field_index = i;
  }

  if (field_index > 0)
  {
    for (var i=field_index-1; i>=0; i--)
    {
      var curr_field_id = page_ns.field_ids[i];
      if (!sf_ns.fields["f" + curr_field_id].is_complete)
      {
        previous_field_id = curr_field_id;
        break;
      }
    }
  }

  if (field_index < page_ns.field_ids.length-1)
  {
    for (var i=field_index+1; i<page_ns.field_ids.length; i++)
    {
      var curr_field_id = page_ns.field_ids[i];
      if (!sf_ns.fields["f" + curr_field_id].is_complete)
      {
        next_field_id = curr_field_id;
        break;
      }
    }
  }

  return [previous_field_id, next_field_id];
}


/**
 * This function overrides any problems that arose and/or fields that haven't had their action
 * resolved.
 */
sf_ns.skip_step = function()
{
  // loop through all incomplete fields and mark them as complete
  for (var i=0; i<page_ns.field_ids.length; i++)
  {
    var field_id = page_ns.field_ids[i];

    if (!sf_ns.smart_filled)
    {
      sf_ns.fields["f" + field_id] = { status: "skipped" }
      $("field_" + field_id + "_action").innerHTML = "<span class=\"medium_grey\">" + g.messages["word_skipped"] + "</span>";
      sf_ns.fields["f" + field_id].is_complete = true;
    }
    else if (!sf_ns.fields["f" + field_id].is_complete)
    {
      sf_ns.fields["f" + field_id].status = "skipped";
      $("field_" + field_id + "_action").innerHTML = "<span class=\"medium_grey\">" + g.messages["word_skipped"] + "</span>";
      sf_ns.fields["f" + field_id].is_complete = true;
    }
  }

  sf_ns.check_all_fields_complete();
}


/**
 * This function can be optionally called by the user if the Smart Fill couldn't read the web pages. It
 * uploads copies of the forms to the server. The
 */
sf_ns.validate_upload_files = function(f)
{
  // check all the fields have been entered
  for (var i=1; i<=page_ns.num_pages; i++)
  {
    if (!f["form_page_" + i].value)
    {
      alert(g.messages["validation_smart_fill_upload_all_pages"]);
      return false;
    }

    // check it has a .html or .htm file
    if (!f["form_page_" + i].value.match(/(\.htm|\.html)$/))
    {
      alert(g.messages["validation_upload_html_files_only"]);
      return false;
    }
  }

  // show the processing icon; this is turned off when the custom onload handler is executed for the
  // upload_files_iframe
  sf_ns.log_activity(true);

  return true;
}


/**
 * This is called when the files have been uploaded to the server to be Smart Filled. If all
 * went well, the iframe should now contain a JSON string listing the files (and paths).
 */
sf_ns.log_files_as_uploaded = function()
{
  // this prevents anything being (unnecessarily) fired on the initial page load
  if (!sf_ns.log_files_as_uploaded_page_loaded)
  {
    sf_ns.log_files_as_uploaded_page_loaded = true;
    return;
  }

  sf_ns.log_activity(false);

  var response = $("upload_files_iframe").contentWindow.document.body.innerHTML;
  try {
    var info = response.evalJSON();
    if (info.success)
    {
      // so far so good! Here, the files have been uploaded to the server and should be accessible
      // to the JS. Get the file URLs from response and load the iframes. When that's done, a message
      // is displayed to the user telling them to try Smart Filling now. Since the
      sf_ns.manual_file_upload_attempted = true;
      for (var i=1; i<=page_ns.num_pages; i++)
      {
        page_ns["form_" + i + "_loaded"] = false;
        $("form_" + i + "_iframe").src = info["url_" + i];
      }
    }
    else
    {
      ft.display_message("ft_message", false, info.message);
    }
  }
  // should never occur, but...
  catch (err)
  {
    ft.display_message("ft_message", false, g.messages["notify_smart_fill_upload_fields_fail"]);
  }

}

/**
 * This function handles the all-important form submit. It sends all the form data to the server via
 * an Ajax call, and redirects to the final page when complete.
 */
sf_ns.submit_form = function()
{
  var params = {
    action: "process_smart_fill_contents"
  };

  for (var i=0; i<page_ns.field_ids.length; i++)
  {
    var field_id   = page_ns.field_ids[i];
    var field_info = sf_ns.fields["f" + field_id];
    var field_type = sf_ns.fields["f" + field_id].field_type;
    params["field_" + field_id + "_type"] = field_type;

    switch (field_type)
    {
      case "radio-buttons":
      case "checkboxes":
        params["field_" + field_id + "_orientation"] = field_info.orientation;

        var num_options = field_info.option_list.length;
        params["field_" + field_id + "_num_options"] = num_options;
        for (var j=0; j<num_options; j++)
        {
          var row_num = j+1;
          params["field_" + field_id + "_opt" + row_num + "_val"] = field_info.option_list[j][0];
          params["field_" + field_id + "_opt" + row_num + "_txt"] = field_info.option_list[j][1];
        }
        break;

      case "select":
      case "multi-select":
        var num_options = field_info.option_list.length;
        params["field_" + field_id + "_num_options"] = num_options;
        for (var j=0; j<num_options; j++)
        {
          var row_num = j+1;
          params["field_" + field_id + "_opt" + row_num + "_val"] = field_info.option_list[j][0];
          params["field_" + field_id + "_opt" + row_num + "_txt"] = field_info.option_list[j][1];
        }
        break;
    }
  }

  // display the "please be patient" message,
  ft.display_message("next_step_message", true, g.messages["notify_smart_fill_submitted"]);
  $("next_step").disabled = true; // disable the next step button, just in case
  $("ajax_activity_bottom").show();
  $("ajax_no_activity_bottom").hide();


  page_url = g.root_url + "/global/code/actions.php";
  new Ajax.Request(page_url, {
    parameters: params,
    method: "post",
    onSuccess: sf_ns.submit_form_response
  });
}


sf_ns.submit_form_response = function(transport)
{
  try {
    var info = transport.responseText.evalJSON();
    if (info.success)
      window.location = "step6.php";
  }
  catch (err)
  {
    ft.display_message("next_step_message", false, transport.responseText);
  }
}