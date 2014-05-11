/**
 * File: general.js
 *
 * Contains general javascript functions for use throughout the application. Also includes the underscore.js
 * library, which contains a few handy functions not included in jQuery.
 */

$(function() {

  // for non-sortable, standard list tables
  $("td.del").live("mouseover", function(e) {
    $(e.target).closest("tr").addClass("delete_row_hover");
    $(this).attr("title", g.messages["word_remove"]);
  });
  $("td.del").live("mouseout", function(e) {
    $(e.target).closest("tr").removeClass("delete_row_hover");
  });
  $("td.edit").live("mouseover", function(e) {
    $(e.target).closest("tr").addClass("edit_row_hover");
    $(this).attr("title", g.messages["word_edit"]);
  });
  $("td.edit").live("mouseout", function(e) {
    $(e.target).closest("tr").removeClass("edit_row_hover");
  });

  $(".check_areas").live("click", function(e) {
    if (!$(e.target).hasClass("check_area")) {
      return;
    }
    var field = $(e.target).find("input");
    var field_type = field.attr("type");

    if (field[0].disabled) {
      return;
    }

    // for radios, we only check the fields - not uncheck them
    if (field_type == "radio") {
      if (!field[0].checked) {
        field[0].checked = true;
      }
    } else {
      field[0].checked = !field[0].checked;
    }
  });

  $(".ft_themes_dropdown").bind("change", function() {
    var id = $(this).attr("id");
    $("." + id + "_swatches").hide();
    $("#" + this.value + "_" + id + "_swatches").show();
  });

  // this adds the functionality so that when a user clicks on the language placeholder icon, it opens
  // the dialog window containing the available placeholder list. This was a LOT simpler before I had
  // to make it work with IE. The iframe was needed to hover the element above the textboxes. *sigh*
  // Pre-requisites: there's a hidden field (id=form_id) in the page
  var counter = 1;
  $(".lang_placeholder_field").each(function() {
    var pos   = $(this).position();
    var width = pos.left + $(this).width();
    var iframe = $("<iframe src=\"\" id=\"placeholder_field_overlay" + counter + "\" class=\"placeholder_field_overlay\" marginwidth=\"0\" marginheight=\"0\" allowtransparency=\"true\"></iframe>").css({ "left": width })
    $(this).before(iframe);

    var form_id = $("#form_id").val();
    var ifrm = $("#placeholder_field_overlay" + counter)[0];
    ifrm = (ifrm.contentWindow) ? ifrm.contentWindow : (ifrm.contentDocument.document) ? ifrm.contentDocument.document : ifrm.contentDocument;
    ifrm.document.open();
    ifrm.document.write('<img src="../../global/images/lang_placeholder_field_icon.png" style="cursor: pointer" onclick="parent.ft.show_form_field_placeholders_dialog({ form_id: ' + form_id + ' });" />');
    ifrm.document.close();
    counter++;
  });

  $(window).resize(function() {
    ft.re_init_placeholder_field_overlays();
  });
});


// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


// the main Form Tools namespace
var ft = {
  urls: [],
  check_url_dialog: $("<div></div>"),
  show_form_dialog: $("<div></div>"),
  form_field_placeholders_dialog: $("<div></div>")
}


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
ft.display_dhtml_page_nav = function(num_results, num_per_page, current_page) {
  total_pages = Math.ceil(num_results / num_per_page);

  // hide/show the appropriate pages
  for (var i=1; i<=total_pages; i++) {
    if (current_page == i) {
      if (!$("#page_" + i)) {
        alert("#page " + i + " doesn't exist");
      }
      $("#page_" + i).show();
      $("#nav_page_" + i).html("<span id=\"list_current_page\">" + i + "</span> ");
    } else {
      $("#page_" + i).hide();
      $("#nav_page_" + i).html("<a href='javascript:ft.display_dhtml_page_nav("
        + num_results + ", " + num_per_page + ", " + i + ")'>" + i + "</a> ");
    }
  }

  // update the "Viewing X-Y" text
  var tmp = (current_page - 1) * num_per_page;
  var max_end = tmp + num_per_page;
  var end = (max_end > num_results) ? num_results : max_end;
  var start = tmp + 1;

  $("#nav_viewing_num_start").html(start);
  $("#nav_viewing_num_end").html(end);

  // update the navigation links: <<
  if (current_page > 1) {
    previous_page = current_page - 1;
    $("#nav_previous_page").html("<a href='javascript:ft.display_dhtml_page_nav("
      + num_results + ", " + num_per_page + ", " + previous_page + ")'>&laquo;</a> ");
  } else {
    $("#nav_previous_page").html("&laquo;");
  }

  // >>
  if (current_page < total_pages) {
    next_page = current_page + 1;
    $("#nav_next_page").html("<a href='javascript:ft.display_dhtml_page_nav("
      + num_results + ", " + num_per_page + ", " + next_page + ")'>&raquo;</a> ");
  } else {
    $("#nav_next_page").html("&raquo;");
  }
}


