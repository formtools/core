/**
 * sortable.js
 * -----------
 *
 * This file is included on any pages that have drag & drop lists. Some lingo:
 *   "sortable":        the actual sortable lists.
 *   "groupable":       sortables that contain rows may be joined / separated for dragging
 *                      as a single unit
 *   "sortable groups": where there are more than one distinct sortable lists and the user
 *                      can drag items from one group to the other.
 *
 * This file contains code for all scenarios:
 *    - sortables
 *    - sortables + groupable items
 *    - grouped sortables
 *    - grouped sortables with groupable items
 *
 * It works by automatically parsing the DOM, assigning the various event handlers and initializing
 * the sortables.
 */

$(function() {

  // initialization code UNIQUE to sortable groups
  $(".sortable_groups").each(function() {
    var id = $(this).attr("id");

    var custom_delete_handler = null;
    var found = $("#" + id).find(".sortable__custom_delete_handler");
    if (found.length > 0) {
      var parts = found.val().split(".");
      custom_delete_handler = window[parts[0]][parts[1]];
    }
    var custom_delete_group_handler = null;
    var found = $("#" + id).find(".sortable__delete_group_handler");
    if (found.length > 0) {
      var parts = found.val().split(".");
      custom_delete_group_handler = window[parts[0]][parts[1]];
    }
    sortable_ns.page_sortables[id] = {
      type:                        "sortable_group",
      custom_delete_handler:       custom_delete_handler,
      custom_delete_group_handler: custom_delete_group_handler,
      delete_tooltip:              $("#" + id).find(".sortable__delete_tooltip").val(),
      edit_tooltip:                $("#" + id).find(".sortable__edit_tooltip").val(),
      deleted_rows:                []
    };

    $(this).closest("form").bind("submit", function() {
      sortable_ns.sortable_submit(this);
    });
    sortable_ns._add_common_event_delegation(this);

    // TODO needs sortupdate to reset tabindexes..,
    $(this).sortable({
      axis:   "y",
      handle: ".sort"
    });
  });


  // initialization code UNIQUE to non-grouped sortables
  $(".sortable").each(function() {

    // if this sortable is part of a group, ignore it! This stuff is all taken care of already
    if ($(this).closest(".sortable_groups").length > 0) {
      return;
    }
    var id = $(this).attr("id");

    var custom_delete_handler = null;
    var found = $("#" + id).find(".sortable__custom_delete_handler");
    if (found.length > 0) {
      var parts = found.val().split(".");
      custom_delete_handler = window[parts[0]][parts[1]];
    }
    sortable_ns.page_sortables[id] = {
      type:                        "sortable",
      custom_delete_handler:       custom_delete_handler,
      custom_delete_group_handler: null,
      delete_tooltip:              $("#" + id).find(".sortable__delete_tooltip").val(),
      edit_tooltip:                $("#" + id).find(".sortable__edit_tooltip").val(),
      deleted_rows:                []
    };

    $(this).closest("form").bind("submit", function() {
      sortable_ns.sortable_submit(this);
    });
    sortable_ns._add_common_event_delegation(this);
  });

  // fine. Should always be run, regardless of whether they are sortable groups or a simple sortable
  $(".groupable").each(function() {
    $(this).find(".sortable_row").prepend("<div class=\"group_block_top\"> </div>");
    $(this).find(".row_group>ul").prepend("<li class=\"col0\"></li>");
  });

  $(".sortable .sortable_row .sort_col").live("mouseover", function(e) {
    if (!$(e.target).hasClass("col0")) {
      $(this).closest(".row_content").addClass("over");
    }
  }).live("mouseout", function(e) {
    $(this).closest(".row_content").removeClass("over");
  });

  // alright! Now we do stuff for ALL sortable elements, regardless of whether they're part of a grouped sortable or not
  $(".sortable").each(function(e) {
    $(this).find(".sortable_row").last().addClass("rowN");
    $(this).find(".sortable_row .row_group").last().addClass("rowN");
    $(this).find(".rows").each(function() {
      var curr_sortable_rows = this;

      // ONLOAD, ADD ROW(S), SORTUPDATE FOR ROWS, SORTUPDATE FOR GROUP
      sortable_ns.update_tab_indexes(curr_sortable_rows, true);

      // ONLOAD, AFTER NEW GROUP INSERT
      $(curr_sortable_rows).sortable({
        axis:        "y",
        connectWith: ".connected_sortable",
        handle:      ".sort_col"
      });

      $(curr_sortable_rows).bind("sortupdate", function(e, ui) {
        sortable_ns.reorder_rows(curr_sortable_rows);
      });
    });
  });


  $(".add_group_link").live("click", sortable_ns.add_group);

  // used for rare cases
  if ($(".sortable__row_offset").length == 1) {
    sortable_ns.row_offset = parseInt($(".sortable__row_offset").val()) - 1;
  }
});


