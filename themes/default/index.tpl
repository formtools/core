{include file="header.tpl"}

  <div class="title">{$login_heading|upper}</div>

  <div style="width:540px">
    {ft_include file="messages.tpl"}
  </div>

  <div class="margin_bottom_large" style="width: 540px">
    {$text_login}
  </div>

  <form name="login" action="{$same_page}{$query_params}" method="post">

    {if $upgrade_notification}
      <div class="notify" id="upgrade_notification">

        <div style="padding:8px">
          <span style="float: right; padding-left: 5px;"><a href="#" onclick="return ft.hide_message('upgrade_notification')">X</a></span>
          {$upgrade_notification}
        </div>
      </div>

      <br />
    {/if}

    <table width="340" cellpadding="1" class="login_outer_table">
    <tr>
      <td colspan="1">

        <table width="100%" cellpadding="0" cellspacing="1" class="login_inner_table">
        <tr>
          <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
          <td>

            <table width="200" cellpadding="0" cellspacing="1">
            <tr>
              <td class="login_table_text">{$LANG.word_username}</td>
              <td><input type="text" size="25" name="username" value="{$username}"></td>
            </tr>
            <tr>
              <td class="login_table_text">{$LANG.word_password}</td>
              <td><input type="password" size="25" name="password" value=""></td>
            </tr>
            </table>

          </td>
          <td align="center" valign="center">

            <script type="text/javascript">
            document.write('<input type="submit" value="{$LANG.phrase_log_in|upper}">&nbsp;');
            </script>

          </td>
        </tr>
        <tr>
          <td colspan="2">&nbsp;</td>
        </tr>
        </table>

      </td>
    </tr>

    {if $error}
    <tr>
      <td colspan="3">
        <div class="login_error pad_left">{$error}</div>
      </td>
    </tr>
    {/if}

    </table>

  </form>

  <noscript>
    <div class="error" style="padding:6px;">
    {$LANG.text_js_required}
    </div>
  </noscript>

{include file="footer.tpl"}