<?php

require_once("../../global/library.php");

use FormTools\Administrator;
use FormTools\Clients;
use FormTools\Core;
use FormTools\General;
use FormTools\Pages;
use FormTools\Sessions;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$success = true;
$message = "";
if (isset($_GET['delete']) && !empty($_GET['client_id'])) {
    list($success, $message) = Clients::deleteClient($_GET['client_id']);
}
if (isset($_GET['login'])) {
    Administrator::loginAsClient($_GET['login']);
}

if (isset($_GET["reset"])) {
    Sessions::set("client_sort_order", "");
    Sessions::set("client_search_keyword", "");
    Sessions::set("client_search_status", "");
}
$order   = General::loadField("order", "client_sort_order", "last_name-ASC");
$keyword = General::loadField("keyword", "client_search_keyword", "");
$status  = General::loadField("status", "client_search_status", "");

$search_criteria = array(
	"order"     => $order,
	"keyword"   => $keyword,
	"status"    => $status
);
$num_clients = Clients::getNumClients();

// retrieve all client information
$clients = Clients::searchClients($search_criteria);
$LANG = Core::$L;


$head_js =<<< END
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

$page = array(
    "page" => "clients",
    "g_success" => $success,
    "g_message" => $message,
    "page_url" => Pages::getPageUrl("clients"),
    "head_title" => $LANG["word_clients"],
    "num_clients" => $num_clients,
    "clients" => $clients,
    "order" => $order,
    "search_criteria" => $search_criteria,
    "pagination" => General::getJsPageNav(count($clients), Sessions::get("settings.num_clients_per_page"), 1),
    "js_messages" => array("phrase_delete_row"),
    "head_js" => $head_js
);

Themes::displayPage("admin/clients/index.tpl", $page);
