<?php


namespace FormTools\FieldTypes;


class Date
{
    public static function get()
    {
        return array(
            "field_type" => array(
                "is_editable"                    => "no",
                "is_enabled"                     => "yes",
                "non_editable_info"              => "{\$LANG.text_non_deletable_fields}",
                "managed_by_module_id"           => null,
                "field_type_name"                => "{\$LANG.word_date}",
                "field_type_identifier"          => "date",
                "is_file_field"                  => "no",
                "is_date_field"                  => "yes",
                "raw_field_type_map"             => "",
                "compatible_field_sizes"         => "small",
                "view_field_rendering_type"      => "php",
                "view_field_php_function_source" => "core",
                "view_field_php_function"        => "FormTools\\FieldTypes::displayFieldTypeDate",
                "view_field_smarty_markup"       => self::getViewField(),
                "edit_field_smarty_markup"       => self::getEditField(),
                "php_processing"                 => self::getPhpProcessing(),
                "resources_css"                  => self::getCSS(),
                "resources_js"                   => self::getJS()
            ),

            "settings" => self::getSettings(),
            "validation" => array(
                array(
                    "rsv_rule"                 => "required",
                    "rule_label"               => "{\$LANG.word_required}",
                    "rsv_field_name"           => "{\$field_name}",
                    "custom_function"          => "",
                    "custom_function_required" => "na",
                    "default_error_message"    => "{\$LANG.validation_default_rule_required}"
                )
            )
        );
    }

