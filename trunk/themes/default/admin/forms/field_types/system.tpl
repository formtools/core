  <form action="{$same_page}?page=field_options" method="post"
    onsubmit="return rsv.validate(this, rules)">
    <input type="hidden" name="field_id" value="{$field.field_id}" />
    <input type="hidden" name="field_type" value="system" />

    <table cellpadding="0" cellspacing="1" width="100%">
    <tr>
      <td width="200" class="pad_left_small">{$LANG.phrase_field_content}</td>
      <td class="blue bold">
        {if $field.col_name == "submission_id"}
          {$LANG.phrase_submission_id}
        {elseif $field.col_name == "submission_date"}
          {$LANG.phrase_submission_date}
        {elseif $field.col_name == "last_modified_date"}
          {$LANG.phrase_last_modified_date}
        {elseif $field.col_name == "ip_address"}
          {$LANG.phrase_ip_address}
        {/if}
      </td>
    </tr>
    <tr>
      <td class="pad_left_small"><label for="field_title">{$LANG.phrase_display_text}</label></td>
      <td><input type="text" style="width:99%" name="field_title" id="field_title" value="{$field.field_title|escape}" /></td>
    </tr>
    <tr>
      <td class="pad_left_small"><label for="include_on_redirect">{$LANG.phrase_pass_on_to_redirect_page}</label></td>
      <td><input type="checkbox" name="include_on_redirect" id="include_on_redirect" {if $field.include_on_redirect == "yes"}checked{/if} /></td>
    </tr>
    </table>

    <p>
      <input type="submit" name="update" value="{$LANG.word_update|upper}" />
    </p>

  </form>