{include file="../../install/templates/install_header.tpl"}

<h2>{$LANG.phrase_system_check}</h2>

<p>
	{$LANG.text_install_system_check}
</p>

<table cellspacing="0" cellpadding="2" width="600" class="info">
	<tr>
		<td width="220">{$LANG.phrase_php_version}</td>
		<td class="bold">{$phpversion}</td>
		<td width="100" align="center">
			{if $valid_php_version}
				<span class="green">{$LANG.word_pass|upper}</span>
			{else}
				<span class="red">{$LANG.word_fail|upper}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td valign="top">PDO available</td>
		<td valign="top" class="bold">
            {if $pdo_available}
				{$LANG.word_yes}
			{else}
                {$LANG.word_no}
			{/if}
		</td>
		<td valign="top" align="center">
            {if $pdo_available}
				<span class="green">{$LANG.word_pass|upper}</span>
            {else}
				<span class="red">{$LANG.word_fail|upper}</span>
            {/if}
		</td>
	</tr>
	<tr>
		<td valign="top">MySQL available</td>
		<td valign="top" class="bold">{$LANG.word_yes}</td>
		<td valign="top" align="center">
            {if $pdo_mysql_available}
				<span class="green">{$LANG.word_pass|upper}</span>
			{else}
				<span class="red">{$LANG.word_fail|upper}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td>PHP Sessions</td>
		<td class="bold">
			{if $sessions_loaded == 1}
				Available
			{else}
				Not Available
			{/if}
		</td>
		<td width="100" align="center">
			{if $sessions_loaded == 1}
				<span class="green">{$LANG.word_pass|upper}</span>
			{else}
				<span class="red">{$LANG.word_fail|upper}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td rowspan="2" valign="top">{$LANG.phrase_write_permissions}</td>
		<td class="bold">
			/upload/
		</td>
		<td align="center">
			{if $upload_folder_writable}
				<span class="green">{$LANG.word_pass|upper}</span>
			{else}
				<span class="red">{$LANG.word_fail|upper}</span>
			{/if}
		</td>
	</tr>
	<tr>
		<td class="bold">
			/themes/{$g_default_theme}/cache/
		</td>
		<td align="center">
			{if $default_theme_cache_dir_writable}
				<span class="green">{$LANG.word_pass|upper}</span>
			{else}
				<span class="red">{$LANG.word_fail|upper}</span>
			{/if}
		</td>
	</tr>
</table>

{if !$valid_php_version || !$pdo_available || !$pdo_mysql_available || !$sessions_loaded}

	<p class="error" style="padding: 6px">
		{$LANG.text_install_form_tools_server_not_supported}
	</p>

{elseif !$upload_folder_writable || !$default_theme_cache_dir_writable}

	<p class="error" style="padding: 6px">
        Please ensure the required folders have write permissions. See the
		<a href="https://docs.formtools.org/installation/step2/">help documentation</a> for further information.
	</p>

{else}

	<form action="step3.php" method="post">

		{if $suhosin_loaded}
			<div class="warning">
				{$LANG.notify_suhosin_installed}
			</div>
		{/if}

		<div id="continue_block">
			<p>
				<input type="submit" name="next" value="{$LANG.word_continue_rightarrow}" />
			</p>
		</div>

	</form>
{/if}

{include file="../../install/templates/install_footer.tpl"}
