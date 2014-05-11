{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><img src="{$images_url}/icon_option_lists.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.phrase_option_lists}</td>
  </tr>
  </table>

  <div>
    {$text_option_list_page}
  </div>

  {ft_include file='messages.tpl'}

  <form action="{$same_page}" method="post">
    <input type="hidden" name="page" value="views" />

    {if $num_option_lists == 0}
      <div class="notify" class="margin_bottom_large">
        <div style="padding:8px">
          {$LANG.notify_no_option_lists}
        </div>
      </div>
    {else}
      {$pagination}
      <table class="list_table" cellspacing="1" cellpadding="0">
      <tr>
        {assign var="up_down" value=""}
        {if     $order == "list_id-DESC"}
          {assign var=sort_order value="order=list_id-ASC"}
          {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
        {elseif $order == "list_id-ASC"}
          {assign var=sort_order value="order=list_id-DESC"}
          {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
        {else}
          {assign var=sort_order value="order=list_id-DESC"}
        {/if}
        <th width="40" class="sortable_col{if $up_down} over{/if}">
          <a href="{$same_page}?{$sort_order}">{$LANG.word_id|upper} {$up_down}</a>
        </th>
        {assign var="up_down" value=""}
        {if     $order == "option_list_name-DESC"}
          {assign var=sort_order value="order=option_list_name-ASC"}
          {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
        {elseif $order == "option_list_name-ASC"}
          {assign var=sort_order value="order=option_list_name-DESC"}
          {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
        {else}
          {assign var=sort_order value="order=option_list_name-DESC"}
        {/if}
        <th class="sortable_col{if $up_down} over{/if}">
          <a href="{$same_page}?{$sort_order}">{$LANG.phrase_option_list_name} {$up_down}</a>
        </th>
        <th nowrap>{$LANG.phrase_num_options}</th>
        <th width="220" nowrap>{$LANG.phrase_used_by_num_form_fields}</th>
        <th class="edit"></th>
        <th class="del"></th>
      </tr>

      {foreach from=$option_lists item=list_info name=row}
        {assign var='index' value=$smarty.foreach.row.index}
        {assign var='count' value=$smarty.foreach.row.iteration}
        {assign var='list_id' value=$list_info.list_id}
        <tr>
          <td class="medium_grey" align="center">{$list_info.list_id}</td>
          <td class="pad_left_small">{$list_info.option_list_name}</td>
          <td class="pad_left_small" align="center">{$list_info.num_option_list_options}</td>
          <td class="pad_left_small" align="center">
            {if $list_info.num_fields == 0}
              <span class="light_grey">{$LANG.word_none}</span>
              {assign var=may_delete_list value="true"}
            {else}
              <select style="width:100%">
                <option value="">
                  {if $list_info.num_fields == 1}
                    1 {$LANG.word_field|lower}
                  {else}
                    {$list_info.num_fields} {$LANG.word_fields|lower}
                  {/if}
                </option>
                {foreach from=$list_info.fields item=grouped_field}
                  <optgroup label="{$grouped_field.form_name}">
                    {foreach from=$grouped_field.fields item=field}
                      <option value="">{$field.field_title}</option>
                    {/foreach}
                  </optgroup>
                {/foreach}
              </select>
              {assign var=may_delete_list value="false"}
            {/if}
          </td>
          <td class="edit"><a href="edit.php?list_id={$list_id}"></a></td>
          <td class="del"><a href="#" onclick="return sf_ns.delete_option_list({$list_id}, {$may_delete_list})"></a></td>
        </tr>
      {/foreach}
      </table>

    {/if}

    <p>
      {if $num_option_lists > 0}
        <select name="create_option_list_from_list_id">
          <option value="">{$LANG.phrase_new_blank_option_list}</option>
          <optgroup label="{$LANG.phrase_copy_settings_from}">
            {foreach from=$all_option_lists item=list_info}
              <option value="{$list_info.list_id}">{$list_info.option_list_name}</option>
            {/foreach}
          </optgroup>
        </select>
      {/if}
      <input type="submit" name="add_option_list" value="{$LANG.phrase_create_new_option_list_rightarrow}" />
      {template_hook location="option_list_button_row"}
    </p>

  </form>

{ft_include file='footer.tpl'}