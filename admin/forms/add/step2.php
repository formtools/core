<?php

require("../../../global/session_start.php");
ft_check_permission("admin");

$form_id = ft_load_field("form_id", "add_form_form_id", "");
$request = array_merge($_POST, $_GET);

// a hash of form values
$page_values = array();

// start setting up the form
if (isset($request["add_form"]))
{
  list($g_success, $g_message, $form_id) = ft_setup_form($request);

  // store the uploading_files value for the duration of this session
  $_SESSION["ft"]["uploading_files"] = isset($request['uploading_files']) ? $request['uploading_files'] : "no";

  // form successfully added. Continue to step 2.
  if ($g_success)
  {
    session_write_close();
    header("location: step3.php?form_id=$form_id");
    exit;
  }

  // error. reload the page with the already entered form values, and display
  // the appropriate error message.
  else
    $page_values = ft_preload_values("post");
}

// update this form
else if (isset($request['update_form']))
{
  // store the uploading_files value for the duration of this session
  $_SESSION["ft"]["uploading_files"] = isset($request["uploading_files"]) ? $request["uploading_files"] : "no";

  list($g_success, $g_message) = ft_set_form_main_settings($request);
  if ($g_success)
  {
    header("location: step3.php?form_id=$form_id");
    exit;
  }
  else
    $page_values = ft_preload_values("post", $form_id);
}

// edit existing form (used for cases where user fails to complete form building process, then returns
// later to finish the job)
else if (!empty($form_id))
{
  $page_values = ft_preload_values("database", $form_id);
}
// otherwise, the user is coming to this page for the first time. init the default values
else
	$page_values = ft_preload_values("new_form");


if (!isset($_SESSION["uploading_files"]))
  $_SESSION["ft"]["uploading_files"] = "no";

$form_query_str = (!empty($form_id)) ? "?form_id=$form_id" : "";


// helper function to preload the page values from different sources, depending
// on what's required.
function ft_preload_values($source, $form_id = "")
{
	global $request;
	$page_values = array();

  switch ($source)
  {
    case "new_form":
      $page_values["client_info"] = array();
      $page_values["form_name"] = "";
      $page_values["form_url"] = "";
      $page_values["is_multi_page_form"] = "no";
      $page_values["multi_page_form_urls"] = array();
      $page_values["redirect_url"] = "";
      $page_values["access_type"]  = "admin";
      $page_values["hidden_fields"] = "<input type=\"hidden\" name=\"add_form\" value=\"1\" />";
      break;

    case "post":
      $page_values["form_name"]    = $request["form_name"];
      $page_values["form_url"]     = $request["form_url"];
      $page_values["is_multi_page_form"] = isset($request["is_multi_page_form"]) ? "yes" : "no";
      $page_values["redirect_url"] = $request["redirect_url"];
      $page_values["access_type"]  = $request["access_type"];
      $page_values["client_info"]  = array();

      if (!empty($form_id))
        $page_values["hidden_fields"] = "
          <input type=\"hidden\" name=\"update_form\" value=\"1\" />
          <input type=\"hidden\" name=\"form_id\" value=\"$form_id\" />";
      else
        $page_values["hidden_fields"] = "<input type=\"hidden\" name=\"add_form\" value=\"1\" />";
      break;

    case "database":
      if (empty($form_id))
        return;

      $form_info = ft_get_form($form_id);
      $page_values["form_name"]    = $form_info["form_name"];
      $page_values["form_url"]     = $form_info["form_url"];
      $page_values["is_multi_page_form"] = $form_info["is_multi_page_form"];
      $page_values["multi_page_form_urls"]  = $form_info["multi_page_form_urls"];
      $page_values["redirect_url"] = $form_info["redirect_url"];
      $page_values["access_type"]  = $form_info["access_type"];
      $page_values["client_info"]  = $form_info["client_info"];

      $page_values["hidden_fields"] = "
        <input type=\"hidden\" name=\"update_form\" value=\"1\" />
        <input type=\"hidden\" name=\"form_id\" value=\"$form_id\" />";
      break;
  }

  return $page_values;
}


$selected_client_ids = array();
for ($i=0; $i<count($page_values["client_info"]); $i++)
  $selected_client_ids[] = $page_values["client_info"][$i]["account_id"];

$num_pages_in_multi_page_form = count($page_values["multi_page_form_urls"]) + 1;

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars["page"]     = "add_form1";
$page_vars["page_url"] = ft_get_page_url("add_form2");
$page_vars["head_title"] = "{$LANG['phrase_add_form']} - {$LANG["phrase_step_2"]}";
$page_vars["page_values"] = $page_values;
$page_vars["form_id"] = $form_id;
$page_vars["form_query_str"] = $form_query_str;
$page_vars["num_pages_in_multi_page_form"] = $num_pages_in_multi_page_form;
$page_vars["selected_client_ids"] = $selected_client_ids;
$page_vars["js_messages"] = array("validation_no_url", "phrase_verify_url", "word_page", "validation_invalid_url",
  "word_verified", "validation_urls_not_verified");
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_forms.js\"></script>";
$page_vars["head_js"] =<<< EOF
var rules = [];
rules.push("required,form_name,{$LANG['validation_no_form_name']}");
rules.push("required,form_url,{$LANG['validation_no_form_url']}");
rules.push("function,mf_ns.check_urls_verified");
rules.push("required,access_type,{$LANG["validation_no_access_type"]}");

rsv.onCompleteHandler = function() { ft.select_all($('selected_client_ids[]')); return true; }

Event.observe(document, "dom:loaded", function() {
  mf_ns.num_multi_page_form_pages = $num_pages_in_multi_page_form;
  mf_ns.toggle_multi_page_form_fields($("is_multi_page_form").checked);
});

EOF;

ft_display_page("admin/forms/add/step2.tpl", $page_vars);