    private static function getSettings()
    {
        return array(

            // Custom Display Format
            array(
                "field_label"              => "{\$LANG.phrase_custom_display_format}",
                "field_setting_identifier" => "display_format",
                "field_type"               => "select",
                "field_orientation"        => "na",
                "default_value_type"       => "static",
                "default_value"            => "yy-mm-dd",
                "options" => array(
                    array(
                        "option_text"       => "2011-11-30",
                        "option_value"      => "yy-mm-dd",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "30/11/2011 (dd/mm/yyyy)",
                        "option_value"      => "dd/mm/yy",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "11/30/2011 (mm/dd/yyyy)",
                        "option_value"      => "mm/dd/yy",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "Nov 30, 2011",
                        "option_value"      => "M d, yy",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "November 30, 2011",
                        "option_value"      => "MM d, yy",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "Wed Nov 30, 2011",
                        "option_value"      => "D M d, yy",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "Wednesday, November 30, 2011",
                        "option_value"      => "DD, MM d, yy",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "30. 08. 2011",
                        "option_value"      => "dd. mm. yy",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "30/11/2011 8:00 PM",
                        "option_value"      => "datetime:dd/mm/yy|h:mm TT|ampm`true",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "11/30/2011 8:00 PM",
                        "option_value"      => "datetime:mm/dd/yy|h:mm TT|ampm`true",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "2011-11-30 8:00 PM",
                        "option_value"      => "datetime:yy-mm-dd|h:mm TT|ampm`true",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "2011-11-30 20:00",
                        "option_value"      => "datetime:yy-mm-dd|hh:mm",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "2011-11-30 20:00:00",
                        "option_value"      => "datetime:yy-mm-dd|hh:mm:ss|showSecond`true",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "30. 08. 2011 20:00",
                        "option_value"      => "datetime:dd. mm. yy|hh:mm",
                        "is_new_sort_group" => "yes"
                    )
                )
            ),

            // Apply Timezone Offset
            array(
                "field_label"              => "{\$LANG.phrase_apply_timezone_offset}",
                "field_setting_identifier" => "apply_timezone_offset",
                "field_type"               => "radios",
                "field_orientation"        => "horizontal",
                "default_value_type"       => "static",
                "default_value"            => "no",
                "options" => array(
                    array(
                        "option_text"       => "{\$LANG.word_yes}",
                        "option_value"      => "yes",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_no}",
                        "option_value"      => "no",
                        "is_new_sort_group" => "yes"
                    )
                )
            ),

            // Field Comments
            array(
                "field_label"              => "{\$LANG.phrase_field_comments}",
                "field_setting_identifier" => "comments",
                "field_type"               => "textarea",
                "field_orientation"        => "na",
                "default_value_type"       => "static",
                "default_value"            => "",
                "options"                  => array()
            )
        );
    }

    private static function getViewField()
    {
        $content =<<< END
{strip}
{if \$VALUE}
  {assign var=tzo value=0}
  {if \$apply_timezone_offset == "yes"}
    {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}
  {/if}
  {if \$display_format == "yy-mm-dd" || !\$display_format}
    {\$VALUE|custom_format_date:\$tzo:"Y-m-d"}
  {elseif \$display_format == "dd/mm/yy"}
    {\$VALUE|custom_format_date:\$tzo:"d/m/Y"}
  {elseif \$display_format == "mm/dd/yy"}
    {\$VALUE|custom_format_date:\$tzo:"m/d/Y"}
  {elseif \$display_format == "M d, yy"}
    {\$VALUE|custom_format_date:\$tzo:"M j, Y"}
  {elseif \$display_format == "MM d, yy"}
    {\$VALUE|custom_format_date:\$tzo:"F j, Y"}
  {elseif \$display_format == "D M d, yy"}
    {\$VALUE|custom_format_date:\$tzo:"D M j, Y"}
  {elseif \$display_format == "DD, MM d, yy"}
    {\$VALUE|custom_format_date:\$tzo:"l M j, Y"}
  {elseif \$display_format == "dd. mm. yy"}
    {\$VALUE|custom_format_date:\$tzo:"d. m. Y"}
  {elseif \$display_format == "datetime:dd/mm/yy|h:mm TT|ampm`true"}
    {\$VALUE|custom_format_date:\$tzo:"d/m/Y g:i A"}
  {elseif \$display_format == "datetime:mm/dd/yy|h:mm TT|ampm`true"}
    {\$VALUE|custom_format_date:\$tzo:"m/d/Y g:i A"}
  {elseif \$display_format == "datetime:yy-mm-dd|h:mm TT|ampm`true"}
    {\$VALUE|custom_format_date:\$tzo:"Y-m-d g:i A"}
  {elseif \$display_format == "datetime:yy-mm-dd|hh:mm"}
    {\$VALUE|custom_format_date:\$tzo:"Y-m-d H:i"}
  {elseif \$display_format == "datetime:yy-mm-dd|hh:mm:ss|showSecond`true"}
    {\$VALUE|custom_format_date:\$tzo:"Y-m-d H:i:s"}
  {elseif \$display_format == "datetime:dd. mm. yy|hh:mm"}
    {\$VALUE|custom_format_date:\$tzo:"d. m. Y H:i"}
  {/if}
{/if}
{/strip}
END;
        return $content;
    }

    private static function getEditField()
    {
        $content =<<< END
{assign var=class value="cf_datepicker"}
{if \$display_format|strpos:"datetime" === 0}
  {assign var=class value="cf_datetimepicker"}
{/if}

{assign var="val" value=""}
{if \$VALUE}
  {assign var=tzo value=""}
  {if \$apply_timezone_offset == "yes"}
    {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}
  {/if}
  {if \$display_format == "yy-mm-dd"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"Y-m-d"}
  {elseif \$display_format == "dd/mm/yy"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"d/m/Y"}
  {elseif \$display_format == "mm/dd/yy"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"m/d/Y"}
  {elseif \$display_format == "M d, yy"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"M j, Y"}
  {elseif \$display_format == "MM d, yy"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"F j, Y"}
  {elseif \$display_format == "D M d, yy"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"D M j, Y"}
  {elseif \$display_format == "DD, MM d, yy"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"l M j, Y"}
  {elseif \$display_format == "dd. mm. yy"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"d. m. Y"}
  {elseif \$display_format == "datetime:dd/mm/yy|h:mm TT|ampm`true"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"d/m/Y g:i A"}
  {elseif \$display_format == "datetime:mm/dd/yy|h:mm TT|ampm`true"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"m/d/Y g:i A"}
  {elseif \$display_format == "datetime:yy-mm-dd|h:mm TT|ampm`true"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"Y-m-d g:i A"}
  {elseif \$display_format == "datetime:yy-mm-dd|hh:mm"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"Y-m-d H:i"}
  {elseif \$display_format == "datetime:yy-mm-dd|hh:mm:ss|showSecond`true"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"Y-m-d H:i:s"}
  {elseif \$display_format == "datetime:dd. mm. yy|hh:mm"}
    {assign var=val value=\$VALUE|custom_format_date:\$tzo:"d. m. Y H:i"}
  {/if}
{/if}

<div class="cf_date_group">
  <input type="input" name="{\$NAME}" id="{\$NAME}_id" class="cf_datefield {\$class}"
    value="{\$val}" /><img class="ui-datepicker-trigger" src="{\$g_root_url}/global/images/calendar.png" 
    id="{\$NAME}_icon_id" />
  <input type="hidden" id="{\$NAME}_format" value="{\$display_format}" />
  {if \$comments}
    <div class="cf_field_comments">{\$comments}</div>
  {/if}
</div>
END;
        return $content;
    }

    private static function getPhpProcessing()
    {
        $content =<<< END
\$field_name     = \$vars["field_info"]["field_name"];
\$date           = \$vars["data"][\$field_name];
\$display_format = \$vars["settings"]["display_format"];
\$atzo           = \$vars["settings"]["apply_timezone_offset"];
\$account_info   = isset(\$vars["account_info"]) ? \$vars["account_info"] : array();

if (empty(\$date)) {
  \$value = "";
} else { 
  if (strpos(\$display_format, "datetime:") === 0) {
    \$parts = explode(" ", \$date);
    switch (\$display_format) {
      case "datetime:dd/mm/yy|h:mm TT|ampm`true":
        \$date = substr(\$date, 3, 2) . "/" . substr(\$date, 0, 2) . "/" . substr(\$date, 6);
        break;
      case "datetime:dd. mm. yy|hh:mm":
        \$date = substr(\$date, 4, 2) . "/" . substr(\$date, 0, 2) . "/" . substr(\$date, 8, 4) . " " . substr(\$date, 14);
        break;
    }
  } else {
    if (\$display_format == "dd/mm/yy") {
      \$date = substr(\$date, 3, 2) . "/" . substr(\$date, 0, 2) . "/" . substr(\$date, 6);
    } else if (\$display_format == "dd. mm. yy") {
      \$parts = explode(" ", \$date);
      \$date = trim(\$parts[1], ".") . "/" . trim(\$parts[0], ".") . "/" . trim(\$parts[2], ".");
    }
  }
  \$time = strtotime(\$date);
  
  // lastly, if this field has a timezone offset being applied to it, do the
  // appropriate math on the date
  if (\$atzo == "yes" && !isset(\$account_info["timezone_offset"])) {
    \$seconds_offset = \$account_info["timezone_offset"] * 60 * 60;
    \$time += \$seconds_offset;
  }
  \$value = date("Y-m-d H:i:s", \$time);
}
END;
        return $content;
    }

    private static function getCSS()
    {
        $css =<<< END
.cf_datepicker {
  width: 160px;
}
.cf_datetimepicker {
  width: 160px;
}
.ui-datepicker-trigger {
  cursor: pointer;
}
END;
        return $css;
    }


    private static function getJS()
    {
        $js =<<< END
$(function() {
  // the datetimepicker has a bug that prevents the icon from appearing. So
  // instead, we add the image manually into the page and assign the open event
  // handler to the image
  var default_settings = {
    changeYear: true,
    changeMonth: true
  }
  $(".cf_datepicker").each(function() {
    var field_name = $(this).attr("name");
    var settings = default_settings;
    if ($("#" + field_name + "_id").length) {
      settings.dateFormat = $("#" + field_name + "_format").val();
    }
    $(this).datepicker(settings);
    $("#" + field_name + "_icon_id").bind("click", { field_id: "#" + field_name + "_id" }, function(e) {
      $.datepicker._showDatepicker($(e.data.field_id)[0]);
    });
  });
  
  $(".cf_datetimepicker").each(function() {
    var field_name = $(this).attr("name");
    var settings = default_settings;

    if ($("#" + field_name + "_id").length) {
      var settings_str = $("#" + field_name + "_format").val();
      settings_str = settings_str.replace(/datetime:/, "");
      var settings_list = settings_str.split("|");
      var settings = {};
      settings.dateFormat = settings_list[0];
      settings.timeFormat = settings_list[1];
      for (var i=2; i<settings_list.length; i++) {
        var parts = settings_list[i].split("`");
        if (parts[1] === "true") {
          parts[1] = true;
        }
        settings[parts[0]] = parts[1];
      }
    }
    
    $(this).datetimepicker(settings);
    $("#" + field_name + "_icon_id").bind("click", { 
      field_id: "#" + field_name + "_id"
    }, function(e) {
      $.datepicker._showDatepicker($(e.data.field_id)[0]);
    });
  });
});
END;
        return $js;
    }
}
