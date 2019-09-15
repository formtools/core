import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { generalUtils } from '../../utils';
import styles from '../Page/Page.scss';
import Button from '../../components/Buttons';
import { NotificationPanel } from '../../components';


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
		this.onSubmit = this.onSubmit.bind(this);
		this.onSuccess = this.onSuccess.bind(this);
		this.onError = this.onError.bind(this);

		this.notificationPanel = React.createRef();
	}

	onSubmit (e) {
		e.preventDefault();

		// update the server
		this.props.saveCacheFolderSetting(this.onSuccess, this.onError)
	}

	onSuccess () {
		this.props.history.push('/step3');
	}

	onError (errorCode) {
		const { i18n } = this.props;

		if (errorCode === 'invalid_folder') {
			this.notificationPanel.current.add({ msg: i18n.text_invalid_cache_folder, msgType: 'error' });
		} else if (errorCode === 'invalid_folder_permissions') {
			this.notificationPanel.current.add({ msg: i18n.text_cache_folder_invalid_permissions, msgType: 'error' });
		}
	}

	getResultsSection () {
		const { results, i18n } = this.props;
		const submitBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

		if (!results.validPhpVersion || !results.pdoAvailable || results.sessionsLoaded) {
			if (!results.validPhpVersion || !results.pdoAvailable || !results.pdoMysqlAvailable || !results.sessionsLoaded) {
				return (
					<p className="error" style={{ padding: 6 }}>
						{i18n.text_install_form_tools_server_not_supported}
					</p>
				);
			} else if (!results.uploadFolderWritable || !results.cacheDirWritable) {
				return (
					<p className="error" style={{ padding: 6 }}>
						{i18n.text_required_folders_need_write_permissions}
					</p>
				);
			} else if (results.suhosinLoaded) {
				return (
					<div className="warning">
						{i18n.notify_suhosin_installed}
					</div>
				);
			}
		}

		return (
			<p>
				<Button type="submit">{submitBtnLabel}</Button>
			</p>
		);
	}

	getCustomCacheFolderField () {
		const { customCacheFolder, useCustomCacheFolder, updateCustomCacheFolder } = this.props;

		if (useCustomCacheFolder) {
			return (
				<input type="text" value={customCacheFolder} onChange={(e) => updateCustomCacheFolder(e.target.value)}
				       style={{ width: '100%' }} />
			);
		}
		return null;
	}

	render () {
		const { i18n, loading, results, useCustomCacheFolder, toggleCustomCacheFolder } = this.props;

		if (loading || results === null) {
			return null;
		}

		return (
			<form method="post" onSubmit={this.onSubmit}>
				<h2>{i18n.phrase_system_check}</h2>

				<p dangerouslySetInnerHTML={{ __html: i18n.text_install_system_check }} />

				<NotificationPanel ref={this.notificationPanel} />

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
							{results.sessionsLoaded ? i18n.word_available : i18n.phrase_not_available}
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
						<td>{i18n.phrase_cache_folder}</td>
						<td className={styles.bold}>/cache/</td>
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
