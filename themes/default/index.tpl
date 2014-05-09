{include file="header.tpl"}

  <div class="title">{$login_heading}</div>

  <div style="width:600px">
    {ft_include file="messages.tpl"}
  </div>

  <div class="margin_bottom_large" style="width: 600px">
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

    <div class="login_panel">
      <div class="login_panel_inner">
        <table cellpadding="0" cellspacing="1">
        <tr>
          <td><label for="username">{$LANG.word_username}</label></td>
          <td><input type="text" name="username" id="username" value="{$username}" /></td>
        </tr>
        <tr>
          <td><label for="password">{$LANG.word_password}</label></td>
          <td><input type="password" name="password" id="password" value="" /></td>
        </tr>
        </table>

        <script>
        document.write('<input type="submit" class="login_submit" value="{$LANG.phrase_log_in|upper}">');
        </script>
        <div class="clear"></div>
      </div>

      {if $error}
        <div>
          <div class="login_error pad_left">{$error}</div>
        </div>
      {/if}
    </div>
  </form>

  <noscript>
    <div class="error" style="padding:6px;">
      {$LANG.text_js_required}
    </div>
  </noscript>

{include file="footer.tpl"}