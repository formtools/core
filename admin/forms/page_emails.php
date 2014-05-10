<?php

if (isset($request["add_email"]))
{
  $create_email_from_email_id = (isset($request["create_email_from_email_id"])) ? $request["create_email_from_email_id"] : "";
  $email_id = ft_create_blank_email_template($form_id, $create_email_from_email_id);
  session_write_close();
  header("Location: edit.php?page=edit_email&form_id=$form_id&email_id=$email_id");
  exit;
}

if (isset($request["delete"]))
{
  list($g_success, $g_message) = ft_delete_email_template($request["delete"]);
}

$form_info = ft_get_form($form_id);
$emails_page = ft_load_field("emails_page", "form_{$form_id}_emails_page", 1);
$form_email_info  = ft_get_email_templates($form_id, $emails_page);
$form_emails      = $form_email_info["results"];
$num_form_emails  = $form_email_info["num_results"];
$registered_form_emails = ft_get_email_fields($form_id);
$num_registered_form_emails = count($registered_form_emails);

// a little irksome, but we also need to retrieve ALL emails, for the "Create Email From Existing Email" dropdown
$all_form_emails = ft_get_email_template_list($form_id);
$php_self = ft_get_clean_php_self();


// compile the templates information
$page_vars["page"]        = "emails";
$page_vars["page_url"]    = ft_get_page_url("edit_form_emails", array("form_id" => $form_id));
$page_vars["form_emails"] = $form_emails;
$page_vars["all_form_emails"] = $all_form_emails;
$page_vars["num_form_emails"] = $num_form_emails;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["word_emails"]}";
$page_vars["form_info"]  = $form_info;
$page_vars["js_messages"] = array("word_edit", "word_remove");

// build values to pass along in nav query string
$pass_along_str = "page=emails&form_id=$form_id";
$page_vars["pagination"] = ft_get_page_nav($num_form_emails, $_SESSION["ft"]["settings"]["num_emails_per_page"], $emails_page, $pass_along_str, "emails_page");
$page_vars["num_registered_form_emails"] = $num_registered_form_emails;

$page_vars["head_js"] =<<< END
var page_ns = {};
page_ns.delete_dialog = $("<div></div>");
page_ns.delete_email = function(email_id) {
  ft.create_dialog({
    title:      "{$LANG["phrase_please_confirm"]}",
    content:    "{$LANG["confirm_delete_email_template"]}",
    popup_type: "warning",
    buttons: [{
      text:  "{$LANG["word_yes"]}",
      click: function() {
        window.location = "$php_self?form_id=$form_id&page=emails&delete=" + email_id;
        $(this).dialog("close");
      }
    },
    {
      text:  "{$LANG["word_no"]}",
      click: function() {
        $(this).dialog("close");
      }
    }]
  });

  return false;
}
END;

ft_display_page("admin/forms/edit.tpl", $page_vars);