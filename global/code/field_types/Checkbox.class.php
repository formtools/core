<?php


namespace FormTools\FieldTypes;


class Checkbox
{
    public static function get()
    {
        $checkboxes_view_field =<<< END
{strip}{if \$contents != ""}
  {assign var=vals value="`\$g_multi_val_delimiter`"|explode:\$VALUE}
  {assign var=is_first value=true}
  {strip}
    {foreach from=\$contents.options item=curr_group_info name=group}
      {assign var=options value=\$curr_group_info.options}
      {foreach from=\$options item=option name=row}
        {if \$option.option_value|in_array:\$vals}
          {if \$is_first == false}, {/if}
          {\$option.option_name}
          {assign var=is_first value=false}
        {/if}
      {/foreach}
    {/foreach}
  {/strip}
{/if}{/strip}
END;

        $checkboxes_edit_field =<<< END
{if \$contents == ""}
  <div class="cf_field_comments">{\$LANG.phrase_not_assigned_to_option_list}</div>
{else}
  {assign var=vals value="`\$g_multi_val_delimiter`"|explode:\$VALUE}
  {assign var=is_in_columns value=false}
  {if \$formatting == "cf_option_list_2cols" || 
      \$formatting == "cf_option_list_3cols" ||
      \$formatting == "cf_option_list_4cols"}
    {assign var=is_in_columns value=true}
  {/if}
  
  {assign var=counter value="1"}
  {foreach from=\$contents.options item=curr_group_info name=group}
    {assign var=group_info value=\$curr_group_info.group_info}
    {assign var=options value=\$curr_group_info.options}
    
    {if array_key_exists("group_name", \$group_info) && !empty(\$group_info["group_name"])}
      <div class="cf_option_list_group_label">{\$group_info.group_name}</div>
    {/if}
    {if \$is_in_columns}<div class="{\$formatting}">{/if}
    
    {foreach from=\$options item=option name=row}
      {if \$is_in_columns}<div class="column">{/if}
      <input type="checkbox" name="{\$NAME}[]" id="{\$NAME}_{\$counter}"
        value="{\$option.option_value|escape}" {if \$option.option_value|in_array:\$vals}checked{/if} />
      <label for="{\$NAME}_{\$counter}">{\$option.option_name}</label>
      {if \$is_in_columns}</div>{/if}
      {if \$formatting == "vertical"}<br />{/if}
      
      {assign var=counter value=\$counter+1}
    {/foreach}
    
    {if \$is_in_columns}</div>{/if}
  {/foreach}

  {if \$comments}
    <div class="cf_field_comments">{\$comments}</div>
  {/if}
{/if}
END;


        return array(
            "field_type" => array(
                "is_editable"                    => "no",
                "is_enabled"                     => "yes",
                "non_editable_info"              => "{\$LANG.text_non_deletable_fields}",
                "managed_by_module_id"           => null,
                "field_type_name"                => "{\$LANG.word_checkboxes}",
                "field_type_identifier"          => "checkboxes",
                "is_file_field"                  => "no",
                "is_date_field"                  => "no",
                "raw_field_type_map"             => "checkboxes",
                "compatible_field_sizes"         => "1char,2chars,tiny,small,medium,large",
                "view_field_rendering_type"      => "php",
                "view_field_php_function_source" => "core",
                "view_field_php_function"        => "FormTools\\FieldTypes::displayFieldTypeCheckboxes",
                "view_field_smarty_markup"       => $checkboxes_view_field,
                "edit_field_smarty_markup"       => $checkboxes_edit_field,
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
    }

}