var sortable_ns = {
  // initialized on page load. It's a hash of TOP sortable element IDs as the keys, to info about the sortable
  // (custom event handlers, deleted rows, etc).
  page_sortables:        {},

  // shared stuff
  connect_rows_icon:     "group_block_connect.png",
  disconnect_rows_icon:  "group_block_disconnect.png",
  delete_group_dialog:   $("<div id=\"delete_group_dialog\"></div>"),
  delete_nonempty_group_dialog: $("<div id=\"delete_nonempty_dialog\"></div>"),

  // this is only used for the Edit Fields page, when you have a sortable that starts on a different row (e.g. page 2).
  // This should really be moved to be stored in page_sortables, but it's just not important right now
  row_offset: 0
};


/**
 * This is used on both sortables and grouped sortables. It adds a number of event delegated
 * functionality to the TOP element:
 *     - mouseover and mouseout rows: edit / delete, join/separate rows icon
 *     - clicks events: deleting rows, joining/separating rows
 *     - deleting group (only needed for grouped sortables, of course, but it doesn't add much weight
 *       when not needed)
 */
sortable_ns._add_common_event_delegation = function(el) {
  var sortable_id = $(el).attr("id");

  $(el).bind("mouseover", function(e) {
    if ($(e.target).hasClass("del")) {
      $(e.target).closest(".row_group").addClass("delete_row_hover");
    } else if ($(e.target).hasClass("edit") || $(e.target).parent().hasClass("edit")) {
      $(e.target).closest(".row_group").addClass("edit_row_hover");
    } else if ($(e.target).hasClass("col0")) {
      var curr_col0 = e.target;
      var has_next_row_group_in_sortable_row = ($(curr_col0).closest(".row_group").next().length > 0);
      if (!$(curr_col0).closest(".sortable_row").next().length && !has_next_row_group_in_sortable_row) {
        return;
      }
      var action = (has_next_row_group_in_sortable_row) ? "disconnect" : "connect";
      sortable_ns._update_connect_image({ el: curr_col0, action: action });
    }
  });

  $(el).bind("mouseout", function(e) {
    if ($(e.target).hasClass("del")) {
      $(e.target).closest(".row_group").removeClass("delete_row_hover");
    } else if ($(e.target).hasClass("edit") || $(e.target).parent().hasClass("edit")) {
      $(e.target).closest(".row_group").removeClass("edit_row_hover");
    } else if ($(e.target).hasClass("col0")) {
      $(e.target).css({
        "background": "",
        "cursor":     "auto"
      });
      sortable_ns.__current_sortable_connection_chain = null;
    }
  });

  $(el).bind("click", function(e) {

    // the default delete behaviour is just to fade out the row: the row will actually be deleted on the server
    // after the user updates the form. However, it can be overridden to do Ajax deletes or pop open a confirmation
    // window or whatever is needed. For that, you'll need a custom_delete_handler
    if ($(e.target).hasClass("del")) {
      if (sortable_ns.page_sortables[sortable_id].custom_delete_handler != null) {
        sortable_ns.page_sortables[sortable_id].custom_delete_handler(e.target);
      } else {
        sortable_ns.delete_row(sortable_id, e.target);
      }
    }

    // get all row_groups in the NEXT sortable row, append them to the current sortable_row, then
    // delete the next one
    else if ($(e.target).hasClass("col0")) {
      if (sortable_ns.__current_sortable_connection_chain == "connect") {
        var curr_sortable_row = $(e.target).closest(".sortable_row");
        curr_sortable_row.find(".row_content").addClass("grouped_row");
        var next_sortable_row = curr_sortable_row.next();
        var next_row_groups = next_sortable_row.find(".row_content").children();

        // N.B. this doesn't clone the node: it MOVES it. This is necessary to prevent having
        // to update the scroll-bar (if defined) which references the actual DOM node
        curr_sortable_row.find(".row_group:last").after(next_row_groups);
        next_sortable_row.remove();
        sortable_ns.__current_sortable_connection_chain = "disconnect";
        sortable_ns._update_connect_image({ el: e.target, action: "disconnect" });
      }
      else if (sortable_ns.__current_sortable_connection_chain == "disconnect") {
        sortable_ns._disconnect_row($(e.target).closest(".row_group"));
        sortable_ns.__current_sortable_connection_chain = "connect";
        sortable_ns._update_connect_image({ el: e.target, action: "connect" });
      }

      // any time rows are joined / separates, reorder the parent sortable
      sortable_ns.reorder_rows($(el).closest(".sortable"));
    }

    // if there's a custom delete group handler specified, it passes off all work to that function. Otherwise,
    // it opens a confirmation dialog that (when agreed to) simply submits the parent form with a
    // [sortable ID prefix]_sortable__delete_group hidden field with the appropriate value - the server-side
    // code can figure out what to delete, depending on the data set.
    else if ($(e.target).hasClass("delete_group")) {
      if (sortable_ns.page_sortables[sortable_id].custom_delete_group_handler != null) {
    	sortable_ns.page_sortables[sortable_id].custom_delete_group_handler(e.target);
      } else {
        ft.create_dialog({
          dialog:     sortable_ns.delete_group_dialog,
          title:      g.messages["phrase_please_confirm"],
          content:    g.messages["confirm_delete_group"],
          popup_type: "warning",
          buttons: [{
            "text":  g.messages["word_yes"],
            "click": function() {
              ft.dialog_activity_icon(sortable_ns.delete_group_dialog, "show");
              var group_id = $(e.target).closest(".sortable_group_header").find(".group_order").val();
              var form = $(e.target).closest("form");

              form.append("<input type=\"hidden\" name=\"" + sortable_id + "_sortable__delete_group\" class=\"sortable__delete_group\" value=\"" + group_id + "\" />");
              form.trigger("submit");
            }
          },
          {
            "text":  g.messages["word_no"],
            "click": function() {
              $(this).dialog("close");
            }
          }]
        });
      }
      return false;
    };
  });
}


