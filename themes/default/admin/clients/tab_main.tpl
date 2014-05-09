    {ft_include file='messages.tpl'}

    <form method="post" name="add_client" id="add_client" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">
      <input type="hidden" name="client_id" value="{$client_id}" />
      <input type="hidden" name="update_client" value="1" />

      {template_hook location="admin_edit_client_main_top"}

      <table class="list_table" cellpadding="0" cellspacing="1">
      <tr>
        <td width="15"> </td>
        <td class="pad_left_small">{$LANG.phrase_last_logged_in}</td>
        <td class="pad_left_small medium_grey">
          {if $client_info.last_logged_in != ""}
            {$client_info.last_logged_in|custom_format_date:$SESSION.account.timezone_offset:$SESSION.account.date_format}
          {else}
            {$LANG.word_never}
          {/if}
        </td>
      </tr>
      <tr>
        <td width="15" align="center" class="red">*</td>
        <td class="pad_left_small">{$LANG.word_status}</td>
        <td>
          <input type="radio" name="account_status" id="as1" value="active" {if $client_info.account_status == "active"}checked{/if} />
            <label for="as1" class="light_green">{$LANG.word_active}</label>
          <input type="radio" name="account_status" id="as2" value="pending" {if $client_info.account_status == "pending"}checked{/if} />
            <label for="as2" class="orange">{$LANG.word_pending}</label>
          <input type="radio" name="account_status" id="as3" value="disabled" {if $client_info.account_status == "disabled"}checked{/if} />
            <label for="as3" class="red">{$LANG.word_disabled}</label>
        </td>
      </tr>
      <tr>
        <td width="15" align="center" class="red">*</td>
        <td width="180" class="pad_left_small">{$LANG.phrase_first_name}</td>
        <td><input type="text" name="first_name" value="{$client_info.first_name|escape}" size="20" /></td>
      </tr>
      <tr>
        <td align="center" class="red">*</td>
        <td class="pad_left_small">{$LANG.phrase_last_name}</td>
        <td><input type="text" name="last_name" value="{$client_info.last_name|escape}" size="20" /></td>
      </tr>
      <tr>
        <td align="center" class="red">*</td>
        <td class="pad_left_small">{$LANG.word_email}</td>
        <td><input type="text" name="email" value="{$client_info.email}" size="50" /></td>
      </tr>
      <tr>
        <td class="red"> </td>
        <td class="pad_left_small">{$LANG.phrase_company_name}</td>
        <td><input type="text" name="company_name" value="{$client_info.settings.company_name}" style="width: 98%;" /></td>
      </tr>
      <tr>
        <td class="red"></td>
        <td class="pad_left_small">{$LANG.word_notes} {$LANG.phrase_not_visible_to_client}</td>
        <td><textarea name="client_notes" style="width:98%; height: 80px;">{$client_info.settings.client_notes}</textarea></td>
      </tr>
      </table>

      {template_hook location="admin_edit_client_main_middle"}

      <p class="subtitle">{$LANG.phrase_change_login_info}</p>

      {if $has_extra_password_requirements}
      <div class="grey_box margin_bottom_large">
        {$LANG.phrase_password_requirements_c}
        <ul class="margin_bottom_small margin_top_small">
          {if $has_min_password_length}<li>{$phrase_password_min}</li>{/if}
          {if "uppercase"|in_array:$required_password_chars}<li>{$LANG.phrase_password_one_uppercase}</li>{/if}
          {if "number"|in_array:$required_password_chars}<li>{$LANG.phrase_password_one_number}</li>{/if}
          {if "special_char"|in_array:$required_password_chars}<li>{$password_special_char}</li>{/if}
        </ul>
      </div>
      {/if}

      <table class="list_table" cellpadding="0" cellspacing="1">
      <tr>
        <td width="15" align="center" class="red">*</td>
        <td class="pad_left_small" width="180">{$LANG.word_username}</td>
        <td><input type="text" name="username" value="{$client_info.username}" size="20" /></td>
      </tr>
      <tr>
        <td> </td>
        <td class="pad_left_small">{$LANG.phrase_new_password}</td>
        <td><input type="password" name="password" value="" size="20" autocomplete="off" /></td>
      </tr>
      <tr>
        <td> </td>
        <td class="pad_left_small">{$LANG.phrase_new_password}</td>
        <td><input type="password" name="password_2" value="" size="20" autocomplete="off" /></td>
      </tr>
      </table>

      {template_hook location="admin_edit_client_main_bottom"}

      <p>
        <input type="submit" name="submit" value="{$LANG.word_update}" />
      </p>

    </form>
