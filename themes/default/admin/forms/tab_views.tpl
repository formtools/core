  <div class="subtitle underline margin_top_large margin_bottom_large">
    {$LANG.word_views|upper}
  </div>

  <div>
    {$LANG.text_view_tab_summary}
  </div>

  {ft_include file='messages.tpl'}

  <form action="{$same_page}" method="post">
    <input type="hidden" name="page" value="views" />

    {if $form_views|@count == 0}

      <div class="error yellow_bg" class="margin_bottom_large">
        <div style="padding:8px">
          <b>{$LANG.word_warning}</b>
          <br/>
          <br/>
          &bull;&nbsp;{$LANG.notify_no_views_defined}
        </div>
      </div>

    {else}

      {$pagination}

      <table class="list_table" cellspacing="1" cellpadding="1">
      <tr>
        {if $form_views|@count > 1}
          <th width="30"><input type="submit" name="update_view_order" value="{$LANG.word_order}" class="bold" /></th>
        {/if}
        <th>{$LANG.word_id|upper}</th>
        <th>{$LANG.phrase_view_name}</th>
        <th>{$LANG.phrase_who_can_access}</th>
        <th width="60">{$LANG.word_edit|upper}</th>
        <th width="60" class="del">{$LANG.word_delete|upper}</th>
      </tr>

      {foreach from=$form_views item=view name=row}
        {assign var='index' value=$smarty.foreach.row.index}
        {assign var='count' value=$smarty.foreach.row.iteration}
        {assign var='view_id' value=$view.view_id}

         <tr>

          {if $form_views|@count > 1}
             <td align="center"><input type="text" name="view_{$view_id}" value="{$view.view_order}" style="width:30px" /></td>
          {/if}

          <td class="medium_grey" align="center">{$view.view_id}</td>
           <td class="pad_left_small">{$view.view_name}</td>
          <td>
            {if $view.access_type == 'admin'}
              <span class="pad_left_small medium_grey">{$LANG.phrase_admin_only}</span>
            {elseif $view.access_type == 'public'}
              {if $view.client_omit_list|@count == 0}
                <span class="pad_left_small blue">{$LANG.phrase_all_clients}</span>
              {else}
                <span class="pad_left_small blue">{$LANG.phrase_all_clients_except_c}</span>
                {clients_dropdown name_id="" only_show_clients=$view.client_omit_list}
              {/if}

            {elseif $view.client_info|@count > 0}
              {if $view.client_info|@count == 1}
                {$view.client_info[0].first_name} {$view.client_info[0].last_name}
              {else}
                <select>
                  {foreach from=$view.client_info item=user name=user_row}
                    <option>{$user.first_name} {$user.last_name}</option>
                  {/foreach}
                </select>
              {/if}
            {else}
              <span class="pad_left_small light_grey">{$LANG.phrase_no_clients}</span>
            {/if}
          </td>
          <td align="center"><a href="{$same_page}?page=edit_view&view_id={$view_id}">{$LANG.word_edit|upper}</a></td>
          <td class="del"><a href="#" onclick="return page_ns.delete_view({$view_id})">{$LANG.word_delete|upper}</a></td>
        </tr>

      {/foreach}

      </table>

    {/if}

    <p>
      {if $all_form_views|@count > 0}
        <select name="create_view_from_view_id">
          <option value="">{$LANG.phrase_new_blank_view}</option>
          <optgroup label="{$LANG.phrase_copy_view_settings_from}">
            {foreach from=$all_form_views key=k item=i}
              <option value="{$i.view_id}">{$i.view_name}</option>
            {/foreach}
          </optgroup>
        </select>
      {/if}
      <input type="submit" name="add_view" value="{$LANG.phrase_create_new_view}" />
    </p>

  </form>