// called after the user re-orders or deletes a row
sortable_ns.reorder_rows = function(sortable_el, update_tab_indexes) {

  // hide/show the default empty row. The empty row is required, to get around a small bug with the
  // jQuery sortable. If it's not there, items can't be dragged to the empty group
  var num_sortable_rows = $(sortable_el).find(".sortable_row").length;
  if (num_sortable_rows > 1) {
    $(sortable_el).find(".empty_group").hide();
  } else {
    $(sortable_el).find(".empty_group").show();
  }

  var counter = 1 + sortable_ns.row_offset;
  $(sortable_el).find(".sort_col").each(function() {
    $(this).html(counter);
    counter++;
  });

  $(sortable_el).find(".sortable_row").removeClass("rowN").last().addClass("rowN");
  $(sortable_el).find(".sortable_row .row_group").removeClass("rowN").not(".empty_group").last().addClass("rowN");

  // finally, re-assign all the tabindexes [move this to an event-delegated focus]
  if (update_tab_indexes === true) {
    sortable_ns.update_tab_indexes(sortable_el);
  }

  // sometimes when dropping a selected row, the hover class is orphaned. This explicitly removes it
  $(sortable_el).find(".row_content").removeClass("over");
}


sortable_ns.update_tab_indexes = function(sortable_el) {
  var selectors = $(sortable_el).closest(".sortable").find(".tabindex_col_selectors");
  if (selectors.length) {
    selectors = selectors.val().split("|");
    var count = 1; // ...! TODO ... dude!
    for (var i=0; i<selectors.length; i++) {
      $(selectors[i]).each(function() {
        $(this).attr("tabindex", count++);
      });
    }
  }
}


