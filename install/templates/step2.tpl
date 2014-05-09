{include file="../../install/templates/install_header.tpl"}

  <h1>{$LANG.phrase_system_check}</h1>

  <p>
    {$LANG.text_install_system_check}
  </p>

  <table cellspacing="0" cellpadding="2" width="600" class="info">
  <tr>
    <td width="160">{$LANG.phrase_php_version}</td>
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
    <td>{$LANG.phrase_mysql_version}</td>
    <td class="bold">{$mysql_get_client_info}</td>
    <td align="center">
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
  </table>

  <br />

  {if !$valid_php_version || !$mysql_loaded || !$valid_mysql_version}

    <p class="error" style="padding: 6px">
      {$LANG.text_install_form_tools_server_not_supported}
    </p>

  {else}

    <form action="step3.php" method="post">
      <p>
        <input type="submit" name="next" value="{$LANG.word_continue_rightarrow}" />
      </p>
    </form>

  {/if}

{include file="../../install/templates/install_footer.tpl"}
