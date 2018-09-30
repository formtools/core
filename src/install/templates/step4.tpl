{include file="../../install/templates/install_header.tpl"}

<h2>{$LANG.phrase_create_config_file}</h2>

{include file='messages.tpl'}

{if $config_file_generated === ""}

	<div class="margin_bottom_large">
		{$LANG.text_install_create_config_file}
	</div>

	<textarea name="content" class="config_file_contents" id="cfc" readonly>{$config_file}</textarea>
    <script>
      new CodeMirror.fromTextArea(document.getElementById("cfc"), {literal}{{/literal}
        mode: "php"
          {literal}});{/literal}
    </script>

	<form name="display_config_content_form" action="{$same_page}" method="post">
		<p>
			<input type="submit" name="generate_file" value="{$LANG.phrase_create_file}" />
		</p>
	</form>

{elseif $config_file_generated === true}

	<div class="margin_bottom_large notify">
		{$LANG.text_config_file_created}
	</div>

	<form action="step5.php" method="post">
		<p>
			<input type="submit" name="next" value="{$LANG.word_continue_rightarrow}" />
		</p>
	</form>

{elseif $config_file_generated === false}

	<div class="margin_bottom_large notify">
		{$LANG.text_config_file_not_created}
	</div>
	<p>
		{$LANG.text_config_file_not_created_instructions}
	</p>

	<form name="display_config_content_form" action="{$same_page}" method="post">
		<textarea name="content" class="config_file_contents" id="cfc_not_generated">{$config_file}</textarea>
        <script>
        new CodeMirror.fromTextArea(document.getElementById("cfc_not_generated"), {literal}{{/literal}
            mode: "php"
        {literal}});{/literal}
        </script>
		<p>
			<input type="submit" name="check_config_contents" value="{$LANG.word_continue_rightarrow}" />
		</p>
	</form>

{/if}

{include file="../../install/templates/install_footer.tpl"}
