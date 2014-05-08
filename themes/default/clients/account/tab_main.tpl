  {ft_include file="messages.tpl"}

  <form method="post" name="login_info" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">

    <table class="list_table" cellpadding="0" cellspacing="1">
    <tr>
      <td width="140" class="pad_left_small">{$LANG.phrase_first_name}</td>
      <td><input type="text" name="first_name" value="{$client_info.first_name}" size="25" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_last_name}</td>
      <td><input type="text" name="last_name" value="{$client_info.last_name}" size="25" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.word_email}</td>
      <td><input type="text" name="email" value="{$client_info.email}" size="40" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_company_name}</td>
      <td><input type="text" name="company_name" value="{$client_info.settings.company_name}" size="40" /></td>
    </tr>
    </table>


    <p class="subtitle">{$LANG.phrase_change_login_info}</p>

    <table class="list_table" cellpadding="0" cellspacing="1">
    <tr>
      <td class="pad_left_small" width="140">{$LANG.word_username}</td>
      <td><input type="text" name="username" value="{$client_info.username}" size="25" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.word_password}</td>
      <td><input type="password" name="password" value="" size="25" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_re_enter_password}</td>
      <td><input type="password" name="password_2" value="" size="25" /></td>
    </tr>
    </table>

    <p>
      <input type="submit" name="update" value="{$LANG.word_update|upper}" />
    </p>

  </form>
