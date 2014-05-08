<?php

// a form ID & view ID should always be set when visiting this page
$view_id = ft_load_field("view_id", "form_{$form_id}_view_id");

// this updates all four sections of the view at once (since all may have been modified)
if (isset($request["update_view"]))
{
	$request["form_id"] = $form_id;
	list($g_success, $g_message) = ft_update_view($view_id, $request);
}

$form_info     = ft_get_form($form_id);
$form_fields   = ft_get_form_fields($form_id);
$view_info     = ft_get_view($view_id);
$view_fields   = $view_info["fields"];
$form_database_column_info = ft_get_form_column_names($form_id);
$view_clients  = ft_get_view_clients($view_id);
$view_tabs     = ft_get_view_tabs($view_id);
$filters       = ft_get_view_filters($view_id);
$num_filters   = count($filters);
$edit_view_tab = ft_load_field("edit_view_tab", "edit_view_tab", 1);
$view_omit_list = ft_get_public_view_omit_list($view_id);
$num_clients_on_omit_list = count($view_omit_list);

// this is placed in the code, since the order and display of the fields & text are strongly
// tied to the language, so it needs to be processed with PHP...
$tabs_html = "<select id=\"assign_fields_tab_selection\">\n";
$tab_options = array();
$has_tabs = false;
foreach ($view_tabs as $key=>$value)
{
  if (!empty($value["tab_label"]))
  {
   	$tab_options[] = "<option value=\"{$key}\">{$value["tab_label"]}</option>";
   	$has_tabs = true;
  }
}
if (empty($tab_options))
	$tab_options[] = "<option value=\"\">{$LANG["validation_no_tabs_defined"]}</option>";
$tabs_html .= join("\n", $tab_options) . "</select>\n";

$replacements = array(
	"x" => "<input type=\"text\" id=\"tab_row_from\" value=\"1\" size=\"3\" />",
	"y" => "<input type=\"text\" id=\"tab_row_to\" value=\"1\" size=\"3\" />",
	"tab" => $tabs_html
		);
$page_vars["assign_rows_to_tabs_html"] = ft_eval_smarty_string($LANG["phrase_assign_rows_to_tabs"], $replacements);


// assumes view_ns.all_form_fields and view_ns.view_tabs JS arrays have been defined in manage_views.js
$js_string = "";
foreach ($form_fields as $field)
{
  $display_name = htmlspecialchars($field["field_title"]);
  $col_name     = $field["col_name"];
  $js_string .= "view_ns.all_form_fields.push([{$field["field_id"]}, \"$display_name\", \"$col_name\"]);\n";

  // if this is the submission ID or Last Modified field, store its field ID in a JS var. This is used to
  // identify this special field in the JS. Specifically, it's needed to prevent showing the "Editable" checkbox for
  if ($col_name == "submission_id")
    $js_string .= "view_ns.submission_id_field_id = {$field["field_id"]};\n";
  else if ($col_name == "last_modified_date")
    $js_string .= "view_ns.last_modified_date_field_id = {$field["field_id"]};\n";
}

for ($i=1; $i<=count($view_tabs); $i++)
{
  if (empty($view_tabs["$i"]["tab_label"]))
    continue;

  $tab_name = addslashes($view_tabs["$i"]["tab_label"]);
  $js_string .= "view_ns.view_tabs.push([\"$i\", \"$tab_name\"]);\n";
}

// for the filters
$js_string .= "view_ns.num_filter_rows = $num_filters;\n";

// build the selected users <options>
$selected_users_str = "";
$selected_user_ids = array();
for ($i=0; $i<count($view_clients); $i++)
{
	$client_id  = $view_clients[$i]["account_id"];
	$first_name = $view_clients[$i]["first_name"];
	$last_name  = $view_clients[$i]["last_name"];
  $selected_users_str .= "<option value=\"$client_id\">$first_name $last_name</option>\n";

  $selected_user_ids[] = $client_id;
}

// build the available users <options>. This is used to populate the Available Clients section
// of Private accessed forms
$available_users_str = "";
foreach ($form_info["client_info"] as $client)
{
	if (in_array($client["account_id"], $selected_user_ids))
		continue;

	$available_users_str .= "<option value=\"{$client['account_id']}\">{$client['first_name']} {$client['last_name']}</option>\n";
}


// build the available fields dropdown
$available_fields = "";
$has_content = false;
foreach ($form_fields as $field)
{
	$field_id = $field["field_id"];

	$contains_view_id = false;
	foreach ($view_fields as $view)
	{
		if ($field_id == $view["field_id"])
			$contains_view_id = true;
	}

	if (!$contains_view_id)
	{
		$available_fields .= "<option value=\"$field_id\">{$field['field_title']}</option>";
		$has_content = true;
	}
}

if (!$has_content)
{
	$available_fields = "<option value=''>{$LANG["phrase_all_fields_displayed"]}</option>\n";
	$no_available_fields = true;
}
else
	$no_available_fields = false;


// get the ID of the previous and next field options. We should probably cache this, but until I'm sure
// it's slowing things down, we'll keep it simple
$ordered_view_ids = ft_get_view_ids($form_id);
$previous_view_link = "<span class=\"light_grey\">{$LANG["phrase_previous_view"]}</span>";
$next_view_link = "<span class=\"light_grey\">{$LANG["phrase_next_view"]}</span>";
$num_views = count($ordered_view_ids);

