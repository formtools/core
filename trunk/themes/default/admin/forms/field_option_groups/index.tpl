{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><img src="{$images_url}/icon_field_option_groups.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.phrase_field_option_groups|upper}</td>
  </tr>
  </table>

  <div class="margin_top_large">
    {$text_field_option_group_page}
  </div>

  {ft_include file='messages.tpl'}

  <form action="{$same_page}" method="post">
    <input type="hidden" name="page" value="views" />

    {if $num_field_option_groups == 0}

      <div class="notify" class="margin_bottom_large">
        <div style="padding:8px">
          {$LANG.notify_no_field_option_groups}
        </div>
      </div>

    {else}

      {$pagination}

      <table class="list_table" cellspacing="1" cellpadding="1">
      <tr>
        <th width="40">{$LANG.word_id}</th>
        <th>{$LANG.phrase_group_name}</th>
        <th width="140" nowrap>{$LANG.phrase_num_field_options}</th>
        <th width="140" nowrap>{$LANG.phrase_used_by_num_form_fields}</th>
        <th width="60">{$LANG.word_edit|upper}</th>
        <th width="60" class="del">{$LANG.word_delete|upper}</th>
      </tr>

      {foreach from=$field_option_groups item=group_info name=row}
        {assign var='index' value=$smarty.foreach.row.index}
        {assign var='count' value=$smarty.foreach.row.iteration}
        {assign var='group_id' value=$group_info.group_id}

        <tr>
          <td class="medium_grey" align="center">{$group_info.group_id}</td>
          <td class="pad_left_small">{$group_info.group_name}</td>
          <td class="pad_left_small" align="center">{$group_info.num_field_group_options}</td>
          <td class="pad_left_small" align="center">
            {if $group_info.num_fields == 0}
              <span class="light_grey">{$LANG.word_none}</span>
              {assign var=may_delete_group value="true"}
            {else}
              {$group_info.num_fields}
              {assign var=may_delete_group value="false"}
            {/if}
          </td>
          <td align="center"><a href="edit.php?group_id={$group_id}">{$LANG.word_edit|upper}</a></td>
          <td class="del"><a href="#" onclick="return page_ns.delete_field_option_group({$group_id}, {$may_delete_group})">{$LANG.word_delete|upper}</a></td>
        </tr>

      {/foreach}

      </table>

    {/if}

    <p>
      {if $num_field_option_groups > 0}
        <select name="create_field_option_group_from_group_id">
          <option value="">{$LANG.phrase_new_blank_field_option_group}</option>
          <optgroup label="{$LANG.phrase_copy_settings_from}">
            {foreach from=$all_field_option_groups item=group_info}
              <option value="{$group_info.group_id}">{$group_info.group_name}</option>
            {/foreach}
          </optgroup>
        </select>
      {/if}
      <input type="submit" name="add_field_option_group" value="{$LANG.phrase_create_new_field_option_group}" />
    </p>

  </form>

{ft_include file='footer.tpl'}