/**
 * Selects all options in a multi-select dropdown field.
 */
ft.select_all_multi_dropdown_options = function(dd_field_id) {
  for (var i=0, len = $(dd_field_id).options.length; i<len; i++) {
    $(dd_field_id).options[i].selected = true;
  }
}


/**
 * Selects all options in a multi-select dropdown field.
 */
ft.unselect_all_multi_dropdown_options = function(dd_field_id) {
  for (var i=0, len = $(dd_field_id).options.length; i<len; i++) {
    $(dd_field_id).options[i].selected = false;
  }
}


/**
 * Adds a new option to a select dropdown box.
 *
 * @param object selectbox the select box object
 * @param string text_val the display text of the select box
 * @param string value the value of the select box
 */
ft.add_option = function(selectbox, text_val, value) {
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
ft.delete_option = function(selectbox, ind) {
  var sel_length = selectbox.length;
  if (sel_length > 0) {
    selectbox.options[ind] = null;
  }
}


/**
 * Moves selected option(s) from one select box to another. This generally is used for multi-select
 * boxes to transfer options from one to the other.
 *
 * @param object sel_from the source select box element, it's ID or a jQuery element
 * @param object sel_to the target select box element, it's ID or a jQuery element
 */
ft.move_options = function(sel_from, sel_to) {
  sel_from = ft.get_dom_el(sel_from);
  sel_to   = ft.get_dom_el(sel_to);

  var sel_length = sel_from.length;
  var sel_texts  = [];
  var sel_vals   = [];
  var sel_count  = 0;

  // find the selected Options in reverse order and delete them from the 'from' Select
  for (var i=sel_length-1; i>=0; i--) {
    if (sel_from.options[i].selected) {
      // if there's no value, that means the lawyer is away. Don't move them.
      if (sel_from.options[i].value == "") {
        continue;
      }
      sel_texts[sel_count] = sel_from.options[i].text;
      sel_vals[sel_count]  = sel_from.options[i].value;
      ft.delete_option(sel_from, i);
      sel_count++;
    }
  }

  // add the selected text/values in reverse order. This will add the Options to the 'to' Select
  // in the same order as they were in the 'from' Select
  for (var i=sel_count-1; i>=0; i--) {
    ft.add_option(sel_to, sel_texts[i], sel_vals[i]);
  }
}


/**
 * Helper function used to select all options in a multi-select dropdown. This is used on form submit
 * to ensure the contents are passed along to the server.
 */
ft.select_all = function(el) {
  el = ft.get_dom_el(el);
  for (var i=0; i<el.length; i++) {
    el[i].selected = true;
  }
  return true;
}


/**
 * This is a helper function introduced after migrating to jQuery. I found that a lot of the functions expected
 * either the ID (string) of a node, or the plain DOM node. But now we also introduced jQuery nodes being
 * thrown around. So, this function takes any of the three as a param and always returns the appropriate DOM node.
 *
 * @param mixed string/DOM element/jQuery element
 */
ft.get_dom_el = function(mixed) {
  var el = null;
  if (typeof mixed == "string") {
    el = document.getElementById(mixed);
  }
  else if (typeof mixed.jquery != 'undefined') {
    el = mixed[0];
  }
  else {
    el = mixed;
  }
  return el;
}


/**
 * Changes the currently displayed tab. Used for "inner-tabs" - tabs within a particular page / tab.
 * It also makes an Ajax call to pass the tabset name and current tab values to the server.
 */
ft.change_inner_tab = function(tab, tabset_name) {
  $("#" + tabset_name + " .tab_row div").removeClass("selected");
  $("#" + tabset_name + " .tab_row div.inner_tab" + tab).addClass("selected");
  $("#" + tabset_name + " .inner_tab_content>div").hide();
  $("#" + tabset_name + " .inner_tab_content>div.inner_tab_content" + tab).show();

  // store the value in memory on the server
  if (tabset_name) {
    var page_url = g.root_url + "/global/code/actions.php";
    $.ajax({
      url:   page_url,
      data:  { action: "remember_inner_tab", tabset: tabset_name, tab: tab },
      type:  "POST",
      error: ft.error_handler
    });
  }

  ft.re_init_placeholder_field_overlays();

  return false;
}


/**
 * This should be explicitly called on any page that uses JS inner tabs. It initializes the
 * components, adding the appropriate JS events.
 */
ft.init_inner_tabs = function() {
  $(".inner_tabset").each(function() {
    var tabset_name = $(this).attr("id");
    var row = 1;
    $(this).find(".tab_row div").each(function() {
      $(this).bind("click", { tab: row, tabset_name: tabset_name }, function(e) {
        ft.change_inner_tab(e.data.tab, e.data.tabset_name);
      });
      row++;
    });
  });
}


/**
 * Can be used whenever you want to open up a form in a dialog window. Just add a show_form class to the link.
 */
ft.init_show_form_links = function() {
  $(".show_form").each(function() {
    var url = $(this).attr("href");
    $(this).bind("click", { url: url }, function(e) {
      ft.create_dialog({
        dialog:     ft.show_form_dialog,
        title:      g.messages["phrase_show_form"],
        min_width:  800,
        min_height: 500,
        content: "<iframe class=\"check_url_iframe\" src=\"" + e.data.url + "\"></iframe>",
        buttons: [{
          text:  g.messages["phrase_open_form_in_new_tab_or_win"],
          click: function() {
            window.open(url);
            $(this).dialog("close");
          }
        },
        {
          text:  g.messages["word_close"],
          click: function() {
            $(this).dialog("close");
          }
        }]
      });
      return false;
    });
  });
}


/**
 * Curious little function. This is a generic helper for adding event handlers to elements. It's passed a list of stuff
 * to do: what elements trigger which elements to be hidden/shown/enabled/disabled. A single event can trigger multiple
 * actions. The function can be called at any point (even before the DOM is ready).
 *
 * The hide/show lacks pizazz: it just instantly hides/shows the required sections. But I made the second function
 * param an object so down the road it can extended.
 *
 * @param array an array of objects. Each object has the following structure
 *    {
 *      el:      the element that is clicked/blurred/whatevered (ID, DOM node or jQuery node)
 *      targets: an array of element info. Each index is itself an object:
 *               {
 *                 el:     the element to show / hide (ID, DOM node or jQuery node)
 *                 action: "show" / "hide" / "disable" / "enable" (string)
 *               }
 *    }
 * @param object options. Right now this object may contain a single property: "event" (defaults to click).
 */
ft.click = function(info) {
  $(function() {
    for (var i=0, j=info.length; i<j; i++) {
      var el = ft.get_dom_el(info[i].el);

      $(el).bind("click", { targets: info[i].targets }, function(e) {
        for (var m=0, n=e.data.targets.length; m<n; m++) {
        var curr_target = e.data.targets[m];
        var target_el   = ft.get_dom_el(curr_target.el);
          if (curr_target.action == "show") {
          $(target_el).show();
          } else if (curr_target.action == "hide") {
            $(target_el).hide();
          } else if (curr_target.action == "disable") {
            $(target_el).attr("disabled", "disabled")
          } else if (curr_target.action == "enable") {
            $(target_el).attr("disabled", "");
          }
        }
      });
    }
  });
}


/**
 * The error handler for the RSV validation library. This overrides the built-in error handler.
 */
function g_rsvErrors(f, errorInfo) {
  var errorHTML = "";
  var problemFields = [];
  var problemStrings = [];

  for (var i=0; i<errorInfo.length; i++) {
    if ($.inArray(errorInfo[i][1], problemStrings) == -1) {
      errorHTML += rsv.errorHTMLItemBullet + errorInfo[i][1] + "<br />";
      problemStrings.push(errorInfo[i][1]);
    }
    if (errorInfo[i][0].length) {
      $(errorInfo[i][0][0]).addClass("rsvErrorField");
      if (i == 0) {
        try {
          $(errorInfo[i][0][0]).focus();
        } catch(e) { }
      }
    } else {
      $(errorInfo[i][0]).addClass("rsvErrorField");
      if (i==0) {
        try {
          $(errorInfo[i][0]).focus();
        } catch(e) { }
      }
    }
    problemFields.push(errorInfo[i][0]);
  }

  if (errorInfo.length > 0) {
    ft.display_message(rsv.errorTargetElementId, 0, errorHTML);
    return false;
  }

  // a hack-like solution to get around the fact that by overriding RSV's built-in error handler
  // any defined onCompleteHandler will be ignored
  if (rsv.onCompleteHandler) {
    return rsv.onCompleteHandler();
  }

  return true;
}


/**
 * Generic function for displaying a message in the UI, as returned from an Ajax response handler.
 *
 * There's one weirdness about this function. The main messages.tpl file outputs a div#ft_message
 * element that contains an empty 8px high div followed by a div#ft_message_inner element. The idea
 * was that it would always provide an 8px-high empty space to pad things out properly on the screen.
 * Other message elements don't contain that padding.
 *
 * This was a dumb decision, made early on. It should be simplified by moving the padding out of the
 * element.
 *
 * @param string target_id the HTML target element
 * @param boolean success whether this is an error or a notification: 1 or 0
 * @param string message the message to display
 */
ft.display_message = function(target_id, success, message) {

  // TODO. What on EARTH was I thinking here? I think I may need to lay off the smack.
  success = parseInt(success);
  var messageClass = (success == 1) ? "notify" : "error";

  // if target_id is the main "ft_message" id string, we do something a little special
  var inner_target_id = (target_id == "ft_message") ? "ft_message_inner" : target_id;

  // remove all old class names and add the new one
  var colour;
  if (success == 1) {
    $("#" + inner_target_id).removeClass("error");
    colour = g.notify_colours[1];
  } else {
    $("#" + inner_target_id).removeClass("notify");
    colour = g.error_colours[1];
  }

  if ($("#" + target_id).length) {
    $("#" + inner_target_id).addClass(messageClass);
    $("#" + inner_target_id).html("<div style=\"padding:8px\">"
      + "<a href=\"#\" onclick=\"return ft.hide_message('" + target_id + "')\" style=\"float:right\" class=\"pad_left_large\">X</a>"
      + message + "</div>");
    $("#" + target_id).show();

    // add the nice highlight effect for the notification message
    $(function() { $("#" + inner_target_id).effect("highlight", { color: "#" + colour }, 1200); });
  }
}


/**
 * With 2.1.0, the "Check URL" functionality is greatly simplified. Instead of opening a separate popup
 * where the user can confirm/deny the URL, it opens a dialog window containing the page in an iframe.
 * There is no approve / deny functionality - it's left to the discretion of the user.
 *
 * This function needs to be called on page load. It maps all Check URL buttons to the corresponding
 */
ft.init_check_url_buttons = function() {
  $(".check_url").live("click", function() {
    var check_url_id = $(this).attr("id");
    var url_field_id = check_url_id.replace(/^check_url__/, "");

    // if there's no URL, just output a message
    var url = $.trim($("#" + url_field_id).val());
    if (url == "") {
      ft.display_message("ft_message", false, g.messages["validation_no_url"]);
      $("#" + url_field_id).focus();
      return false;
    }

    if (!ft.is_valid_url(url)) {
      ft.display_message("ft_message", false, g.messages["validation_invalid_url"]);
      $("#" + url_field_id).select().focus();
      return false;
    }

    ft.create_dialog({
      dialog:     ft.check_url_dialog,
      title:      g.messages["phrase_check_url"] + " - <i>" + url + "</i>",
      min_width:  800,
      min_height: 500,
      content: "<iframe class=\"check_url_iframe\" src=\"" + url + "\"></iframe>",
      buttons: [{
        text: g.messages["word_close"],
        click: function() {
          $(this).dialog("close");
          $("#" + url_field_id).focus();
        }
      }]
    });
  });
}

/**
 * This handles all dialog window creation in Form Tools. Dialogs take two forms:
 * 1. The markup for the dialog already exists in the page. This is handy for dialogs for adding/editing stuff,
 *    where by and large the markup is pre-defined
 * 2. dialogs that change all the time - like errors & notifications. Here, there's not much point pre-creating
 *    a hidden element containing the dialog content, since the content is always different.
 *
 * This function handles both scenarios.
 *
 * [jQuery settings like to be in camel; Form Tools uses underscore. Hence the oddity of switching here]
 */
ft.create_dialog = function(info) {
  var settings = $.extend({

    // a reference to the dialog window itself. If this isn't included, the dialog is a one-off
    dialog:      "<div></div>",

    // there are two ways to create a dialog. Either specify the ID of the element in the page
    // containing the markup, or just pass the HTML here.
    content:     "",
    title:       "",
    auto_open:   true,
    modal:       true,
    min_width:   400,
    min_height:  100,
    buttons:     [],
    popup_type:  null,
    open:        function() {},
    close:       function() { $(this).dialog("destroy"); },
    resize:      function() {},
    resize_stop: function() {}
  }, info);

  // if there's a popup_type specified and we want to add in an icon
  if (settings.popup_type) {
    var content = "<table cellspacing=\"0\" cellpadding=\"0\" width=\"100%\"><tr>";
    switch (settings.popup_type) {
      case "warning":
        content += "<td valign=\"top\"><span class=\"popup_icon popup_type_warning\"></span></td>";
        break;
      case "error":
        content += "<td valign=\"top\"><span class=\"popup_icon popup_type_error\"></span></td>";
        break;
      case "info":
        content += "<td valign=\"top\"><span class=\"popup_icon popup_type_info\"></span></td>";
        break;
    }
    content += "<td>" + settings.content + "</td></tr></table>";
  } else {
    content = settings.content;
  }

  var dialog_content = "";
  if (settings.content) {
    dialog_content = $(settings.dialog).html(content);
  } else {
    dialog_content = $(settings.dialog);
  }

  var final_settings = {
    title:      settings.title,
    modal:      settings.modal,
    autoOpen:   settings.auto_open,
    minWidth:   settings.min_width,
    minHeight:  settings.min_height,
    maxHeight:  settings.max_height,
    buttons:    settings.buttons,
    open:       settings.open,
    close:      settings.close,
    resize:     settings.resize,
    resizeStop: settings.resize_stop
  };

  if (settings.width) {
    final_settings.width = settings.width;
  }
  if (settings.width) {
    final_settings.height = settings.height;
  }

  dialog_content.dialog(final_settings);
}


/**
 * Simple helper function to show / hide an Ajax loading icon.
 */
ft.dialog_activity_icon = function(popup, action) {
  var dialog = $(popup).closest(".ui-dialog");
  if (action == "show") {
    if ($(dialog).find(".ajax_activity").length == 0) {
      $(dialog).find(".ui-dialog-buttonpane").append("<div class=\"ajax_activity\"></div>");
    }
  } else {
    $(dialog).find(".ajax_activity").remove();
  }
}


/**
 * Disables a button (by label) in a dialog window.
 */
ft.dialog_disable_button = function(popup, label) {
  $(popup).closest(".ui-dialog").find(".ui-dialog-buttonpane button:contains(" + label + ")").button("disable");
}


/**
 * Hides a message on the screen by by blinding it up.
 */
ft.hide_message = function(target_id) {
  $("#" + target_id).hide("blind");
  return false;
}


/**
 * Used in any pages that need to display the placeholder page.
 */
ft.show_form_field_placeholders_dialog = function(options) {
  ft.create_dialog({
    title:  g.messages["phrase_form_field_placeholders"],
    dialog: ft.form_field_placeholders_dialog,
    min_width:  800,
    min_height: 500,
    max_height: 500,
    content: "<div id=\"placeholders_dialog_content\"><div style=\"text-align: center; margin: 50px\"><img src=\"" + g.root_url + "/global/images/loading.gif /></div></div>",
    open: function() {
      $.ajax({
        url: g.root_url + "/global/code/actions.php",
        data: {
          action:  "get_form_field_placeholders",
          form_id: options.form_id
        },
        dataType: "html",
        success: function(result) {
          $("#placeholders_dialog_content").html(result);
        }
      })
    },
    buttons: { "Close": function() { $(this).dialog("close"); } }
  });
}

/**
 * Checks that a folder has both read and write permissions, and displays the result in an element
 * in the page.
 */
ft.test_folder_permissions = function(folder, target_message_id) {
  $.ajax({
    url:      g.root_url + "/global/code/actions.php",
    data:     "file_upload_dir=" + folder + "&action=test_folder_permissions&return_vals[]=target_message_id:" + target_message_id,
    dataType: "json",
    type:     "POST",
    success:  ft.response_handler,
    error:    ft.error_handler
  });
}


/**
 * Checks that a folder and a URL are both referring to the same location.
 */
ft.test_folder_url_match = function(folder, url, target_message_id) {
  $.ajax({
    url:      g.root_url + "/global/code/actions.php",
    data:     "file_upload_dir=" + folder + "&file_upload_url=" + url + "&action=test_folder_url_match" + "&return_vals[]=target_message_id:" + target_message_id,
    dataType: "json",
    type:     "POST",
    success:  ft.response_handler,
    error:    ft.error_handler
  });
}


/**
 * Different field types allow different field sizes. It wouldn't make sense for textareas or file fields, for
 * instance, to have a storage size of 1 character. This function is called whenever a user changes a field's
 * type: it updates the list of available field sizes. Since some fields may only have a single field size,
 * the second target_el parameter is the element CONTAINING the field size dropdown. It re-creates the
 * dropdown (or single field size string) in that element.
 *
 * If the new available field sizes contain the currently selected field size, it just sets the new size
 * to the same value.
 *
 * @param node the field type dropdown that the user just changed
 * @param string the ID of the element where the new field size dropdown will be updated
 * @param object options assorted, configurable options
 */
ft.update_field_size_dropdown = function(el, target_el, options) {
  var field_type_id = $(el).val();

  var opts = $.extend({
    name:                 null,
    id:                   null,
    selected:             null,
    html_class:           null,
    field_type_size_list: page_ns.field_types["field_type_" + field_type_id],
    field_size_labels:    page_ns.field_sizes
  }, options);

  var field_type_sizes = opts.field_type_size_list.split(",");
  var dd_options = [];
  for (var i=0; i<field_type_sizes.length; i++) {
    dd_options.push({
      value:    field_type_sizes[i],
      text:     opts.field_size_labels[field_type_sizes[i]],
      selected: (field_type_sizes[i] == opts.selected) ? " selected" : ""
    });
  }

  var html = "";

  // if there are no options, the admin made a boo-boo and didn't assign any field sizes to the field type
  if (dd_options.length == 0) {

  } else if (dd_options.length == 1) {
    html = "<input type=\"hidden\"";
    if (opts.name) {
      html += " name=\"" + opts.name + "\"";
    }
    if (opts.id) {
      html += " id=\"" + opts.id + "\"";
    }
    html += " value=\"" + dd_options[0].value + "\" />" + dd_options[0].text;
  } else {
    html = "<select";
    if (opts.name) {
      html += " name=\"" + opts.name + "\"";
    }
    if (opts.id) {
      html += " id=\"" + opts.id + "\"";
    }
    if (opts.html_class) {
      html += " class=\"" + opts.html_class + "\"";
    }
    html += ">\n";

    for (var i=0; i<dd_options.length; i++) {
      html += "<option value=\"" + dd_options[i].value + "\"" + dd_options[i].selected + ">" + dd_options[i].text + "</option>\n";
    }
    html += "</select>";
  }

  $(target_el).html(html);
}


/**
 * This is the main, generic Ajax response handler for all successful (i.e. successfully processed) Ajax
 * calls. This function expects the Ajax function to have passed a "target_message_id" parameter to the
 * actions.php script - which is passed back to here - identifying the page element ID to insert the
 * error/success message.
 */
ft.response_handler = function(data) {

  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  ft.display_message(data.target_message_id, data.success, data.message);
}


/**
 * TODO expand this to work with dialogs. It should close all open dialogs and hide their loading
 * images, if shown.
 */
ft.error_handler = function(xml_http_request, text_status, error_thrown) {
  ft.display_message("ft_message", false, "Error: " + error_thrown);
}


/**
 * Called when the administrator clicks on the "Update" link - it gets the upgrade info form
 * from the server, inserts it into the page and submits it.
 */
ft.check_updates = function() {
  if ($("#upgrade_form").length) {
    $("#upgrade_form").submit();
  } else {
    $.ajax({
      url:      g.root_url + "/global/code/actions.php",
      data:     "action=get_upgrade_form_html",
      dataType: "html",
      type:     "POST",
      success:  ft.embed_and_submit_upgrade_form,

      // bit of an assumption here: it assumes that every page has the default ft_message notification
      // element. If it doesn't, the error just won't show
      error:    ft.error_handler
    });
  }

  return false;
}


ft.embed_and_submit_upgrade_form = function(data) {
  // check the user wasn't logged out / denied permissions
  if (!ft.check_ajax_response_permissions(data)) {
    return;
  }

  $("body").append(data);
  ft.queue.push([
    function() { $("#upgrade_form").submit(); },
    function() { return ($("#upgrade_form").length > 0); }
  ]);
  ft.process_queue();
}


/**
 * Helper function to toggle between any number of classes that can be applied to a single element
 * at one time. The idea is that often an element needs a different class at a different time, e.g.
 * "red", "blue", green" but cannot have more than one at once. This function ensures it's correct.
 *
 * @param object el a node
 * @param string class the class name to apply
 * @param array all_classes. All class that may
 */
ft.toggle_unique_class = function(el, new_class, all_classes) {
  for (var i=0; i<all_classes.length; i++) {
    if ($(el).hasClass(all_classes[i])) {
      $(el).removeClass(all_classes[i]);
    }
  }
  $(el).addClass(new_class);
}


/**
 * This function is used to bundle together a sequential group of nodes into a Document Fragment
 * so it may be inserted into the DOM with a single action. The node_list param may be a mixed
 * array of jQuery nodes or plain DOM nodes.
 *
 * @param array node_list
 * @return node a document fragment
 */
ft.group_nodes = function(node_list) {
  var fragment = document.createDocumentFragment();
  for (var i=0, j=node_list.length; i<j; i++) {
    fragment.appendChild(node_list[i][0]);
  }
  return fragment;
}

/**
 * A generic JS queuing function. For purpose and usage, see post:
 * http://www.benjaminkeen.com/?p=136
 *
 * [0] : code to execute - (function)
 * [1] : boolean test to determine completion - (function)
 * [2] : interval ID (managed internally by script) - (integer)
 */
ft.queue = [];
ft.process_queue = function() {
  if (!ft.queue.length)
    return;

  // if this code hasn't begun being executed, start 'er up
  if (!ft.queue[0][2]) {
    ft.queue[0][0]();
    timeout_id = window.setInterval("ft.check_queue_item_complete()", 50);
    ft.queue[0][2] = timeout_id;
  }
}

ft.check_queue_item_complete = function() {
  if (ft.queue[0][1]()) {
    window.clearInterval(ft.queue[0][2]);
    ft.queue.shift();
    ft.process_queue();
  }
}

ft.is_valid_url = function(url) {
  var RegExp = /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
  return RegExp.test(url);
}


ft.check_ajax_response_permissions = function(json) {
  try {
    if (typeof json.ft_logout != undefined && json.ft_logout == 1) {
      ft.create_dialog({
        title:      "Sessions expired",
        content:    "Sorry, your session has expired. Please click the button below to log back in.",
        popup_type: "error",
        buttons: [
          {
            text:  "Return to login screen",
            click: function() {
              window.location = g.root_url;
            }
          }
        ]
      });
      return false;
    }
  }
  catch (e) {
  }

  return true;
}


/**
 * Helper function to find the value for a field name, serialized with jQuery serializeArray().
 *
 * @param array arr each index is a hash with two keys: name and value
 * @param string name
 */
ft._extract_array_val = function(arr, name) {
  var value = "";
  for (var i=0, j=arr.length; i<j; i++) {
    if (arr[i].name == name) {
      value = arr[i].value;
      break;
    }
  }
  return value;
}

ft.re_init_placeholder_field_overlays = function() {
  $(".lang_placeholder_field").each(function() {
    var pos   = $(this).offset();
    var width = pos.left + $(this).width();
    $(this).parent().find(".placeholder_field_overlay").css({ "left": width })
  });
}