for ($i=0; $i<$num_views; $i++)
{
	$curr_view_id = $ordered_view_ids[$i];

	if ($curr_view_id == $view_id)
	{
		if ($i != 0)
		{
			$previous_view_id = $ordered_view_ids[$i-1];
			$previous_view_link = "<a href=\"{$_SERVER["PHP_SELF"]}?page=edit_view&view_id=$previous_view_id\">{$LANG["phrase_previous_view"]}</a>";
		}
		if ($i != $num_views - 1)
		{
			$next_view_id = $ordered_view_ids[$i+1];
			$next_view_link = "<a href=\"{$_SERVER["PHP_SELF"]}?page=edit_view&view_id=$next_view_id\">{$LANG["phrase_next_view"]}</a>";
		}
	}
}

// -----------------------------------------------------------------------------------------------

// compile the templates information
$page_vars["page"]       = "edit_view";
$page_vars["page_url"]   = ft_get_page_url("edit_view");
$page_vars["tabs"]       = $tabs;
$page_vars["form_id"]    = $form_id;
$page_vars["view_id"]    = $view_id;
$page_vars["head_title"] = "{$LANG["phrase_edit_form"]} - {$LANG["phrase_edit_view"]}";
$page_vars["available_fields"] = $available_fields;
$page_vars["no_available_fields"] = $no_available_fields;
$page_vars["filters"]     = $filters;
$page_vars["num_filters"] = $num_filters;
$page_vars["edit_view_tab"] = $edit_view_tab;
$page_vars["has_tabs"]    = $has_tabs;
$page_vars["form_info"]   = $form_info;
$page_vars["view_tabs"]   = $view_tabs;
$page_vars["view_info"]   = $view_info;
$page_vars["form_fields"] = $form_fields;
$page_vars["view_fields"] = $view_fields;
$page_vars["previous_view_link"] = $previous_view_link;
$page_vars["next_view_link"] = $next_view_link;
$page_vars["selected_users"] = $selected_users_str;
$page_vars["available_users"] = $available_users_str;
$page_vars["form_database_column_info"] = $form_database_column_info;
$page_vars["selected_user_ids"] = $selected_user_ids;
$page_vars["num_clients_on_omit_list"] = $num_clients_on_omit_list;
$page_vars["head_string"] = "
	<script type=\"text/javascript\" src=\"{$g_root_url}/global/scripts/manage_views.js\"></script>
  <link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{$g_root_url}/global/jscalendar/skins/aqua/theme.css\" title=\"Aqua\" />
  <script type=\"text/javascript\" src=\"{$g_root_url}/global/jscalendar/calendar.js\"></script>
  <script type=\"text/javascript\" src=\"{$g_root_url}//global/jscalendar/calendar-setup.js\"></script>
  <script type=\"text/javascript\" src=\"{$g_root_url}/global/jscalendar/lang/calendar-en.js\"></script>";

$replacements = array("user_doc_link" => "http://docs.formtools.org/userdoc/index.php?page=view_filters");
$page_vars["text_filters_tips"] = ft_eval_smarty_string($LANG["text_filters_tips"], $replacements);
$replacements = array("number" => "<input type=\"text\" name=\"num_filter_rows\" id=\"num_filter_rows\" value=\"1\" size=\"2\" />");
$page_vars["add_num_rows_input_field"] = ft_eval_smarty_string($LANG["phrase_add_num_rows"], $replacements);

$page_vars["js_messages"] = array("word_remove", "validation_no_tabs_defined", "phrase_all_fields_displayed", "validation_invalid_tab_assign_values",
        "validation_num_rows_to_add", "phrase_please_select", "word_before", "word_after", "word_equals",
        "phrase_not_equal", "word_like", "phrase_not_like", "validation_no_view_name", "validation_no_num_submissions_per_page",
        "validation_no_view_fields", "validation_no_column_selected", "validation_no_view_fields_selected");

$page_vars["head_js"] = "
$js_string

Event.observe(document, 'dom:loaded',
	function()
	{
		// if there are no filters, init the form with a single, empty filter
		if (view_ns.num_filter_rows == 0)
		   view_ns.add_filters(\"1\");

		// add the onblur handler to the tab label fields so that the dropdowns in the Fields tab are automatically
		// updated to show the available tabs
		$$('.tab_label').invoke('observe', 'blur', function(e) { view_ns.update_field_tabs(); });
	}
);


var page_ns = {};
page_ns.toggle_view_type = function(form_type)
{
  switch (form_type)
  {
    case \"admin\":
      $(\"client_omit_list_button\").disabled = true;
      $(\"custom_clients\").hide();
	    break;
    case \"public\":
	    $(\"client_omit_list_button\").disabled = false;
	    $(\"custom_clients\").hide();
	    break;
    case \"private\":
	    $(\"client_omit_list_button\").disabled = true;
	    $(\"custom_clients\").show();
	    break;
  }
}
	";

ft_display_page("admin/forms/edit.tpl", $page_vars);
