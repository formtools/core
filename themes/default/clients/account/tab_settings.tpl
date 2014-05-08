    {ft_include file='messages.tpl'}

    {* kind of ugly, but least work to code. If the client doesn't have ANY permission to *}
    {if $client_info.settings.may_edit_page_titles == "no" &&
        $client_info.settings.may_edit_footer_text == "no" &&
        $client_info.settings.may_edit_theme == "no" &&
        $client_info.settings.may_edit_logout_url == "no" &&
        $client_info.settings.may_edit_language == "no" &&
        $client_info.settings.may_edit_timezone_offset == "no" &&
        $client_info.settings.may_edit_sessions_timeout == "no" &&
        $client_info.settings.may_edit_date_format == "no"}

      <div class="notify yellow_bg">
        <div style="padding:8px">
          {$LANG.notify_no_client_permissions}
        </div>
      </div>
      <br />

    {else}

      <form method="post" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">
        <input type="hidden" name="client_id" value="{$client_id}" />
        <input type="hidden" name="page" value="settings" />

        {template_hook location="edit_client_settings_top"}

        <table class="list_table" cellpadding="0" cellspacing="1">
        {if $client_info.settings.may_edit_page_titles == "yes"}
          <tr>
            <td width="15" class="red" align="center">*</td>
            <td class="pad_left_small" width="180">{$LANG.phrase_page_titles}</td>
            <td><input type="text" name="page_titles" id="page_titles" style="width:98%" value="{$client_info.settings.page_titles|escape}" /></td>
          </tr>
        {/if}
        {if $client_info.settings.may_edit_footer_text == "yes"}
          <tr>
            <td width="15" class="red" align="center"> </td>					
            <td class="pad_left_small" width="180">{$LANG.phrase_footer_text}</td>
            <td><input type="text" name="footer_text" style="width:98%" value="{$client_info.settings.footer_text|escape}" /></td>
          </tr>
        {/if}
        {if $client_info.settings.may_edit_theme == "yes"}
          <tr>
            <td width="15" class="red" align="center">*</td>					
            <td class="pad_left_small" width="180">{$LANG.word_theme}</td>
            <td>{themes_dropdown name_id="theme" default=$client_info.theme}</td>
          </tr>
        {/if}
        {if $client_info.settings.may_edit_logout_url == "yes"}
          <tr>
            <td width="15" class="red" align="center">*</td>					
            <td class="pad_left_small" width="180">{$LANG.phrase_logout_url}</td>
            <td><input type="text" name="logout_url" value="{$client_info.logout_url}" style="width: 300px" /></td>
          </tr>
        {/if}
        {if $client_info.settings.may_edit_language == "yes"}
          <tr>
            <td width="15" class="red" align="center">*</td>					
            <td class="pad_left_small" width="180">{$LANG.word_language}</td>
            <td>{languages_dropdown name_id="ui_language" default=$client_info.ui_language}</td>
          </tr>
        {/if}
        {if $client_info.settings.may_edit_timezone_offset == "yes"}
          <tr>
            <td width="15" class="red" align="center">*</td>					
            <td class="pad_left_small" width="180">{$LANG.phrase_system_time_offset}</td>
            <td>{timezone_offset_dropdown name_id="timezone_offset" default=$client_info.timezone_offset}</td>
          </tr>
        {/if}
        {if $client_info.settings.may_edit_sessions_timeout == "yes"}
          <tr>
            <td width="15" class="red" align="center">*</td>					
            <td class="pad_left_small" width="180">{$LANG.phrase_sessions_timeout}</td>
            <td><input type="text" name="sessions_timeout" value="{$client_info.sessions_timeout}" style="width: 30px" /> {$LANG.word_minutes}</td>
          </tr>
        {/if}
        {if $client_info.settings.may_edit_date_format == "yes"}
          <tr>
            <td width="15" class="red" align="center">*</td>					
            <td class="pad_left_small" width="180">{$LANG.phrase_date_format}</td>
            <td><input type="text" name="date_format" value="{$client_info.date_format}" style="width: 80px" /> <span class="medium_grey">{$text_date_formatting_link}</span></td>
          </tr>
        {/if}
        </table>

        {template_hook location="edit_client_settings_bottom"}
				
        <p>
          <input type="submit" name="update_account_settings" value="{$LANG.word_update|upper}" />
        </p>

      </form>

    {/if}