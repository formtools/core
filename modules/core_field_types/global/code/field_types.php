<?php

/**
 * This file contains functions that return data structures of all the Core field types info - settings, validation
 * etc, for use by the module's installation and upgrade functions.
 */


// ------------------------------------------------------------------------------------------------

function cft_get_field_types()
{
  $cft_field_types = array();
  $cft_field_types["textbox"] = array(

    "field_type" => array(
      "is_editable"                    => "no",
      "non_editable_info"              => "'{\$LANG.text_non_deletable_fields}'",
      "managed_by_module_id"           => "NULL",
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
      "edit_field_smarty_markup"       => "<input type=\"text\" name=\"{\$NAME}\" value=\"{\$VALUE|escape}\" \r\n  class=\"{\$size}{if \$highlight} {\$highlight}{/if}\" \r\n  {if \$maxlength}maxlength=\"{\$maxlength}\"{/if} />\r\n \r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}",
      "php_processing"                 => "",
      "resources_css"                  => "input.cf_size_tiny {\r\n  width: 30px; \r\n}\r\ninput.cf_size_small {\r\n  width: 80px; \r\n}\r\ninput.cf_size_medium {\r\n  width: 150px; \r\n}\r\ninput.cf_size_large {\r\n  width: 250px;\r\n}\r\ninput.cf_size_full_width {\r\n  width: 99%; \r\n}",
      "resources_js"                   => ""
    ),

    "settings" => array(

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
    ),

    "validation" => array(
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
    )
  );


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["textarea"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.word_textarea}",
      "field_type_identifier"          => "textarea",
      "is_file_field"                  => "no",
      "is_date_field"                  => "no",
      "raw_field_type_map"             => "textarea",
      "compatible_field_sizes"         => "medium,large,very_large",
      "view_field_rendering_type"      => "smarty",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "",
      "view_field_smarty_markup"       => "{if \$CONTEXTPAGE == \"edit_submission\"}  \r\n  {\$VALUE|nl2br}\r\n{else}\r\n  {\$VALUE}\r\n{/if}",
      "edit_field_smarty_markup"       => "{* figure out all the classes *}\r\n{assign var=classes value=\$height}\r\n{if \$highlight_colour}\r\n  {assign var=classes value=\"`\$classes` `\$highlight_colour`\"}\r\n{/if}\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_wordcounter max`\$maxlength`\"}\r\n{elseif \$input_length == \"chars\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_textcounter max`\$maxlength`\"}\r\n{/if}\r\n\r\n<textarea name=\"{\$NAME}\" id=\"{\$NAME}_id\" class=\"{\$classes}\">{\$VALUE}</textarea>\r\n\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} {\$LANG.phrase_word_limit_p} <span></span> {\$LANG.phrase_remaining_words}\r\n  </div>\r\n{elseif \$input_length == \"chars\" && \$maxlength != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} {\$LANG.phrase_characters_limit_p} <span></span> {\$LANG.phrase_remaining_characters}\r\n  </div>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments|nl2br}</div>\r\n{/if}",
      "php_processing"                 => "",
      "resources_css"                  => ".cf_counter span {\r\n  font-weight: bold; \r\n}\r\ntextarea {\r\n  width: 99%;\r\n}\r\ntextarea.cf_size_tiny {\r\n  height: 30px;\r\n}\r\ntextarea.cf_size_small {\r\n  height: 80px;  \r\n}\r\ntextarea.cf_size_medium {\r\n  height: 150px;  \r\n}\r\ntextarea.cf_size_large {\r\n  height: 300px;\r\n}",
      "resources_js"                   => "/**\r\n * The following code provides a simple text/word counter option for any  \r\n * textarea. It either just keeps counting up, or limits the results to a\r\n * certain number - all depending on what the user has selected via the\r\n * field type settings.\r\n */\r\nvar cf_counter = {};\r\ncf_counter.get_max_count = function(el) {\r\n  var classes = \$(el).attr(''class'').split(\" \").slice(-1);\r\n  var max = null;\r\n  for (var i=0; i<classes.length; i++) {\r\n    var result = classes[i].match(/max(\\\\d+)/);\r\n    if (result != null) {\r\n      max = result[1];\r\n      break;\r\n    }\r\n  }\r\n  return max;\r\n}\r\n\r\n\$(function() {\r\n  \$(\"textarea[class~=''cf_wordcounter'']\").each(function() {\r\n    var max = cf_counter.get_max_count(this);\r\n    if (max == null) {\r\n      return;\r\n    }\r\n    \$(this).bind(\"keydown\", function() {\r\n      var val = \$(this).val();\r\n      var len        = val.split(/[\\\\s]+/);\r\n      var field_name = \$(this).attr(\"name\");\r\n      var num_words  = len.length - 1;\r\n      if (num_words > max) {\r\n        var allowed_words = val.split(/[\\\\s]+/, max);\r\n        truncated_str = allowed_words.join(\" \");\r\n        \$(this).val(truncated_str);\r\n      } else {\r\n        \$(\"#\" + field_name + \"_counter\").find(\"span\").html(parseInt(max) - parseInt(num_words));\r\n      }\r\n    });     \r\n    \$(this).trigger(\"keydown\");\r\n  });\r\n\r\n  \$(\"textarea[class~=''cf_textcounter'']\").each(function() {\r\n    var max = cf_counter.get_max_count(this);\r\n    if (max == null) {\r\n      return;\r\n    }\r\n    \$(this).bind(\"keydown\", function() {    \r\n      var field_name = \$(this).attr(\"name\");      \r\n      if (this.value.length > max) {\r\n        this.value = this.value.substring(0, max);\r\n      } else {\r\n        \$(\"#\" + field_name + \"_counter\").find(\"span\").html(max - this.value.length);\r\n      }\r\n    });\r\n    \$(this).trigger(\"keydown\");\r\n  });});"
    ),

    "settings" => array(

      // Height
      array(
        "field_label"              => "{\$LANG.word_height}",
        "field_setting_identifier" => "height",
        "field_type"               => "select",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "cf_size_small",

        "options" => array(
          array(
            "option_text"       => "{\$LANG.phrase_tiny_30px}",
            "option_value"      => "cf_size_tiny",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_small_80px}",
            "option_value"      => "cf_size_small",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_medium_150px}",
            "option_value"      => "cf_size_medium",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_large_300px}",
            "option_value"      => "cf_size_large",
            "is_new_sort_group" => "yes"
          )
        )
      ),

      // Highlight
      array(
        "field_label"              => "{\$LANG.phrase_highlight_colour}",
        "field_setting_identifier" => "highlight_colour",
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

      // Input Length
      array(
        "field_label"              => "{\$LANG.phrase_input_length}",
        "field_setting_identifier" => "input_length",
        "field_type"               => "radios",
        "field_orientation"        => "horizontal",
        "default_value_type"       => "static",
        "default_value"            => "",

        "options" => array(
          array(
            "option_text"       => "{\$LANG.phrase_no_limit}",
            "option_value"      => "",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.word_words}",
            "option_value"      => "words",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.word_characters}",
            "option_value"      => "chars",
            "is_new_sort_group" => "yes"
          )
        )
      ),

      // - Max length (words / chars)
      array(
        "field_label"              => "{\$LANG.phrase_max_length_words_chars}",
        "field_setting_identifier" => "maxlength",
        "field_type"               => "textbox",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "",
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
        "rsv_rule"                 => "required",
        "rule_label"               => "{\$LANG.word_required}",
        "rsv_field_name"           => "{\$field_name}",
        "custom_function"          => "",
        "custom_function_required" => "na",
        "default_error_message"    => "{\$LANG.validation_default_rule_required}"
      )
    )
  );


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["password"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
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
      "edit_field_smarty_markup"       => "<input type=\"password\" name=\"{\$NAME}\" value=\"{\$VALUE|escape}\" \r\n  class=\"cf_password\" />\r\n \r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}",
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


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["dropdown"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.word_dropdown}",
      "field_type_identifier"          => "dropdown",
      "is_file_field"                  => "no",
      "is_date_field"                  => "no",
      "raw_field_type_map"             => "select",
      "compatible_field_sizes"         => "1char,2chars,tiny,small,medium,large",
      "view_field_rendering_type"      => "php",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "ft_display_field_type_dropdown",
      "view_field_smarty_markup"       => "{strip}{if \$contents != \"\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$VALUE == \$option.option_value}{\$option.option_name}{/if}\r\n    {/foreach}\r\n  {/foreach}\r\n{/if}{/strip}",
      "edit_field_smarty_markup"       => "{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">\r\n    {\$LANG.phrase_not_assigned_to_option_list} \r\n  </div>\r\n{else}\r\n  <select name=\"{\$NAME}\">\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {if \$group_info.group_name}\r\n      <optgroup label=\"{\$group_info.group_name|escape}\">\r\n    {/if}\r\n    {foreach from=\$options item=option name=row}\r\n      <option value=\"{\$option.option_value}\"\r\n        {if \$VALUE == \$option.option_value}selected{/if}>{\$option.option_name}</option>\r\n    {/foreach}\r\n    {if \$group_info.group_name}\r\n      </optgroup>\r\n    {/if}\r\n  {/foreach}\r\n  </select>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}",
      "php_processing"                 => "",
      "resources_css"                  => "",
      "resources_js"                   => ""
    ),

    "settings" => array(

      // Option List / Contents
      array(
        "use_for_option_list_map"  => true,
        "field_label"              => "{\$LANG.phrase_option_list_or_contents}",
        "field_setting_identifier" => "contents",
        "field_type"               => "option_list_or_form_field",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "",
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
        "rsv_rule"                 => "required",
        "rule_label"               => "{\$LANG.word_required}",
        "rsv_field_name"           => "{\$field_name}",
        "custom_function"          => "",
        "custom_function_required" => "na",
        "default_error_message"    => "{\$LANG.validation_default_rule_required}"
      )
    )
  );


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["multi_select_dropdown"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.phrase_multi_select_dropdown}",
      "field_type_identifier"          => "multi_select_dropdown",
      "is_file_field"                  => "no",
      "is_date_field"                  => "no",
      "raw_field_type_map"             => "multi-select",
      "compatible_field_sizes"         => "1char,2chars,tiny,small,medium,large",
      "view_field_rendering_type"      => "php",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "ft_display_field_type_multi_select_dropdown",
      "view_field_smarty_markup"       => "{if \$contents != \"\"}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_first value=true}\r\n  {strip}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$option.option_value|in_array:\$vals}\r\n        {if \$is_first == false}, {/if}\r\n        {\$option.option_name}\r\n        {assign var=is_first value=false}\r\n      {/if}\r\n    {/foreach}\r\n  {/foreach}\r\n  {/strip}\r\n{/if}",
      "edit_field_smarty_markup"       => "{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  <select name=\"{\$NAME}[]\" multiple size=\"{if \$num_rows}{\$num_rows}{else}5{/if}\">\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {if \$group_info.group_name}\r\n      <optgroup label=\"{\$group_info.group_name|escape}\">\r\n    {/if}\r\n    {foreach from=\$options item=option name=row}\r\n      <option value=\"{\$option.option_value}\"\r\n        {if \$option.option_value|in_array:\$vals}selected{/if}>{\$option.option_name}</option>\r\n    {/foreach}\r\n    {if \$group_info.group_name}\r\n      </optgroup>\r\n    {/if}\r\n  {/foreach}\r\n  </select>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}",
      "php_processing"                 => "",
      "resources_css"                  => "",
      "resources_js"                   => ""
    ),

    "settings" => array(

      // Option List / Contents
      array(
        "use_for_option_list_map"  => true,
        "field_label"              => "{\$LANG.phrase_option_list_or_contents}",
        "field_setting_identifier" => "contents",
        "field_type"               => "option_list_or_form_field",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "",
        "options"                  => array()
      ),

      // Num Rows
      array(
        "field_label"              => "{\$LANG.phrase_num_rows}",
        "field_setting_identifier" => "num_rows",
        "field_type"               => "textbox",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "5",
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
        "rsv_rule"                 => "required",
        "rule_label"               => "{\$LANG.word_required}",
        "rsv_field_name"           => "{\$field_name}[]",
        "custom_function"          => "",
        "custom_function_required" => "na",
        "default_error_message"    => "{\$LANG.validation_default_rule_required}"
      )
    )
  );


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["radio_buttons"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.phrase_radio_buttons}",
      "field_type_identifier"          => "radio_buttons",
      "is_file_field"                  => "no",
      "is_date_field"                  => "no",
      "raw_field_type_map"             => "radio-buttons",
      "compatible_field_sizes"         => "1char,2chars,tiny,small,medium,large",
      "view_field_rendering_type"      => "php",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "ft_display_field_type_radios",
      "view_field_smarty_markup"       => "{strip}{if \$contents != \"\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$VALUE == \$option.option_value}{\$option.option_name}{/if}\r\n    {/foreach}\r\n  {/foreach}\r\n{/if}{/strip}",
      "edit_field_smarty_markup"       => "{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=is_in_columns value=false}\r\n  {if \$formatting == \"cf_option_list_2cols\" || \r\n      \$formatting == \"cf_option_list_3cols\" || \r\n      \$formatting == \"cf_option_list_4cols\"}\r\n    {assign var=is_in_columns value=true}\r\n  {/if}\r\n\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n\r\n    {if \$group_info.group_name}\r\n      <div class=\"cf_option_list_group_label\">{\$group_info.group_name}</div>\r\n    {/if}\r\n\r\n    {if \$is_in_columns}<div class=\"{\$formatting}\">{/if}\r\n\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$is_in_columns}<div class=\"column\">{/if}\r\n        <input type=\"radio\" name=\"{\$NAME}\" id=\"{\$NAME}_{\$counter}\" \r\n          value=\"{\$option.option_value}\"\r\n          {if \$VALUE == \$option.option_value}checked{/if} />\r\n          <label for=\"{\$NAME}_{\$counter}\">{\$option.option_name}</label>\r\n      {if \$is_in_columns}</div>{/if}\r\n      {if \$formatting == \"vertical\"}<br />{/if}\r\n      {assign var=counter value=\$counter+1}\r\n    {/foreach}\r\n\r\n    {if \$is_in_columns}</div>{/if}\r\n  {/foreach}\r\n\r\n  {if \$comments}<div class=\"cf_field_comments\">{\$comments}</div>{/if}\r\n{/if}",
      "php_processing"                 => "",
      "resources_css"                  => "/* All CSS styles for this field type are found in Shared Resources */",
      "resources_js"                   => ""
    ),

    "settings" => array(

      // Option List / Contents
      array(
        "use_for_option_list_map"  => true,
        "field_label"              => "{\$LANG.phrase_option_list_or_contents}",
        "field_setting_identifier" => "contents",
        "field_type"               => "option_list_or_form_field",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "",
        "options"                  => array()
      ),

      // Formatting
      array(
        "field_label"              => "{\$LANG.word_formatting}",
        "field_setting_identifier" => "formatting",
        "field_type"               => "select",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "horizontal",

        "options" => array(
          array(
            "option_text"       => "{\$LANG.word_horizontal}",
            "option_value"      => "horizontal",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.word_vertical}",
            "option_value"      => "vertical",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_2_columns}",
            "option_value"      => "cf_option_list_2cols",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_3_columns}",
            "option_value"      => "cf_option_list_3cols",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_4_columns}",
            "option_value"      => "cf_option_list_4cols",
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


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["checkboxes"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.word_checkboxes}",
      "field_type_identifier"          => "checkboxes",
      "is_file_field"                  => "no",
      "is_date_field"                  => "no",
      "raw_field_type_map"             => "checkboxes",
      "compatible_field_sizes"         => "1char,2chars,tiny,small,medium,large",
      "view_field_rendering_type"      => "php",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "ft_display_field_type_checkboxes",
      "view_field_smarty_markup"       => "{strip}{if \$contents != \"\"}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_first value=true}\r\n  {strip}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$option.option_value|in_array:\$vals}\r\n        {if \$is_first == false}, {/if}\r\n        {\$option.option_name}\r\n        {assign var=is_first value=false}\r\n      {/if}\r\n    {/foreach}\r\n  {/foreach}\r\n  {/strip}\r\n{/if}{/strip}",
      "edit_field_smarty_markup"       => "{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_in_columns value=false}\r\n  {if \$formatting == \"cf_option_list_2cols\" || \r\n      \$formatting == \"cf_option_list_3cols\" || \r\n      \$formatting == \"cf_option_list_4cols\"}\r\n    {assign var=is_in_columns value=true}\r\n  {/if}\r\n\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n\r\n    {if \$group_info.group_name}\r\n      <div class=\"cf_option_list_group_label\">{\$group_info.group_name}</div>\r\n    {/if}\r\n\r\n    {if \$is_in_columns}<div class=\"{\$formatting}\">{/if}\r\n\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$is_in_columns}<div class=\"column\">{/if}\r\n        <input type=\"checkbox\" name=\"{\$NAME}[]\" id=\"{\$NAME}_{\$counter}\" \r\n          value=\"{\$option.option_value|escape}\" \r\n          {if \$option.option_value|in_array:\$vals}checked{/if} />\r\n          <label for=\"{\$NAME}_{\$counter}\">{\$option.option_name}</label>\r\n      {if \$is_in_columns}</div>{/if}\r\n      {if \$formatting == \"vertical\"}<br />{/if}\r\n      {assign var=counter value=\$counter+1}\r\n    {/foreach}\r\n\r\n    {if \$is_in_columns}</div>{/if}\r\n  {/foreach}\r\n\r\n  {if {\$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div> \r\n  {/if}\r\n{/if}",
      "php_processing"                 => "",
      "resources_css"                  => "/* all CSS is found in Shared Resources */",
      "resources_js"                   => ""
    ),

    "settings" => array(

      // Option List / Contents
      array(
        "use_for_option_list_map"  => true,
        "field_label"              => "{\$LANG.phrase_option_list_or_contents}",
        "field_setting_identifier" => "contents",
        "field_type"               => "option_list_or_form_field",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "",
        "options"                  => array()
      ),

      // Formatting
      array(
        "field_label"              => "{\$LANG.word_formatting}",
        "field_setting_identifier" => "formatting",
        "field_type"               => "select",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "horizontal",

        "options" => array(
          array(
            "option_text"       => "{\$LANG.word_horizontal}",
            "option_value"      => "horizontal",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.word_vertical}",
            "option_value"      => "vertical",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_2_columns}",
            "option_value"      => "cf_option_list_2cols",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_3_columns}",
            "option_value"      => "cf_option_list_3cols",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "{\$LANG.phrase_4_columns}",
            "option_value"      => "cf_option_list_4cols",
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
    ),

    "validation" => array(
      array(
        "rsv_rule"                 => "required",
        "rule_label"               => "{\$LANG.word_required}",
        "rsv_field_name"           => "{\$field_name}[]",
        "custom_function"          => "",
        "custom_function_required" => "na",
        "default_error_message"    => "{\$LANG.validation_default_rule_required}"
      )
    )
  );


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["date"] = array(

    "field_type" => array(
      "is_editable"                    => "no",
      "non_editable_info"              => "'{\$LANG.text_non_deletable_fields}'",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.word_date}",
      "field_type_identifier"          => "date",
      "is_file_field"                  => "no",
      "is_date_field"                  => "yes",
      "raw_field_type_map"             => "",
      "compatible_field_sizes"         => "small",
      "view_field_rendering_type"      => "php",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "ft_display_field_type_date",
      "view_field_smarty_markup"       => "{strip}\r\n  {if \$VALUE}\r\n    {assign var=tzo value=\"\"}\r\n    {if \$apply_timezone_offset == \"yes\"}\r\n      {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}\r\n    {/if}\r\n    {if \$display_format == \"yy-mm-dd\" || !\$display_format}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d\"}\r\n    {elseif \$display_format == \"dd/mm/yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d/m/Y\"}\r\n    {elseif \$display_format == \"mm/dd/yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"m/d/Y\"}\r\n    {elseif \$display_format == \"M d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"M j, Y\"}\r\n    {elseif \$display_format == \"MM d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"F j, Y\"}\r\n    {elseif \$display_format == \"D M d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"D M j, Y\"}\r\n    {elseif \$display_format == \"DD, MM d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"l M j, Y\"}\r\n    {elseif \$display_format == \"dd. mm. yy.\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d. m. Y.\"}\r\n    {elseif \$display_format == \"datetime:dd/mm/yy|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d/m/Y g:i A\"}\r\n    {elseif \$display_format == \"datetime:mm/dd/yy|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"m/d/Y g:i A\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d g:i A\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm:ss|showSecond`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i:s\"}\r\n    {elseif \$display_format == \"datetime:dd. mm. yy.|hh:mm\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d. m. Y. H:i\"}\r\n    {/if}\r\n{/if}{/strip}",
      "edit_field_smarty_markup"       => "{assign var=class value=\"cf_datepicker\"}\r\n{if \$display_format|strpos:\"datetime\" === 0}\r\n  {assign var=class value=\"cf_datetimepicker\"}\r\n{/if}\r\n\r\n{assign var=\"val\" value=\"\"}\r\n{if \$VALUE}\r\n  {assign var=tzo value=\"\"}\r\n  {if \$apply_timezone_offset == \"yes\"}\r\n    {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}\r\n  {/if}\r\n  {if \$display_format == \"yy-mm-dd\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d\"}\r\n  {elseif \$display_format == \"dd/mm/yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d/m/Y\"}\r\n  {elseif \$display_format == \"mm/dd/yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"m/d/Y\"}\r\n  {elseif \$display_format == \"M d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"M j, Y\"}\r\n  {elseif \$display_format == \"MM d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"F j, Y\"}\r\n  {elseif \$display_format == \"D M d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"D M j, Y\"}\r\n  {elseif \$display_format == \"DD, MM d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"l M j, Y\"}\r\n  {elseif \$display_format == \"dd. mm. yy.\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d. m. Y.\"}\r\n  {elseif \$display_format == \"datetime:dd/mm/yy|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d/m/Y g:i A\"}\r\n  {elseif \$display_format == \"datetime:mm/dd/yy|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"m/d/Y g:i A\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d g:i A\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm:ss|showSecond`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i:s\"}\r\n  {elseif \$display_format == \"datetime:dd. mm. yy.|hh:mm\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d. m. Y. H:i\"}\r\n  {/if}\r\n{/if}\r\n\r\n<div class=\"cf_date_group\">\r\n  <input type=\"input\" name=\"{\$NAME}\" id=\"{\$NAME}_id\" \r\n    class=\"cf_datefield {\$class}\" value=\"{\$val}\" /><img class=\"ui-datepicker-trigger\" src=\"{\$g_root_url}/global/images/calendar.png\" id=\"{\$NAME}_icon_id\" />\r\n  <input type=\"hidden\" id=\"{\$NAME}_format\" value=\"{\$display_format}\" />\r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>",
      "php_processing"                 => "\$field_name     = \$vars[\"field_info\"][\"field_name\"];\r\n\$date           = \$vars[\"data\"][\$field_name];\r\n\$display_format = \$vars[\"settings\"][\"display_format\"];\r\n\$atzo           = \$vars[\"settings\"][\"apply_timezone_offset\"];\r\n\$account_info   = isset(\$vars[\"account_info\"]) ? \$vars[\"account_info\"] : array();\r\n\r\nif (empty(\$date))\r\n{\r\n  \$value = \"\";\r\n}\r\nelse\r\n{\r\n  if (strpos(\$display_format, \"datetime:\") === 0)\r\n  {\r\n    \$parts = explode(\" \", \$date);\r\n    switch (\$display_format)\r\n    {\r\n      case \"datetime:dd/mm/yy|h:mm TT|ampm`true\":\r\n        \$date = substr(\$date, 3, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n          substr(\$date, 6);\r\n        break;\r\n      case \"datetime:dd. mm. yy.|hh:mm\":\r\n        \$date = substr(\$date, 4, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n          substr(\$date, 8, 4) . \" \" . substr(\$date, 14);\r\n        break;\r\n    }\r\n  }\r\n  else\r\n  {\r\n    if (\$display_format == \"dd/mm/yy\")\r\n    {\r\n      \$date = substr(\$date, 3, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n        substr(\$date, 6);\r\n    } \r\n    else if (\$display_format == \"dd. mm. yy.\")\r\n    {\r\n      \$parts = explode(\" \", \$date);\r\n      \$date = trim(\$parts[1], \".\") . \"/\" . trim(\$parts[0], \".\") . \"/\" . trim(\$parts[2], \".\");\r\n    }\r\n  }\r\n\r\n  \$time = strtotime(\$date);\r\n  \r\n  // lastly, if this field has a timezone offset being applied to it, do the\r\n  // appropriate math on the date\r\n  if (\$atzo == \"yes\" && !isset(\$account_info[\"timezone_offset\"]))\r\n  {\r\n    \$seconds_offset = \$account_info[\"timezone_offset\"] * 60 * 60;\r\n    \$time += \$seconds_offset;\r\n  }\r\n\r\n  \$value = date(\"Y-m-d H:i:s\", \$time);\r\n}",
      "resources_css"                  => ".cf_datepicker {\r\n  width: 160px; \r\n}\r\n.cf_datetimepicker {\r\n  width: 160px; \r\n}\r\n.ui-datepicker-trigger {\r\n  cursor: pointer; \r\n}",
      "resources_js"                   => "\$(function() {\r\n  // the datetimepicker has a bug that prevents the icon from appearing. So\r\n  // instead, we add the image manually into the page and assign the open event\r\n  // handler to the image\r\n  var default_settings = {\r\n    changeYear: true,\r\n    changeMonth: true   \r\n  }\r\n\r\n  \$(\".cf_datepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      settings.dateFormat = \$(\"#\" + field_name + \"_format\").val();\r\n    }\r\n    \$(this).datepicker(settings);\r\n    \$(\"#\" + field_name + \"_icon_id\").bind(\"click\",\r\n      { field_id: \"#\" + field_name + \"_id\" }, function(e) {      \r\n      \$.datepicker._showDatepicker(\$(e.data.field_id)[0]);\r\n    });\r\n  });\r\n    \r\n  \$(\".cf_datetimepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      var settings_str = \$(\"#\" + field_name + \"_format\").val();\r\n      settings_str = settings_str.replace(/datetime:/, \"\");\r\n      var settings_list = settings_str.split(\"|\");\r\n      var settings = {};\r\n      settings.dateFormat = settings_list[0];\r\n      settings.timeFormat = settings_list[1];      \r\n      for (var i=2; i<settings_list.length; i++) {\r\n        var parts = settings_list[i].split(\"`\");\r\n        if (parts[1] === \"true\") {\r\n          parts[1] = true;\r\n        }\r\n        settings[parts[0]] = parts[1];\r\n      }\r\n    }\r\n    \$(this).datetimepicker(settings);\r\n    \$(\"#\" + field_name + \"_icon_id\").bind(\"click\",\r\n      { field_id: \"#\" + field_name + \"_id\" }, function(e) {      \r\n      \$.datepicker._showDatepicker(\$(e.data.field_id)[0]);\r\n    });\r\n  });  \r\n});"
    ),

    "settings" => array(

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
            "option_text"       => "30. 08. 2011.",
            "option_value"      => "dd. mm. yy.",
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
            "option_text"       => "30. 08. 2011. 20:00",
            "option_value"      => "datetime:dd. mm. yy.|hh:mm",
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


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["time"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.word_time}",
      "field_type_identifier"          => "time",
      "is_file_field"                  => "no",
      "is_date_field"                  => "no",
      "raw_field_type_map"             => "",
      "compatible_field_sizes"         => "small",
      "view_field_rendering_type"      => "none",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "",
      "view_field_smarty_markup"       => "",
      "edit_field_smarty_markup"       => "<div class=\"cf_date_group\">\r\n  <input type=\"input\" name=\"{\$NAME}\" value=\"{\$VALUE}\" class=\"cf_datefield cf_timepicker\" />\r\n  <input type=\"hidden\" id=\"{\$NAME}_id\" value=\"{\$display_format}\" />\r\n  \r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>",
      "php_processing"                 => "",
      "resources_css"                  => ".cf_timepicker {\r\n  width: 60px; \r\n}\r\n.ui-timepicker-div .ui-widget-header{ margin-bottom: 8px; }\r\n.ui-timepicker-div dl{ text-align: left; }\r\n.ui-timepicker-div dl dt{ height: 25px; }\r\n.ui-timepicker-div dl dd{ margin: -25px 0 10px 65px; }\r\n.ui-timepicker-div td { font-size: 90%; }",
      "resources_js"                   => "\$(function() {  \r\n  var default_settings = {\r\n    buttonImage:     g.root_url + \"/global/images/clock.png\",      \r\n    showOn:          \"both\",\r\n    buttonImageOnly: true\r\n  }\r\n  \$(\".cf_timepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      var settings_list = \$(\"#\" + field_name + \"_id\").val().split(\"|\");      \r\n      if (settings_list.length > 0) {\r\n        settings.timeFormat = settings_list[0];\r\n        for (var i=1; i<settings_list.length; i++) {\r\n          var parts = settings_list[i].split(\"`\");\r\n          if (parts[1] === \"true\") {\r\n            parts[1] = true;\r\n          } else if (parts[1] === \"false\") {\r\n            parts[1] = false;\r\n          }\r\n          settings[parts[0]] = parts[1];\r\n        }\r\n      }\r\n    }\r\n    \$(this).timepicker(settings);\r\n  });\r\n});"
    ),

    "settings" => array(

      // Custom Display Format
      array(
        "field_label"              => "{\$LANG.phrase_custom_display_format}",
        "field_setting_identifier" => "display_format",
        "field_type"               => "select",
        "field_orientation"        => "na",
        "default_value_type"       => "static",
        "default_value"            => "h:mm TT|ampm`true",

        "options" => array(
          array(
            "option_text"       => "8:00 AM",
            "option_value"      => "h:mm TT|ampm`true",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "16:00",
            "option_value"      => "hh:mm|ampm`false",
            "is_new_sort_group" => "yes"
          ),
          array(
            "option_text"       => "16:00:00",
            "option_value"      => "hh:mm:ss|showSecond`true|ampm`false",
            "is_new_sort_group" => "yes"
          ),
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


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["phone"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.phrase_phone_number}",
      "field_type_identifier"          => "phone",
      "is_file_field"                  => "no",
      "is_date_field"                  => "no",
      "raw_field_type_map"             => "",
      "compatible_field_sizes"         => "small,medium",
      "view_field_rendering_type"      => "php",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "ft_display_field_type_phone_number",
      "view_field_smarty_markup"       => "{php}\r\n\$format = \$this->get_template_vars(\"phone_number_format\");\r\n\$values = explode(\"|\", \$this->get_template_vars(\"VALUE\"));\r\n\$pieces = preg_split(\"/(x+)/\", \$format, 0, PREG_SPLIT_DELIM_CAPTURE);\r\n\$counter = 1;\r\n\$output = \"\";\r\n\$has_content = false;\r\nforeach (\$pieces as \$piece)\r\n{\r\n  if (empty(\$piece))\r\n    continue;\r\n\r\n  if (\$piece[0] == \"x\") {    \r\n    \$value = (isset(\$values[\$counter-1])) ? \$values[\$counter-1] : \"\";\r\n    \$output .= \$value;\r\n    if (!empty(\$value))\r\n    {\r\n      \$has_content = true;\r\n    }\r\n    \$counter++;\r\n  } else {\r\n    \$output .= \$piece;\r\n  }\r\n}\r\n\r\nif (!empty(\$output) && \$has_content)\r\n  echo \$output;\r\n{/php}",
      "edit_field_smarty_markup"       => "{php}\r\n\$format = \$this->get_template_vars(\"phone_number_format\");\r\n\$values = explode(\"|\", \$this->get_template_vars(\"VALUE\"));\r\n\$name   = \$this->get_template_vars(\"NAME\");\r\n\r\n\$pieces = preg_split(\"/(x+)/\", \$format, 0, PREG_SPLIT_DELIM_CAPTURE);\r\n\$counter = 1;\r\nforeach (\$pieces as \$piece)\r\n{\r\n  if (strlen(\$piece) == 0)\r\n    continue;\r\n\r\n  if (\$piece[0] == \"x\") {\r\n    \$size = strlen(\$piece); \r\n    \$value = (isset(\$values[\$counter-1])) ? \$values[\$counter-1] : \"\";\r\n    \$value = htmlspecialchars(\$value);\r\n    echo \"<input type=\\\\\"text\\\\\" name=\\\\\"{\$name}_\$counter\\\\\" value=\\\\\"\$value\\\\\"\r\n            size=\\\\\"\$size\\\\\" maxlength=\\\\\"\$size\\\\\" />\";\r\n    \$counter++;\r\n  } else {\r\n    echo \$piece;\r\n  }\r\n}\r\n{/php}\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}",
      "php_processing"                 => "\$field_name = \$vars[\"field_info\"][\"field_name\"];\r\n\$joiner = \"|\";\r\n\r\n\$count = 1;\r\n\$parts = array();\r\nwhile (isset(\$vars[\"data\"][\"{\$field_name}_\$count\"]))\r\n{\r\n  \$parts[] = \$vars[\"data\"][\"{\$field_name}_\$count\"];\r\n  \$count++;\r\n}\r\n\$value = implode(\"|\", \$parts);",
      "resources_css"                  => "",
      "resources_js"                   => "var cf_phone = {};\r\ncf_phone.check_required = function() {\r\n  var errors = [];\r\n  for (var i=0; i<rsv_custom_func_errors.length; i++) {\r\n    if (rsv_custom_func_errors[i].func != \"cf_phone.check_required\") {\r\n      continue;\r\n    }\r\n    var field_name = rsv_custom_func_errors[i].field;\r\n    var fields = $(\"input[name^=\\\\\"\" + field_name + \"_\\\\\"]\");\r\n    fields.each(function() {\r\n      if (!this.name.match(/_(\\\\d+)$/)) {\r\n        return;\r\n      }\r\n      var req_len = $(this).attr(\"maxlength\");\r\n      var actual_len = this.value.length;\r\n      if (req_len != actual_len || this.value.match(/\\\\D/)) {\r\n        var el = document.edit_submission_form[field_name];\r\n        errors.push([el, rsv_custom_func_errors[i].err]);\r\n        return false;\r\n      }\r\n    });\r\n  }\r\n\r\n  if (errors.length) {\r\n    return errors;\r\n  }\r\n\r\n  return true;\r\n  \r\n}"
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


  // ------------------------------------------------------------------------------------------------


  $cft_field_types["code_markup"] = array(

    "field_type" => array(
      "is_editable"                    => "yes",
      "non_editable_info"              => "NULL",
      "managed_by_module_id"           => "NULL",
      "field_type_name"                => "{\$LANG.phrase_code_markup_field}",
      "field_type_identifier"          => "code_markup",
      "is_file_field"                  => "no",
      "is_date_field"                  => "no",
      "raw_field_type_map"             => "textarea",
      "compatible_field_sizes"         => "large,very_large",
      "view_field_rendering_type"      => "php",
      "view_field_php_function_source" => "core",
      "view_field_php_function"        => "ft_display_field_type_code_markup",
      "view_field_smarty_markup"       => "{if \$CONTEXTPAGE == \"edit_submission\"}\r\n  <textarea id=\"{\$NAME}_id\" name=\"{\$NAME}\">{\$VALUE}</textarea>\r\n  <script>\r\n  var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(\"{\$NAME}_id\", \r\n  {literal}{{/literal}\r\n    height: \"{\$SIZE_PX}px\",\r\n    path:   \"{\$g_root_url}/global/codemirror/js/\",\r\n    readOnly: true,\r\n    {if \$code_markup == \"HTML\" || \$code_markup == \"XML\"}\r\n      parserfile: [\"parsexml.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/xmlcolors.css\"\r\n    {elseif \$code_markup == \"CSS\"}\r\n      parserfile: [\"parsecss.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/csscolors.css\"\r\n    {elseif \$code_markup == \"JavaScript\"}  \r\n      parserfile: [\"tokenizejavascript.js\", \"parsejavascript.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/jscolors.css\"\r\n    {/if}\r\n  {literal}});{/literal}\r\n  </script>\r\n{else}\r\n  {\$VALUE|strip_tags}\r\n{/if}",
      "edit_field_smarty_markup"       => "<div class=\"editor\">\r\n  <textarea id=\"{\$NAME}_id\" name=\"{\$NAME}\">{\$VALUE}</textarea>\r\n</div>\r\n<script>\r\n  var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(\"{\$NAME}_id\", \r\n  {literal}{{/literal}\r\n    height: \"{\$height}px\",\r\n    path:   \"{\$g_root_url}/global/codemirror/js/\",\r\n    {if \$code_markup == \"HTML\" || \$code_markup == \"XML\"}\r\n      parserfile: [\"parsexml.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/xmlcolors.css\"\r\n    {elseif \$code_markup == \"CSS\"}\r\n      parserfile: [\"parsecss.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/csscolors.css\"\r\n    {elseif \$code_markup == \"JavaScript\"}  \r\n      parserfile: [\"tokenizejavascript.js\", \"parsejavascript.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/jscolors.css\"\r\n    {/if}\r\n  {literal}});{/literal}\r\n</script>\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}",
      "php_processing"                 => "",
      "resources_css"                  => ".cf_view_markup_field {\r\n  margin: 0px; \r\n}",
      "resources_js"                   => "var cf_code = {};\r\ncf_code.check_required = function() {\r\n  var errors = [];\r\n  for (var i=0; i<rsv_custom_func_errors.length; i++) {\r\n    if (rsv_custom_func_errors[i].func != \"cf_code.check_required\") {\r\n      continue;\r\n    }\r\n    var field_name = rsv_custom_func_errors[i].field;\r\n    var val = \$.trim(window[\"code_mirror_\" + field_name].getCode());\r\n    if (!val) {\r\n      var el = document.edit_submission_form[field_name];\r\n      errors.push([el, rsv_custom_func_errors[i].err]);\r\n    }\r\n  }\r\n  if (errors.length) {\r\n    return errors;\r\n  }\r\n  return true;  \r\n}"
    ),

    "settings" => array(

      // Code / Markup Type
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
    ),

    "validation" => array(
      array(
        "rsv_rule"                 => "function",
        "rule_label"               => "{\$LANG.word_required}",
        "rsv_field_name"           => "",
        "custom_function"          => "cf_code.check_required",
        "custom_function_required" => "yes",
        "default_error_message"    => "{\$LANG.validation_default_rule_required}"
      )
    )
  );

  return $cft_field_types;
}
