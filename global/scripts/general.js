/**
 * File: general.js
 *
 * Contains general javascript functions for use throughout the application.
 */

// the main Form Tools namespace used for all functions in this file.
var ft = {};
ft.urls = [];


/**
 * Display DHTML page navigation for lists. This function hides/shows the current page - all of
 * which exist in the page but are hidden with CSS. It also updates other aspects of the pagination:
 * the "viewing X-Y" section, the >> and << links.
 *
 * The assumption if that the PHP counterpart function with the same name has been called in the
 * calling page - that function creates the HTML & JS to interact with this function.
 *
 * @param integer num_results the total number of results<b>
 * @param integer num_per_page the number of listings that should appear per page
 * @param integer current_page the current page
 */
ft.display_dhtml_page_nav = function(num_results, num_per_page, current_page)
{
  total_pages = Math.ceil(num_results / num_per_page);

  // hide/show the appropriate pages
  for (var i=1; i<=total_pages; i++)
  {
    if (current_page == i)
    {
      if (!$("page_" + i))
        alert("page " + i + " doesn't exist");

      $("page_" + i).style.display = "block";
      $("nav_page_" + i).innerHTML = "<span id=\"list_current_page\">" + i + "</span> ";
    }
    else
    {
      $("page_" + i).style.display = "none";
      $("nav_page_" + i).innerHTML = "<a href='javascript:ft.display_dhtml_page_nav("
        + num_results + ", " + num_per_page + ", " + i + ")'>" + i + "</a> ";
    }
  }

  // update the "Viewing X-Y" text
  var tmp = (current_page - 1) * num_per_page;
  var max_end = tmp + num_per_page;
  var end = (max_end > num_results) ? num_results : max_end;
  var start = tmp + 1;

  $("nav_viewing_num_start").innerHTML = start;
  $("nav_viewing_num_end").innerHTML = end;


  // update the navigation links: <<
  if (current_page > 1)
  {
    previous_page = current_page - 1;
    $("nav_previous_page").innerHTML = "<a href='javascript:ft.display_dhtml_page_nav("
      + num_results + ", " + num_per_page + ", " + previous_page + ")'>&laquo;</a> ";
  }
  else
    $("nav_previous_page").innerHTML = "&laquo;";

  // >>
  if (current_page < total_pages)
  {
    next_page = current_page + 1;
    $("nav_next_page").innerHTML = "<a href='javascript:ft.display_dhtml_page_nav("
      + num_results + ", " + num_per_page + ", " + next_page + ")'>&raquo;</a> ";
  }
  else
    $("nav_next_page").innerHTML = "&raquo;";
}


/**
 * Selects all options in a multi-select dropdown field.
 */
ft.select_all_multi_dropdown_options = function(dd_field_id)
{
  for (var i=0; i<$(dd_field_id).options.length; i++)
    $(dd_field_id).options[i].selected = true;
}

/**
 * Selects all options in a multi-select dropdown field.
 */
ft.unselect_all_multi_dropdown_options = function(dd_field_id)
{
  for (var i=0; i<$(dd_field_id).options.length; i++)
    $(dd_field_id).options[i].selected = false;
}


/**
 * Adds a new option to a select dropdown box.
 *
 * @param object selectbox the select box object
 * @param string text_val the display text of the select box
 * @param string value the value of the select box
 */
ft.add_option = function(selectbox, text_val, value)
{
  var new_option = new Option(text_val, value);
  var sel_length = selectbox.length;
  selectbox.options[sel_length] = new_option;
}


/**
 * Deletes an option from a select box.
 *
 * @param object selectbox the select box element
 * @param integer ind the index of the item to remove
 */
ft.delete_option = function(selectbox, ind)
{
  var sel_length = selectbox.length;
  if (sel_length > 0)
    selectbox.options[ind] = null;
}


/**
 * Moves selected option(s) from one select box to another. This generally is used for multi-select
 * boxes to transfer options from one to the other.
 *
 * @param object sel_from the source select box element
 * @param object sel_to the target select box element
 */
