<?php

require("../../global/session_start.php");
ft_check_permission("admin");

// if required, include the Image Manager module
if (ft_check_module_enabled("image_manager"))
  ft_include_module("image_manager");

// blur the GET and POST variables into a single variable for easy reference
$request = array_merge($_GET, $_POST);
$form_id = ft_load_field("form_id", "curr_form_id");

if (isset($request["view_id"]))
{
	$view_id = $request["view_id"];
	$_SESSION["ft"]["form_{$form_id}_view_id"] = $view_id;
}
else
{
  $view_id = isset($_SESSION["ft"]["form_{$form_id}_view_id"]) ? $_SESSION["ft"]["form_{$form_id}_view_id"] : "";
}

// if the View ID isn't set, here - they probably just linked to the page directly from an email, module
// or elsewhere in the script. For this case, use the default View
if (empty($view_id))
{
	$view_id = ft_get_default_view($form_id);
}

$submission_id = $request["submission_id"];
$tab_number = ft_load_field("tab", "view_{$view_id}_current_tab", 1);

// store this submission ID
$_SESSION["ft"]["last_submission_id"] = $submission_id;

// get a list of all editable fields in the View. This is used both for security purposes
// for the update function and to determine whether the page contains any editable fields
$editable_field_ids = _ft_get_editable_view_fields($view_id);

// get the tabs for this View
$view_tabs = ft_get_view_tabs($view_id, true);


// handle POST requests
if (isset($_POST) && !empty($_POST))
{
	// add the view ID to the request hash, for use by the ft_update_submission function
	$request["view_id"] = $view_id;
	$request["editable_field_ids"] = $editable_field_ids;
  list($g_success, $g_message) = ft_update_submission($form_id, $submission_id, $request);

	// required. The reason being, this setting determines whether the submission IDs in the current form-view-search
	// are cached. Any time the data changes, the submission may then belong to different Views, so we need to re-cache it
	$_SESSION["ft"]["new_search"] = "yes";

  // if required, remove a file or image
  $file_deleted = false;
  if (isset($_POST['delete_file_type']) && $_POST['delete_file_type'] == "file")
  {
    list($g_success, $g_message) = ft_delete_file_submission($form_id, $submission_id, $_POST['field_id']);
    $file_deleted = true;
  }
  else if (isset($_POST['delete_file_type']) && $_POST['delete_file_type'] == "image")
  {
    list($g_success, $g_message) = img_delete_image_file_submission($form_id, $submission_id, $_POST['field_id']);
    $file_deleted = true;
  }

  // TODO this deprecated??
  else if (isset($_POST['email_user']) && !empty($_POST['email_user']))
  {
    $g_success = ft_send_email("user", $form_id, $submission_id);
    if ($g_success)
      $g_message = $LANG["notify_email_sent_to_user"];
  }
}

$form_info       = ft_get_form($form_id);
$view_info       = ft_get_view($view_id);
$submission_info = ft_get_submission($form_id, $submission_id, $view_id);

// get the subset of fields (and IDs) from $submission_info that appear on the current tab (or tab-less page)
$submission_tab_fields    = array();
$submission_tab_field_ids = array();
$wysiwyg_field_ids        = array();
$image_field_info         = array();

for ($i=0; $i<count($submission_info); $i++)
{
	// if this view has tabs, ignore those fields that aren't on the current tab.
	if (count($view_tabs) > 0 && (!isset($submission_info[$i]["tab_number"]) || $submission_info[$i]["tab_number"] != $tab_number))
	  continue;

	$curr_field_id = $submission_info[$i]["field_id"];

	if ($submission_info[$i]["field_type"] == "wysiwyg")
	  $wysiwyg_field_ids[] = "field_{$curr_field_id}_wysiwyg";

	// if this is an image field, keep track of its extended image settings. These are passed to the image rendering Smarty
	// plugin function to let it know how to display it
	if ($submission_info[$i]["field_type"] == "image")
	  $image_field_info[$curr_field_id] = ft_get_extended_field_settings($curr_field_id, "image_manager");

	$submission_tab_field_ids[] = $curr_field_id;
  $submission_tab_fields[]    = $submission_info[$i];
}

$wysiwyg_field_id_list = join(",", $wysiwyg_field_ids);

// get a list of editable fields on this tab
$editable_tab_fields = array_intersect($submission_tab_field_ids, $editable_field_ids);

$search = isset($_SESSION["ft"]["current_search"]) ? $_SESSION["ft"]["current_search"] : array();

// if we're just coming here from the search results page, get a fresh list of every submission ID in this
// search result set. This is used to build the internal "<< previous   next >>" nav on this details page
if (isset($_SESSION["ft"]["new_search"]) && $_SESSION["ft"]["new_search"] == "yes")
{
  // extract the original search settings and get the list of IDs
  $_SESSION["ft"]["form_{$form_id}_view_{$view_id}_submissions"] = ft_get_search_submission_ids($form_id, $view_id, $search["results_per_page"], $search["order"], $search["search_fields"]);
  $_SESSION["ft"]["new_search"] = "no";
}


$previous_link_html       = "";
$search_results_link_html = "";
$next_link_html           = "";

