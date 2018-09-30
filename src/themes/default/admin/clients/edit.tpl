{ft_include file='header.tpl'}

  <table width="100%" cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="./"><img src="{$images_url}/icon_accounts.gif" border="0" width="34" height="34" /></a></td>
    <td class="title">
      <a href="./">{$LANG.word_clients}</a>
      <span class="joiner">&raquo;</span> {$client_info.first_name} {$client_info.last_name}
      (<span class="identifier">{$client_id}</span>)
    </td>
    <td align="right">
      <a href="index.php?login={$client_id}" title="{$LANG.phrase_login_as_user}"><img src="{$images_url}/login.png" border="0" /></a>
    </td>
  </tr>
  </table>

  {template_hook location="admin_edit_client_pages_top"}

  {ft_include file='tabset_open.tpl'}

    {if $page == "main"}
      {ft_include file='admin/clients/tab_main.tpl'}
    {elseif $page == "settings"}
      {ft_include file='admin/clients/tab_settings.tpl'}
    {elseif $page == "forms"}
      {ft_include file='admin/clients/tab_forms.tpl'}
    {else}
      {ft_include file='admin/clients/tab_main.tpl'}
    {/if}

  {ft_include file='tabset_close.tpl'}

  {template_hook location="admin_edit_client_pages_bottom"}

{ft_include file='footer.tpl'}