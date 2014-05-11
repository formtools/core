{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" width="100%" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="./"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title">
      <a href="./">{$LANG.word_forms}</a> <span class="joiner">&raquo;</span>
      {$form_info.form_name} (<span class="identifier">{$form_id}</span>)
    </td>
    <td align="right" valign="top">
      <div style="float:right; padding-left: 4px;">
	      <a href="{$view_submissions_link}"><img src="{$images_url}/admin_edit.png" border="0" alt="{$LANG.phrase_view_submissions}"
	        title="{$LANG.phrase_view_submissions}" /></a>
	    </div>
    </td>
  </tr>
  </table>

  {ft_include file='tabset_open.tpl'}

  {if     $page == "main"}
    {ft_include file='admin/forms/tab_main.tpl'}
  {elseif $page == "public_form_omit_list"}
    {ft_include file='admin/forms/tab_public_form_omit_list.tpl'}
  {elseif $page == "fields"}
    {ft_include file='admin/forms/tab_fields.tpl'}
  {elseif $page == "field_options"}
    {ft_include file='admin/forms/tab_field_options.tpl'}
  {elseif $page == "files"}
    {ft_include file='admin/forms/tab_files.tpl'}
  {elseif $page == "emails"}
    {ft_include file='admin/forms/tab_emails.tpl'}
  {elseif $page == "email_settings"}
    {ft_include file='admin/forms/tab_email_settings.tpl'}
  {elseif $page == "edit_email"}
    {ft_include file='admin/forms/tab_edit_email.tpl'}
  {elseif $page == "views"}
    {ft_include file='admin/forms/tab_views.tpl'}
  {elseif $page == "edit_view"}
    {ft_include file='admin/forms/tab_edit_view.tpl'}
  {elseif $page == "public_view_omit_list"}
    {ft_include file='admin/forms/tab_public_view_omit_list.tpl'}
  {elseif $page == "add_view"}
    {ft_include file='admin/forms/tab_add_view.tpl'}
  {elseif $page == "database"}
    {ft_include file='admin/forms/tab_database.tpl'}
  {else}
    {if "admin_edit_form_content"|hook_call_defined}
      {template_hook location="admin_edit_form_content"}
    {else}
      {ft_include file='admin/forms/tab_main.tpl'}
    {/if}
  {/if}

  {ft_include file='tabset_close.tpl'}

{ft_include file='footer.tpl'}