ft.move_options = function(sel_from, sel_to)
{
  var sel_length = sel_from.length;
  var sel_texts  = [];
  var sel_vals   = [];
  var sel_count = 0;

  var i;

  // find the selected Options in reverse order and delete them from the 'from' Select
  for (i=sel_length-1; i>=0; i--)
  {
    if (sel_from.options[i].selected)
    {
      // if there's no value, that means the lawyer is away. Don't move them.
      if (sel_from.options[i].value == "")
        continue;

      sel_texts[sel_count] = sel_from.options[i].text;
      sel_vals[sel_count]  = sel_from.options[i].value;
      ft.delete_option(sel_from, i);
      sel_count++;
    }
  }

  // add the selected text/values in reverse order. This will add the Options to the 'to' Select
  // in the same order as they were in the 'from' Select
  for (i=sel_count-1; i>=0; i--)
    ft.add_option(sel_to, sel_texts[i], sel_vals[i]);
}


/**
 * Helper function used to select all options in a multi-select dropdown. This is used on form submit
 * to ensure the contents are passed along to the server.
 */
ft.select_all = function(el)
{
  for (var i=0; i<el.length; i++)
    el[i].selected = true;

  return true;
}


/**
 * Opens a URL in a popup window, to let the user confirm it is what they intended.
 */
ft.verify_url = function(url_field, form_page)
{
  var url = $(url_field).value;
  if (!url)
  {
    ft.display_message("ft_message", false, g.messages["validation_no_url"]);
    return false;
  }
  else if (!ft.is_valid_url(url))
  {
    ft.display_message("ft_message", false, g.messages["validation_invalid_url"]);
    return false;
  }
  else
  {
    ft.urls.push([form_page, url_field]);
    var verify_url_page = g.root_url + "/admin/verify_url.php?form_page=" + form_page + "&url=" + escape(url);

    window.open(verify_url_page, "verify_url", "width=900,height=600,menu=no,toolbar=no,resizable=yes");
  }
}


/**
 * This is called by the popup after the user confirms a URL. It sets the URL specified in the ft.urls
 * array as complete, and marks the field as verified for the page validation.
 */
ft.verify_url_page = function(form_page, url)
{
  $("form_url_" + form_page + "_button").removeClassName("red");
  $("form_url_" + form_page + "_button").addClassName("green");
  $("form_url_" + form_page + "_button").value = g.messages["word_verified"];

  // now update the URL
  for (var i=0; i<ft.urls.length; i++)
  {
    if (ft.urls[i][0] == form_page)
    {
      $(ft.urls[i][1]).value = url;
      break;
    }
  }

  $("form_url_" + form_page + "_verified").value = "yes";
}


/**
 * Helper function to return the current checked value in a set of radio buttons.
 *
 * @param mixed value
 */
ft.get_checked_value = function(el)
{
  if (!el)
    return "";

  // a single radio field
  if (el.length == undefined)
  {
    if (el.checked)
      return el.value;
    else
      return "";
  }

  for (var i=0; i<el.length; i++)
  {
    if (el[i].checked)
      return el[i].value;
  }

  return "";
}


/**
 * Changes the currently displayed tab. Used for "inner-tabs" - tabs within a particular page / tab.
 * It also makes an Ajax call to pass the tabset name and current tab values to the server.
 */
ft.change_inner_tab = function(tab, num_tabs, tabset_name)
{
  for (var i=1; i<=num_tabs; i++)
  {
    if (i == tab)
    {
      $("inner_tab" + i).removeClassName("inner_tab_unselected");
      $("inner_tab" + i).addClassName("inner_tab_selected");
      $("inner_tab_content" + i).style.display = "block";
    }
    else
    {
      $("inner_tab" + i).removeClassName("inner_tab_selected");
      $("inner_tab" + i).addClassName("inner_tab_unselected");
      $("inner_tab_content" + i).style.display = "none";
    }
  }

  // store the value on the server
  page_url = g.root_url + "/global/code/actions.php";

  switch (tabset_name)
  {
    case "edit_view_tab":
      new Ajax.Request(page_url, {
        parameters: { action: "remember_edit_view_tab", edit_view_tab: tab },
        method: 'post'
          });
      break;
    case "edit_email_tab":
      new Ajax.Request(page_url, {
        parameters: { action: "remember_edit_email_tab", edit_email_tab: tab },
        //onSuccess: function(transport) { alert("Success: " + transport.responseText); },
        //onFailure: function(transport) { alert("Failure: " + transport.responseText); },
        method: 'post'
          });
      break;
  }

  return false;
}



