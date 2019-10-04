<?php


namespace FormTools\FieldTypes;


class Textarea
{
	public static function get()
	{

		$textarea_view_field = <<< END
{if \$CONTEXTPAGE == "edit_submission"}  
  {\$VALUE|nl2br}
{else}
  {\$VALUE}
{/if}
END;

		$textarea_edit_field = <<< END
{* figure out all the classes *}
{assign var=classes value=\$height}
{if \$highlight_colour}
  {assign var=classes value="`\$classes` `\$highlight_colour`"}
{/if}
{if \$input_length == "words" && \$maxlength != ""}
  {assign var=classes value="`\$classes` cf_wordcounter max`\$maxlength`"}
{elseif \$input_length == "chars" && \$maxlength != ""}
  {assign var=classes value="`\$classes` cf_textcounter max`\$maxlength`"}
{/if}

<textarea name="{\$NAME}" id="{\$NAME}_id" class="{\$classes}">{\$VALUE}</textarea>

{if \$input_length == "words" && \$maxlength != ""}
  <div class="cf_counter" id="{\$NAME}_counter">
    {\$maxlength} {\$LANG.phrase_word_limit_p} <span></span> {\$LANG.phrase_remaining_words}
  </div>
{elseif \$input_length == "chars" && \$maxlength != ""}
  <div class="cf_counter" id="{\$NAME}_counter">
    {\$maxlength} {\$LANG.phrase_characters_limit_p} <span></span> {\$LANG.phrase_remaining_characters}
  </div>
{/if}

{if \$comments}
  <div class="cf_field_comments">{\$comments|nl2br}</div>
{/if}
END;

		$textarea_css = <<< END
.cf_counter span {
  font-weight: bold;
}
textarea {
  width: 99%;
}
textarea.cf_size_tiny {
  height: 30px;
}
textarea.cf_size_small {
  height: 80px;
}
textarea.cf_size_medium {
  height: 150px;
}
textarea.cf_size_large {
  height: 300px;
}
END;

		$textarea_js = <<< END
/**
 * The following code provides a simple text/word counter option for any 
 * textarea. It either just keeps counting up, or limits the results to a
 * certain number - all depending on what the user has selected via the
 * field type settings.
 */
var cf_counter = {};
cf_counter.get_max_count = function(el) {
  var classes = $(el).attr('class').split(" ").slice(-1);
  var max = null;
  for (var i=0; i<classes.length; i++) {
    var result = classes[i].match(/max(\d+)/);
    if (result != null) {
      max = result[1];
      break;
    }
  }
  return max;
}

$(function() {
  $("textarea[class~='cf_wordcounter']").each(function() {
    var max = cf_counter.get_max_count(this);
    if (max == null) {
      return;
    }
    $(this).bind("keydown", function() {
      var val = $(this).val();
      var len = val.split(/[\s]+/);
      var field_name = $(this).attr("name");
      var num_words  = len.length - 1;
      if (num_words > max) {
        var allowed_words = val.split(/[\s]+/, max);
        truncated_str = allowed_words.join(" ");
        $(this).val(truncated_str);
      } else {
        $("#" + field_name + "_counter").find("span").html(parseInt(max) - parseInt(num_words));
      }
    });
    $(this).trigger("keydown");
  });
  $("textarea[class~='cf_textcounter']").each(function() {
    var max = cf_counter.get_max_count(this);
    if (max == null) {
      return;
    }
    $(this).bind("keydown", function() {
      var field_name = $(this).attr("name");
      if (this.value.length > max) {
        this.value = this.value.substring(0, max);
      } else {
        $("#" + field_name + "_counter").find("span").html(max - this.value.length);
      }
    });
    $(this).trigger("keydown");
  });
});
END;

		return array(
			"field_type" => array(
				"is_editable" => "no",
                "is_enabled" => "yes",
				"non_editable_info" => "{\$LANG.text_non_deletable_fields}",
				"managed_by_module_id" => null,
				"field_type_name" => "{\$LANG.word_textarea}",
				"field_type_identifier" => "textarea",
				"is_file_field" => "no",
				"is_date_field" => "no",
				"raw_field_type_map" => "textarea",
				"compatible_field_sizes" => "medium,large,very_large",
				"view_field_rendering_type" => "smarty",
				"view_field_php_function_source" => "core",
				"view_field_php_function" => "",
				"view_field_smarty_markup" => $textarea_view_field,
				"edit_field_smarty_markup" => $textarea_edit_field,
				"php_processing" => "",
				"resources_css" => $textarea_css,
				"resources_js" => $textarea_js
			),

			"settings" => array(

				// Height
				array(
					"field_label" => "{\$LANG.word_height}",
					"field_setting_identifier" => "height",
					"field_type" => "select",
					"field_orientation" => "na",
					"default_value_type" => "static",
					"default_value" => "cf_size_small",

					"options" => array(
						array(
							"option_text" => "{\$LANG.phrase_tiny_30px}",
							"option_value" => "cf_size_tiny",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.phrase_small_80px}",
							"option_value" => "cf_size_small",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.phrase_medium_150px}",
							"option_value" => "cf_size_medium",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.phrase_large_300px}",
							"option_value" => "cf_size_large",
							"is_new_sort_group" => "yes"
						)
					)
				),

				// Highlight
				array(
					"field_label" => "{\$LANG.phrase_highlight_colour}",
					"field_setting_identifier" => "highlight_colour",
					"field_type" => "select",
					"field_orientation" => "na",
					"default_value_type" => "static",
					"default_value" => "",

					"options" => array(
						array(
							"option_text" => "{\$LANG.word_none}",
							"option_value" => "",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.word_red}",
							"option_value" => "cf_colour_red",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.word_orange}",
							"option_value" => "cf_colour_orange",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.word_yellow}",
							"option_value" => "cf_colour_yellow",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.word_green}",
							"option_value" => "cf_colour_green",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.word_blue}",
							"option_value" => "cf_colour_blue",
							"is_new_sort_group" => "yes"
						)
					)
				),

				// Input Length
				array(
					"field_label" => "{\$LANG.phrase_input_length}",
					"field_setting_identifier" => "input_length",
					"field_type" => "radios",
					"field_orientation" => "horizontal",
					"default_value_type" => "static",
					"default_value" => "",

					"options" => array(
						array(
							"option_text" => "{\$LANG.phrase_no_limit}",
							"option_value" => "",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.word_words}",
							"option_value" => "words",
							"is_new_sort_group" => "yes"
						),
						array(
							"option_text" => "{\$LANG.word_characters}",
							"option_value" => "chars",
							"is_new_sort_group" => "yes"
						)
					)
				),

				// - Max length (words / chars)
				array(
					"field_label" => "{\$LANG.phrase_max_length_words_chars}",
					"field_setting_identifier" => "maxlength",
					"field_type" => "textbox",
					"field_orientation" => "na",
					"default_value_type" => "static",
					"default_value" => "",
					"options" => array()
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
