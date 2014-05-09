  <div class="previous_page_icon">
    <a href="edit.php?page=main&form_id={$form_id}"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}"
      alt="{$LANG.phrase_previous_page}" border="0" /></a>
  </div>

  <div class="subtitle underline margin_top_large">{$LANG.phrase_public_form_omit_list|upper}</div>

  {ft_include file="messages.tpl"}

  <div class="margin_bottom_large">
    {$LANG.text_public_form_omit_list_page}
  </div>

  <form method="post" action="{$same_page}" onsubmit="ft.select_all('selected_client_ids[]')">
    <input type="hidden" name="form_id" value="{$form_id}" />
    <input type="hidden" name="page" value="public_form_omit_list" />

    <table cellpadding="1" cellspacing="0" class="list_table">
    <tr>
      <td class="medium_grey pad_left_small">{$LANG.phrase_clients_can_access_form}</td>
      <td></td>
      <td class="medium_grey pad_left_small">{$LANG.phrase_clients_cannot_access_form}</td>
    </tr>
    <tr>
      <td>
        {clients_dropdown name_id="available_client_ids[]" multiple="true" multiple_action="hide"
          clients=$form_omit_list size="4" style="width: 280px"}
      </td>
      <td align="center" valign="middle" width="100">
        <input type="button" value="{$LANG.word_add_uc_rightarrow}"
          onclick="ft.move_options('available_client_ids[]', 'selected_client_ids[]');" /><br />
        <input type="button" value="{$LANG.word_remove_uc_leftarrow}"
          onclick="ft.move_options('selected_client_ids[]', 'available_client_ids[]');" />
      </td>
      <td>
        {clients_dropdown name_id="selected_client_ids[]" multiple="true" multiple_action="show"
          clients=$form_omit_list size="4" style="width: 280px"}
      </td>
    </tr>
    </table>

    <p>
      <input type="submit" name="update_public_form_omit_list" value="{$LANG.word_update}" />
      <input type="button" value="{$LANG.phrase_clear_omit_list}" class="blue" onclick="page_ns.clear_omit_list()" />
    </p>
  </form>
