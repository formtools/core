{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" height="35">
  <tr>
    <td width="45"><img src="{$images_url}/icon_login.gif" height="34" width="34" /></td>
    <td class="title">{$LANG.phrase_your_account}</td>
  </tr>
  </table>

  {ft_include file='messages.tpl'}

  {template_hook location="admin_account_top"}

  <form method="post" name="login_info" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">

    <table class="list_table" cellpadding="0" cellspacing="1">
    <tr>
      <td class="pad_left" width="180">{$LANG.phrase_first_name}</td>
      <td><input type="text" name="first_name" value="{$admin_info.first_name}" size="20" /></td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.phrase_last_name}</td>
      <td><input type="text" name="last_name" value="{$admin_info.last_name}" size="20" /></td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.word_email}</td>
      <td><input type="text" name="email" value="{$admin_info.email}" size="50" /></td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.word_theme}</td>
      <td>{themes_dropdown name_id="theme" default=$admin_info.theme default_swatch=$admin_info.swatch}</td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.phrase_login_page}</td>
      <td>{pages_dropdown menu_type="admin" name_id="login_page" default=$admin_info.login_page omit_pages="custom_url,logout"}</td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.phrase_logout_url}</td>
      <td><input type="text" name="logout_url" value="{$admin_info.logout_url}" style="width:98%" /></td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.word_language}</td>
      <td>
        {languages_dropdown name_id="ui_language" default=$admin_info.ui_language}
        <input type="hidden" name="old_ui_language" value="{$admin_info.ui_language}" />
      </td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.phrase_system_time_offset}</td>
      <td>{timezone_offset_dropdown name_id="timezone_offset" default=$admin_info.timezone_offset}</td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.phrase_sessions_timeout}</td>
      <td><input type="text" name="sessions_timeout" value="{$admin_info.sessions_timeout}" style="width: 30px" /> {$LANG.word_minutes}</td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.phrase_date_format}</td>
      <td>
        <input type="text" name="date_format" value="{$admin_info.date_format}" style="width: 80px" />
        <span class="medium_grey">{$text_date_formatting_link}</span>
      </td>
    </tr>
    </table>

    <p class="subtitle">{$LANG.phrase_change_login_info}</p>

    <table class="list_table" cellpadding="0" cellspacing="1">
    <tr>
      <td class="pad_left" width="180">{$LANG.word_username}</td>
      <td><input type="text" name="username" value="{$admin_info.username}" size="20" /></td>
    </tr>
    <tr>
      <td class="pad_left">{$LANG.phrase_new_password}</td>
      <td><input type="password" name="password" value="" size="20" autocomplete="off" /></td>
    </tr>
    <tr>
      <td class="pad_left" width="180">{$LANG.phrase_new_password_reenter}</td>
      <td><input type="password" name="password_2" value="" size="20" autocomplete="off" /></td>
    </tr>
    </table>

    {template_hook location="admin_account_bottom"}

    <p>
      <input type="submit" value="{$LANG.word_update}" />
    </p>

  </form>

{ft_include file='footer.tpl'}
