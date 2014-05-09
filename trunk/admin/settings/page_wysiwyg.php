<?php

// update the settings
if (isset($request["update_wysiwyg"]))
	list($g_success, $g_message) = ft_update_wysiwyg_settings($request);


$theme = $_SESSION["ft"]["account"]["theme"];
$content_css = "$g_root_url/global/css/tinymce.css";
$tinymce_path_info_location = $_SESSION["ft"]["settings"]["tinymce_path_info_location"];

// if show path is set to "no", overwrite the theme_advanced_path_location val
if ($_SESSION["ft"]["settings"]["tinymce_show_path"] == "no")
	$tinymce_path_info_location = "";

$tiny_resize = ($_SESSION["ft"]["settings"]["tinymce_resize"] == "yes") ? "true" : "false";


// compile the header information
$page_vars = array();
$page_vars["page"] = "wysiwyg";
$page_vars["page_url"] = ft_get_page_url("settings_wysiwyg");
$page_vars["tabs"] = $tabs;
$page_vars["head_title"] = "{$LANG["word_settings"]} - {$LANG["word_wysiwyg"]}";
$page_vars["head_string"] = "
	<script type=\"text/javascript\" src=\"$g_root_url/global/tiny_mce/tiny_mce.js\"></script>
	<script type=\"text/javascript\" src=\"$g_root_url/global/scripts/wysiwyg_settings.js\"></script>
";
$page_vars["head_js"] = "

// load up the example editor
g_content_css = \"$content_css\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].elements = \"example\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].theme_advanced_toolbar_location = \"{$_SESSION["ft"]["settings"]["tinymce_toolbar_location"]}\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].theme_advanced_toolbar_align = \"{$_SESSION["ft"]["settings"]["tinymce_toolbar_align"]}\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].theme_advanced_path_location = \"$tinymce_path_info_location\";
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].theme_advanced_resizing = $tiny_resize;
editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"].content_css = \"$content_css\";
tinyMCE.init(editors[\"{$_SESSION["ft"]["settings"]["tinymce_toolbar"]}\"]);

// this assigns an onchange handler to all the config options, so that whenever the user
// changes the settings, the example editor is automatically updated to reflect the new settings
Event.observe(document, 'dom:loaded',
	function()
	{
		$$('.update_example').invoke('observe', 'change', function(e) { wysiwyg_ns.update_editor('example'); });
		$$('#tinymce_show_path1', '#tinymce_show_path2').invoke('observe', 'click',
			function(e)
			{
				var is_disabled = true;
				if (Event.element(e).value == 'yes')
					is_disabled = false;

				$('tinymce_path_info_location1').disabled = is_disabled;
				$('tinymce_path_info_location2').disabled = is_disabled;
				$('tinymce_resize1').disabled = is_disabled;
				$('tinymce_resize2').disabled = is_disabled;
			});
	} );
";

ft_display_page("admin/settings/index.tpl", $page_vars);