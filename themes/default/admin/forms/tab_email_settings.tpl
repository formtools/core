  <div class="previous_page_icon">
    <a href="edit.php?page=emails&form_id={$form_id}"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
  </div>

  <div class="subtitle underline margin_top_large">{$LANG.phrase_form_email_field_configuration|upper}</div>

  {ft_include file='messages.tpl'}

  <div class="margin_bottom_large">
    {$LANG.text_email_settings_intro}
  </div>

  {if $registered_form_emails|@count > 0}
    <table class="list_table margin_bottom_large" cellspacing="1" cellpadding="0">
    <tr>
      <th width="200" class="pad_left_small">{$LANG.word_email}</th>
      <th class="pad_left_small">{$LANG.phrase_first_name}</th>
      <th class="pad_left_small">{$LANG.phrase_name_or_last_name}</th>
      <th class="del"></th>
    </tr>
    {foreach from=$registered_form_emails item=email_info}
      <tr>
        <td class="pad_left_small">{$email_info.email_field_label}</td>
        <td class="pad_left_small">{$email_info.first_name_field_label|default:'&#8212;'}</td>
        <td class="pad_left_small">{$email_info.last_name_field_label|default:'&#8212;'}</td>
        <td class="del"><a href="#" onclick="return emails_ns.delete_form_email_field_config({$email_info.form_email_id})"></a></td>
      </tr>
    {/foreach}
    </table>
  {/if}

  {if $columns|@count > 0}
    <form action="{$same_page}" method="post" onsubmit="return rsv.validate(this, g.rules)">
      <input type="hidden" name="page" value="email_settings" />

      <table class="margin_bottom_large" cellspacing="1" cellpadding="0">
        <tr>
          <td width="140" class="pad_right_large">{$LANG.word_email}</td>
          <td width="120">{dropdown options=$columns name="email_field_id" tabindex="1"}</td>
          <td rowspan="3" align="center" width="120">
            <input type="submit" name="update_email_settings" value="{$LANG.phrase_register_new_email}" tabindex="4" />
          </td>
        </tr>
        <tr>
          <td class="pad_right_large">{$LANG.phrase_first_name}</td>
          <td>{dropdown options=$columns name="first_name_field_id" blank_option_text=$LANG.word_na tabindex="2"}</td>
        </tr>
        <tr>
          <td class="pad_right_large">{$LANG.phrase_name_or_last_name}</td>
          <td>{dropdown options=$columns name="last_name_field_id" blank_option_text=$LANG.word_na tabindex="3"}</td>
        </tr>
      </table>

    </form>
  {/if}
