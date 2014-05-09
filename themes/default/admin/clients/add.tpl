{ft_include file='header.tpl'}

  <table width="100%" cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><a href="./"><img src="{$images_url}/icon_accounts.gif" border="0" width="34" height="34" /></a></td>
    <td class="title">
      <a href="./">{$LANG.word_clients}</a> <span class="joiner">&raquo;</span>
      {$LANG.phrase_add_client}
    </td>
  </tr>
  </table>

  {ft_include file="messages.tpl"}

  <div class="pad_bottom_large">
    {$LANG.text_create_new_client_account}
  </div>

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

  <form action="{$same_page}" id="add_client_form" method="post" autocomplete="off" onsubmit="return rsv.validate(this, rules)">

    {template_hook location="admin_add_client_top"}

    <table cellpadding="0" cellspacing="1">
    <tr>
      <td class="medium_grey" width="130">{$LANG.phrase_first_name}</td>
      <td><input type="text" name="first_name" id="first_name" style="width: 150px" value="{$vals.first_name|escape}" /></td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.phrase_last_name}</td>
      <td><input type="text" name="last_name" style="width: 150px" value="{$vals.last_name|escape}" /></td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.word_email}</td>
      <td><input type="text" name="email" style="width: 150px" value="{$vals.email|escape}" /></td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.word_username}</td>
      <td><input type="text" name="username" style="width: 100px" value="{$vals.username|escape}" /></td>
    </tr>
    <tr>
      <td valign="top" class="medium_grey">{$LANG.word_password}</td>
      <td>
        <input type="password" name="password" style="width: 100px" value="{$vals.password}" />
      </td>
    </tr>
    <tr>
      <td class="medium_grey">{$LANG.phrase_re_enter_password}</td>
      <td><input type="password" name="password_2" style="width: 100px" value="{$vals.password_2}"/></td>
    </tr>
    </table>

    {template_hook location="admin_add_client_bottom"}

    <p>
      <input type="submit" name="add_client" value="{$LANG.phrase_add_client}" />
    </p>

  </form>

{ft_include file='footer.tpl'}