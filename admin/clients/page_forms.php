<?php

// update this client
if (isset($_POST["update_client"]))
  list($g_success, $g_message) = ft_admin_update_client($request, 3);

$client_info  = ft_get_account_info($client_id);
$forms        = ft_get_forms(); // all forms in the database, regardless of permission type

$forms_js_rows = array();
$forms_js_rows[] = "var page_ns = {}";
$forms_js_rows[] = "page_ns.forms = []";
$form_views_js_info = array("page_ns.form_views = []");

// convert ALL form and View info into Javascript, for use in the page
foreach ($forms as $form_info)
{
  // ignore those forms that aren't set up
  if ($form_info["is_complete"] == "no")
    continue;

  $form_id = $form_info["form_id"];
  $form_name = htmlspecialchars($form_info["form_name"]);
  $forms_js_rows[] = "page_ns.forms.push([$form_id, \"$form_name\"])";

  $form_views = ft_get_views($form_id);

  $v = array();
  foreach ($form_views["results"] as $form_view)
  {
    $view_id   = $form_view["view_id"];
    $view_name = htmlspecialchars($form_view["view_name"]);
    $v[] = "[$view_id, \"$view_name\"]";
  }
  $views = implode(",", $v);

  $form_views_js_info[] = "page_ns.form_views.push([$form_id,[$views]])";
}

$forms_js = implode(";\n", $forms_js_rows);
$form_views_js = implode(";\n", $form_views_js_info);

// loop through each form and add all the Views
$all_form_views = array();
foreach ($forms as $form_info)
{
  $form_id = $form_info["form_id"];
  $all_form_views[$form_id] = ft_get_form_views($form_id);
}

$client_forms = ft_search_forms($client_id, true);
$updated_client_forms = array();
foreach ($client_forms as $form_info)
{
  $form_id = $form_info["form_id"];
  $form_info["views"] = ft_get_form_views($form_id, $client_id);
  $updated_client_forms[] = $form_info;
}

// -------------------------------------------------------------------------------------------

// compile header information
$page_vars["page"] = "forms";
$page_vars["page_url"] = ft_get_page_url("edit_client_forms", array("client_id" => $client_id));
$page_vars["head_title"]   = "{$LANG["phrase_edit_client"]} - {$LANG["word_forms"]}";
$page_vars["client_info"]    = $client_info;
$page_vars["forms"]          = $forms;
$page_vars["client_forms"]   = $updated_client_forms;
$page_vars["all_form_views"] = $all_form_views;
$page_vars["client_id"]      = $client_id;
$page_vars["js_messages"]    = array("word_delete", "phrase_please_select", "phrase_please_select_form", "word_add_uc_rightarrow",
   "word_remove_uc_leftarrow", "phrase_form_already_selected");
$page_vars["head_string"]    = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_client_forms.js\"></script>";
$page_vars["head_js"] =<<< END
$forms_js
$form_views_js
END;

ft_display_page("admin/clients/edit.tpl", $page_vars);