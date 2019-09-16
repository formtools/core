import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import Button from '../../components/Buttons';
import { NotificationPanel } from '../../components';
import { generalUtils } from '../../utils';
import styles from '../Page/Page.scss';


class Step3 extends Component {
	constructor (props) {
		super(props);

		this.state = {
			tablesAlreadyExist: false, // temporary error state after an update request.
			existingTables: [],
			dbConnectionError: false
		};

		generalUtils.bindMethods([
			'onSubmit', 'onSuccess', 'onError', 'getTips', 'overwriteTables', 'chooseNewTablePrefix', 'nextPage'
		], this);

		this.notificationPanel = React.createRef();

		// we'll have to determine a better way to validate forms going forward. Formik?
		this.dbHostname = React.createRef();
		this.dbName = React.createRef();
		this.dbPort = React.createRef();
		this.dbUsername = React.createRef();
		this.dbPassword = React.createRef();
		this.dbTablePrefix = React.createRef();
	}

	onSubmit (e) {
		e.preventDefault();
		const { i18n, dbHostname, dbName, dbUsername, dbTablePrefix, saveDbSettings } = this.props;

		const errors = [];
		const fields = [];
		if (!dbHostname) {
			fields.push('dbHostname');
			errors.push(i18n.validation_no_db_hostname);
		}
		if (!dbName) {
			fields.push('dbName');
			errors.push(i18n.validation_no_db_name);
		} else if (/[.\\/\\\\]/.test(dbName)) {
			fields.push('dbName');
			errors.push(i18n.validation_db_name);
		}
		if (!dbUsername) {
			fields.push('dbUsername');
			errors.push(i18n.validation_no_db_username);
		}
		if (!dbTablePrefix) {
			fields.push('dbTablePrefix');
			errors.push(i18n.validation_no_table_prefix);
		} else if (!(/^[0-9a-z_]+$/.test(dbTablePrefix))) {
			fields.push('dbTablePrefix');
			errors.push(i18n.validation_invalid_table_prefix);
		}

		if (errors.length) {
			const error = `${i18n.phrase_error_text_intro}<br />&bull; ` + errors.join('<br />&bull; ');
			this.notificationPanel.current.add({ msg: error, msgType: 'error' });
			this[fields[0]].current.focus();
		} else {
			saveDbSettings(this.onSuccess, this.onError);
		}
	};

	onSuccess () {
		this.setState({ dbConnectionError: false });
		this.nextPage();
	}

	nextPage () {
		this.props.history.push('/step4');
	}

	onError (data) {
		const { i18n } = this.props;
		const { error, response } = data;
		this.notificationPanel.current.clear();

		if (error === 'db_connection_error') {
			let msg = generalUtils.evalI18nString(i18n.notify_install_invalid_db_info, { db_connection_error: response });
			msg += `<br /><br />${i18n.phrase_check_db_settings_try_again}`;

			this.notificationPanel.current.add({ msg, msgType: 'error' });
			this.setState({ dbConnectionError: true });
		} else if (error === 'db_tables_already_exist') {
			this.setState({
				tablesAlreadyExist: true,
				existingTables: data.tables,
				dbConnectionError: false
			});
		}
	}

	chooseNewTablePrefix() {
		this.setState({
			tablesAlreadyExist: false
		}, () => {
			this.dbTablePrefix.current.focus();
		});
	}

	overwriteTables() {
		this.props.saveDbSettings(this.onSuccess, this.onError, true);
	}

	getTablesAlreadyExistContent () {
		const { existingTables } = this.state;
		const { i18n } = this.props;

		return (
			<>
				<h2>{i18n.phrase_tables_already_exist}</h2>

				<p>
					{i18n.text_tables_exist_desc}
				</p>

				<div className={styles.existingTables}>
					<blockquote>
						<pre>
							{existingTables.join('\n')}
						</pre>
					</blockquote>
				</div>

				<p>
					<Button buttonType="danger" onClick={this.overwriteTables}>{i18n.phrase_overwrite_tables}</Button>
					<Button onClick={this.chooseNewTablePrefix}>{i18n.phrase_choose_new_table_prefix}</Button>
				</p>
			</>
		);
	}

	getTips () {
		const { dbConnectionError  } = this.state;
		const { i18n } = this.props;

		if (!dbConnectionError) {
			return null;
		}

		return (
			<>
				<p><b>{i18n.word_tips}</b></p>

				<ul className={styles.tips}>
					<li dangerouslySetInnerHTML={{ __html: i18n.text_install_db_tables_error_tip_1 }} />
					<li dangerouslySetInnerHTML={{ __html: i18n.text_install_db_tables_error_tip_2 }} />
					<li dangerouslySetInnerHTML={{ __html: i18n.text_install_db_tables_error_tip_3 }} />
					<li dangerouslySetInnerHTML={{ __html: i18n.text_install_db_tables_error_tip_4 }} />
				</ul>
			</>
		);
	}

