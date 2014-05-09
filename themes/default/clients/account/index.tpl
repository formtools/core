{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><img src="{$images_url}/icon_login.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.phrase_account_info}</td>
  </tr>
  </table>

  {ft_include file='tabset_open.tpl'}

    {if $page == "main"}
      {ft_include file='clients/account/tab_main.tpl'}
    {elseif $page == "settings"}
      {ft_include file='clients/account/tab_settings.tpl'}
    {else}
      {ft_include file='clients/account/tab_main.tpl'}
    {/if}

  {ft_include file='tabset_close.tpl'}

{ft_include file='footer.tpl'}