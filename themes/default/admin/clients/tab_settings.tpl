    {ft_include file='messages.tpl'}

    <form method="post" id="settings_form" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">
      <input type="hidden" name="client_id" value="{$client_id}" />
      <input type="hidden" name="page" value="settings" />

      {template_hook location="admin_edit_client_settings_top"} 

      <table class="list_table" cellpadding="0" cellspacing="1">
      <tr>
        <th>{$LANG.word_setting}</th>
        <th>{$LANG.phrase_setting_value}</th>
        <th>{$LANG.phrase_client_may_edit}</th>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_page_titles}</td>
        <td><input type="text" name="page_titles" id="page_titles" style="width:98%" value="{$client_info.settings.page_titles|escape}" /></td>
        <td align="center"><input type="checkbox" name="may_edit_page_titles" {if $client_info.settings.may_edit_page_titles == "yes"}checked{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_footer_text}</td>
        <td><input type="text" name="footer_text" style="width:98%" value="{$client_info.settings.footer_text|escape}" /></td>
        <td align="center"><input type="checkbox" name="may_edit_footer_text" {if $client_info.settings.may_edit_footer_text == "yes"}checked{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.word_theme}</td>
        <td>{themes_dropdown name_id="theme" default=$client_info.theme}</td>
        <td align="center"><input type="checkbox" name="may_edit_theme" {if $client_info.settings.may_edit_theme == "yes"}checked{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.word_menu}</td>
        <td>{menus_dropdown type="client" name_id="menu_id" default=$client_info.menu_id}</td>
        <td> </td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_login_page}</td>
        <td>{pages_dropdown menu_type="client" name_id="login_page" default=$client_info.login_page omit_pages="logout,custom_url,client_form_submissions"}</td>
        <td align="center"> </td>
      </tr>
      <tr>
        <td class="pad_left_small" width="180">{$LANG.phrase_logout_url}</td>
        <td><input type="text" name="logout_url" value="{$client_info.logout_url}" style="width: 300px" /></td>
        <td align="center"><input type="checkbox" name="may_edit_logout_url" {if $client_info.settings.may_edit_logout_url == "yes"}checked{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.word_language}</td>
        <td>
          {languages_dropdown name_id="ui_language" default=$client_info.ui_language}
          <input type="button" value="{$LANG.phrase_refresh_list}" onclick="window.location='edit.php?client_id={$client_id}&page=settings&refresh_lang_list'" />
          <a href="http://www.formtools.org/translations/" target="_blank">{$LANG.phrase_get_more}</a>
        </td>
        <td align="center"><input type="checkbox" name="may_edit_language" {if $client_info.settings.may_edit_language == "yes"}checked{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_system_time_offset}</td>
        <td>{timezone_offset_dropdown name_id="timezone_offset" default=$client_info.timezone_offset}</td>
        <td align="center"><input type="checkbox" name="may_edit_timezone_offset" {if $client_info.settings.may_edit_timezone_offset == "yes"}checked{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small" width="180">{$LANG.phrase_sessions_timeout}</td>
        <td><input type="text" name="sessions_timeout" value="{$client_info.sessions_timeout}" style="width: 30px" /> {$LANG.word_minutes}</td>
        <td align="center"><input type="checkbox" name="may_edit_sessions_timeout" {if $client_info.settings.may_edit_sessions_timeout == "yes"}checked{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_date_format}</td>
        <td><input type="text" name="date_format" value="{$client_info.date_format}" style="width: 80px" /> <span class="medium_grey">{$text_date_formatting_link}</span></td>
        <td align="center"><input type="checkbox" name="may_edit_date_format" {if $client_info.settings.may_edit_date_format == "yes"}checked{/if} /></td>
      </tr>
      </table>

      {template_hook location="admin_edit_client_settings_bottom"}

      <p>
        <input type="submit" name="update_client" value="{$LANG.word_update|upper}" />
      </p>

    </form>