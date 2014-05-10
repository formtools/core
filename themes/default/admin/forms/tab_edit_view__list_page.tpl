    <div class="hint margin_bottom_large">
      {$LANG.text_edit_view_list_page}
    </div>

    <div id="no_view_columns_defined" class="margin_bottom" {if $view_info.columns|@count > 0}style="display:none"{/if}>
      <div class="error">
        <div style="padding: 6px">
          No columns defined! You won't be able to use this View until you choose some form fields to
          appear as columns. Click the Add Row link below.
        </div>
      </div>
    </div>

    <div class="sortable submission_list check_areas margin_bottom" id="{$submission_list_sortable_id}" {if $view_info.columns|@count == 0}style="display:none"{/if}>
      <input type="hidden" class="sortable__custom_delete_handler" value="view_ns.delete_view_column" />

      <ul class="header_row">
        <li class="col1">{$LANG.word_order}</li>
        <li class="col2">{$LANG.word_field}</li>
        <li class="col3">{$LANG.word_sortable}</li>
        <li class="col4">{$LANG.phrase_column_width}</li>
        <li class="col5">{$LANG.word_truncate_q}</li>
        <li class="col6 colN del"></li>
      </ul>
      <div class="clear"></div>
      <ul class="rows" id="rows">

      {assign var=previous_item value=""}
      {foreach from=$view_info.columns key=k item=i name=view_columns}
        {assign var=row_num value=$i.list_order}
        {if $previous_item != ""}
          </div>
          <div class="clear"></div>
        </li>
        {/if}
        <li class="sortable_row">
          <div class="row_content">
            {assign var=previous_item value=$i}
            <div class="row_group{if $smarty.foreach.view_columns.last} rowN{/if}">
              <input type="hidden" class="sr_order" value="{$i.list_order}" />
              <ul>
                <li class="col1 sort_col">{$row_num}</li>
                <li class="col2">
                  <select name="field_id_{$row_num}">
                    {foreach from=$form_fields item=field name=field_row}
                      {assign var=curr_field_id value=$field.field_id}
                      {assign var="selected" value=""}
                      {if $i.field_id == $curr_field_id}
                        {assign var="selected" value="selected"}
                      {/if}
                      <option value="{$curr_field_id}" {$selected}>{$field.field_title}</option>
                    {/foreach}
                  </select>
                </li>
                <li class="col3 check_area">
                  <input type="checkbox" name="is_sortable_{$row_num}" {if $i.is_sortable == "yes"}checked{/if} />
                </li>
                <li class="col4 {if $i.auto_size == "yes"}light_grey{/if}">
                  <input type="checkbox" name="auto_size_{$row_num}" id="auto_size_{$row_num}"
                    {if $i.auto_size == "yes"}checked{/if} class="auto_size" /><label for="auto_size_{$row_num}"
                      class="{if $i.auto_size == "yes"}black{else}light_grey{/if}">{$LANG.phrase_auto_size}</label>
                  &#8212; {$LANG.word_width_c}
                  <input type="text" name="custom_width_{$row_num}" class="custom_width" value="{$i.custom_width|escape}"
                    {if $i.auto_size == "yes"}disabled{/if} />px
                </li>
                <li class="col5">
                  <select name="truncate_{$row_num}">
                    <option value="truncate" {if $i.truncate == "truncate"}selected{/if}>{$LANG.word_yes}</option>
                    <option value="no_truncate" {if $i.truncate == "no_truncate"}selected{/if}>{$LANG.word_no}</option>
                  </select>
                </li>
                <li class="col6 colN del"></li>
              </ul>
              <div class="clear"></div>
            </div>

        {if $smarty.foreach.view_columns.last}
          </div>
          <div class="clear"></div>
        </li>
        {/if}

      {/foreach}
      </ul>
    </div>

    <script>view_ns.num_view_columns = {$view_info.columns|@count};</script>

    <div>
      <a href="#" onclick="return view_ns.add_view_column()">{$LANG.phrase_add_row}</a>
    </div>
