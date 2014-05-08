            <input type="hidden" name="num_filters" id="num_filters" value="{$num_filters}" />

            <div class="pad_bottom">
              {$LANG.text_filters_page}
              {$text_filters_tips}
            </div>

            <table cellspacing="1" cellpadding="0" class="list_table" width="100%" id="filters_table">
            <tbody><tr>
              <th>{$LANG.word_field}</th>
              <th>{$LANG.word_operator}</th>
              <th width="130">{$LANG.word_value_sp}</th>
              <th width="60" class="del">{$LANG.word_remove|upper}</th>
            </tr>

            {* display all the existing filters for this view *}
            {foreach from=$filters item=filter name=row}
              {assign var='count' value=$smarty.foreach.row.iteration}
              {assign var='field_id' value=$filter.field_id}
              {assign var='operator' value=$filter.operator}
              {assign var='filter_values' value=$filter.filter_values}

              <tr id="row_{$count}">
                <td>

                  {* TODO: move this to separate Smarty template *}
                  <select name="filter_{$count}_field_id" id="filter_{$count}_field_id" onchange="view_ns.change_filter_field({$count})">
                    {assign var="selected_field_is_date_field" value=false}

                    {foreach from=$form_fields item=field name=field_row}
                      {assign var=curr_field_id value=$field.field_id}

                      {if $field_id == $curr_field_id}
                        {assign var="selected" value="selected"}
                      {else}
                        {assign var="selected" value=""}
                      {/if}

                      {if $selected && ($field.col_name == "submission_date" || $field.col_name == "last_modified_date")}
                        {assign var="selected_field_is_date_field" value=true}
                      {/if}

                      <option value="{$curr_field_id}" {$selected}>{$field.field_title}</option>
                    {/foreach}
                  </select>
              </td>
              <td>
                <div id="filter_{$count}_operators_dates_div" {if !$selected_field_is_date_field}style="display:none"{/if}>
                  <select name="filter_{$count}_operator_date">
                    <option value="before" {if $operator == "before"}selected{/if}>{$LANG.word_before}</option>
                    <option value="after"  {if $operator == "after"}selected{/if}>{$LANG.word_after}</option>
                  </select>
                </div>
                <div id="filter_{$count}_operators_div" {if $selected_field_is_date_field}style="display:none"{/if}>
                    <select name="filter_{$count}_operator">
                      <option value="equals"     {if $operator == "equals"}selected{/if}>{$LANG.word_equals}</option>
                      <option value="not_equals" {if $operator == "not_equals"}selected{/if}>{$LANG.phrase_not_equal}</option>
                      <option value="like"       {if $operator == "like"}selected{/if}>{$LANG.word_like}</option>
                      <option value="not_like"   {if $operator == "not_like"}selected{/if}>{$LANG.phrase_not_like}</option>
                    </select>
                </div>
              </td>
              <td>
                <div id="filter_{$count}_values_dates_div" {if !$selected_field_is_date_field}style="display:none"{/if}>
                  <table cellspacing="0" cellpadding="0" border="0">
                  <tr>
                    <td><input type="text" name="filter_{$count}_filter_date_values" id="date_{$count}" style="width: 120px;" value="{$filter_values}" /></td>
                    <td><img src="{$images_url}/calendar_icon.gif" id="date_image_{$count}" border="0" /></td>
                  </tr>
                  </table>

                  <script type="text/javascript">
                  {literal}Calendar.setup({{/literal}
                     inputField     :    "date_{$count}",
                     showsTime      :    true,
                     timeFormat     :    "24",
                     ifFormat       :    "%Y-%m-%d %H:%M:00",
                     button         :    "date_image_{$count}",
                     align          :    "Bl",
                     singleClick    :    true
                  {literal}});{/literal}
                  </script>
                </div>

                <div id="filter_{$count}_values_div" {if $selected_field_is_date_field}style="display:none"{/if}>
                  <input type="text" name="filter_{$count}_filter_values" style="width: 137px;" value="{$filter_values}" />
                </div>
              </td>
              <td class="del"><a href="#" onclick="return view_ns.delete_filter_row({$count})">{$LANG.word_remove|upper}</a></td>
              </tr>
              {/foreach}
              </tbody>
            </table>

            <br />

            <table cellspacing="0" cellpadding="0" width="100%">
            <tr>
              <td>
                {$add_num_rows_input_field}
                <input type="button" value="{$LANG.word_add|upper}" onclick="view_ns.add_filters($('num_filter_rows').value)" />
              </td>
            </tr>
            </table>
