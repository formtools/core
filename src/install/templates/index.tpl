{include file="../../install/templates/install_header.tpl"}

<h2>{$LANG.word_welcome}</h2>

{include file='messages.tpl'}

<p>
    Welcome to Form Tools! The next few pages installs the script on your server. You can start by choosing the
    primary language for this Form Tools installation.
</p>

<form action="{$same_page}" method="post">
	<table cellspacing="0" cellpadding="0">
		<tr>
			<td width="100" class="label">{$LANG.word_language}</td>
			<td>
				<select name="lang_file" class="margin_right">
					{foreach from=$available_languages item=row}
						<option value="{$row->code}" {if $lang == $row->code}selected{/if}>{$row->lang}</option>
					{/foreach}
				</select>
			</td>
			<td>
				<input type="submit" name="select_language"	 value="{$LANG.word_select}" />
			</td>
		</tr>
	</table>

	<p>
		<input type="submit" name="next" value="{$LANG.word_continue_rightarrow}" />
	</p>
</form>

{include file="../../install/templates/install_footer.tpl"}