// used in sortable tables. This returns the necessary markup to append to .rows.
sortable_ns.get_sortable_row_markup = function(info) {
  var settings = $.extend({
    row_group:  info.row_group,
    is_grouped: true
  }, info);

  var li = $("<li class=\"sortable_row\"></li>");
  if (settings.is_grouped) {
    li.append("<div class=\"group_block_top\"></div>");
  }

  var grouped_row_class = "";
  if (settings.is_grouped) {
    var group_count = $("<div></div>").append(settings.row_group).find(".row_group").length;
    grouped_row_class = (group_count > 1) ? " grouped_row" : "";
  }

  var row_content = $("<div class=\"row_content" + grouped_row_class + "\"></div>");
  row_content.append(settings.row_group);
  li.append(row_content);
  li.append("<div class=\"clear\"></div>");

  return li;
}


/**
 * Automatically called when a form containing a sortable is submitted. It appends three hidden fields to
 * the form. [prefix] is the unique sortable ID of the top level sortable element.
 *
 *     [prefix]sortable__rows: a comma delimited list of rows. If the form contains grouped sortables,
 *                             this contains a string of the form:
 *                             group_id1|sortable_id1,sortable_id2,...`group_id2|sortable_id1...
 *
 *     [prefix]sortable__new_groups: a comma delimited list of row numbers that are the start of a
 *                             new sort group.
 *
 *     [prefix]sortable__deleted_rows: a comma delimited list of rows that were deleted. In order for
 *                             this to be useful, the row numbers have to map to something on the server,
 *                             like field IDs or View IDs. If the rows were just temporarily
 *                             created in the client then deleted, this contains no value.
 */
sortable_ns.sortable_submit = function(form_el) {

  // remove any sortable submit info already appended to the page in case this was already called but cancelled
  $("input[name$=sortable__rows],input[name$=sortable__new_groups],input[name$=sortable__deleted_rows]").remove();

  for (var sortable_id in sortable_ns.page_sortables) {
    var sortable_info = sortable_ns.page_sortables[sortable_id];

    // technically, we could have separate sortables in multiple forms on the page (even though we don't do this yet)
    // so just in case, check that this current sortable is part of the form being submitted. If not, ignore the sucker
    if ($(form_el).find("#" + sortable_id).length == 0) {
      alert("This sortable is not in the form. Please report this problem in the forums.");
      continue;
    }

    var row_order_list = [];
    var sortable__rows = "";

    if (sortable_info.type == "sortable_group") {
      $("#" + sortable_id).find(".sortable_group").each(function() {
        var group_str = $(this).find(".sortable_group_header .group_order").val();
        var row_ids = [];
        $(this).find(".sr_order").each(function() {
          row_ids.push($(this).val());
        });
        group_str += "|" + row_ids.toString();
        row_order_list.push(group_str);
      });
      sortable__rows = row_order_list.join("~");
    } else {
      $("#" + sortable_id).find(".sr_order").each(function() {
        row_order_list.push($(this).val());
      });
      sortable__rows = row_order_list.toString();
    }

    var new_groups = [];
    $("#" + sortable_id).find(".sortable_row").not(".empty_group").each(function() {
      new_groups.push($(this).find(".row_group:first input.sr_order").val());
    });

    $(form_el).append("<input type=\"hidden\" name=\"" + sortable_id + "_sortable__rows\" value=\"" + sortable__rows + "\" />"
                    + "<input type=\"hidden\" name=\"" + sortable_id + "_sortable__new_groups\" value=\"" + new_groups.toString() + "\" />"
                    + "<input type=\"hidden\" name=\"" + sortable_id + "_sortable__deleted_rows\" value=\"" + sortable_info.deleted_rows + "\" />");
  }
}


