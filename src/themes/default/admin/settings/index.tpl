{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" height="35" class="margin_bottom_large">
  <tr>
    <td width="45"><img src="{$images_url}/icon_settings.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.word_settings}</td>
  </tr>
  </table>

  {ft_include file='tabset_open.tpl'}

    {if $page == "main"}
      {ft_include file='admin/settings/tab_main.tpl'}
    {elseif $page == "accounts"}
      {ft_include file='admin/settings/tab_accounts.tpl'}
    {elseif $page == "files"}
      {ft_include file='admin/settings/tab_files.tpl'}
    {elseif $page == "menus"}
      {ft_include file='admin/settings/tab_menus.tpl'}
    {elseif $page == "add_menu"}
      {ft_include file='admin/settings/tab_add_menu.tpl'}
    {elseif $page == "edit_client_menu"}
      {ft_include file='admin/settings/tab_edit_client_menu.tpl'}
    {elseif $page == "edit_admin_menu"}
      {ft_include file='admin/settings/tab_edit_admin_menu.tpl'}
    {else}
      {ft_include file='admin/settings/tab_main.tpl'}
    {/if}

  {ft_include file='tabset_close.tpl'}

{ft_include file='footer.tpl'}