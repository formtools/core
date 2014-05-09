<?php

require("../../global/session_start.php");
ft_check_permission("admin");

if (isset($_GET['delete']) && isset($_GET['form_id']))
  ft_delete_form($_GET["form_id"]);

_ft_cache_form_stats();
if (isset($_GET["reset"]))
{
	$_SESSION["ft"]["form_sort_order"] = "";
	$_SESSION["ft"]["form_search_keyword"] = "";
	$_SESSION["ft"]["form_search_status"] = "";
	$_SESSION["ft"]["form_search_client_id"] = "";
}
$order     = ft_load_field("order", "form_sort_order", "form_name-ASC");
$keyword   = ft_load_field("keyword", "form_search_keyword", "");
$status    = ft_load_field("status", "form_search_status", "");
$client_id = ft_load_field("client_id", "form_search_client_id", "");

$search_criteria = array(
  "order"     => $order,
  "keyword"   => $keyword,
  "status"    => $status,
  "client_id" => $client_id
    );

$num_forms = ft_get_form_count();
$forms     = ft_search_forms($client_id, true, $search_criteria);
$clients   = ft_get_client_list();

// ------------------------------------------------------------------------------------------------

// compile template info
$page_vars = array();
$page_vars["page"] = "admin_forms";
$page_vars["page_url"] = ft_get_page_url("admin_forms");
$page_vars["head_title"] = $LANG["word_forms"];
$page_vars["has_client"] = (count($clients) > 0) ? true : false;
$page_vars["num_forms"] = $num_forms;
$page_vars["forms"] = $forms;
$page_vars["order"] = $order;
$page_vars["clients"] = $clients;
$page_vars["search_criteria"] = $search_criteria;
$page_vars["pagination"] = ft_get_dhtml_page_nav(count($forms), $_SESSION["ft"]["settings"]["num_forms_per_page"], 1);
$page_vars["js_messages"] = array("word_remove", "word_edit");
$page_vars["head_js"] =<<< END

var page_ns = {};
page_ns.show_form_dialog = $("<div></div>");

$(function() {
  $(".show_form").each(function() {
    var url = $(this).attr("href");
    $(this).bind("click", { url: url }, function(e) {
      ft.create_dialog({
        dialog:     page_ns.show_form_dialog,
        title:      "Show Form",
        min_width:  800,
        min_height: 500,
        content: "<iframe class=\"check_url_iframe\" src=\"" + e.data.url + "\"></iframe>",
        buttons: [{
          text: "{$LANG["phrase_open_form_in_new_tab_or_win"]}",
          click: function() {
            window.open(url);
            $(this).dialog("close");
          }
        },
        {
          text: "{$LANG["word_close"]}",
          click: function() {
            $(this).dialog("close");
          }
        }]
      });
      return false;
    });
  });
});


END;

ft_display_page("admin/forms/index.tpl", $page_vars);
