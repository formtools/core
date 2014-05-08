            <div style="float:right">
              {$assign_rows_to_tabs_html}
              <input type="button" id="assign_fields_button" value="{$LANG.word_assign}" onclick="view_ns.assign_field_tabs()" />
            </div>

            <div class="pad_bottom">
              <input type="checkbox" name="may_edit_submissions" id="cmes" value="yes"
                onchange="view_ns.toggle_editable_fields(this.checked)"
              {if $view_info.may_edit_submissions == "yes"}checked{/if} />
              <label for="cmes">{$LANG.phrase_allow_fields_edited}</label>
            </div>

            <table id="view_fields_table" class="list_table" width="100%" cellpadding="0" cellspacing="1">
            <tbody><tr style="height: 20px;">
              <th width="40">{$LANG.word_order}</th>
              <th width="60">{$LANG.word_column}</th>
              <th width="60">{$LANG.word_sortable}</th>
              <th width="60">{$LANG.word_editable}</th>
              <th width="60">{$LANG.word_searchable}</th>
              <th width="200">{$LANG.phrase_display_text}</th>
              <th>{$LANG.word_tab}</th>
              <th width="60" class="del">{$LANG.word_remove|upper}</th>
            </tr>

            {* used to generate the tab indexes. Leave be! *}
            {assign var="tabindex_increment" value=1000}

            {foreach from=$view_fields item=field name=row}
              {assign var='field_id' value=$field.field_id}
              {assign var='index' value=$smarty.foreach.row.index}
              {assign var='count' value=$smarty.foreach.row.iteration}

              {assign var=col1_tabindex value=$tabindex_increment+$count}
              {assign var=col2_tabindex value=$tabindex_increment*2+$count}
              {assign var=col3_tabindex value=$tabindex_increment*3+$count}
              {assign var=col4_tabindex value=$tabindex_increment*4+$count}
              {assign var=col5_tabindex value=$tabindex_increment*5+$count}
              {assign var=col6_tabindex value=$tabindex_increment*6+$count}

              <tr id="field_row_{$field_id}">
                <td class="greyCell" align="center">
                  <input type="text" name="field_{$field_id}_order" id="field_{$field_id}_order" style="width: 30px;" value="{$count}" tabindex="{$col1_tabindex}" />
                </td>
                <td align="center">
                  <input type="checkbox" name="field_{$field_id}_is_column" id="field_{$field_id}_is_column" onclick="view_ns.toggle_sortable_field({$field_id}, this.checked)"
									  {if $field.is_column == "yes"}checked{/if} tabindex="{$col2_tabindex}" />
                </td>
                <td align="center">
                  <div id="sortable_{$field_id}" {if $field.is_column == 'no'}style="display: none;"{/if}>
                    <input type="checkbox" name="field_{$field_id}_is_sortable" {if $field.is_sortable == "yes"}checked{/if} tabindex="{$col3_tabindex}" />
                  </div>
                </td>
                <td align="center">
                  {* everything except the Submission ID and Last Modified Date is editable *}
                  {if $field.col_name != "submission_id" && $field.col_name != "last_modified_date"}
                    <input type="checkbox" name="field_{$field_id}_is_editable" id="field_{$field_id}_is_editable" {if $field.is_editable == "yes"}checked{/if}
                      {if $view_info.may_edit_submissions == "no"}disabled{/if} tabindex="{$col4_tabindex}" />
                  {/if}
                </td>
                <td align="center">
                  <input type="checkbox" name="field_{$field_id}_is_searchable" {if $field.is_searchable == "yes"}checked{/if} tabindex="{$col5_tabindex}" />
                </td>
                <td class="pad_left_small">{$field.field_title}</td>
                <td>
                  <select name="field_{$field_id}_tab" id="field_{$field_id}_tab" tabindex="{$col6_tabindex}">
                    {foreach from=$view_tabs item=view_tab name=tab_row}
                      {assign var='counter' value=$smarty.foreach.tab_row.iteration}

                      {* this only shows the tabs that have a label *}
                      {if $view_tab.tab_label}
                        <option value="{$counter}"{if $counter == $field.tab_number}selected{/if}>{$view_tab.tab_label}</option>
                      {/if}
                    {/foreach}
                    {if !$has_tabs}<option value="">{$LANG.validation_no_tabs_defined}</option>{/if}
                  </select>
                </td>
                <td class="del"><a href="#" onclick="return view_ns.remove_view_field({$field_id})">{$LANG.word_remove|upper}</a></td>
              </tr>
            {/foreach}
            </tbody></table>

            {* store the field IDs in javascript *}
            <script type="text/javascript">
            {foreach from=$view_fields item=field name=row}
              view_ns.field_ids.push({$field.field_id});
            {/foreach}
            </script>

            <input type="hidden" name="field_ids" id="field_ids" value="" />

            <div class="pad_top_large">
              <div style="float:left" class="pad_right">
              <select id="available_fields" multiple size="5">
                  {$available_fields}
                </select>
              </div>

              <div class="pad_bottom_large">
                <input type="button" id="add_field_button" value="{$LANG.phrase_add_field_sp}" {if $no_available_fields}disabled style="color: #999999"{/if}
                  onclick="view_ns.add_view_fields('available_fields');" />
              </div>

              <input type="button" id="add_field_button" value="{$LANG.phrase_select_all}" onclick="ft.select_all_multi_dropdown_options('available_fields');" /><br />
              <input type="button" id="add_field_button" value="{$LANG.phrase_unselect_all}" onclick="ft.unselect_all_multi_dropdown_options('available_fields');" />
            </div>
