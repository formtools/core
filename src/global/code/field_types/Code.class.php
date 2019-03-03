<?php


namespace FormTools\FieldTypes;


class Code
{
    public static function get()
    {
        return array(
            "field_type" => array(
                "is_editable"                    => "no",
                "is_enabled"                     => "yes",
                "non_editable_info"              => "{\$LANG.text_non_deletable_fields}",
                "managed_by_module_id"           => null,
                "field_type_name"                => "{\$LANG.phrase_code_markup_field}",
                "field_type_identifier"          => "code_markup",
                "is_file_field"                  => "no",
                "is_date_field"                  => "no",
                "raw_field_type_map"             => "textarea",
                "compatible_field_sizes"         => "large,very_large",
                "view_field_rendering_type"      => "php",
                "view_field_php_function_source" => "core",
                "view_field_php_function"        => "FormTools\\FieldTypes::displayFieldTypeCodeMarkup",
                "view_field_smarty_markup"       => self::getViewField(),
                "edit_field_smarty_markup"       => self::getEditField(),
                "php_processing"                 => "",
                "resources_css"                  => self::getCSS(),
                "resources_js"                   => self::getJS()
            ),

            "settings" => self::getSettings(),
            "validation" => self::getValidation()
        );

    }

    private static function getViewField()
    {
        $content =<<< END
{if \$CONTEXTPAGE == "edit_submission"}
<div class="cf_code_view">
    <textarea id="{\$NAME}_id" name="{\$NAME}">kay{\$VALUE}</textarea>
    <script>
    var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(document.getElementById("{\$NAME}_id"), 
    {literal}{{/literal} height: "{\$height}px",
        readOnly: true,
    {if \$code_markup == "HTML" || \$code_markup == "XML"}
        mode: "xml"
    {elseif \$code_markup == "CSS"}
        mode: "css"
    {elseif \$code_markup == "JavaScript"}
        mode: "javascript"
    {/if}
    {literal}});{/literal}
    </script>
</div>

{else}
  {\$VALUE|strip_tags}
{/if}
END;
        return $content;
    }

    private static function getEditField()
    {
        $content =<<< END
<div class="editor">
  <textarea id="{\$NAME}_id" name="{\$NAME}">{\$VALUE}</textarea>
</div>

<script>
var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(document.getElementById("{\$NAME}_id"), {literal}{{/literal}
  height: "{\$height}px",
  {if \$code_markup == "HTML" || \$code_markup == "XML"} 
    mode: "xml"
  {elseif \$code_markup == "CSS"}
    mode: "css"
  {elseif \$code_markup == "JavaScript"}
    mode: "javascript"
  {/if}
  {literal}});{/literal}
</script>

{if \$comments}
  <div class="cf_field_comments">{\$comments}</div>
{/if}
END;
        return $content;
    }

    private static function getJS()
    {
        $js =<<< END
var cf_code = {};
cf_code.check_required = function() {
  var errors = [];
  for (var i=0; i<rsv_custom_func_errors.length; i++) {
    if (rsv_custom_func_errors[i].func != "cf_code.check_required") {
      continue;
    }
    var field_name = rsv_custom_func_errors[i].field;
    var val = $.trim(window["code_mirror_" + field_name].getCode());
    if (!val) {
      var el = document.edit_submission_form[field_name];
      errors.push([el, rsv_custom_func_errors[i].err]);
    }
  }
  if (errors.length) {
    return errors;
  }
  return true;
}
END;
        return $js;
    }


    private static function getCSS()
    {
        $css =<<< END
.cf_view_markup_field {
    margin: 0px;
}
.cf_code_view .CodeMirror {
    background-color: transparent;
    height: auto;
}
.cf_code_view .CodeMirror-scroll {
    max-height: 300px;
}
.cf_code_view .CodeMirror-cursor { 
    display: none !important;
}
END;
        return $css;
    }


    private static function getSettings()
    {
        return array(

            // code/markup type
            array(
                "field_label"              => "{\$LANG.phrase_code_markup_type}",
                "field_setting_identifier" => "code_markup",
                "field_type"               => "select",
                "field_orientation"        => "na",
                "default_value_type"       => "static",
                "default_value"            => "HTML",

                "options" => array(
                    array(
                        "option_text"       => "CSS",
                        "option_value"      => "CSS",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "HTML",
                        "option_value"      => "HTML",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "JavaScript",
                        "option_value"      => "JavaScript",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "XML",
                        "option_value"      => "XML",
                        "is_new_sort_group" => "yes"
                    )
                )
            ),

            // Height
            array(
                "field_label"              => "{\$LANG.word_height}",
                "field_setting_identifier" => "height",
                "field_type"               => "select",
                "field_orientation"        => "na",
                "default_value_type"       => "static",
                "default_value"            => "200",

                "options" => array(
                    array(
                        "option_text"       => "{\$LANG.phrase_tiny_50px}",
                        "option_value"      => "50",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.phrase_small_100px}",
                        "option_value"      => "100",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.phrase_medium_200px}",
                        "option_value"      => "200",
                        "is_new_sort_group" => "yes"
                    ),
                    array(
                        "option_text"       => "{\$LANG.phrase_large_400px}",
                        "option_value"      => "400",
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

    private static function getValidation()
    {
        return array(
            array(
                "rsv_rule"                 => "function",
                "rule_label"               => "{\$LANG.word_required}",
                "rsv_field_name"           => "",
                "custom_function"          => "cf_code.check_required",
                "custom_function_required" => "yes",
                "default_error_message"    => "{\$LANG.validation_default_rule_required}"
            )
        );
    }
}
