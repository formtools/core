{ft_include file="header.tpl"}

  <div class="title">{$LANG.phrase_forgot_password}</div>

  {ft_include file='messages.tpl'}

  <div class="margin_bottom_large" style="width: 540px">
    {$text_forgot_password}
  </div>

  <form name="forget_password" action="{$same_page}{$g_query_params}" method="post"
    onsubmit="return rsv.validate(this, rules)">

    <div class="login_panel">
      <div class="login_panel_inner">
        <table cellpadding="0" cellspacing="1">
        <tr>
          <td>{$LANG.word_username}</td>
          <td><input type="text" name="username" value="{$username}" /></td>
          <td><input type="submit" value="{$LANG.word_email|upper}" class="margin_left_large margin_right_large" /></td>
        </tr>
        </table>
        <div class="clear"></div>
      </div>
    </div>
  </form>

  <p>
    <a href="index.php{$query_params}">{$LANG.phrase_login_panel_leftarrows}</a>
  </p>

  <noscript>
    <div class="error" style="padding:6px;">
      {$LANG.text_js_required}
    </div>
  </noscript>

{ft_include file="footer.tpl"}