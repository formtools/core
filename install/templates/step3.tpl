{include file="../../install/templates/install_header.tpl"}

  {if $tables_already_exist}

    <h2>Tables already exist!</h2>

    {include file='messages.tpl'}

    <div class="error margin_bottom_large">
      <div style="padding: 6px">
        <b>Warning!</b> It appears that some tables already exist with the table prefix that you specified
        (see list below). You can either choose to overwrite these tables or pick a new table prefix.
      </div>
    </div>

    <div id="existing_tables"><blockquote><pre>
{foreach from=$existing_tables item=table}{$table}
{/foreach}
</pre></blockquote></div>

    <form action="{$same_page}" method="post">
      <p>
        <input type="submit" name="overwrite_tables" value="Overwrite Tables" class="red" />
        <input type="submit" name="pick_new_table_prefix" value="Pick New Table Prefix" />
      </p>
    </form>

  {else}

    <h2>{$LANG.phrase_create_database_tables}</h2>

    {include file='messages.tpl'}

    <div>
      {$LANG.text_install_create_database_tables}
    </div>

    {* here there was a problem, display whatever errors occurred *}
    {if $error != ""}

       <div class="error" style="padding: 5px; margin-top: 8px">
        {$LANG.phrase_error_occurred_c}<br />
        <br />
        <div class="red">{$error}</div>
        <br/>
        {$LANG.phrase_check_db_settings_try_again}
      </div>

      <p><b>{$LANG.word_tips}</b></p>

      <ul class="tips">
        <li><div>{$LANG.text_install_db_tables_error_tip_1}</div></li>
        <li><div>{$LANG.text_install_db_tables_error_tip_2}</div></li>
        <li><div>{$LANG.text_install_db_tables_error_tip_3}</div></li>
        <li><div>{$LANG.text_install_db_tables_error_tip_4}</div></li>
      </ul>

    {/if}

    {include file='messages.tpl'}

    <form name="db_settings_form" action="{$same_page}" method="post" onsubmit="return rsv.validate(this, rules);">

      <p><b>{$LANG.phrase_database_settings}</b></p>

      <table cellpadding="1" cellspacing="0">
      <tr>
        <td class="label" width="140">{$LANG.phrase_database_hostname}</td>
        <td><input type="text" size="20" name="g_db_hostname" value="{$g_db_hostname}" /> {$LANG.phrase_often_localhost}</td>
      </tr>
      <tr>
        <td class="label">{$LANG.phrase_database_name}</td>
        <td><input type="text" size="20" name="g_db_name" value="{$g_db_name}" /></td>
      </tr>
      <tr>
        <td class="label">{$LANG.phrase_database_username}</td>
        <td><input type="text" size="20" name="g_db_username" value="{$g_db_username}" /></td>
      </tr>
      <tr>
        <td class="label">{$LANG.phrase_database_password}</td>
        <td><input type="text" size="20" name="g_db_password" value="{$g_db_password}" /></td>
      </tr>
      <tr>
        <td class="label">{$LANG.phrase_database_table_prefix}</td>
        <td><input type="text" size="20" maxlength="10" name="g_table_prefix" value="{$g_table_prefix}" /></td>
      </tr>
      </table>

      <p>
        <input type="submit" name="create_database" value="{$LANG.phrase_create_database_tables}" />
      </p>
    </form>

    <script type="text/javascript">
    document.db_settings_form.g_db_hostname.focus();
    </script>

  {/if}

{include file="../../install/templates/install_footer.tpl"}