/**
 * The error handler for the RSV validation library. This overrides the built-in error handler.
 */
function g_rsvErrors(f, errorInfo)
{
  var errorHTML = "";
  var problemFields = [];
  var problemStrings = [];

  for (var i=0; i<errorInfo.length; i++)
  {
    if (!$(problemStrings).include(errorInfo[i][1]))
    {
      errorHTML += rsv.errorHTMLItemBullet + errorInfo[i][1] + "<br />";
      problemStrings.push(errorInfo[i][1]);
    }

    if (errorInfo[i][0].length)
    {
      $(errorInfo[i][0][0]).addClassName("rsvErrorField");

      if (i==0)
      {
        try {
          $(errorInfo[i][0][0]).focus();
        } catch(e) { }
      }
    }
    else
    {
      $(errorInfo[i][0]).addClassName("rsvErrorField");

      if (i==0)
      {
        try {
          $(errorInfo[i][0]).focus();
        } catch(e) { }
      }
    }

    problemFields.push(errorInfo[i][0]);
  }

  if (errorInfo.length > 0)
  {
    ft.display_message(rsv.errorTargetElementId, 0, errorHTML);
    return false;
  }

  // a hack-like solution to get around the fact that by overriding RSV's built-in error handler
  // any defined onCompleteHandler will be ignored
  if (rsv.onCompleteHandler)
    return rsv.onCompleteHandler();

  return true;
}


/**
 * Generic function for displaying a message in the UI, as returned from an Ajax response handler. This
 * does all the fancy-pants stuff like blind-upping the message, and changing the colour on the message
 * with the Fade Anything Technique (fat.js) to draw attention to it.
 *
 * Assumption: the element with the target_id contains a single DIV tag. This is done so that the styles
 * may be applied to the inner DIV, allowing the outer div to be smoothly blind-upped and -downed.
 *
 * @param string target_id the HTML target element
 * @param boolean success whether this is an error or a notification
 * @param string message the message to display
 */
ft.display_message = function(target_id, success, message)
{
  var messageClass = (success == 1) ? "notify" : "error";
  var closeImage = "";

  // if target_id is the main "ft_message" id string, we do something a little special:
  var inner_target_id = (target_id == "ft_message") ? "ft_message_inner" : target_id;

  // remove all old class names and add the new one
  if (success)
  {
    $(inner_target_id).removeClassName("error");
    from_colour = g.notify_colours[0];
    to_colour   = g.notify_colours[1];
  }
  else
  {
    $(inner_target_id).removeClassName("notify");
    from_colour = g.error_colours[0];
    to_colour   = g.error_colours[1];
  }

  $(inner_target_id).addClassName(messageClass);

  $(inner_target_id).innerHTML = "<div style=\"padding:8px\">"
    + "<a href=\"#\" onclick=\"return ft.hide_message('" + target_id + "')\" style=\"float:right\" class=\"pad_left_large\">X</a>"
    + message + "</div>";
  $(target_id).style.display = "block";

  // add the nice fade effect for the notification message
  Fat.fade_element(inner_target_id, 60, 1500, "#" + from_colour, "#" + to_colour);
}


/**
 * Hides a message on the screen by fading it out and blinding up at the same time.
 */
ft.hide_message = function(target_id)
{
  Effect.BlindUp($(target_id));
  Effect.Fade($(target_id));
  return false;
}


/**
 * Checks that a folder has both read and write permissions, and displays the result in an element
 * in the page.
 */
ft.test_folder_permissions = function(folder, target_message_id)
{
  var info = "file_upload_dir=" + folder + "&action=test_folder_permissions&return_vals[]=target_message_id:" + target_message_id;

  var params = info.toQueryParams();
  var ajaxActionsURL = g.root_url + "/global/code/actions.php";
  new Ajax.Request(ajaxActionsURL, {
    parameters: params,
    method: 'post',
    onSuccess: ft.response_handler,
    onFailure: function() { alert("Problem loading page"); }
  });
}


