<?php


namespace FormTools\FieldTypes;


class Password
{

    public static function get()
    {
        $password_edit_field =<<< END
<input type="password" name="{\$NAME}" value="{\$VALUE|escape}" class="cf_password" />
{if \$comments}
  <div class="cf_field_comments">{\$comments}</div>
{/if}
END;

        return array(
            "field_type" => array(
                "is_editable"                    => "no",
                "is_enabled"                     => "yes",
                "non_editable_info"              => "{\$LANG.text_non_deletable_fields}",
                "managed_by_module_id"           => null,
                "field_type_name"                => "{\$LANG.word_password}",
                "field_type_identifier"          => "password",
                "is_file_field"                  => "no",
                "is_date_field"                  => "no",
                "raw_field_type_map"             => "password",
                "compatible_field_sizes"         => "1char,2chars,tiny,small,medium",
                "view_field_rendering_type"      => "none",
                "view_field_php_function_source" => "core",
                "view_field_php_function"        => "",
                "view_field_smarty_markup"       => "",
                "edit_field_smarty_markup"       => $password_edit_field,
                "php_processing"                 => "",
                "resources_css"                  => "input.cf_password {\r\n  width: 120px;\r\n}",
                "resources_js"                   => ""
            ),

            "settings" => array(

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

}
