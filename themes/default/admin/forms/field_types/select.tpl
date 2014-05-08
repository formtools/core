  <form action="{$same_page}?page=field_options" method="post" onsubmit="return rsv.validate(this, rules)">
    <input type="hidden" name="field_id" value="{$field.field_id}" />
    <input type="hidden" name="field_type" value="select" />

    <table cellpadding="0" cellspacing="1" width="100%">
    <tr>
      <td width="200" class="pad_left_small"><label for="field_title">{$LANG.phrase_display_text}</label></td>
      <td><input type="text" name="field_title" id="field_title" value="{$field.field_title|escape}" style="width:99%" /></td>
    </tr>
    <tr>
      <td class="pad_left_small"><label for="field_size">{$LANG.phrase_db_field_size}</label></td>
      <td>
        <select name="field_size" id="field_size" tabindex="{$count}">
          <option {if $field.field_size == "tiny"}selected{/if} value="tiny">{$LANG.phrase_size_tiny}</option>
          <option {if $field.field_size == "small"}selected{/if} value="small">{$LANG.phrase_size_small}</option>
          <option {if $field.field_size == "medium"}selected{/if} value="medium">{$LANG.phrase_size_medium}</option>
          <option {if $field.field_size == "large"}selected{/if} value="large">{$LANG.phrase_size_large}</option>
          <option {if $field.field_size == "very_large"}selected{/if} value="very_large">{$LANG.phrase_size_very_large}</option>
        </select>
      </td>
    </tr>
    <tr>
      <td class="pad_left_small"><label for="include_on_redirect">{$LANG.phrase_pass_on_to_redirect_page}</label></td>
      <td><input type="checkbox" name="include_on_redirect" id="include_on_redirect" value="yes" {if $field.include_on_redirect == "yes"}checked{/if} /></td>
    </tr>
    <tr>
      <td valign="top" class="pad_left_small"><label for="group_id">{$LANG.phrase_field_option_group}</label></td>
      <td>
        {field_option_groups_dropdown name_id="group_id" default=$field.field_group_id}
        <input type="button" value="{$LANG.word_edit}" onclick="fo_ns.edit_field_option_group($('group_id').value)" />
        <input type="button" value="{$LANG.phrase_create_new_group}" onclick="fo_ns.create_new_field_option_group({$field.field_id})" />
        <div class="medium_grey">
          {$LANG.text_field_option_group_dropdown_explanation}
          {$LANG.text_field_option_group_explanation}
        </div>
      </td>
    </tr>
    </table>

    <p>
	    <input type="submit" name="update" value="{$LANG.word_update|upper}" />
    </p>

	</form>