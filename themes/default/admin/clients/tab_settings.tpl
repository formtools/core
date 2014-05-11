    {ft_include file='messages.tpl'}

    <form method="post" id="settings_form" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">
      <input type="hidden" name="client_id" value="{$client_id}" />
      <input type="hidden" name="page" value="settings" />

      {template_hook location="admin_edit_client_settings_top"}

      <table class="list_table check_areas" cellpadding="0" cellspacing="1">
      <tr>
        <th>{$LANG.word_setting}</th>
        <th>{$LANG.phrase_setting_value}</th>
        <th>{$LANG.phrase_client_may_edit}</th>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_page_titles}</td>
        <td><input type="text" name="page_titles" id="page_titles" style="width:98%" value="{$client_info.settings.page_titles|escape}" /></td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_page_titles" {if $client_info.settings.may_edit_page_titles == "yes"}checked="checked"{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_footer_text}</td>
        <td><input type="text" name="footer_text" style="width:98%" value="{$client_info.settings.footer_text|escape}" /></td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_footer_text" {if $client_info.settings.may_edit_footer_text == "yes"}checked="checked"{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.word_theme}</td>
        <td>{themes_dropdown name_id="theme" default=$client_info.theme default_swatch=$client_info.swatch}</td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_theme" {if $client_info.settings.may_edit_theme == "yes"}checked="checked"{/if} /></td>
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
        <td><input type="text" name="logout_url" value="{$client_info.logout_url}" style="width: 98%" /></td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_logout_url" {if $client_info.settings.may_edit_logout_url == "yes"}checked="checked"{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.word_language}</td>
        <td>
          {languages_dropdown name_id="ui_language" default=$client_info.ui_language}
          <input type="button" value="{$LANG.phrase_refresh_list}" onclick="window.location='edit.php?client_id={$client_id}&page=settings&refresh_lang_list'" />
          <a href="http://translations.formtools.org" target="_blank">{$LANG.phrase_get_more}</a>
        </td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_language" {if $client_info.settings.may_edit_language == "yes"}checked="checked"{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_system_time_offset}</td>
        <td>{timezone_offset_dropdown name_id="timezone_offset" default=$client_info.timezone_offset}</td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_timezone_offset" {if $client_info.settings.may_edit_timezone_offset == "yes"}checked="checked"{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small" width="180">{$LANG.phrase_sessions_timeout}</td>
        <td><input type="text" name="sessions_timeout" value="{$client_info.sessions_timeout}" style="width: 30px" /> {$LANG.word_minutes}</td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_sessions_timeout" {if $client_info.settings.may_edit_sessions_timeout == "yes"}checked="checked"{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_date_format}</td>
        <td><input type="text" name="date_format" value="{$client_info.date_format}" style="width: 80px" /> <span class="medium_grey">{$text_date_formatting_link}</span></td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_date_format" {if $client_info.settings.may_edit_date_format == "yes"}checked="checked"{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_forms_page_default_message}</td>
        <td><textarea name="forms_page_default_message" style="width:98%">{$client_info.settings.forms_page_default_message}</textarea></td>
        <td align="center"></td>
      </tr>
      </table>

      <p class="subtitle">{$LANG.phrase_security_settings}</p>

      <table class="list_table check_areas" cellpadding="0" cellspacing="1">
      <tr>
        <th>{$LANG.word_setting}</th>
        <th>{$LANG.phrase_setting_value}</th>
        <th>{$LANG.phrase_client_may_edit}</th>
      </tr>
      <tr>
        <td width="290" class="pad_left_small">{$LANG.phrase_auto_disable_account}</td>
        <td>
          <select name="max_failed_login_attempts">
            <option value=""   {if $client_info.settings.max_failed_login_attempts == ""}selected{/if}>{$LANG.word_na}</option>
            <option value="3"  {if $client_info.settings.max_failed_login_attempts == "3"}selected{/if}>3</option>
            <option value="4"  {if $client_info.settings.max_failed_login_attempts == "4"}selected{/if}>4</option>
            <option value="5"  {if $client_info.settings.max_failed_login_attempts == "5"}selected{/if}>5</option>
            <option value="6"  {if $client_info.settings.max_failed_login_attempts == "6"}selected{/if}>6</option>
            <option value="10" {if $client_info.settings.max_failed_login_attempts == "10"}selected{/if}>10</option>
          </select>
        </td>
        <td class="check_area" align="center"><input type="checkbox" name="may_edit_max_failed_login_attempts"
          {if $client_info.settings.may_edit_max_failed_login_attempts == "yes"}checked="checked"{/if} /></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_min_password_length}</td>
        <td>
          <select name="min_password_length">
            <option value=""   {if $client_info.settings.min_password_length == ""}selected{/if}>{$LANG.word_na}</option>
            <option value="4"  {if $client_info.settings.min_password_length == "4"}selected{/if}>4</option>
            <option value="5"  {if $client_info.settings.min_password_length == "5"}selected{/if}>5</option>
            <option value="6"  {if $client_info.settings.min_password_length == "6"}selected{/if}>6</option>
            <option value="7"  {if $client_info.settings.min_password_length == "7"}selected{/if}>7</option>
            <option value="8"  {if $client_info.settings.min_password_length == "8"}selected{/if}>8</option>
            <option value="9"  {if $client_info.settings.min_password_length == "9"}selected{/if}>9</option>
            <option value="10" {if $client_info.settings.min_password_length == "10"}selected{/if}>10</option>
            <option value="12" {if $client_info.settings.min_password_length == "12"}selected{/if}>12</option>
          </select>
        </td>
        <td></td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_required_password_chars}</td>
        <td>
          {assign var=required_password_chars_arr value=","|explode:$client_info.settings.required_password_chars}
          <div>
            <input type="checkbox" name="required_password_chars[]" value="uppercase" id="rpc1"
              {if "uppercase"|in_array:$required_password_chars_arr}checked="checked"{/if} />
              <label for="rpc1">{$LANG.phrase_one_char_upper}</label>
          </div>
          <div>
            <input type="checkbox" name="required_password_chars[]" value="number" id="rpc2"
              {if "number"|in_array:$required_password_chars_arr}checked="checked"{/if} />
              <label for="rpc2">{$LANG.phrase_one_char_number}</label>
          </div>
          <div>
            <input type="checkbox" name="required_password_chars[]" value="special_char" id="rpc3"
              {if "special_char"|in_array:$required_password_chars_arr}checked="checked"{/if} />
              <label for="rpc3">{$phrase_one_special_char}</label>
          </div>
        </td>
        <td>
        </td>
      </tr>
      <tr>
        <td class="pad_left_small">{$LANG.phrase_prevent_password_reuse}</td>
        <td>
          <select name="num_password_history">
            <option value=""   {if $client_info.settings.num_password_history == ""}selected{/if}>{$LANG.word_na}</option>
            <option value="1"  {if $client_info.settings.num_password_history == "1"}selected{/if}>1</option>
            <option value="2"  {if $client_info.settings.num_password_history == "2"}selected{/if}>2</option>
            <option value="3"  {if $client_info.settings.num_password_history == "3"}selected{/if}>3</option>
            <option value="4"  {if $client_info.settings.num_password_history == "4"}selected{/if}>4</option>
            <option value="5"  {if $client_info.settings.num_password_history == "5"}selected{/if}>5</option>
            <option value="6"  {if $client_info.settings.num_password_history == "6"}selected{/if}>6</option>
            <option value="7"  {if $client_info.settings.num_password_history == "7"}selected{/if}>7</option>
            <option value="8"  {if $client_info.settings.num_password_history == "8"}selected{/if}>8</option>
            <option value="9"  {if $client_info.settings.num_password_history == "9"}selected{/if}>9</option>
            <option value="10" {if $client_info.settings.num_password_history == "10"}selected{/if}>10</option>
          </select>
        </td>
        <td></td>
      </tr>
      </table>

      {template_hook location="admin_edit_client_settings_bottom"}

      <p>
        <input type="submit" name="update_client" value="{$LANG.word_update}" />
      </p>

    </form>