/**
 * Checks that a folder and a URL are both referring to the same location.
 */
ft.test_folder_url_match = function(folder, url, target_message_id)
{
  var info = "file_upload_dir=" + folder + "&file_upload_url=" + url + "&action=test_folder_url_match"
    + "&return_vals[]=target_message_id:" + target_message_id;

  var params = info.toQueryParams();
  var ajaxActionsURL = g.root_url + "/global/code/actions.php";
  new Ajax.Request(ajaxActionsURL, {
    parameters: params,
    method: 'post',
    onSuccess: ft.response_handler,
    onFailure: ft.error_handler
  });
}


/**
 * This is the main, generic Ajax response handler for all successful (i.e. successfully processed) Ajax
 * calls. This function expects the Ajax function to have passed a "target_message_id" parameter to the
 * actions.php script - which is passed back to here - identifying the page element ID to insert the
 * error/success message.
 */
ft.response_handler = function(transport)
{
  var info = transport.responseText.evalJSON();

  ft.display_message(info.target_message_id, info.success, info.message);
}

ft.error_handler = function(transport)
{
  ft.display_message("ft_message", false, "Error: " + transport.responseText);
}

/**
 * Called when the administrator clicks on the "Update" link - it gets the upgrade info form
 * from the server, inserts it into the page and submits it.
 */
ft.check_updates = function()
{
  if ($("upgrade_form") != null)
    $("upgrade_form").submit();
  else
  {
    var ajaxActionsURL = g.root_url + "/global/code/actions.php";
    new Ajax.Request(ajaxActionsURL, {
      parameters: { action: "get_upgrade_form_html" },
      method:     'post',
      onSuccess:  ft.embed_and_submit_upgrade_form
    });
  }

  return false;
}

ft.embed_and_submit_upgrade_form = function(transport)
{
  var body = $$("body")[0];
  var div = document.createElement("div");
  div.innerHTML = transport.responseText;
  body.appendChild(div);

  ft.queue.push([
    function() { $("upgrade_form").submit(); },
    function() { return ($("upgrade_form").length > 0); }
  ]);
  ft.process_queue();
}


/**
 * The Fade Anything Technique - Adam Michela
 * http://www.axentric.com/aside/fat/
 *
 * @version 1.0-RC1
 */
var Fat = {
  make_hex : function (r,g,b)
  {
    r = r.toString(16); if (r.length == 1) r = '0' + r;
    g = g.toString(16); if (g.length == 1) g = '0' + g;
    b = b.toString(16); if (b.length == 1) b = '0' + b;
    return "#" + r + g + b;
  },
  fade_all : function ()
  {
    var a = document.getElementsByTagName("*");
    for (var i = 0; i < a.length; i++)
    {
      var o = a[i];
      var r = /fade-?(\w{3,6})?/.exec(o.className);
      if (r)
      {
        if (!r[1]) r[1] = "";
        if (o.id) Fat.fade_element(o.id,null,null,"#"+r[1]);
      }
    }
  },
  fade_element : function (id, fps, duration, from, to)
  {
    if (!fps) fps = 30;
    if (!duration) duration = 3000;
    if (!from || from == "#") from = "#FFFF33";
    if (!to) to = this.get_bgcolor(id);

    var frames = Math.round(fps * (duration / 1000));
    var interval = duration / frames;
    var delay = interval;
    var frame = 0;

    if (from.length < 7) from += from.substr(1,3);
    if (to.length < 7) to += to.substr(1,3);

    var rf = parseInt(from.substr(1,2),16);
    var gf = parseInt(from.substr(3,2),16);
    var bf = parseInt(from.substr(5,2),16);
    var rt = parseInt(to.substr(1,2),16);
    var gt = parseInt(to.substr(3,2),16);
    var bt = parseInt(to.substr(5,2),16);

    var r,g,b,h;
    while (frame < frames)
    {
      r = Math.floor(rf * ((frames-frame)/frames) + rt * (frame/frames));
      g = Math.floor(gf * ((frames-frame)/frames) + gt * (frame/frames));
      b = Math.floor(bf * ((frames-frame)/frames) + bt * (frame/frames));
      h = this.make_hex(r,g,b);

      setTimeout("Fat.set_bgcolor('"+id+"','"+h+"')", delay);

      frame++;
      delay = interval * frame;
    }
    setTimeout("Fat.set_bgcolor('"+id+"','"+to+"')", delay);
  },
  set_bgcolor : function(id, c)
  {
    var o = $(id);
    o.style.backgroundColor = c;
  },
  get_bgcolor : function(id)
  {
    var o = $(id);
    while(o)
    {
      var c;
      if (window.getComputedStyle) c = window.getComputedStyle(o,null).getPropertyValue("background-color");
      if (o.currentStyle) c = o.currentStyle.backgroundColor;
      if ((c != "" && c != "transparent") || o.tagName == "BODY") { break; }
      o = o.parentNode;
    }
    if (c == undefined || c == "" || c == "transparent") c = "#FFFFFF";
    var rgb = c.match(/rgb\s*\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)/);
    if (rgb) c = this.make_hex(parseInt(rgb[1]),parseInt(rgb[2]),parseInt(rgb[3]));
    return c;
  }
}


