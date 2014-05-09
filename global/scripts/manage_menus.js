/**
 * File: manage_menus.js
 *
 * Used for managing both the administrator and client menus pages.
 */

// our namespace for the menu management functionality
if (typeof mm == 'undefined')
  mm = {};

mm.num_rows = 0;


$(".sortable .col2 select").live("change keyup", function() {
  var row = $(this).closest(".row_group").find(".sr_order").val();
  mm.change_page(row, $(this).val());
});

/**
 * Adds a new menu item row.
 */
mm.add_menu_item_row = function() {
  var currRow = ++mm.num_rows;

  var li0 = $("<li class=\"col0\"></li>");
  var li1 = $("<li class=\"col1 sort_col\">" + currRow + "</li>");

  var pages_dd = $("#menu_options").html().replace(/%%X%%/gi, currRow);
  var li2 = $("<li class=\"col2\">" + pages_dd + "</li>");
  var li3 = $("<li class=\"col3\"><input type=\"text\" name=\"display_text_" + currRow + "\" id=\"display_text_" + currRow + "\" /></li>");
  var li4 = $("<li class=\"col4\" id=\"row_" + currRow + "_options\"><span class=\"medium_grey\">" + g.messages["word_na"] + "</span></li>");
  var li5 = $("<li class=\"col5 check_area\"><input type=\"checkbox\" name=\"submenu_" + currRow + "\" id=\"submenu_" + currRow + "\" /></li>");
  var li6 = $("<li class=\"col6 colN del\"></li>");

  var ul = $("<ul></ul>");
  ul.append(li0);
  ul.append(li1);
  ul.append(li2);
  ul.append(li3);
  ul.append(li4);
  ul.append(li5);
  ul.append(li6);

  var main_div = $("<div class=\"row_group\"><input type=\"hidden\" class=\"sr_order\" value=\"" + currRow + "\" /></div>");
  main_div.append(ul);
  main_div.append("<div class=\"clear\"></div>");

  $(".rows").append(sortable_ns.get_sortable_row_markup({ row_group: main_div }));
  sortable_ns.reorder_rows($(".edit_menu"), false);

  return false;
}


mm.change_page = function(row, page) {
  // first, if the Display Text field is empty, set its value to the same as the display text
  // in the dropdown menu
  $("#page_identifier_" + row + " option").each(function() {
    if ($(this).val() == page) {
      if ($(this).val()) {
        $("#display_text_" + row).val($(this).html());
      } else {
        $("#display_text_" + row).val("");
      }
      return;
    }
  });

  // show / hide the appropriate options for this page
  switch (page) {
    case "custom_url":
      var html = g.messages["word_url_c"] + "&nbsp;<input type=\"text\" name=\"custom_options_" + row + "\" style=\"width:155px\" />";
      $("#row_" + row + "_options").html(html);
      break;

    case "client_form_submissions":
    case "form_submissions":
    case "edit_form":
    case "edit_form_main":
    case "edit_form_fields":
    case "edit_form_views":
    case "edit_form_emails":
      var form_dd = $("#form_dropdown_template").html().replace(/%%X%%/gi, row);
      $("#row_" + row + "_options").html(form_dd);
      break;

    case "edit_client":
      var client_dd = $("#client_dropdown_template").html().replace(/%%X%%/gi, row);
      $("#row_" + row + "_options").html(client_dd);
      break;

    default:
      $("#row_" + row + "_options").html("<span class=\"medium_grey\">" + g.messages["word_na"] + "</span>");
      break;
  }
}


/**
 * The onsubmit handler for the update admin menu form. This runs a little validation on the
 * values to confirm all required fields are entered, and there's nothing missing.
 */
mm.update_admin_menu_submit = function(f) {
  // [page identifier, page label, is included]
  var required_pages = [
    ["admin_forms", g.messages["word_forms"], false],
    ["clients", g.messages["word_clients"], false],
    ["your_account", g.messages["phrase_your_account"], false],
    ["modules", g.messages["word_modules"], false],
    ["settings", g.messages["word_settings"], false],
    ["logout", g.messages["word_logout"], false]
  ];

  $(".page_type").each(function() {
    for (var j=0; j<required_pages.length; j++) {
      var curr_page_identifier = required_pages[j][0];
      if ($(this).val() == curr_page_identifier) {
        required_pages[j][2] = true;
      }
    }
  });

  // now check to see if all required pages were included
  var pages = [];
  for (var j=0; j<required_pages.length; j++) {
    if (!required_pages[j][2]) {
      pages.push(required_pages[j][1]);
    }
  }

  if (pages.length) {
    var pages_str = pages.join(", ");
    var message = g.messages["notify_required_admin_pages"].replace(/\{\$remaining_pages\}/, pages_str);
    ft.display_message("ft_message", false, message);
    return false;
  }

  return true;
}


/**
 * The onsubmit handler for the update client menu form.
 */
mm.update_client_menu_submit = function(f) {
  // check the menu name isn't already taken
  var menu_name = $("#menu_name").val();
  for (var i=0; i<page_ns.menu_names.length; i++) {
    if (menu_name == page_ns.menu_names[i]) {
      ft.display_message("ft_message", false, g.messages["validation_menu_name_taken"]);
      return false;
    }
  }
  return true;
}
