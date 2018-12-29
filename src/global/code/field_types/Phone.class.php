<?php


namespace FormTools\FieldTypes;


class Phone
{

    public static function get()
    {
        $phone_php_processing =<<< END
\$field_name = \$vars["field_info"]["field_name"];
\$joiner = "|";

\$count = 1;
\$parts = array();
while (isset(\$vars["data"]["{\$field_name}_\$count"])) {
  \$parts[] = \$vars["data"]["{\$field_name}_\$count"];
  \$count++;
}
\$value = implode("|", \$parts);
END;

        $phone_js =<<< END
var cf_phone = {};
cf_phone.check_required = function() {
  var errors = [];
  for (var i=0; i<rsv_custom_func_errors.length; i++) {
    if (rsv_custom_func_errors[i].func != "cf_phone.check_required") {
      continue;
    }
    var field_name = rsv_custom_func_errors[i].field;
    var fields = $("input[name^=\"" + field_name + "_\"]");
    fields.each(function() {
      if (!this.name.match(/_(\d+)$/)) {
        return;
      }
      var req_len = $(this).attr("maxlength");
      var actual_len = this.value.length;
      if (req_len != actual_len || this.value.match(/\D/)) {
        var el = document.edit_submission_form[field_name];
        errors.push([el, rsv_custom_func_errors[i].err]);
        return false;
      }
    });
  }
  if (errors.length) {
    return errors;
  }
  
  return true;
}
END;

        return array(
            "field_type" => array(
                "is_editable"                    => "no",
                "is_enabled"                     => "yes",
                "non_editable_info"              => "{\$LANG.text_non_deletable_fields}",
                "managed_by_module_id"           => null,
                "field_type_name"                => "{\$LANG.phrase_phone_number}",
                "field_type_identifier"          => "phone",
                "is_file_field"                  => "no",
                "is_date_field"                  => "no",
                "raw_field_type_map"             => "",
                "compatible_field_sizes"         => "small,medium",
                "view_field_rendering_type"      => "smarty",
                "view_field_php_function_source" => "core",
                "view_field_php_function"        => "FormTools\\FieldTypes::displayFieldTypePhoneNumber",
                "view_field_smarty_markup"       => "{view_phone_field}",
                "edit_field_smarty_markup"       => "{edit_phone_field}",
                "php_processing"                 => $phone_php_processing,
                "resources_css"                  => "",
                "resources_js"                   => $phone_js
            ),

            "settings" => array(

                // Phone Number Format
                array(
                    "field_label"              => "{\$LANG.phrase_phone_number_format}",
                    "field_setting_identifier" => "phone_number_format",
                    "field_type"               => "textbox",
                    "field_orientation"        => "na",
                    "default_value_type"       => "static",
                    "default_value"            => "(xxx) xxx-xxxx",
                    "options"                  => array()
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
            ),

            "validation" => array(
                array(
                    "rsv_rule"                 => "function",
                    "rule_label"               => "{\$LANG.word_required}",
                    "rsv_field_name"           => "",
                    "custom_function"          => "cf_phone.check_required",
                    "custom_function_required" => "yes",
                    "default_error_message"    => "{\$LANG.validation_default_phone_num_required}"
                )
            )
        );
    }

}
