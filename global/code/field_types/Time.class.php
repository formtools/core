<?php


namespace FormTools\FieldTypes;


class Time
{
	public static function get()
	{
		$time_edit_field = <<< END
<div class="cf_date_group">
  <input type="input" name="{\$NAME}" value="{\$VALUE}" class="cf_datefield cf_timepicker" />
  <input type="hidden" id="{\$NAME}_id" value="{\$display_format}" />
  {if \$comments}
    <div class="cf_field_comments">{\$comments}</div>
  {/if}
</div>
END;

		$time_css = <<< END
.cf_timepicker {
  width: 60px;
}
.ui-timepicker-div .ui-widget-header {
  margin-bottom: 8px;
}
.ui-timepicker-div dl {
  text-align: left;
}
.ui-timepicker-div dl dt {
  height: 25px;
}
.ui-timepicker-div dl dd {
  margin: -25px 0 10px 65px;
}
.ui-timepicker-div td {
  font-size: 90%;
}
END;

		$time_js = <<< END
$(function() {
  var default_settings = {
    buttonImage:     g.root_url + "/global/images/clock.png",
    showOn:          "both",
    buttonImageOnly: true
  }
  $(".cf_timepicker").each(function() {
    var field_name = $(this).attr("name");
    var settings = default_settings;
    if ($("#" + field_name + "_id").length) {
      var settings_list = $("#" + field_name + "_id").val().split("|");
      if (settings_list.length > 0) {
        settings.timeFormat = settings_list[0];
        for (var i=1; i<settings_list.length; i++) {
          var parts = settings_list[i].split("`");
          if (parts[1] === "true") {
            parts[1] = true;
          } else if (parts[1] === "false") {
            parts[1] = false;
          }
          settings[parts[0]] = parts[1];
        }
      }
    }
    $(this).timepicker(settings);
  });
});
END;


		return array(
			"field_type" => array(
				"is_editable" => "no",
				"is_enabled" => "yes",
				"non_editable_info" => "{\$LANG.text_non_deletable_fields}",
				"managed_by_module_id" => null,
				"field_type_name" => "{\$LANG.word_time}",
				"field_type_identifier" => "time",
				"is_file_field" => "no",
				"is_date_field" => "no",
				"raw_field_type_map" => "",
				"compatible_field_sizes" => "small",
				"view_field_rendering_type" => "none",
				"view_field_php_function_source" => "core",
				"view_field_php_function" => "",
				"view_field_smarty_markup" => "",
				"edit_field_smarty_markup" => $time_edit_field,
				"php_processing" => "",
				"resources_css" => $time_css,
				"resources_js" => $time_js
			),

			"settings" => array(

				// Custom Display Format
				array(
					"field_label" => "{\$LANG.phrase_custom_display_format}",
					"field_setting_identifier" => "display_format",
					"field_type" => "select",
					"field_orientation" => "na",
					"default_value_type" => "static",
					"default_value" => "h:mm TT|ampm`true",

					"options" => array(
						array(
							"option_text" => "8:00 AM",
							"option_value" => "h:mm TT|ampm`true",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "16:00",
							"option_value" => "hh:mm|ampm`false",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "16:00:00",
							"option_value" => "hh:mm:ss|showSecond`true|ampm`false",
							"is_new_sort_group" => "yes"
						),
					)
				),

				// Field Comments
				array(
					"field_label" => "{\$LANG.phrase_field_comments}",
					"field_setting_identifier" => "comments",
					"field_type" => "textarea",
					"field_orientation" => "na",
					"default_value_type" => "static",
					"default_value" => "",
					"options" => array()
				)
			),

			"validation" => array(
				array(
					"rsv_rule" => "required",
					"rule_label" => "{\$LANG.word_required}",
					"rsv_field_name" => "{\$field_name}",
					"custom_function" => "",
					"custom_function_required" => "na",
					"default_error_message" => "{\$LANG.validation_default_rule_required}"
				)
			)
		);

	}

}
