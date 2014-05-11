{include file="../../install/templates/install_header.tpl"}

  <h2>{$LANG.phrase_system_check}</h2>

  <p>
    {$LANG.text_install_system_check}
  </p>

  <table cellspacing="0" cellpadding="2" width="600" class="info">
  <tr>
    <td width="220">{$LANG.phrase_php_version}</td>
    <td class="bold">{$phpversion}</td>
    <td width="100" align="center">
      {if $valid_php_version}
        <span class="green">{$LANG.word_pass|upper}</span>
      {else}
        <span class="red">{$LANG.word_fail|upper}</span>
      {/if}
    </td>
  </tr>
  {if $mysql_loaded}
  <tr>
    <td valign="top">{$LANG.phrase_mysql_version}</td>
    <td valign="top" class="bold">{$mysql_get_client_info}</td>
    <td valign="top" align="center">
      {if $overridden_invalid_db_version}
        <span class="orange">{$LANG.word_overridden|upper}</span>
      {else}
        {if $valid_mysql_version}
          <span class="green">{$LANG.word_pass|upper}</span>
        {else}
          <span class="red">{$LANG.word_fail|upper}</span>
          <form action="step2.php" method="post">
            <input type="submit" name="override_invalid_db_version" value="{$LANG.word_ignore}" />
          </form>
        {/if}
      {/if}
    </td>
  </tr>
  {else}
  <tr>
    <td>{$LANG.phrase_mysql_version}</td>
    <td class="bold red">MySQL extension not available</td>
    <td width="100" align="center">
      <span class="red">{$LANG.word_fail|upper}</span>
    </td>
  </tr>
  {/if}
  <tr>
    <td>PHP Sessions</td>
    <td class="bold">
      {if $sessions_loaded == 1}
        Available
      {else}
        Not Available
      {/if}
    </td>
    <td width="100" align="center">{$sessions_enabled}
      {if $sessions_loaded == 1}
        <span class="green">{$LANG.word_pass|upper}</span>
      {else}
        <span class="red">{$LANG.word_fail|upper}</span>
      {/if}
    </td>
  </tr>
  <tr>
    <td rowspan="2" valign="top">{$LANG.phrase_write_permissions}</td>
    <td class="bold">
      /upload/
    </td>
    <td align="center">
      {if $upload_folder_writable}
        <span class="green">{$LANG.word_pass|upper}</span>
      {else}
        <span class="red">{$LANG.word_fail|upper}</span>
      {/if}
    </td>
  </tr>
  <tr>
    <td class="bold">
      /themes/{$g_default_theme}/cache/
    </td>
    <td align="center">
      {if $default_theme_cache_dir_writable}
        <span class="green">{$LANG.word_pass|upper}</span>
      {else}
        <span class="red">{$LANG.word_fail|upper}</span>
      {/if}
    </td>
  </tr>
  <tr>
    <td><a href="http://modules.formtools.org/core_field_types/" target="_blank">{$LANG.phrase_core_field_types}</a> module available?</td>
    <td class="bold">
      {if $core_field_types_module_available}
        {$LANG.word_yes}
      {else}
        {$LANG.word_no}
      {/if}
    </td>
    <td align="center">
      {if $core_field_types_module_available}
        <span class="green">{$LANG.word_pass|upper}</span>
      {else}
        <span class="red">{$LANG.word_fail|upper}</span>
      {/if}
    </td>
  </tr>
  </table>

  {if !$valid_php_version || !$mysql_loaded || !$valid_mysql_version || !$sessions_loaded || !$core_field_types_module_available}

    <p class="error" style="padding: 6px">
      {$LANG.text_install_form_tools_server_not_supported}
    </p>

  {else}

    {if $suhosin_loaded}
      <div class="warning">
        {$LANG.notify_suhosin_installed}
      </div>
    {/if}

    <form action="step3.php" method="post">
      <p>
        <input type="submit" name="next" value="{$LANG.word_continue_rightarrow}" />
      </p>
    </form>

  {/if}

{include file="../../install/templates/install_footer.tpl"}
