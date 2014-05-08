    <div class="subtitle underline margin_top_large">{$LANG.phrase_client_account_settings|upper}</div>

    {ft_include file='messages.tpl'}

    <div class="margin_bottom_large">
      {$LANG.text_account_settings_page}
    </div>

    <form action="{$same_page}" method="post" onsubmit="return rsv.validate(this, rules)">
    <input type="hidden" name="page" value="accounts" />

    <table class="list_table" cellpadding="0" cellspacing="1">
    <tr>
      <th>{$LANG.word_setting}</th>
      <th>{$LANG.phrase_setting_value}</th>
      <th>{$LANG.phrase_clients_may_edit}</th>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_page_titles}</td>
      <td><input type="text" name="default_page_titles" style="width:98%" value="{$settings.default_page_titles|escape}" /></td>
      <td align="center"><input type="checkbox" name="clients_may_edit_page_titles" {if $settings.clients_may_edit_page_titles == "yes"}checked{/if} /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_footer_text}</td>
      <td><input type="text" name="default_footer_text" style="width:98%" value="{$settings.default_footer_text|escape}" /></td>
      <td align="center"><input type="checkbox" name="clients_may_edit_footer_text" {if $settings.clients_may_edit_footer_text == "yes"}checked{/if} /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_default_theme}</td>
      <td>{themes_dropdown name_id="default_theme" default=$settings.default_theme}</td>
      <td align="center"><input type="checkbox" name="clients_may_edit_theme" {if $settings.clients_may_edit_theme == "yes"}checked{/if} /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_default_menu}</td>
      <td>{menus_dropdown name_id="default_client_menu_id" type="client" default=$settings.default_client_menu_id}</td>
      <td align="center"> </td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_login_page}</td>
      <td>{pages_dropdown menu_type="client" name_id="default_login_page" default=$settings.default_login_page omit_pages="logout" omit_pages="logout,custom_url,client_form_submissions"}</td>
      <td align="center"> </td>
    </tr>
    <tr>
      <td class="pad_left_small" width="180">{$LANG.phrase_logout_url}</td>
      <td><input type="text" name="default_logout_url" value="{$settings.default_logout_url}" style="width: 98%" /></td>
      <td align="center"><input type="checkbox" name="clients_may_edit_logout_url" {if $settings.clients_may_edit_logout_url == "yes"}checked{/if} /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_default_language}</td>
      <td>
        {languages_dropdown name_id="default_language" default=$settings.default_language}
        <input type="button" value="{$LANG.phrase_refresh_list}" onclick="window.location='index.php?page=accounts&refresh_lang_list'" />
        <a href="http://translations.formtools.org" target="_blank">{$LANG.phrase_get_more}</a>
      </td>
      <td align="center"><input type="checkbox" name="clients_may_edit_ui_language" {if $settings.clients_may_edit_ui_language == "yes"}checked{/if} /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_system_time_offset}</td>
      <td>{timezone_offset_dropdown name_id="default_timezone_offset" default=$settings.default_timezone_offset}</td>
      <td align="center"><input type="checkbox" name="clients_may_edit_timezone_offset" {if $settings.clients_may_edit_timezone_offset == "yes"}checked{/if} /></td>
    </tr>
    <tr>
      <td class="pad_left_small" width="180">{$LANG.phrase_default_sessions_timeout}</td>
      <td><input type="text" name="default_sessions_timeout" value="{$settings.default_sessions_timeout}" style="width: 30px" /> {$LANG.word_minutes}</td>
      <td align="center"><input type="checkbox" name="clients_may_edit_sessions_timeout" {if $settings.clients_may_edit_sessions_timeout == "yes"}checked{/if} /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_date_format}</td>
      <td><input type="text" name="default_date_format" value="{$settings.default_date_format}" style="width: 80px" /> <span class="medium_grey">{$text_date_formatting_link}</span></td>
      <td align="center"><input type="checkbox" name="clients_may_edit_date_format" {if $settings.clients_may_edit_date_format == "yes"}checked{/if} /></td>
    </tr>
    {template_hook location="admin_settings_client_settings_bottom"}
    </table>

    <p>
      <input type="submit" name="update_accounts" value="{$LANG.word_update|upper}" />
    </p>

  </form>
