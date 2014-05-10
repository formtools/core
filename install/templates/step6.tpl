{include file="../../install/templates/install_header.tpl"}

  <h1>{$LANG.phrase_clean_up}</h1>

  <p class="notify">
    {$LANG.text_ft_installed}
  </p>

 	<p>
 	  {$LANG.text_must_delete_install_folder}
  </p>

  <ul>
    <li><a href="{$g_root_url}">{$LANG.text_log_in_to_ft}</a></li>
    <li><a href="http://docs.formtools.org/tutorials/adding_first_form/">{$LANG.text_tutorial_adding_first_form}</a></li>
    <li><a href="http://docs.formtools.org/userdoc2_1/">{$LANG.text_review_user_doc}</a></li>
  </ul>

{include file="../../install/templates/install_footer.tpl"}