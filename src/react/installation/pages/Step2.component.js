import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { generalUtils } from '../../utils';
import styles from '../Layout/Layout.scss';
import Button from '../../components/Buttons';
import { NotificationPanel, OverflowTip } from '../../components';


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
		const { saveCacheFolderSetting } = this.props;
		saveCacheFolderSetting(this.onSuccess, this.onError);
	}

	onSuccess () {
		this.props.history.push('/step3');
	}

	onError (errorCode) {
		const { i18n } = this.props;

		if (errorCode === 'invalid_folder') {
			this.notificationPanel.current.add({ msg: i18n.text_invalid_cache_folder, msgType: 'error' });
		} else if (errorCode === 'invalid_upload_folder_permissions') {
			this.notificationPanel.current.add({ msg: i18n.text_upload_folder_invalid_permissions, msgType: 'error' });
		} else if (errorCode === 'invalid_custom_cache_folder_permissions') {
			this.notificationPanel.current.add({ msg: i18n.text_custom_cache_folder_invalid_permissions, msgType: 'error' });
		} else if (errorCode === 'invalid_cache_folder_permissions') {
			this.notificationPanel.current.add({ msg: i18n.text_cache_folder_invalid_permissions, msgType: 'error' });
		}
	}

	getResultsSection () {
		const { results, i18n } = this.props;
		let submitBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

		if (!results.defaultCacheFolderWritable || !results.uploadFolderWritable) {
			submitBtnLabel = i18n.word_refresh;
		}

		if (!results.validPhpVersion || !results.pdoAvailable || !results.sessionsLoaded) {
			return null;
		}

		return (
			<p>
				<Button type="submit">{submitBtnLabel}</Button>
			</p>
		);
	}

	getErrorMessage () {
		const { results, i18n } = this.props;
		let msg = null;
		if (!results.validPhpVersion || !results.pdoAvailable || !results.pdoMysqlAvailable || !results.sessionsLoaded) {
			msg = i18n.text_install_form_tools_server_not_supported;
		} else if (!results.uploadFolderWritable || !results.defaultCacheFolderWritable) {
			msg = i18n.text_required_folders_need_write_permissions;
		} else if (results.suhosinLoaded) {
			msg = i18n.notify_suhosin_installed;
		}
		return (msg) ? <NotificationPanel msg={msg} msgType="error" showCloseIcon={false} /> : null;
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

	getCustomCacheFolderRow () {
		const { i18n, systemCheckPassed, useCustomCacheFolder, toggleCustomCacheFolder } = this.props;

		if (systemCheckPassed) {
			return null;
		}

		return (
			<div className={styles.row}>
				<div className={styles.label}>
					&#8212;
					<input type="checkbox" id="useCustomCacheFolder"
						checked={useCustomCacheFolder} onChange={toggleCustomCacheFolder} />
					<label htmlFor="useCustomCacheFolder">{i18n.phrase_use_custom_cache_folder}</label>
				</div>
				<div className={styles.fullValue}>
					{this.getCustomCacheFolderField()}
				</div>
			</div>
		);
	}

	getCacheFolder () {
		const { useCustomCacheFolder, customCacheFolder, systemCheckPassed, defaultCacheFolder } = this.props;
		let folder = defaultCacheFolder;

		if (useCustomCacheFolder && systemCheckPassed) {
			folder = customCacheFolder;
		}

		return (
			<div className={styles.value}>
				<OverflowTip>{folder}</OverflowTip>
			</div>
		);
	}

	render () {
		const { i18n, loading, systemCheckPassed, uploadFolder, results } = this.props;
		if (loading || results === null) {
			return null;
		}

		let text = i18n.text_install_system_check;

		// technically a user could have passed this step, changed the permissions on the folder then returned
		if (!results.uploadFolderWritable || !results.defaultCacheFolderWritable) {
			text = '';
		} else if (systemCheckPassed) {
			text = i18n.text_system_check_passed;
		}

		return (
			<form method="post" onSubmit={this.onSubmit}>
				<h2>{i18n.phrase_system_check}</h2>

				<p dangerouslySetInnerHTML={{ __html: text }} />

				<NotificationPanel ref={this.notificationPanel} />

				<div className={`${styles.table} ${styles.systemCheckTable}`}>
					<div className={styles.row}>
						<div className={styles.label}>{i18n.phrase_php_version}</div>
						<div className={styles.value}>{results.phpVersion}</div>
						<div className={styles.result}>
							{showResult(results.validPhpVersion, i18n)}
						</div>
					</div>
					<div className={styles.row}>
						<div className={styles.label}>{i18n.phrase_pdo_available}</div>
						<div className={styles.value}>
							{results.pdoAvailable ? i18n.word_yes : i18n.word_no}
						</div>
						<div className={styles.result}>
							{showResult(results.pdoAvailable, i18n)}
						</div>
					</div>
					<div className={styles.row}>
						<div className={styles.label}>{i18n.phrase_mysql_available}</div>
						<div className={styles.value}>{i18n.word_yes}</div>
						<div className={styles.result}>
							{showResult(results.pdoMysqlAvailable, i18n)}
						</div>
					</div>
					<div className={styles.row}>
						<div className={styles.label}>{i18n.phrase_php_sessions}</div>
						<div className={styles.value}>
							{results.sessionsLoaded ? i18n.word_available : i18n.phrase_not_available}
						</div>
						<div className={styles.result}>
							{showResult(results.sessionsLoaded, i18n)}
						</div>
					</div>
					<div className={styles.row}>
						<div className={styles.label}>{i18n.phrase_upload_folder}</div>
						<div className={styles.value}>
							<OverflowTip>{uploadFolder}</OverflowTip>
						</div>
						<div className={styles.result}>
							{showResult(results.uploadFolderWritable, i18n)}
						</div>
					</div>
					<div className={styles.row}>
						<div className={styles.label}>{i18n.phrase_cache_folder}</div>
						{this.getCacheFolder()}
						<div className={styles.result}>
							{showResult(results.defaultCacheFolderWritable, i18n)}
						</div>
					</div>
					{this.getCustomCacheFolderRow()}
				</div>

				{this.getErrorMessage()}
				{this.getResultsSection()}
			</form>
		);
	}
}


export default withRouter(Step2);
