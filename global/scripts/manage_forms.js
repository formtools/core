// our namespace for the Manage Form functions and vars
var mf_ns = {};
mf_ns.num_multi_page_form_pages = null;


/**
 * This function is called in the Add Form process, and on the Edit Form -> main tab. It dynamically
 * adds rows to the "Form URLs" section, letting the user add as many page URLs as their form contains.
 */
mf_ns.add_multi_page_form_page = function() {
  var curr_row = ++mf_ns.num_multi_page_form_pages;

  var li1 = $("<li class=\"col1 sort_col\"></li>");
  var li2 = $("<li class=\"col2\"><input type=\"text\" name=\"multi_page_urls[]\" id=\"mp_url_" + curr_row + "\" /></li>");
  var li3 = $("<li class=\"col3\"><input type=\"button\" value=\"" + g.messages["phrase_check_url"] +
              "\" id=\"check_url__mp_url_" + curr_row + "\" class=\"check_url\"></li>");
  var li4 = $("<li class=\"col4 colN del\"></li>");
  var ul  = $("<ul></ul>").append(ft.group_nodes([li1, li2, li3, li4]));

  var hidden_sort_field = $("<input type=\"hidden\" value=\"1\" class=\"sr_order\">");
  var clr = $("<div class=\"clear\"></div>");
  var row_group  = $("<div class=\"row_group\"></div>").append(ft.group_nodes([hidden_sort_field, ul, clr]));

  var html = sortable_ns.get_sortable_row_markup({row_group: row_group, is_grouped: false });

  $(".multi_page_form_list .rows").append(html);
  sortable_ns.reorder_rows($(".multi_page_form_list"), true);

  return false;
}


/**
 * Used in validation to check that at least one URL has been entered. In 2.1.0 we no longer force the
 * user to ensure it's a valid URL (it was too annoying). Instead, the onus is on them to enter the
 * correct URL and click the "Check URL" button to validate it.
 *
 * This is used on both the Add External Form step 2 page and the Edit Form main tab. Assumptions:
 * - the page contains two elements with IDs: "form_type" and "submission_type" containing the appropriate
 *   values.
 */
mf_ns.check_first_form_url = function() {
  if ($("#form_type").val() != "external") {
    return true;
  }

  var is_multi_page_form = $(".is_multi_page_form:checked").val();

  if ($("#submission_type").val() == "direct" || is_multi_page_form == "no") {
  	var form_url = $.trim($("#form_url").val());
  	if (!form_url) {
      return [[$("#form_url")[0], g.messages["validation_no_form_url"]]];
  	} else {
      $("#form_url").removeClass("rsvErrorField");
	  }
  } else if (is_multi_page_form == "yes") {
    var rows = $(".multi_page_form_list .rows");
    if (rows.find(".sortable_row").length == 0) {
      mf_ns.add_multi_page_form_page();
      return [[$(".is_multi_page_form")[0], g.messages["validation_no_form_url"]]];
    } else {
      var first_field = rows.find(".sortable_row:first [name^=multi_page_urls]");
      var first_form_page_url = $.trim(first_field.val());
      if (!first_form_page_url) {
        return [[first_field[0], g.messages["validation_no_form_url"]]];
      } else {
        first_field.removeClass("rsvErrorField");
      }
    }
  }

  return true;
}
