  <input type="hidden" name="num_standard_filters" id="num_standard_filters" value="{$num_standard_filters}" />
  <input type="hidden" name="num_client_map_filters" id="num_client_map_filters" value="{$num_client_map_filters}" />

  <div class="grey_box margin_bottom">
    <a href="#" onclick="return view_ns.toggle_filter_section('standard')">{$LANG.phrase_standard_filters}</a>

    <div id="standard_filters" {if $view_info.has_standard_filter == "no"}style="display:none"{/if}>
      <div class="margin_top margin_bottom">
        {$LANG.text_filters_page}
        {$text_filters_tips}
      </div>

      <table cellspacing="1" cellpadding="0" class="list_table" width="100%" id="standard_filters_table">
      <tbody><tr>
        <th>{$LANG.word_field}</th>
        <th width="180">{$LANG.word_operator}</th>
        <th width="150">{$LANG.word_value_sp}</th>
        <th class="del"></th>
      </tr>
      {* display all the existing filters for this View *}
      {foreach from=$standard_filters item=filter name=row}
        {assign var='count' value=$smarty.foreach.row.iteration}
        {assign var='field_id' value=$filter.field_id}
        {assign var='operator' value=$filter.operator}
        {assign var='filter_values' value=$filter.filter_values}
        <tr id="standard_row_{$count}">
          <td>
            <select name="standard_filter_{$count}_field_id" id="standard_filter_{$count}_field_id"
              onchange="view_ns.change_standard_filter_field({$count})">
              {assign var="selected_field_is_date_field" value=false}
              {foreach from=$form_fields item=field name=field_row}
                {assign var=curr_field_id value=$field.field_id}
                {if $field_id == $curr_field_id}
                  {assign var="selected" value="selected"}
                  {if $curr_field_id|in_array:$date_field_ids}
                    {assign var="selected_field_is_date_field" value=true}
                  {/if}
                {else}
                  {assign var="selected" value=""}
                {/if}
                <option value="{$curr_field_id}" {$selected}>{$field.field_title}</option>
              {/foreach}
            </select>
          </td>
          <td>
            <div id="standard_filter_{$count}_operators_dates_div" {if !$selected_field_is_date_field}style="display:none"{/if}>
              <select name="standard_filter_{$count}_operator_date">
                <option value="before" {if $operator == "before"}selected{/if}>{$LANG.word_before}</option>
                <option value="after"  {if $operator == "after"}selected{/if}>{$LANG.word_after}</option>
              </select>
            </div>
            <div id="standard_filter_{$count}_operators_div" {if $selected_field_is_date_field}style="display:none"{/if}>
              <select name="standard_filter_{$count}_operator">
                <option value="equals"     {if $operator == "equals"}selected{/if}>{$LANG.word_equals}</option>
                <option value="not_equals" {if $operator == "not_equals"}selected{/if}>{$LANG.phrase_not_equal}</option>
                <option value="like"       {if $operator == "like"}selected{/if}>{$LANG.word_like}</option>
                <option value="not_like"   {if $operator == "not_like"}selected{/if}>{$LANG.phrase_not_like}</option>
              </select>
            </div>
          </td>
          <td>
            <div id="standard_filter_{$count}_values_dates_div" {if !$selected_field_is_date_field}style="display:none"{/if} class="cf_date_group">
              <input type="text" name="standard_filter_{$count}_filter_date_values" id="standard_date_{$count}" value="{$filter_values|escape}" /><img
                src="{$g_root_url}/global/images/calendar.png" id="standard_date_image_{$count}" border="0" />
            </div>
            <script>
              $(function() {literal}{{/literal} $("#standard_date_{$count}").datetimepicker({literal}{{/literal}
                showSecond: true,
                timeFormat: "hh:mm:ss",
                dateFormat: "yy-mm-dd"
                {literal}}{/literal});
              {literal}}{/literal});
            </script>
            <div id="standard_filter_{$count}_values_div" {if $selected_field_is_date_field}style="display:none"{/if}>
              <input type="text" name="standard_filter_{$count}_filter_values" style="width: 144px;" value="{$filter_values|escape}" />
            </div>
          </td>
          <td class="del"><a href="#" onclick="return view_ns.delete_filter_row('standard', {$count})"></a></td>
        </tr>
        {/foreach}
      </tbody></table>

      <div class="margin_top">
        {$add_standard_filter_num_rows_input_field}
        <input type="button" value="{$LANG.word_add|upper}" onclick="view_ns.add_standard_filters($('#num_standard_filter_rows').val())" />
      </div>
    </div>
  </div>

  <div class="grey_box">
    <a href="#" onclick="return view_ns.toggle_filter_section('client_map')">{$LANG.phrase_client_map_filters}</a>
    <div id="client_map_filters" {if $view_info.has_client_map_filter == "no"}style="display:none"{/if}>
      <div class="margin_top margin_bottom">
        {$LANG.text_client_map_filters_desc1}
      </div>

      <table cellspacing="1" cellpadding="0" class="list_table" width="100%" id="client_map_filters_table">
      <tbody><tr>
        <th>{$LANG.word_field}</th>
        <th>{$LANG.word_operator}</th>
        <th>{$LANG.phrase_client_field}</th>
        <th class="del"></th>
      </tr>

      {* display all the existing client map filters for this view *}
      {foreach from=$client_map_filters item=filter name=row}
        {assign var='count' value=$smarty.foreach.row.iteration}
        {assign var='field_id' value=$filter.field_id}
        {assign var='operator' value=$filter.operator}
        {assign var='filter_values' value=$filter.filter_values}

        <tr id="client_map_row_{$count}">
          <td>
            <select name="client_map_filter_{$count}_field_id" id="client_map_filter_{$count}_field_id">
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
            <select name="client_map_filter_{$count}_operator">
              <option value="equals"     {if $operator == "equals"}selected{/if}>{$LANG.word_equals}</option>
              <option value="not_equals" {if $operator == "not_equals"}selected{/if}>{$LANG.phrase_not_equal}</option>
              <option value="like"       {if $operator == "like"}selected{/if}>{$LANG.word_like}</option>
              <option value="not_like"   {if $operator == "not_like"}selected{/if}>{$LANG.phrase_not_like}</option>
            </select>
          </td>
          <td>
            <select name="client_map_filter_{$count}_client_field" style="width:160px">
              <option value="">{$LANG.phrase_please_select}</option>
              <optgroup label="{$LANG.phrase_core_fields}">
                <option value="account_id"              {if $filter_values == "account_id"}selected{/if}>{$LANG.word_id}</option>
                <option value="first_name"              {if $filter_values == "first_name"}selected{/if}>{$LANG.phrase_first_name}</option>
                <option value="last_name"               {if $filter_values == "last_name"}selected{/if}>{$LANG.phrase_last_name}</option>
                <option value="email"                   {if $filter_values == "email"}selected{/if}>{$LANG.word_email}</option>
                <option value="settings__company_name"  {if $filter_values == "settings__company_name"}selected{/if}>{$LANG.phrase_company_name}</option>
              </optgroup>
              {template_hook location="admin_edit_view_client_map_filter_dropdown"}
            </select>
          </td>
          <td class="del"><a href="#" onclick="return view_ns.delete_filter_row('client_map', {$count})"></a></td>
        </tr>
        {/foreach}
        </tbody>
      </table>

      <div class="margin_top">
        {$add_client_map_filter_num_rows_input_field}
        <input type="button" value="{$LANG.word_add|upper}" onclick="view_ns.add_client_map_filters($('#num_client_map_filter_rows').val())" />
      </div>
    </div>
  </div>
