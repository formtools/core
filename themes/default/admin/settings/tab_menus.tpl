    <div class="subtitle underline margin_top_large">{$LANG.word_menus|upper}</div>

    {ft_include file='messages.tpl'}

    <div class="pad_bottom_large">
      {$LANG.text_edit_client_menu_page}
    </div>

    {template_hook location="admin_settings_menus_top"}

    {$pagination}

    <table class="list_table" cellspacing="1" cellpadding="0">
    <tr>
      <th>{$LANG.word_menu}</th>
      <th>{$LANG.phrase_menu_type}</th>
      <th>{$LANG.word_account_sp}</th>
      <th class="edit"></th>
      <th class="del"></th>
    </tr>

    {foreach from=$menus item=menu name=row}
      {assign var='index' value=$smarty.foreach.row.index}
      {assign var='menu_info' value=$menus[$index]}
      {assign var='menu_id' value=$menu_info.menu_id}
      <tr>
        <td class="pad_left_small">{$menu_info.menu}</td>
        <td class="pad_left_small">
          {if $menu_info.menu_type == "admin"}
            <span class="light_green">{$LANG.phrase_admin_menu}</span>
          {else}
            <span class="blue">{$LANG.phrase_client_menu}</span>
          {/if}
        </td>
        <td class="pad_left_small">
          {if $menu_info.menu_type == "admin"}
            {$LANG.word_administrator}
          {else}
            {if $menu_info.account_info|@count == 0}
              {$LANG.phrase_no_clients}
            {elseif $menu_info.account_info|@count == 1}
              {$menu_info.account_info[0].first_name} {$menu_info.account_info[0].last_name}
            {else}
              <select>
                {foreach from=$menu_info.account_info item=account name=account_row}
                  <option>{$account.first_name} {$account.last_name}</option>
                {/foreach}
              </select>
            {/if}
          {/if}
        </td>
        <td class="edit">
          {if $menu_info.menu_type == "admin"}
            <a href="{$same_page}?page=edit_admin_menu&menu_id={$menu_id}"></a>
          {else}
            <a href="{$same_page}?page=edit_client_menu&menu_id={$menu_id}"></a>
          {/if}
        </td>
        <td{if $menu_info.menu_type != "admin"} class="del"{/if}>
          {if $menu.menu_type == "client"}
            <a href="#" onclick="return page_ns.delete_menu({$menu_id})"></a>
          {/if}
        </td>
      </tr>
    {/foreach}
    </table>

    <form action="{$same_page}" method="post">
      <input type="hidden" name="page" value="edit_client_menu" />
      <p>
        <input type="submit" name="create_new_menu" value="{$LANG.phrase_create_new_menu}" />
      </p>
    </form>
