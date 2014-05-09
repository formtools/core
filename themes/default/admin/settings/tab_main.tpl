  <div class="subtitle underline margin_top_large">{$LANG.word_settings|upper}</div>

  {ft_include file='messages.tpl'}

  <form action="{$same_page}" method="post" onsubmit="return rsv.validate(this, rules)">
    <input type="hidden" name="page" value="main" />

    <table class="list_table" cellpadding="0" cellspacing="1">
    <tr>
      <td class="pad_left_small" width="200">{$LANG.phrase_core_version}</td>
      <td class="pad_left_small">
        {if $settings.release_type == "alpha"}
          <span>{$settings.program_version}-alpha-{$settings.release_date}</span>
        {elseif $settings.release_type == "beta"}
          <span>{$settings.program_version}-beta-{$settings.release_date}</span>
        {else}
          <span>{$settings.program_version}</span>
        {/if}
      </td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_api_version}</td>
      <td class="pad_left_small">
        {$settings.api_version|default:"<span class=\"light_grey\">`$LANG.notify_no_api_installed`</span>"}
      </td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_program_name}</td>
      <td><input type="text" name="program_name" value="{$settings.program_name}" style="width: 98%" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_logo_link_url}</td>
      <td><input type="text" name="logo_link" value="{$settings.logo_link}" style="width: 98%" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_num_clients_per_page}</td>
      <td><input type="text" name="num_clients_per_page" value="{$settings.num_clients_per_page}" style="width: 30px" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_num_emails_per_page}</td>
      <td><input type="text" name="num_emails_per_page" value="{$settings.num_emails_per_page}" style="width: 30px" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_num_forms_per_page}</td>
      <td><input type="text" name="num_forms_per_page" value="{$settings.num_forms_per_page}" style="width: 30px" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_num_option_lists_per_page}</td>
      <td><input type="text" name="num_option_lists_per_page" value="{$settings.num_option_lists_per_page}" style="width: 30px" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_num_menus_per_page}</td>
      <td><input type="text" name="num_menus_per_page" value="{$settings.num_menus_per_page}" style="width: 30px" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_num_modules_per_page}</td>
      <td><input type="text" name="num_modules_per_page" value="{$settings.num_modules_per_page}" style="width: 30px" /></td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_default_date_field_search_value}</td>
      <td>
        <select name="default_date_field_search_value">
          <option value="none" {if $settings.default_date_field_search_value == "none"}selected{/if}>{$LANG.word_none}</option>
          <option value="today" {if $settings.default_date_field_search_value == "today"}selected{/if}>{$LANG.word_today}</option>
          <option value="last_7_days" {if $settings.default_date_field_search_value == "last_7_days"}selected{/if}>{$LANG.phrase_last_7_days}</option>
          <option value="month_to_date" {if $settings.default_date_field_search_value == "month_to_date"}selected{/if}>{$LANG.phrase_month_to_date}</option>
          <option value="year_to_date" {if $settings.default_date_field_search_value == "year_to_date"}selected{/if}>{$LANG.phrase_year_to_date}</option>
          <option value="previous_month" {if $settings.default_date_field_search_value == "previous_month"}selected{/if}>{$LANG.phrase_the_previous_month}</option>
        </select>
      </td>
    </tr>
    {template_hook location="admin_settings_main_tab_bottom"}
    </table>

    <p>
      <input type="submit" name="update_main" value="{$LANG.word_update}" />
    </p>

  </form>

