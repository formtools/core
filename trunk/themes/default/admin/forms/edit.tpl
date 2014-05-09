{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" width="100%" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="./"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title"><a href="./">{$LANG.word_forms|upper}</a>: {$form_info.form_name|upper} (<span class="bold">{$form_id}</span>)</td>
    <td align="right">
      <div style="float:right; padding-left: 6px;">
	      <a href="submissions.php?form_id={$form_id}"><img src="{$images_url}/view_small.gif" border="0" alt="{$LANG.phrase_view_submissions}"
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
  {elseif $page == "images"}
    {ft_include file='../../modules/image_manager/templates/tab_images.tpl'}
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
  {elseif $page == "add_fields"}
    {ft_include file='admin/forms/tab_add_fields.tpl'}
  {else}
    {ft_include file='admin/forms/tab_main.tpl'}
  {/if}

  {ft_include file='tabset_close.tpl'}

{ft_include file='footer.tpl'}