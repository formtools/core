{include file="../../install/templates/install_header.tpl"}

  <h1>{$LANG.word_welcome}</h1>

  <div class="notify">
    {$LANG.text_install_already_upgraded}
  </div>

  <p>
    {$LANG.text_install_intro}
  </p>

  <form action="{$same_page}" method="post">
    <table cellspacing="0" cellpadding="0">
    <tr>
      <td width="100" class="label">{$LANG.word_language}</td>
      <td>
        <select name="lang_file" class="margin_right">
          {foreach from=$available_languages key=k item=v}
            <option value="{$k}" {if $lang_file == $k}selected{/if}>{$v}</option>
          {/foreach}
        </select>
      </td>
      <td>
        <input type="submit" name="select_language" value="{$LANG.word_select}" />
      </td>
    </tr>
    </table>

    <p>
      <input type="submit" name="next" value="{$LANG.word_continue_rightarrow}" />
    </p>

  </form>

{include file="../../install/templates/install_footer.tpl"}