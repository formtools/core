<?php


namespace FormTools\FieldTypes;


class MultiSelect
{
    public static function get()
    {
        $multi_select_view_field =<<< END
{if \$contents != ""}
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
{/if}
END;

        $multi_select_edit_field =<<< END
{if \$contents == ""}
  <div class="cf_field_comments">{\$LANG.phrase_not_assigned_to_option_list}</div>
{else}
  {assign var=vals value="`\$g_multi_val_delimiter`"|explode:\$VALUE}
  <select name="{\$NAME}[]" multiple size="{if \$num_rows}{\$num_rows}{else}5{/if}">
  {foreach from=\$contents.options item=curr_group_info name=group}
    {assign var=group_info value=\$curr_group_info.group_info}
    {assign var=options value=\$curr_group_info.options}
    {if array_key_exists("group_name", \$group_info) && !empty(\$group_info["name"])}
    <optgroup label="{\$group_info.group_name|escape}">
    {/if}
    {foreach from=\$options item=option name=row}
      <option value="{\$option.option_value}" {if \$option.option_value|in_array:\$vals}selected{/if}>{\$option.option_name}</option>
    {/foreach}
    {if array_key_exists("group_name", \$group_info) && !empty(\$group_info["name"])}
    </optgroup>
    {/if}
  {/foreach}
  </select>
{/if}

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
                "field_type_name"                => "{\$LANG.phrase_multi_select_dropdown}",
                "field_type_identifier"          => "multi_select_dropdown",
                "is_file_field"                  => "no",
                "is_date_field"                  => "no",
                "raw_field_type_map"             => "multi-select",
                "compatible_field_sizes"         => "1char,2chars,tiny,small,medium,large",
                "view_field_rendering_type"      => "php",
                "view_field_php_function_source" => "core",
                "view_field_php_function"        => "FormTools\\FieldTypes::displayFieldTypeMultiSelectDropdown",
                "view_field_smarty_markup"       => $multi_select_view_field,
                "edit_field_smarty_markup"       => $multi_select_edit_field,
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
    }

}
