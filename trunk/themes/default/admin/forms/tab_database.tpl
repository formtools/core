
  <div class="subtitle underline margin_bottom_large margin_top_large">{$LANG.word_database|upper}</div>

  <div class="error">
    <div style="padding: 8px;">
      <b>{$LANG.word_warning_c}</b> {$LANG.text_delete_field_warning}
    </div>
  </div>

  {ft_include file="messages.tpl"}

  <form action="{$same_page}" method="post" onsubmit="return rsv.validate(this, rules)">

    <table class="list_table" width="600" cellpadding="0" cellspacing="1">
    <tr style="height: 20px;">
      <th>{$LANG.phrase_display_text}</th>
      <th width="150">{$LANG.phrase_field_size}</th>
      <th width="60">{$LANG.phrase_data_type}</th>
      <th width="160" nowrap>{$LANG.phrase_db_column}<span class="pad_right">&nbsp;</span><input type="button" value="{$LANG.phrase_smart_fill}" onclick="return page_ns.smart_fill()" class="bold"/></th>
      <th width="50" class="del">{$LANG.word_delete|upper}</th>
    </tr>

    {foreach from=$form_fields item=field name=row}
      {assign var='field_id' value=$field.field_id}
      {assign var='count' value=$smarty.foreach.row.iteration}

      {if $field.field_type == "system"}
        {assign var="class_style" value="row_highlight"}
      {else}
        {assign var="class_style" value=""}
      {/if}

      <tr class="{$class_style}">
        <td class="pad_left_small">{$field.field_title}</td>
        <td>

        <input type="hidden" name="field_{$field_id}_type" value="{$field.field_type}" />

        {if $field.field_type == "system"}
          <span class="pad_left_small medium_grey">{$LANG.word_na}</span>
        {elseif $field.field_type == "image"}
          <span class="pad_left_small medium_grey">{$LANG.phrase_size_large}</span>
          <input type="hidden" name="field_{$field_id}_size" value="large" />
        {elseif $field.field_type == "file"}
          <span class="pad_left_small medium_grey">{$LANG.phrase_size_medium}</span>
          <input type="hidden" name="field_{$field_id}_size" value="medium" />
        {else}
          <select name="field_{$field_id}_size" tabindex="{$count}">
            <option {if $field.field_size == "tiny"}selected{/if} value="tiny">{$LANG.phrase_size_tiny}</option>
            <option {if $field.field_size == "small"}selected{/if} value="small">{$LANG.phrase_size_small}</option>
            <option {if $field.field_size == "medium"}selected{/if} value="medium">{$LANG.phrase_size_medium}</option>
            <option {if $field.field_size == "large"}selected{/if} value="large">{$LANG.phrase_size_large}</option>
            <option {if $field.field_size == "very_large"}selected{/if} value="very_large">{$LANG.phrase_size_very_large}	</option>
           </select>
        {/if}

      </td>
      <td>

        {if $field.field_type == "system"}
          <span class="pad_left_small medium_grey">{$LANG.word_na}</span>
        {elseif $field.field_type == "image" || $field.field_type == "file"}
          <span class="pad_left_small medium_grey">{$LANG.word_string}</span>
          <input type="hidden" name="field_{$field_id}_data_type" value="string" />
        {else}
          <select name="field_{$field_id}_data_type" tabindex="{$count+10000}">
            <option {if $field.data_type == "string"}selected{/if} value="string">{$LANG.word_string}</option>
            <option {if $field.data_type == "number"}selected{/if} value="number">{$LANG.word_number}</option>
          </select>
        {/if}

      </td>
      <td class="greyCell">

      {if $field.field_type == "system"}
        <span class="pad_left_small medium_grey">{$field.col_name}</span>
      {else}
        <input type="text" name="col_{$field_id}_name" id="col_{$field_id}_name" style="width: 95%;" value="{$field.col_name}" tabindex="{$count+20000}" />
      {/if}

      </td>
      <td class="del">
      {if $field.field_type != "system"}
        <input type="checkbox" name="field_{$field_id}_remove" />
      {/if}
      </td>
    </tr>

    {/foreach}
    </table>

    <br />

    <table cellspacing="0" cellpadding="0" border="0" width="100%">
    <tr>
      <td>
        <input type="submit" name="update_database" value="{$LANG.word_update|upper}" />
      </td>
      <td align="right">
        {$LANG.word_add} <input type="text" name="num_fields" size="3" value="1" /><input type="submit" name="add_field" value="{$LANG.word_field_sp}" />
      </td>
    </tr>
    </table>

  </form>
