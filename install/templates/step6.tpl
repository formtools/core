{include file="../../install/templates/install_header.tpl"}

  <h2>{$LANG.phrase_clean_up}</h2>

  <p class="notify">
    {$LANG.text_ft_installed} {$LANG.text_must_delete_install_folder}
  </p>

  <form action="{$g_root_url}" method="post">
    <input type="submit" value="{$LANG.text_log_in_to_ft}" />
  </form>

  <div class="divider"></div>

  <p><b>{$LANG.phrase_getting_started|ucwords}</b></p>
  <ul>
    <li><a href="http://docs.formtools.org/tutorials/adding_first_form/">{$LANG.text_tutorial_adding_first_form}</a></li>
    <li><a href="http://docs.formtools.org/userdoc2_1/">{$LANG.text_review_user_doc}</a></li>
  </ul>

{include file="../../install/templates/install_footer.tpl"}