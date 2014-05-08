{ft_include file='header.tpl'}

  <table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><a href="./"><img src="{$images_url}/icon_accounts.gif" border="0" width="34" height="34" /></a></td>
    <td class="title">
      {$LANG.phrase_add_client|upper}
    </td>
  </tr>
  </table>

  {ft_include file="messages.tpl"}

  <div class="pad_bottom_large">
    {$LANG.text_create_new_client_account}
  </div>

  <form action="{$same_page}" id="add_client_form" method="post" autocomplete="off" onsubmit="return rsv.validate(this, rules)">

    {template_hook location="admin_add_client_top"}

    <table cellpadding="0" cellspacing="1">
    <tr>
      <td class="medium_grey" width="130">{$LANG.phrase_first_name}</td>
      <td><input type="text" name="first_name" id="first_name" style="width: 150px" /></td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.phrase_last_name}</td>
      <td><input type="text" name="last_name" style="width: 150px" /></td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.word_email}</td>
      <td><input type="text" name="email" style="width: 150px" /></td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.word_username}</td>
      <td><input type="text" name="username" style="width: 100px" /></td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.word_password}</td>
      <td><input type="password" name="password" style="width: 100px" /></td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.phrase_re_enter_password}</td>
      <td><input type="password" name="password_2" style="width: 100px" /></td>
    </tr>
    </table>

    {template_hook location="admin_add_client_bottom"}

    <p>
      <input type="submit" name="add_client" value="{$LANG.phrase_add_client|upper}" />
    </p>

  </form>

{ft_include file='footer.tpl'}