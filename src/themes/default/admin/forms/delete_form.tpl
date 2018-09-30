{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><img src="{$images_url}/icon_forms.gif" width="34" height="34" /></td>
    <td class="title">
	    <a href="./">{$LANG.word_forms}</a> <span class="joiner">&raquo;</span>
	    <a href="./">{$form_info.form_name}</a> (<span class="identifier">{$form_id}</span>)
	    <span class="joiner">&raquo;</span>
	    {$LANG.phrase_delete_form}
    </td>
  </tr>
  </table>

  <div class="error yellow_bg">
    <div style="padding: 8px;">
      <b>{$LANG.word_warning_c}</b> {$LANG.text_delete_form_warning}
    </div>
  </div>

  {ft_include file="messages.tpl"}

  <form method="post" action="{$same_page}" onsubmit="return rsv.validate(this, rules)" id="delete_form_form">
    <input type="hidden" name="form_id" value="{$form_id}" />
    <input type="checkbox" name="delete_form" id="delete_form" value="yes" />
    <label for="delete_form">{$LANG.text_confirm_delete_form}</label><br />

    {if $uploaded_files|@count > 0}
      <input type="checkbox" name="delete_files" id="delete_files" value="yes" />
      <label for="delete_files">{$LANG.text_delete_all_forms}</label>
      (<a href="#" onclick="page_ns.show_uploaded_files(); return false">{$LANG.phrase_view_uploaded_files}</a>)<br />
    {/if}

    <br />
    <input type="button" name="sss" value="{$LANG.phrase_return_form_list}" onclick="window.location='index.php'" />
    <input type="submit" name="sss2" value="{$LANG.phrase_delete_form|upper}" class="bold" />
  </form>

  {if $uploaded_files|@count > 0}
    <div id="uploaded_files" style="display: none;">
      <br />
      <hr size="1" />
      {foreach from=$uploaded_files item=file_info}
        <a href="{$file_info.folder_url}/{$file_info.filename}" target="_blank">{$file_info.filename}</a><br />
      {/foreach}
    </div>
  {/if}

{ft_include file='footer.tpl'}