  <div class="subtitle underline margin_top_large">{$LANG.word_fields|upper}</div>

  {ft_include file='messages.tpl'}

  <div class="margin_bottom_large">
    {$text_fields_tab_summary}
  </div>

  <form action="{$same_page}" name="display_form" id="display_form" method="post" onsubmit="return rsv.validate(this, rules)">
    <input type="hidden" name="page" value="fields" />

    <table class="list_table" style="width:100%" cellpadding="0" cellspacing="1">
    <tr>
      <th width="30">{$LANG.word_order}</th>
      <th>{$LANG.phrase_display_text}</th>
      <th>{$LANG.phrase_form_field}</th>
      <th width="150">{$LANG.phrase_field_type}</th>
      <th width="50" class="nowrap pad_left_small">{$LANG.phrase_pass_on}</th>
      <th width="60">{$LANG.word_options}</th>
    </tr>

    {assign var='field_ids' value=''}
    {assign var='total_num_fields' value=$form_fields|@count}
    {foreach from=$form_fields item=field name=row}
      {assign var='count' value=$smarty.foreach.row.iteration}
      {assign var='field_id' value=$field.field_id}
      {assign var='field_ids' value="$field_ids,`$field.field_id`"}

      {if $field.field_type == "system"}
        {assign var="class_style" value="row_highlight"}
      {else}
        {assign var="class_style" value=""}
      {/if}

      <tr class="{$class_style}">
        <td align="center">
          <input type="hidden" name="field_{$field_id}" value="1" />
          <input type="text" name="field_{$field_id}_order" style="width: 30px;" value="{$count}" tabindex="{$count}" />
        </td>
        <td><input type="text" name="field_{$field_id}_display_name" style="width: 97%" value="{$field.field_title|escape}" tabindex="{$count+$total_num_fields}" /></td>
        <td>
          {if $field.field_type == "system"}
            <span class="pad_left_small medium_grey">{$LANG.word_na}</span>
          {else}
            {assign var=offset2 value=$total_num_fields*2}
            <input type="text" name="field_{$field_id}_name" id="field_{$field_id}_name" style="width: 97%;" value="{$field.field_name}" tabindex="{$count+$offset2}" />
          {/if}
        </td>
        <td>
          <input type="hidden" name="old_field_{$field_id}_type" value="{$field.field_type}" />

          {if $field.field_type == "system"}
            <span class="pad_left_small medium_grey">
            {if     $field.col_name == "ip_address"}
              {$LANG.phrase_ip_address}
              <script type="text/javascript">var g_ip_address_field={$field_id}</script>
            {elseif $field.col_name == "submission_date"}
              {$LANG.phrase_submission_date}
            {elseif $field.col_name == "last_modified_date"}
              {$LANG.phrase_last_modified_date}
            {elseif $field.col_name == "submission_id"}
              {$LANG.phrase_submission_id}
            {/if}
            </span>
            <input type="hidden" name="field_{$field_id}_type" value="system" />
          {else}
            {assign var=offset3 value=$total_num_fields*3}
            <select name="field_{$field_id}_type" tabindex="{$count+$offset3}">
              <optgroup label="{$LANG.phrase_standard_fields}">
                <option value="textbox"       {if $field.field_type == "textbox"}selected{/if}>{$LANG.word_textbox}</option>
                <option value="textarea"      {if $field.field_type == "textarea"}selected{/if}>{$LANG.word_textarea}</option>
                <option value="password"      {if $field.field_type == "password"}selected{/if}>{$LANG.word_password}</option>
                <option value="select"        {if $field.field_type == "select"}selected{/if}>{$LANG.word_dropdown}</option>
                <option value="multi-select"  {if $field.field_type == "multi-select"}selected{/if}>{$LANG.phrase_multi_select_dropdown}</option>
                <option value="radio-buttons" {if $field.field_type == "radio-buttons"}selected{/if}>{$LANG.phrase_radio_buttons}</option>
                <option value="checkboxes"    {if $field.field_type == "checkboxes"}selected{/if}>{$LANG.word_checkboxes}</option>
              </optgroup>
              <optgroup label="{$LANG.phrase_special_fields}">
                <option value="file" {if $field.field_type == "file"}selected{/if}>{$LANG.word_file}</option>
                <option value="wysiwyg" {if $field.field_type == "wysiwyg"}selected{/if}>{$LANG.phrase_wysiwyg_field}</option>
              </optgroup>
            </select>
          {/if}

        </td>
        <td align="center">
          {assign var=offset4 value=$total_num_fields*4}
          <input type="checkbox" name="field_{$field_id}_include_on_redirect" {if $field.include_on_redirect == "yes"}checked{/if} tabindex="{$count+$offset4}" />
        </td>
        <td align="center"><a href="edit.php?page=field_options&field_id={$field_id}">{$LANG.word_options}</a></td>
      </tr>

    {/foreach}

    </table>

    <input type="hidden" id="field_ids" value="{$field_ids}" />

    <p>
      <input type="submit" name="update_fields" value="{$LANG.word_update|upper}" />
    </p>

  </form>