	getContent () {
		const { i18n, dbHostname, dbName, dbPort, dbUsername, dbPassword, dbTablePrefix, updateField } = this.props;

		return (
			<div>
				<h2>{i18n.phrase_create_database_tables}</h2>

				<p dangerouslySetInnerHTML={{ __html: i18n.text_install_create_database_tables}} />

				<NotificationPanel ref={this.notificationPanel} />

				{this.getTips()}

				<form method="post" onSubmit={this.onSubmit}>

					<p><b>{i18n.phrase_database_settings}</b></p>

					<table cellPadding="1" cellSpacing="0" className={styles.info}>
						<tbody>
						<tr>
							<td className={styles.label} width="140">{i18n.phrase_database_hostname}</td>
							<td>
								<input type="text" size="20" value={dbHostname} autoFocus ref={this.dbHostname}
							       onChange={(e) => updateField('dbHostname', e.target.value)}/> {i18n.phrase_often_localhost}
							</td>
						</tr>
						<tr>
							<td className={styles.label}>{i18n.phrase_database_name}</td>
							<td>
								<input type="text" size="20" value={dbName} maxLength="64" ref={this.dbName}
								       onChange={(e) => updateField('dbName', e.target.value)}/>
							</td>
						</tr>
						<tr>
							<td className={styles.label}>{i18n.word_port}</td>
							<td>
								<input type="text" size="10" value={dbPort} ref={this.dbPort}
								       onChange={(e) => updateField('dbPort', e.target.value)}/>
							</td>
						</tr>
						<tr>
							<td className={styles.label}>{i18n.phrase_database_username}</td>
							<td>
								<input type="text" size="20" value={dbUsername} ref={this.dbUsername}
								       onChange={(e) => updateField('dbUsername', e.target.value)}/>
							</td>
						</tr>
						<tr>
							<td className={styles.label}>{i18n.phrase_database_password}</td>
							<td>
								<input type="text" size="20" value={dbPassword} ref={this.dbPassword}
								       onChange={(e) => updateField('dbPassword', e.target.value)}/>
							</td>
						</tr>
						<tr>
							<td className={styles.label}>{i18n.phrase_database_table_prefix}</td>
							<td>
								<input type="text" size="20" maxLength="10" value={dbTablePrefix} ref={this.dbTablePrefix}
								       onChange={(e) => updateField('dbTablePrefix', e.target.value)}/>
							</td>
						</tr>
						</tbody>
					</table>

					<p>
						<Button type="submit">{i18n.phrase_create_database_tables}</Button>
					</p>
				</form>
			</div>
		);
	}

	getTablesCreatedContent () {
		const { i18n, dbHostname, dbName, dbPort, dbUsername, dbPassword, dbTablePrefix } = this.props;
		const submitBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

		return (
			<>
				<h2>Database Tables</h2>

				<p>
					Your database tables have been created.
				</p>

				<table cellPadding="1" cellSpacing="0" className={styles.info}>
					<tbody>
					<tr>
						<td className={styles.label} width="140">{i18n.phrase_database_hostname}</td>
						<td>{dbHostname}</td>
					</tr>
					<tr>
						<td className={styles.label}>{i18n.phrase_database_name}</td>
						<td>{dbName}</td>
					</tr>
					<tr>
						<td className={styles.label}>{i18n.word_port}</td>
						<td>{dbPort}</td>
					</tr>
					<tr>
						<td className={styles.label}>{i18n.phrase_database_username}</td>
						<td>{dbUsername}</td>
					</tr>
					<tr>
						<td className={styles.label}>{i18n.phrase_database_password}</td>
						<td>{dbPassword}</td>
					</tr>
					<tr>
						<td className={styles.label}>{i18n.phrase_database_table_prefix}</td>
						<td>{dbTablePrefix}</td>
					</tr>
					</tbody>
				</table>

				<p>
					<Button onClick={this.nextPage}>{submitBtnLabel}</Button>
				</p>
			</>
		);
	}

	render () {
		const { tablesAlreadyExist } = this.state;
		const { tablesCreated } = this.props;
		let content;

		if (tablesAlreadyExist) {
			content = this.getTablesAlreadyExistContent();
		} else if (tablesCreated) {
			content = this.getTablesCreatedContent();
		} else {
			content = this.getContent();
		}

		return content;
	}
}


export default withRouter(Step3);
