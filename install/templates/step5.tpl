{include file="../../install/templates/install_header.tpl"}

  <h2>{$LANG.phrase_create_admin_account}</h2>

  {include file='messages.tpl'}

  {if !$account_created}

    <form name="create_account_form" action="{$same_page}" method="post" onsubmit="return rsv.validate(this, rules)">
	    <div class="margin_bottom_large">
	      {$LANG.text_create_admin_account}
	    </div>

	    <table cellpadding="0">
	    <tr>
	      <td width="160">{$LANG.phrase_first_name}</td>
	      <td class="answer"><input type="text" name="first_name" value="" style="width:200px" /></td>
	    </tr>
	    <tr>
	      <td>{$LANG.phrase_last_name}</td>
	      <td class="answer"><input type="text" name="last_name" value="" style="width:200px" /></td>
	    </tr>
	    <tr>
	      <td>{$LANG.word_email}</td>
	      <td class="answer"><input type="text" name="email" value="" style="width:200px" /></td>
	    </tr>
	    <tr>
	      <td>{$LANG.phrase_login_username}</td>
	      <td class="answer"><input type="text" name="username" value="" style="width:140px" /></td>
	    </tr>
	    <tr>
	      <td>{$LANG.phrase_login_password}</td>
	      <td class="answer"><input type="password" name="password" value="" style="width:140px" /></td>
	    </tr>
	    <tr>
	      <td>{$LANG.phrase_re_enter_password}</td>
	      <td class="answer"><input type="password" name="password_2" value="" style="width:140px" /></td>
	    </tr>
	    </table>

	    <p>
	      <input type="submit" name="add_account" value="{$LANG.phrase_create_account}" />
	    </p>

	  </form>

    <script>
    document.create_account_form.first_name.focus();
    </script>

	{/if}

{include file="../../install/templates/install_footer.tpl"}