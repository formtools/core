<?php

require("../../global/session_start.php");
ft_check_permission("admin");

if (isset($_GET['delete']) && !empty($_GET['client_id']))
  list($g_success, $g_message) = ft_delete_client($_GET['client_id']);

if (isset($_GET['login']))
  list($g_success, $g_message) = ft_login_as_client($_GET['login']);


if (isset($_GET["reset"]))
{
  $_SESSION["ft"]["client_sort_order"] = "";
  $_SESSION["ft"]["client_search_keyword"] = "";
  $_SESSION["ft"]["client_search_status"] = "";
}
$order   = ft_load_field("order", "client_sort_order", "last_name-ASC");
$keyword = ft_load_field("keyword", "client_search_keyword", "");
$status  = ft_load_field("status", "client_search_status", "");

$search_criteria = array(
  "order"     => $order,
  "keyword"   => $keyword,
  "status"    => $status
    );
$num_clients = ft_get_client_count();

// retrieve all client information
$clients = ft_search_clients($search_criteria);

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars = array();
$page_vars["page"] = "clients";
$page_vars["page_url"] = ft_get_page_url("clients");
$page_vars["head_title"] = $LANG["word_clients"];
$page_vars["num_clients"] = $num_clients;
$page_vars["clients"]  = $clients;
$page_vars["order"] = $order;
$page_vars["search_criteria"] = $search_criteria;
$page_vars["pagination"] = ft_get_dhtml_page_nav(count($clients), $_SESSION["ft"]["settings"]["num_clients_per_page"], 1);
$page_vars["js_messages"] = array("phrase_delete_row");

$page_vars["head_js"] =<<< END
  var page_ns = {};
  page_ns.dialog = $("<div></div>");
  page_ns.delete_client = function(account_id) {
    ft.create_dialog({
      dialog:     page_ns.dialog,
      title:      "{$LANG["phrase_please_confirm"]}",
      content:    "{$LANG["validation_check_delete_client"]}",
      popup_type: "warning",
      buttons: [
        {
          text: "{$LANG["word_yes"]}",
          click: function() {
            window.location = "index.php?delete=1&client_id=" + account_id;
          }
        },
        {
          text: "{$LANG["word_no"]}",
          click: function() {
            $(this).dialog("close");
          }
        }
      ]
    });
    return false;
  }
END;

ft_display_page("admin/clients/index.tpl", $page_vars);