/**
 * Helper function to toggle between any number of classes that can be applied to a single element
 * at one time. The idea is that often an element needs a different class at a different time, e.g.
 * "red", "blue", green" but cannot have more than one at once. This function ensures it's correct.
 *
 * @param object el a Prototype extended node
 * @param string class the class name to apply
 * @param array all_classes. All class that may
 */
ft.toggle_unique_class = function(el, new_class, all_classes)
{
  for (var i=0; i<all_classes.length; i++)
  {
    if (el.hasClassName(all_classes[i]))
      el.removeClassName(all_classes[i]);
  }

  el.addClassName(new_class);
}


/**
 * Helper function to return the first ancestor node of any other node. If the node isn't found, it
 * returns null.
 *
 * @param object a Prototype extended node
 *
 */
ft.get_ancestor_node = function(el, target_node)
{
  target_node = target_node.toUpperCase();
  var found_el = null;
  while (el.ancestors().length)
  {
    if (el.nodeName == target_node)
    {
      found_el = el;
      break;
    }
    el = el.ancestors()[0];
  }

  return found_el;
}


/**
 * RUZEE.Ellisis 0.1
 * (c) 2007 Steffen Rusitschka
 *
 * RUZEE.Ellipsis is freely distributable under the terms of an MIT-style license.
 * For details, see http://www.ruzee.com/
 */
function ellipsis(e)
{
    var w = e.getWidth() - 10000;
    var t = e.innerHTML;
    e.innerHTML = "<span>" + t + "</span>";
    e = e.down();
    while (t.length > 0 && e.getWidth() >= w) {
      t = t.substr(0, t.length - 1);
      e.innerHTML = t + "...";
    }
  }

document.write('<style type="text/css">' + '.ellipsis { margin-right:-10000px; } #content { overflow: hidden }</style>');


/**
 * A generic JS queuing function. For purpose and usage, see my post:
 * http://www.benjaminkeen.com/?p=136
 *
 * [0] : code to execute - (function)
 * [1] : boolean test to determine completion - (function)
 * [2] : interval ID (managed internally by script) - (integer)
 */
ft.queue = [];
ft.process_queue = function()
{
  if (!ft.queue.length)
    return;

  // if this code hasn't begun being executed, start 'er up
  if (!ft.queue[0][2])
  {
    // run the code
    ft.queue[0][0]();
    timeout_id = window.setInterval("ft.check_queue_item_complete()", 50);
    ft.queue[0][2] = timeout_id;
  }
}

ft.check_queue_item_complete = function()
{
  if (ft.queue[0][1]())
  {
    window.clearInterval(ft.queue[0][2]);
    ft.queue.shift();
    ft.process_queue();
  }
}

ft.is_valid_url = function(url)
{
  var RegExp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
  return RegExp.test(url);
}
