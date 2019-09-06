import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { generalUtils } from '../../utils';
import styles from '../Page/Page.scss';

const showResult = (passed, i18n) => {
	let className = styles.red;
	let label = i18n.word_fail;
	if (passed) {
		className = styles.green;
		label = i18n.word_pass;
	}
	return (
		<span className={className}>{label.toUpperCase()}</span>
	);
};


class Step2 extends Component {
	constructor(props) {
		super(props);
	}

	componentWillMount () {
		this.props.getSystemCheckResults();
	}

	onSubmit (e) {
		e.preventDefault();
		this.props.history.push('/step3');
	}

	getResultsSection () {
		const { results, i18n } = this.props;
		const submitBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

		if (!results.validPhpVersion || !results.pdoAvailable || results.sessionsLoaded) {
			if (!results.validPhpVersion || !results.pdoAvailable || !results.pdoMysqlAvailable || !results.sessionsLoaded) {
				return (
					<p className="error" style="padding: 6px">
						{i18n.text_install_form_tools_server_not_supported}
					</p>
				);
			} else if (!results.uploadFolderWritable || !results.cacheDirWritable) {
				return (
					<p className="error" style="padding: 6px">
						{$text_required_folders_need_write_permissions}
					</p>
				);
			} else if (results.suhosinLoaded) {
				return (
					<div className="warning">
						{$LANG.notify_suhosin_installed}
					</div>
				);
			}
		}

		return (
			<p>
				<input type="submit" name="next" id="next" value={submitBtnLabel} />
			</p>
		);
	}

	getCustomCacheFolderField () {
		const { i18n, results, customCacheFolder, useCustomCacheFolder, updateCustomCacheFolder } = this.props;

		console.log(customCacheFolder);

		if (useCustomCacheFolder) {
			return (
				<input type="text" value={customCacheFolder} onChange={(e) => updateCustomCacheFolder(e.target.value)}
				       style={{ width: '100%' }} />
			);
		}
		return null;
	}

	render () {
		const { i18n, isLoading, results, useCustomCacheFolder, toggleCustomCacheFolder } = this.props;

		// TODO
		if (isLoading || results === null) {
			return "loading...";
		}

		return (
			<form method="post" onSubmit={this.onSubmit}>
				<h2>{i18n.phrase_system_check}</h2>

				<p>
					{i18n.text_install_system_check}
				</p>

				<table cellSpacing="0" cellPadding="2" width="600" className={styles.info}>
					<tbody>
					<tr>
						<td width="220">{i18n.phrase_php_version}</td>
						<td className={styles.bold}>{results.phpVersion}</td>
						<td width="100" align="center">
							{showResult(results.validPhpVersion, i18n)}
						</td>
					</tr>
					<tr>
						<td>PDO available</td>
						<td className={styles.bold}>
							{results.pdoAvailable ? i18n.word_yes : i18n.word_no}
						</td>
						<td align="center">
							{showResult(results.pdoAvailable, i18n)}
						</td>
					</tr>
					<tr>
						<td>MySQL available</td>
						<td className={styles.bold}>{i18n.word_yes}</td>
						<td align="center">
							{showResult(results.pdoMysqlAvailable, i18n)}
						</td>
					</tr>
					<tr>
						<td>PHP Sessions</td>
						<td className={styles.bold}>
							{results.sessions_loaded === 1 ? i18n.word_available : i18n.phrase_not_available}
						</td>
						<td width="100" align="center">
							{showResult(results.sessionsLoaded, i18n)}
						</td>
					</tr>
					<tr>
						<td>{i18n.phrase_upload_folder}</td>
						<td className={styles.bold}>/upload/</td>
						<td align="center">
							{showResult(results.uploadFolderWritable, i18n)}
						</td>
					</tr>
					<tr>
						<td>{i18n.phraseCacheFolder}</td>
						<td className={styles.bold}>{results.cacheFolder}</td>
						<td align="center">
							{showResult(results.cacheDirWritable, i18n)}
						</td>
					</tr>
					<tr>
						<td>
							&#8212;
							<input type="checkbox" id="useCustomCacheFolder"
							       checked={useCustomCacheFolder} onChange={toggleCustomCacheFolder} />
							<label htmlFor="useCustomCacheFolder">{i18n.phrase_use_custom_cache_folder}</label>
						</td>
						<td colSpan="2">
							{this.getCustomCacheFolderField()}
						</td>
					</tr>
					</tbody>
				</table>

				{this.getResultsSection()}
			</form>
		);
	}
};


export default withRouter(Step2);