if (isset($_SESSION["ft"]["form_{$form_id}_view_{$view_id}_submissions"]) && !empty($_SESSION["ft"]["form_{$form_id}_view_{$view_id}_submissions"]))
{
  $submission_ids = $_SESSION["ft"]["form_{$form_id}_view_{$view_id}_submissions"];
  $current_sub_id_index = array_search($submission_id, $submission_ids);

  // PREVIOUS link
  $previous_link_html = "";
  if ($submission_ids[0] == $submission_id)
    $previous_link_html = "<span class=\"light_grey\">{$LANG['word_previous_leftarrow']}</span>";
  else
  {
    $previous_submission_id = $submission_ids[$current_sub_id_index - 1];
    $previous_link_html = "<a href=\"{$_SERVER['PHP_SELF']}?form_id=$form_id&view_id=$view_id&submission_id=$previous_submission_id\">{$LANG['word_previous_leftarrow']}</a>";
  }

  $results_per_page = $search["results_per_page"];
  $submission_ids_per_page = array_chunk($submission_ids, 10);

  $return_page = 1;
  for ($i=0; $i<count($submission_ids_per_page); $i++)
  {
    if (in_array($submission_id, $submission_ids_per_page[$i]))
    {
      $return_page = $i+1;
      break;
    }
  }

  $search_results_link_html = "<a href=\"submissions.php?form_id=$form_id&page=$return_page\">{$LANG['phrase_back_to_search_results']}</a>";

  // NEXT link
  $next_link_html = "";
  if ($submission_ids[count($submission_ids) - 1] == $submission_id)
    $next_link_html = "<span class=\"light_grey\">{$LANG['word_next_rightarrow']}</span>";
  else
  {
    $next_submission_id = $submission_ids[$current_sub_id_index + 1];
    $next_link_html = "<a href='{$_SERVER['PHP_SELF']}?form_id=$form_id&view_id=$view_id&submission_id=$next_submission_id'>{$LANG['word_next_rightarrow']}</a>";
  }
}

$tabs = array();
while (list($key, $value) = each($view_tabs))
{
  $tabs[$key] = array(
    "tab_label" => $value["tab_label"],
    "tab_link" => "{$_SERVER["PHP_SELF"]}?tab=$key&submission_id={$submission_id}"
    );
}

$image_manager_enabled = ft_check_module_enabled("image_manager");

// ------------------------------------------------------------------------------------------------

// compile the header information
$page_vars = array();
$page_vars["page"]   = "admin_edit_submission";
$page_vars["page_url"] = ft_get_page_url("admin_edit_submission");
$page_vars["tabs"] = $tabs;
$page_vars["submission_id"] = $submission_id;
$page_vars["submission_info"] = $submission_info;
$page_vars["previous_link_html"] = $previous_link_html;
$page_vars["search_results_link_html"] = $search_results_link_html;
$page_vars["next_link_html"] = $next_link_html;
$page_vars["tab_has_editable_fields"] = count($editable_tab_fields) > 0;
$page_vars["view_info"] = $view_info;
$page_vars["image_field_info"] = $image_field_info;
$page_vars["form_id"] = $form_id;
$page_vars["view_id"] = $view_id;
$page_vars["submission_tab_fields"] = $submission_tab_fields;
$page_vars["submission_tab_field_id_str"] = join(",", $submission_tab_field_ids);
$page_vars["tab_number"] = $tab_number;
$page_vars["js_messages"] = array("confirm_delete_submission", "notify_no_email_template_selected");
$page_vars["head_title"] = "{$LANG['phrase_edit_submission']} - $submission_id";
$page_vars["head_string"] = "<script type=\"text/javascript\" src=\"$g_root_url/global/tiny_mce/tiny_mce.js\"></script>
  <script type=\"text/javascript\" src=\"$g_root_url/global/scripts/wysiwyg_settings.js\"></script>
  <script type=\"text/javascript\" src=\"$g_root_url/global/scripts/manage_submissions.js\"></script>
  <link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$g_root_url}/global/jscalendar/skins/aqua/theme.css\" title=\"Aqua\" />
  <script type=\"text/javascript\" src=\"$g_root_url/global/jscalendar/calendar.js\"></script>
  <script type=\"text/javascript\" src=\"$g_root_url/global/jscalendar/calendar-setup.js\"></script>
  <script type=\"text/javascript\" src=\"$g_root_url/global/jscalendar/lang/calendar-en.js\"></script>
	<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/lightbox.js\"></script>
  <link rel=\"stylesheet\" href=\"$g_root_url/global/css/lightbox.css\" type=\"text/css\" media=\"screen\" />";

$tiny_resize = ($_SESSION["ft"]["settings"]["tinymce_resize"] == "yes") ? "true" : "false";
$content_css = "$g_root_url/global/css/tinymce.css";

	$page_vars["head_js"] = "

// load up any WYWISYG editors in the page
g_content_css = \"$content_css\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].elements = \"$wysiwyg_field_id_list\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].theme_advanced_toolbar_location = \"{$_SESSION["ft"]["settings"]["tinymce_toolbar_location"]}\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].theme_advanced_toolbar_align = \"{$_SESSION["ft"]["settings"]["tinymce_toolbar_align"]}\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].theme_advanced_path_location = \"{$_SESSION["ft"]["settings"]["tinymce_path_info_location"]}\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].theme_advanced_resizing = $tiny_resize;
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].content_css = \"$content_css\";
tinyMCE.init(editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"]);";

ft_display_page("admin/forms/edit_submission.tpl", $page_vars);
