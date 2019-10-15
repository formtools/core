  {ft_include file="messages.tpl"}

  <form method="post" name="login_info" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">

    {template_hook location="edit_client_main_top"}

    <table class="list_table" cellpadding="0" cellspacing="1">
    <tr>
      <td width="15" class="red" align="center">*</td>
      <td width="180" class="pad_left_small">{$LANG.phrase_first_name}</td>
      <td><input type="text" name="first_name" value="{$client_info.first_name}" size="25" /></td>
    </tr>
    <tr>
      <td class="red" align="center">*</td>
      <td class="pad_left_small">{$LANG.phrase_last_name}</td>
      <td><input type="text" name="last_name" value="{$client_info.last_name}" size="25" /></td>
    </tr>
    <tr>
      <td class="red" align="center">*</td>
      <td class="pad_left_small">{$LANG.word_email}</td>
      <td><input type="text" name="email" value="{$client_info.email}" size="40" /></td>
    </tr>
    <tr>
      <td class="red" align="center"> </td>
      <td class="pad_left_small">{$LANG.phrase_company_name}</td>
      <td><input type="text" name="company_name" value="{$client_info.settings.company_name}" size="40" /></td>
    </tr>
    </table>

    {template_hook location="edit_client_main_middle"}

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
      <td width="15" class="red" align="center">*</td>
      <td class="pad_left_small" width="180">{$LANG.word_username}</td>
      <td><input type="text" name="username" value="{$client_info.username}" size="25" /></td>
    </tr>
    <tr>
      <td class="red" align="center"> </td>
      <td class="pad_left_small">{$LANG.word_password}</td>
      <td><input type="password" name="password" value="" size="25" /></td>
    </tr>
    <tr>
      <td class="red" align="center"> </td>
      <td class="pad_left_small">{$LANG.phrase_re_enter_password}</td>
      <td><input type="password" name="password_2" value="" size="25" /></td>
    </tr>
    </table>

    {template_hook location="edit_client_main_bottom"}

    <p>
      <input type="submit" name="update" value="{$LANG.word_update}" />
    </p>

  </form>