sortable_ns.delete_row = function(sortable_id, el) {
  var sortable_row   = $(el).closest(".sortable_row");
  var num_row_groups = $(el).closest(".row_content").find(".row_group").length;
  var curr_row_group = $(el).closest(".row_group");
  var row_number     = curr_row_group.find(".sr_order").val();

  // delete the row_group. This is the div containing the row <ul> and the following clearer div
  curr_row_group.fadeOut(200);

  // log the deleted row numbers
  sortable_ns.page_sortables[sortable_id].deleted_rows.push(row_number);

  // if the parent .row_content div doesn't contain any more .row_group divs, delete the entire sortable row.
  // Otherwise just delete the specific .row_group
  setTimeout(function() {
    var sortable_el = $(el).closest(".sortable");
    if (num_row_groups == 1) {
      sortable_row.remove();
    } else {
      curr_row_group.remove();
    }
    sortable_ns.reorder_rows(sortable_el, false);
  }, 220);
}


sortable_ns._update_connect_image = function(info) {
  var image   = (info.action == "connect") ? sortable_ns.connect_rows_icon : sortable_ns.disconnect_rows_icon;
  var tooltip = (info.action == "connect") ? g.messages["phrase_connect_rows"] : g.messages["phrase_disconnect_rows"];
  sortable_ns.__current_sortable_connection_chain = info.action;

  $(info.el).attr("title", tooltip);
  $(info.el).css({
    "cursor":     "pointer",
    "background": "url(" + g.root_url + "/global/images/" + image + ")"
  });
}


/**
 * Helper function to actually disconnect a row in a groupable-sortable.
 */
sortable_ns._disconnect_row = function(row_group) {
  // if there is only one item before this one being disconnected, remove the grouped_row class
  var previous_row_groups = row_group.prevUntil();
  if (previous_row_groups.length < 1) {
    $(row_group).closest(".row_content").removeClass("grouped_row");
  }

  // get all subsequent row_groups and move them into a new sortable_row
  var following_row_groups = row_group.nextUntil();
  var grouped_row_class = (following_row_groups.length > 1) ? " grouped_row" : "";

  var new_li = $("<li class=\"sortable_row\"><div class=\"group_block_top\"></div></li>");
  var row_content = $("<div class=\"row_content" + grouped_row_class + "\"></div>");
  row_content.append(following_row_groups);
  new_li.append(row_content);
  new_li.append("<div class=\"clear\"></div>");
  $(row_group).closest(".sortable_row").after(new_li);
}


sortable_ns._has_previous_field = function(row_group) {
  if (row_group.prev().length > 0) {
    return true;
  } else {
    return $(row_group).closest(".sortable_row").prev().length > 0;
  }
}


sortable_ns._has_next_field = function(row_group) {
  if (row_group.next().length > 0) {
    return true;
  } else {
    return $(row_group).closest(".sortable_row").next().length > 0;
  }
}

sortable_ns._get_previous_row_group = function(row_group) {
  var next_row_group = null;
  if (row_group.prev().length > 0) {
    next_row_group = row_group.prev();
  } else {
    if ($(row_group).closest(".sortable_row").prev().length > 0) {
      next_row_group = $(row_group).closest(".sortable_row").prev().find(".row_group:last");
    }
  }
  return next_row_group;
}


sortable_ns._get_next_row_group = function(row_group) {
  var next_row_group = null;
  if (row_group.next().length > 0) {
    next_row_group = row_group.next();
  } else {
    if ($(row_group).closest(".sortable_row").next().length > 0) {
      next_row_group = $(row_group).closest(".sortable_row").next().find(".row_group:first");
    }
  }
  return next_row_group;
}


