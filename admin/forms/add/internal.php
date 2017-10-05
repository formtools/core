<?php

require_once("../../../global/library.php");

use FormTools\Core;
use FormTools\Forms;
use FormTools\General;
use FormTools\Pages;
use FormTools\Themes;

Core::init();
Core::$user->checkAuth("admin");

$success = true;
$message = "";
if (isset($request["add_form"])) {
	list($success, $message, $new_form_id) = Forms::createInternalForm($request);
	if ($message) {
        General::redirect("../edit/?form_id={$new_form_id}&message=notify_internal_form_created");
	}
}

$LANG = Core::$L;

$head_js =<<< END
ft.click([
  { el: "at1", targets: [{ el: "custom_clients", action: "hide" }] },
  { el: "at2", targets: [{ el: "custom_clients", action: "hide" }] },
  { el: "at3", targets: [{ el: "custom_clients", action: "show" }] }
]);

$(function() {
  $("#form_name").focus();
  $("#create_internal_form").bind("submit",function(e) {
    var rules = [];
    rules.push("required,form_name,{$LANG["validation_no_form_name"]}");
    rules.push("required,num_fields,{$LANG["validation_no_num_form_fields"]}");
    rules.push("digits_only,num_fields,{$LANG["validation_invalid_num_form_fields"]}");
    rules.push("range<=1000,num_fields,{$LANG["validation_internal_form_too_many_fields"]}");
    rules.push("required,access_type,{$LANG["validation_no_access_type"]}");
    if (!rsv.validate(this, rules)) {
      e.preventDefault();
    }
    ft.select_all("selected_client_ids[]");
  });
});
END;

// compile the header information
$page = array(
    "page"     => "add_form_internal",
    "g_success" => $success,
    "g_message" => $message,
    "page_url" => Pages::getPageUrl("add_form_internal"),
    "head_title" => $LANG["phrase_add_form"],
    "head_js" => $head_js
);

Themes::displayPage("admin/forms/add/internal.tpl", $page);
