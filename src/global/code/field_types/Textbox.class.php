<?php


namespace FormTools\FieldTypes;


class Textbox
{

    public static function get() {

        $textbox_edit_field =<<< END
<input type="text" name="{\$NAME}" value="{\$VALUE|escape}" class="{\$size}{if \$highlight} {\$highlight}{/if}"
  {if \$maxlength}maxlength="{\$maxlength}"{/if} />
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
                "field_type_name"                => "{\$LANG.word_textbox}",
                "field_type_identifier"          => "textbox",
                "is_file_field"                  => "no",
                "is_date_field"                  => "no",
                "raw_field_type_map"             => "textbox",
                "compatible_field_sizes"         => "1char,2chars,tiny,small,medium,large,very_large",
                "view_field_rendering_type"      => "smarty",
                "view_field_php_function_source" => "core",
                "view_field_php_function"        => "",
                "view_field_smarty_markup"       => "{\$VALUE|htmlspecialchars}",
                "edit_field_smarty_markup"       => $textbox_edit_field,
                "php_processing"                 => "",
                "resources_css"                  => self::getCSS(),
                "resources_js"                   => ""
            ),
            "settings" => self::getSettings(),
            "validation" => self::getValidation()
        );
    }

    public static function getSettings () {
        return array(

            // Size
            array(
                "field_label"              => "{\$LANG.word_size}",
                "field_setting_identifier" => "size",
                "field_type"               => "select",
                "field_orientation"        => "na",
                "default_value_type"       => "static",
                "default_value"            => "cf_size_medium",
                "options" => array(
                    array(
                        "option_text"       => "{\$LANG.word_tiny}",
                        "option_value"      => "cf_size_tiny",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_small}",
                        "option_value"      => "cf_size_small",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_medium}",
                        "option_value"      => "cf_size_medium",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_large}",
                        "option_value"      => "cf_size_large",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.phrase_full_width}",
                        "option_value"      => "cf_size_full_width",
                        "is_new_sort_group" => "yes"
                    )
                )
            ),

            // Max Length
            array(
                "field_label"              => "{\$LANG.phrase_max_length}",
                "field_setting_identifier" => "maxlength",
                "field_type"               => "textbox",
                "field_orientation"        => "na",
                "default_value_type"       => "static",
                "default_value"            => "",
                "options"                  => array()
            ),

            // Highlight
            array(
                "field_label"              => "{\$LANG.word_highlight}",
                "field_setting_identifier" => "highlight",
                "field_type"               => "select",
                "field_orientation"        => "na",
                "default_value_type"       => "static",
                "default_value"            => "",
                "options" => array(
                    array(
                        "option_text"       => "{\$LANG.word_none}",
                        "option_value"      => "",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_red}",
                        "option_value"      => "cf_colour_red",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_orange}",
                        "option_value"      => "cf_colour_orange",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_yellow}",
                        "option_value"      => "cf_colour_yellow",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_green}",
                        "option_value"      => "cf_colour_green",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.word_blue}",
                        "option_value"      => "cf_colour_blue",
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


    public static function getValidation() {
        return array(
            array(
                "rsv_rule"                 => "required",
                "rule_label"               => "{\$LANG.word_required}",
                "rsv_field_name"           => "{\$field_name}",
                "custom_function"          => "",
                "custom_function_required" => "no",
                "default_error_message"    => "{\$LANG.validation_default_rule_required}"
            ),
            array(
                "rsv_rule"                 => "valid_email",
                "rule_label"               => "{\$LANG.phrase_valid_email}",
                "rsv_field_name"           => "{\$field_name}",
                "custom_function"          => "",
                "custom_function_required" => "no",
                "default_error_message"    => "{\$LANG.validation_default_rule_valid_email}"
            ),
            array(
                "rsv_rule"                 => "digits_only",
                "rule_label"               => "{\$LANG.phrase_numbers_only}",
                "rsv_field_name"           => "{\$field_name}",
                "custom_function"          => "",
                "custom_function_required" => "no",
                "default_error_message"    => "{\$LANG.validation_default_rule_numbers_only}"
            ),
            array(
                "rsv_rule"                 => "letters_only",
                "rule_label"               => "{\$LANG.phrase_letters_only}",
                "rsv_field_name"           => "{\$field_name}",
                "custom_function"          => "",
                "custom_function_required" => "no",
                "default_error_message"    => "{\$LANG.validation_default_rule_letters_only}"
            ),
            array(
                "rsv_rule"                 => "is_alpha",
                "rule_label"               => "{\$LANG.phrase_alphanumeric}",
                "rsv_field_name"           => "{\$field_name}",
                "custom_function"          => "",
                "custom_function_required" => "no",
                "default_error_message"    => "{\$LANG.validation_default_rule_alpha}"
            )
        );
    }

    public static function getCSS() {
        $css =<<< END
input.cf_size_tiny {
    width: 30px;
}
input.cf_size_small {
    width: 80px;
}
input.cf_size_medium {
    width: 150px;
}
input.cf_size_large {
    width: 250px;
}
input.cf_size_full_width {
    width: 99%;
}
END;
        return $css;
    }

}
