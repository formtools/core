{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="./"><img src="{$images_url}/icon_field_option_groups.gif" width="34" height="34" border="0" /></a></td>
    <td class="title"><a href="./">GROUP:</a> {$group_info.group_name|upper}</td>
  </tr>
  </table>

  {ft_include file='tabset_open.tpl'}

    {if $page == "main"}
      {ft_include file='admin/forms/field_option_groups/tab_main.tpl'}
    {elseif $page == "form_fields"}
      {ft_include file='admin/forms/field_option_groups/tab_form_fields.tpl'}
    {else}
      {ft_include file='admin/forms/field_option_groups/tab_main.tpl'}
    {/if}

  {ft_include file='tabset_close.tpl'}

{ft_include file='footer.tpl'}