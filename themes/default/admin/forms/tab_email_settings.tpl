  <div class="previous_page_icon">
    <a href="edit.php?page=emails&form_id={$form_id}"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
  </div>

  <div class="subtitle underline margin_top_large">{$LANG.phrase_form_email_field_configuration|upper}</div>

  {ft_include file='messages.tpl'}

  <div class="margin_bottom_large">
    {$LANG.text_email_settings_intro}
  </div>

  <form action="{$same_page}" method="post">
    <input type="hidden" name="page" value="email_settings" />

    <table class="list_table" cellspacing="1" cellpadding="0">
    <tr>
      <td width="160" class="pad_left_small">{$LANG.phrase_user_email_address_field}</td>
      <td>
        {dropdown options=$columns name="user_email_field" default=$form_info.user_email_field}
      </td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_user_name_field}</td>
      <td>
        {dropdown options=$columns name="user_first_name_field" default=$form_info.user_first_name_field}
      </td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_user_last_name_field}</td>
      <td>
        {dropdown options=$columns name="user_last_name_field" default=$form_info.user_last_name_field}
       </td>
    </tr>
    </table>

    <p>
      <input type="submit" name="update_email_settings" value="{$LANG.word_update|upper}" />
    </p>

  </form>