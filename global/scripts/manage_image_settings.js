/**
 * File: manage_field_options.js
 *
 * This contains all JS for the Image Settings page, where the user may define their own custom settings
 * for their image upload field. Basically this code allows them to dynamically add & remove their own custom
 * settings from a settings table.
 */

var image_settings_ns = {};
image_settings_ns.num_rows = 0;
image_settings_ns.deleted_rows = [];


/**
 * Adds one or more rows to the fields options page for a multi-select element (radio buttons, checkboxes,
 * select or multi-select).
 */
image_settings_ns.add_row = function()
{
  var row = ++image_settings_ns.num_rows;

  $("customize_settings").style.display = "block";
  $("default_settings").style.display = "none";

  var tbody = document.getElementById("image_settings_table").getElementsByTagName("tbody")[0];
  var tr = document.createElement("tr");
  tr.setAttribute("id", "setting_row_" + row);

  // [1] Settings dropdown
  var td1 = document.createElement("td");
  td1.setAttribute("valign", "top");
  var tmpdiv = document.createElement("div");
  tmpdiv.innerHTML = $("section_image_settings").innerHTML.replace(/%%X%%/g, row);
  var selects = tmpdiv.getElementsByTagName("select");
  var select = selects[0];
  select.onchange = function() { image_settings_ns.select_setting(row); };
  td1.appendChild(select);

  // [2] the Values table cell
  var td2 = document.createElement("td");
  td2.setAttribute("valign", "top");
  var div = document.createElement("div");
  div.setAttribute("id", "values_" + row);
  td2.appendChild(div);

  // [3] Delete link
  var td3 = document.createElement("td");
  $(td3).addClassName("del");
  td3.setAttribute("valign", "top");
  td3.setAttribute("align", "center");
  var a = document.createElement("a");
  a.setAttribute("href", "#");
  a.onclick = function() { return image_settings_ns.remove_row(row); }
  a.appendChild(document.createTextNode(g.messages["word_delete"].toUpperCase()));
  td3.appendChild(a);

  tr.appendChild(td1);
  tr.appendChild(td2);
  tr.appendChild(td3);
  tbody.appendChild(tr);

  return false;
}


/**
 * Removes a row.
 */
image_settings_ns.remove_row = function(row)
{
  $("setting_row_" + row).remove();
  image_settings_ns.deleted_rows.push(row);

  var num_rows = image_settings_ns.get_num_rows();
  if (num_rows == 0)
  {
    $("customize_settings").style.display = "none";
    $("default_settings").style.display = "block";
  }
  else
  {
    $("customize_settings").style.display = "block";
    $("default_settings").style.display = "none";
  }
}


/**
 * Selects a setting from the available custom settings dropdown. If the setting has already
 * been chosen, it informs the user.
 */
image_settings_ns.select_setting = function(row)
{
  var choice = $("row_" + row).value;

  // see if the setting has already been selected
  var already_used = false;
  for (var i=1; i<image_settings_ns.num_rows; i++)
  {
    if ($(image_settings_ns.deleted_rows).include(i) || i == row)
      continue;

    if ($("row_" + i).value == choice)
      already_used = true;
  }

  if (already_used)
  {
    $("values_" + row).innerHTML = "<span class=\"medium_grey\">" + g.messages["notify_setting_already_overwritten"] + "</span>";
    return;
  }

  if (choice)
  {
    var tmpdiv = document.createElement("div");
    tmpdiv.innerHTML = $("section_" + choice).innerHTML.replace(/%%ROW%%/g, row);
    $("values_" + row).innerHTML = "";
    setTimeout(function() { $("values_" + row).appendChild(tmpdiv); initLightbox(); }, 100);
  }
  else
    $("values_" + row).innerHTML = "";



}


/**
 * Helper function to return the number of rows in the custom image settings table.
 */
image_settings_ns.get_num_rows = function()
{
  return image_settings_ns.num_rows - image_settings_ns.deleted_rows.length;
}


image_settings_ns.submit = function()
{
  // note that this sends the MAX number of settings, including any rows that were deleted by
  // the user. The PHP does the job of discarding delete settings
  $("num_settings").value = image_settings_ns.num_rows;
}


image_settings_ns.init_page = function()
{
  image_settings_ns.num_rows = $("num_settings").value;
}