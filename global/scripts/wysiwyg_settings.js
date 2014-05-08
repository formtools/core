// these vars need to be set by the calling script
g_elements         = "";
g_toolbar_location = "";
g_toolbar_align    = "";
g_toolbar_path_location  = "";
g_toolbar_allow_resizing = "";
g_content_css = "";


var editors = [];
editors["basic"] = {
  mode : "exact",
  elements : g_elements,
  theme : "advanced",
  theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,bullist,numlist",
  theme_advanced_buttons2 : "",
  theme_advanced_buttons3 : "",
  theme_advanced_toolbar_location : g_toolbar_location,
  theme_advanced_toolbar_align : g_toolbar_align,
  theme_advanced_path_location : g_toolbar_path_location,
  theme_advanced_resizing : g_toolbar_allow_resizing,
  theme_advanced_resize_horizontal : false,
  content_css : g_content_css
    };

editors["simple"] = {
  mode : "exact",
  elements : g_elements,
  theme : "advanced",
  theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,bullist,numlist,|,outdent,indent,|,blockquote,hr,|,link,unlink,forecolorpicker,backcolorpicker",
  theme_advanced_buttons2 : "",
  theme_advanced_buttons3 : "",
  theme_advanced_toolbar_location : g_toolbar_location,
  theme_advanced_toolbar_align : g_toolbar_align,
  theme_advanced_path_location : g_toolbar_path_location,
  theme_advanced_resizing : g_toolbar_allow_resizing,
  theme_advanced_resize_horizontal : false,
  content_css : g_content_css
    };

editors["advanced"] = {
  mode : "exact",
  elements : g_elements,
  theme : "advanced",
  theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,bullist,numlist,|,outdent,indent,|,blockquote,hr,|,undo,redo,link,unlink,|,fontselect,fontsizeselect",
  theme_advanced_buttons2 : "forecolorpicker,backcolorpicker,|,sub,sup,code",
  theme_advanced_buttons3 : "",
  theme_advanced_toolbar_location : g_toolbar_location,
  theme_advanced_toolbar_align : g_toolbar_align,
  theme_advanced_path_location : g_toolbar_path_location,
  theme_advanced_resizing : g_toolbar_allow_resizing,
  theme_advanced_resize_horizontal : false,
  content_css : g_content_css
    };

editors["expert"] = {
  mode : "exact",
  elements : g_elements,
  theme : "advanced",
  theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,bullist,numlist,|,outdent,indent,|,blockquote,hr,|,undo,redo,link,unlink,|,formatselect,fontselect,fontsizeselect",
  theme_advanced_buttons2 : "undo,redo,|,forecolorpicker,backcolorpicker,|,sub,sup,|,newdocument,blockquote,charmap,removeformat,cleanup,code",
  theme_advanced_buttons3 : "",
  theme_advanced_toolbar_location : g_toolbar_location,
  theme_advanced_toolbar_align : g_toolbar_align,
  theme_advanced_path_location : g_toolbar_path_location,
  theme_advanced_resizing : g_toolbar_allow_resizing,
  theme_advanced_resize_horizontal : false,
  content_css : g_content_css
      };

// ----------------------------------------------------------------------------------------------

var wysiwyg_ns = {};

// changes a toolbar for a particular textarea
wysiwyg_ns.update_editor = function(editor_id)
{
  var f = $("wysiwyg_config_form");
  var toolbar = f.tinymce_toolbar.value;

  tinyMCE.execCommand("mceRemoveControl", false, editor_id);
  editors[toolbar].elements = "example";
  editors[toolbar].theme_advanced_toolbar_location = ft.get_checked_value(f.tinymce_toolbar_location);
  editors[toolbar].theme_advanced_toolbar_align = ft.get_checked_value(f.tinymce_toolbar_align);
  editors[toolbar].theme_advanced_resizing = (ft.get_checked_value(f.tinymce_resize) == "yes") ? true : false;

  if (ft.get_checked_value(f.tinymce_show_path) == "yes")
    editors[toolbar].theme_advanced_path_location = ft.get_checked_value(f.tinymce_path_info_location);
  else
    editors[toolbar].theme_advanced_path_location = "";

  editors[toolbar].content_css = g_content_css;

  tinyMCE.init(editors[toolbar]);
  tinyMCE.execCommand("mceAddControl", false, editor_id);
}

