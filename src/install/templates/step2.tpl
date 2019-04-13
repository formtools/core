{include file="../../install/templates/install_header.tpl"}

<h2>{$LANG.phrase_system_check}</h2>

{include file='messages.tpl'}

<div class="margin_bottom_large">
	{$LANG.text_install_system_check}
</div>

<form action="step2.php" method="post">

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
		<td>PDO available</td>
		<td class="bold">
            {if $pdo_available}
				{$LANG.word_yes}
			{else}
                {$LANG.word_no}
			{/if}
		</td>
		<td align="center">
            {if $pdo_available}
				<span class="green">{$LANG.word_pass|upper}</span>
            {else}
				<span class="red">{$LANG.word_fail|upper}</span>
            {/if}
		</td>
	</tr>
	<tr>
		<td>MySQL available</td>
		<td class="bold">{$LANG.word_yes}</td>
		<td align="center">
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
				{$LANG.word_available}
			{else}
                {$LANG.phrase_not_available}
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
		<td>{$LANG.phrase_upload_folder}</td>
        <td class="bold">/upload/</td>
		<td align="center">
			{if $upload_folder_writable}
				<span class="green">{$LANG.word_pass|upper}</span>
			{else}
				<span class="red">{$LANG.word_fail|upper}</span>
			{/if}
		</td>
	</tr>
	<tr>
        <td>{$LANG.phrase_cache_folder}</td>
        <td class="bold">{$cache_folder}</td>
		<td align="center">
            {if $cache_dir_writable}
                <span class="green">{$LANG.word_pass|upper}</span>
            {else}
                <span class="red">{$LANG.word_fail|upper}</span>
            {/if}
        </td>
    </tr>
    <tr>
        <td class="cache_folder_writable_block">
            &#8212;
                <input type="checkbox" id="use_custom_cache_folder" name="use_custom_cache_folder"
                {if $use_custom_cache_folder}checked="checked"{/if}/>
                <label for="use_custom_cache_folder">{$LANG.phrase_use_custom_cache_folder}</label>
        </td>
        <td colspan="2">
            <div id="cache_folder_custom" {if !$use_custom_cache_folder}style="display:none"{/if}>
                <input type="text" name="custom_cache_folder" value="{$custom_cache_folder}" style="width: 100%;" />
            </div>
        </td>
    </tr>
</table>

{if !$valid_php_version || !$pdo_available || !$pdo_mysql_available || !$sessions_loaded}

	<p class="error" style="padding: 6px">
		{$LANG.text_install_form_tools_server_not_supported}
	</p>

{elseif !$upload_folder_writable || !$cache_dir_writable}

	<p class="error" style="padding: 6px">
        {$text_required_folders_need_write_permissions}
	</p>

{else}

    {if $suhosin_loaded}
        <div class="warning">
            {$LANG.notify_suhosin_installed}
        </div>
    {/if}

    <div id="continue_block">
        <p>
            <input type="submit" name="next" id="next" value="{$LANG.word_continue_rightarrow}" />
        </p>
    </div>

{/if}

</form>

{include file="../../install/templates/install_footer.tpl"}
