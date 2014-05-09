  {ft_include file="messages.tpl"}

  {if $form_fields|@count == 0}

    <div class="notify margin_bottom_large">
      <div style="padding:6px">
        {$LANG.text_unused_option_list}
      </div>
    </div>

  {else}

    <div class="margin_bottom_large">
      {$LANG.text_used_option_list}
    </div>

    <table cellspacing="1" cellpadding="0" class="list_table margin_bottom_large">
    <tr>
      <th width="40"> </th>
      <th>{$LANG.word_form}</th>
      <th>{$LANG.word_field}</th>
      <th>{$LANG.phrase_field_type}</th>
      <th width="100">{$LANG.phrase_edit_field|upper}</th>
    </tr>
    {foreach from=$form_fields item=field_info name=row}
      {assign var=count value=$smarty.foreach.row.iteration}
      <tr>
        <td align="center" class="medium_grey">{$count}</td>
        <td class="pad_left_small">
          {$field_info.form_name}
          {if $field_info.form_id|in_array:$incomplete_forms}
            <span class="red">({$LANG.word_incomplete})</span>
          {/if}
        </td>
        <td class="pad_left_small">{$field_info.field_title}</td>
        <td class="pad_left_small">{display_field_type_name field_type_id=$field_info.field_type_id}</td>
        <td align="center">
          {if $field_info.form_id|in_array:$incomplete_forms}
            <span class="light_grey">{$LANG.phrase_edit_field|upper}</span>
          {else}
            <a href="../edit.php?page=fields&field_id={$field_info.field_id}&form_id={$field_info.form_id}">{$LANG.phrase_edit_field|upper}</a>
          {/if}
        </td>
      </tr>
    {/foreach}
    </table>

  {/if}
