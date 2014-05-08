{include file="../../install/templates/install_header.tpl"}

  <h1>{$LANG.phrase_create_config_file}</h1>

  {if $config_file_generated == ""}

    <p>
      {$LANG.text_install_create_config_file}
    </p>

    <textarea name="content" style="width:100%; height:240px;" readonly>{$config_file}</textarea>

	  <form name="display_config_content_form" action="{$same_page}" method="post">
	    <p>
	      <input type="submit" name="generate_file" value="{$LANG.phrase_create_file}" />
	    </p>
	  </form>

  {elseif $config_file_generated == true}

    <p class="notify">
      {$LANG.text_config_file_created}
    </p>

    <form action="step5.php" method="post">
	    <p>
	      <input type="submit" name="next" value="{$LANG.word_continue_rightarrow}" />
	    </p>
	  </form>

  {elseif $config_file_generated == false}

	  <p>
	    {$LANG.text_config_file_not_created}
    </p>
    <p>
      {$LANG.text_config_file_not_created_instructions}
    </p>

	  <form name="display_config_content_form" action="{$same_page}" method="post">
	    <textarea name="content" style="width:100%; height:240px;">{$config_file}</textarea>

	    <p>
	    	<input type="submit" name="check_config_contents" value="{$LANG.word_continue_rightarrow}" />
	    </p>
	  </form>

  {/if}

{include file="../../install/templates/install_footer.tpl"}