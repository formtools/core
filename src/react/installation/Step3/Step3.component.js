import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import styles from '../Page/Page.scss';


class Step2 extends Component {
	constructor (props) {
		super(props);
		this.onSubmit = this.onSubmit.bind(this);
	}

	onSubmit () {
		e.preventDefault();
		this.props.history.push('/step4');
	};

	getTablesAlreadyExistContent () {
		const { existingTables } = this.props;

		return (
			<div>
				<h2>Tables already exist!</h2>

				include file='messages.tpl'

				<div className="error margin_bottom_large">
					<div style="padding: 6px">
						<b>Warning!</b> It appears that some tables already exist with the table prefix that you specified
						(see list below). You can either choose to overwrite these tables or pick a new table prefix.
					</div>
				</div>

				<div className={styles.existingTables}><blockquote><pre>{existingTables}</pre></blockquote></div>

				<form action="" method="post">
					<p>
						<input type="submit" name="overwrite_tables" value="Overwrite Tables" className={styles.red} />
						<input type="submit" name="pick_new_table_prefix" value="Pick New Table Prefix" />
					</p>
				</form>
			</div>
		);
	}

	getError () {
		/*
			{if $error != ""}

		<div class="error" style="padding: 5px; margin-top: 8px">
			{$LANG.phrase_error_occurred_c}<br />
			<br />
			<div class="red">{$error}</div>
			<br/>
			{$LANG.phrase_check_db_settings_try_again}
		</div>

		<p><b>{$LANG.word_tips}</b></p>

		<ul class="tips">
			<li><div>{$LANG.text_install_db_tables_error_tip_1}</div></li>
			<li><div>{$LANG.text_install_db_tables_error_tip_2}</div></li>
			<li><div>{$LANG.text_install_db_tables_error_tip_3}</div></li>
			<li><div>{$LANG.text_install_db_tables_error_tip_4}</div></li>
		</ul>

	{/if}

		 */
}

	getContent () {
		const { i18n } = this.props;

		return (
			<div>
				<h2>{i18n.phrase_create_database_tables}</h2>

				include file='messages.tpl'

				<div>
					{i18n.text_install_create_database_tables}
				</div>

				{this.getError()}

				include file='messages.tpl'

				<form name="db_settings_form" method="post" onSubmit="return rsv.validate(this, rules);">

					<p><b>{i18n.phrase_database_settings}</b></p>

					<table cellPadding="1" cellSpacing="0">
					<tr>
						<td className="label" width="140">{i18n.phrase_database_hostname}</td>
						<td>
							<input type="text" size="20" name="g_db_hostname" value="{$g_db_hostname}" autoFocus /> {i18n.phrase_often_localhost}
						</td>
					</tr>
					<tr>
						<td className="label">{i18n.phrase_database_name}</td>
						<td><input type="text" size="20" name="g_db_name" value="{$g_db_name}" maxLength="64" /></td>
					</tr>
					<tr>
						<td className="label">{i18n.word_port}</td>
						<td><input type="text" size="20" name="g_db_port" value="{$g_db_port}" /></td>
					</tr>
					<tr>
						<td className="label">{i18n.phrase_database_username}</td>
						<td><input type="text" size="20" name="g_db_username" value="{$g_db_username}" /></td>
					</tr>
					<tr>
						<td className="label">{i18n.phrase_database_password}</td>
						<td><input type="text" size="20" name="g_db_password" value="{$g_db_password}" /></td>
					</tr>
					<tr>
						<td className="label">{i18n.phrase_database_table_prefix}</td>
						<td><input type="text" size="20" maxLength="10" name="g_table_prefix" value="{$g_table_prefix}" /></td>
					</tr>
					</table>

					<p>
						<input type="submit" name="create_database" value="{$LANG.phrase_create_database_tables}" />
					</p>
				</form>
			</div>
		);
	}

	render () {
		const { tablesAlreadyExist } = this.props;
		let content;

		if (tablesAlreadyExist) {
			content = this.getTablesAlreadyExistContent();
		} else {
			content = this.getContent();
		}

		return content;
	}
}


export default withRouter(Step2);
