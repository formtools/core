import React from 'react';
import { withRouter } from 'react-router-dom';
import { generalUtils } from '../../utils';

const showResult = (passed, i18n) => {
	let className = 'red';
	let label = i18n.word_fail;
	if (passed) {
		className = 'green';
		label = i18n.word_fail;
	}
	return (
		<span className={className}>{label}</span>
	);
};


const Step2 = ({ i18n, isLoading, history, results }) => {
	const onSubmit = (e) => {
		e.preventDefault();
		history.push('/step3');
	};

	const submitBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

	if (isLoading) {
		return "loading...";
	}

	return (
		<form method="post" onSubmit={onSubmit}>
			<h2>{i18n.phrase_system_check}</h2>

			<div className="margin_bottom_large">
				{i18n.text_install_system_check}
			</div>

			<table cellSpacing="0" cellPadding="2" width="600" className="info">
				<tbody>
					<tr>
						<td width="220">{i18n.phrase_php_version}</td>
						<td className="bold">{results.phpVersion}</td>
						<td width="100" align="center">
							{showResult(results.hasValidPhpVersion, i18n)}
						</td>
					</tr>
					<tr>
						<td>PDO available</td>
						<td className="bold">
							{results.pdo_available ? i18n.word_yes : i18n.word_no}
						</td>
						<td align="center">
							{showResult(results.pdo_available, i18n)}
						</td>
					</tr>
					<tr>
						<td>MySQL available</td>
						<td className="bold">{$LANG.word_yes}</td>
						<td align="center">
							{showResult(results.pdo_mysql_available, i18n)}
						</td>
					</tr>
					<tr>
						<td>PHP Sessions</td>
						<td className="bold">
							{results.sessions_loaded === 1 ? i18n.word_available : i18n.phrase_not_available}
						</td>
						<td width="100" align="center">
							{showResult(results.sessions_loaded === 1, i18n)}
						</td>
					</tr>
					<tr>
						<td>{$LANG.phrase_upload_folder}</td>
						<td className="bold">/upload/</td>
						<td align="center">
							{showResult(results.upload_folder_writable, i18n)}
						</td>
					</tr>
					<tr>
						<td>{$LANG.phrase_cache_folder}</td>
						<td className="bold">{$cache_folder}</td>
						<td align="center">
							{showResult(results.cache_dir_writable, i18n)}
						</td>
					</tr>
					<tr>
						<td className="cache_folder_writable_block">
							&#8212;
							<input type="checkbox" id="use_custom_cache_folder" name="use_custom_cache_folder"
							       checked={use_custom_cache_folder} />
							<label htmlFor="use_custom_cache_folder">{i18n.phrase_use_custom_cache_folder}</label>
						</td>
						<td colSpan="2">
							<div id="cache_folder_custom"
							     style={{ display: use_custom_cache_folder ? 'block' : 'none'}}>
								<input type="text" name="custom_cache_folder" value={custom_cache_folder} style={{ width: '100%' }} />
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
	);
};

							/*
{if !$valid_php_version || !$pdo_available || !$sessions_loaded}
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
*/

export default withRouter(Step2);
