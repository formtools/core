{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="./"><img src="{$images_url}/icon_option_lists.gif" width="34" height="34" border="0" /></a></td>
    <td class="title">
      <a href="./">{$LANG.phrase_option_lists}</a> <span class="joiner">&raquo;</span> {$list_info.option_list_name}
    </td>
  </tr>
  </table>

  {ft_include file='tabset_open.tpl'}

    {if $page == "main"}
      {ft_include file='admin/forms/option_lists/tab_main.tpl'}
    {elseif $page == "form_fields"}
      {ft_include file='admin/forms/option_lists/tab_form_fields.tpl'}
    {else}
      {ft_include file='admin/forms/option_lists/tab_main.tpl'}
    {/if}

  {ft_include file='tabset_close.tpl'}

{ft_include file='footer.tpl'}