/**
 * This function is called whenever the user clicks on an "Create New Group >>" link. All it
 * does is create a new dialog where they enter the group name, then farm out the actual work
 * to a custom function. This lets us re-use this functionality anywhere and execute any
 * custom code we need. Generally, the calling function will do an Ajax request to create the
 * new group, then call sortable_ns.insert_new_group to insert it in the page - all without a
 * server reload.
 *
 * If this function is too restrictive (e.g. you need to change the width or some other attribute
 * of the popup, don't give the .
 *
 * e.g. on the Edit View Fields tab.
 */
sortable_ns.add_group = function() {
  // assumes the add group function is object namespaced, like: my_ns.my_function
  var parts = $(".sortable__add_group_handler")[0].value.split(".");
  var add_group_handler = window[parts[0]][parts[1]];

  ft.create_dialog({
    dialog:     $("#add_group_popup"),
    title:      $(".add_group_popup_title").val(),
    buttons: [{
      "text":  g.messages["phrase_create_group"],
      "click": function() {
        add_group_handler();
      }
    },
    {
      "text":  g.messages["word_cancel"],
      "click": function() {
        $(this).dialog("close");
      }
    }]
  });

  return false;
}


/**
 * Dynamically inserts a new group into the page, at the end of .sortable_groups. This function is
 * strongly coupled with the page, because unfortunately the markup is pretty custom for each use -
 * classes, labels, IDs etc.
 *
 * Most groups just have: label - input field for group name - delete icon. To use that, you need the following:
 *   - hidden field in page with class "sortable__new_group_name" containing the
 *   - hidden field in page with class "sortable__class" that contains the main class name for the group
 *
 * If you need additional customization, just don't call this function: define your own and use
 * sortable_ns.append_new_sortable_group() to actually do the job of inserting the new group.
 *
 * @param object info - MUST contain group_name and group_id params
 */
sortable_ns.insert_new_group = function(info) {

  var info = $.extend({
  	// required!
	  group_name:   "",
  	group_id:     "",

  	// optional
	  is_groupable: false
  }, info);

  var group_label    = $(".sortable__new_group_name").val();
  var sortable_class = $(".sortable__class").val();

  var html = "<div class=\"sortable_group\">\n"
        + "<div class=\"sortable_group_header\">\n"
          + "<div class=\"sort\"></div>\n"
          + "<label>" + group_label + "</label>\n"
          + "<input type=\"text\" name=\"group_name_" + info.group_id + "\" class=\"group_name\" value=\""
            + info.group_name.replace(/"/, "&quot;") + "\" />\n"
          + "<div class=\"delete_group\"></div>\n"
          + "<input type=\"hidden\" class=\"group_order\" value=\"" + info.group_id + "\" />\n"
          + "<div class=\"clear\"></div>\n"
        + "</div>\n"
        + "<div class=\"sortable " + sortable_class + "\">\n";

  html += $("#sortable__new_group_header").html();

  html += "<div class=\"clear\"></div>\n"
          + "<ul class=\"rows connected_sortable";

  if (info.is_groupable) {
    html += " groupable";
  }

  html += "\">\n"
            + "<li class=\"sortable_row rowN empty_group\"><div class=\"clear\"></div></li>"
          + "</ul>\n"
        + "</div>\n"
        + "<div class=\"clear\"></div>\n";

  if ($("#sortable__new_group_footer").length) {
    html += $("#sortable__new_group_footer").html();
  }

  html += "</div>\n";

  sortable_ns.append_new_sortable_group(html);
}


/**
 * Helper function to actually append the new sortable group to the end of the sortable groups. It
 * also does a little stuff behind the scenes to ensure that the new sortable group is tied to
 * the existing sortables, etc.
 *
 * @param string html
 */
sortable_ns.append_new_sortable_group = function(html) {
  $(".sortable_groups").append(html);

  // now re-create the sortables
  $(".sortable_groups").find(".rows").each(function() {
    // destroy the old sortable
    $(this).sortable("destroy");
    $(this).unbind("sortupdate");

    var curr_sortable_rows = this;
    $(this).sortable({
      axis:        "y",
      connectWith: ".connected_sortable",
      handle:      ".sort_col"
    });

    $(this).bind("sortupdate", function(e, ui) {
      sortable_ns.reorder_rows(this);
    });
  });
}