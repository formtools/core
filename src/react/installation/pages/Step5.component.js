import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import styles from '../Layout/Layout.scss';
import Button from '../../components/Buttons';
import { NotificationPanel } from '../../components';
import { validationUtils } from '../../utils';


class Step5 extends Component {
	constructor (props) {
		super(props);
		this.onSubmit = this.onSubmit.bind(this);
		this.onSuccess = this.onSuccess.bind(this);
		this.onError = this.onError.bind(this);

		this.notificationPanel = React.createRef();
		this.firstName = React.createRef();
		this.lastName = React.createRef();
		this.email = React.createRef();
		this.username = React.createRef();
		this.password = React.createRef();
		this.password2 = React.createRef();
	}

	onSubmit (e) {
		e.preventDefault();
		const { i18n, firstName, lastName, email, username, password, password2, saveAdminAccount } = this.props;

		const errors = [];
		const fields = [];
		if (!firstName) {
			fields.push('firstName');
			errors.push(i18n.validation_no_first_name);
		}
		if (!lastName) {
			fields.push('lastName');
			errors.push(i18n.validation_no_last_name);
		}
		if (!email) {
			fields.push('email');
			errors.push(i18n.validation_no_admin_email);
		} else if (!validationUtils.validateEmail(email)) {
			fields.push('email');
			errors.push(i18n.validation_invalid_admin_email);
		}

		if (!username) {
			fields.push('username');
			errors.push(i18n.validation_no_username);
		} else if (!(/^[0-9a-z_]+$/.test(username))) { // is alpha
			fields.push('username');
			errors.push(i18n.validation_invalid_admin_username);
		}

		if (!password) {
			fields.push('password');
			errors.push(i18n.validation_no_password);
		}
		if (!password2) {
			fields.push('password2');
			errors.push(i18n.validation_no_second_password);
		}
		if (password !== password2) {
			fields.push('password2');
			errors.push(i18n.validation_passwords_different);
		}

		if (errors.length) {
			const error = `${i18n.phrase_error_text_intro}<br />&bull; ` + errors.join('<br />&bull; ');
			this.notificationPanel.current.add({ msg: error, msgType: 'error' });
			this[fields[0]].current.focus();
		} else {
			saveAdminAccount(this.onSuccess, this.onError);
		}
	};

	onSuccess () {
		this.props.history.push('/step6');
	}

	onError () {

	}

	render () {
		const { i18n, firstName, lastName, email, username, password, password2, updateField } = this.props;

		return (
			<form onSubmit={this.onSubmit}>
				<h2>{i18n.phrase_create_admin_account}</h2>

				<p>
					{i18n.text_create_admin_account}
				</p>

				<NotificationPanel ref={this.notificationPanel} />

				<table cellPadding="0" className={styles.info}>
					<tbody>
					<tr>
						<td width="160">{i18n.phrase_first_name}</td>
						<td className="answer">
							<input type="text" value={firstName} style={{ width: 200 }} ref={this.firstName} autoFocus
								onChange={(e) => updateField('firstName', e.target.value)} />
						</td>
					</tr>
					<tr>
						<td>{i18n.phrase_last_name}</td>
						<td className="answer">
							<input type="text" value={lastName} style={{ width: 200 }} ref={this.lastName}
								onChange={(e) => updateField('lastName', e.target.value)} />
						</td>
					</tr>
					<tr>
						<td>{i18n.word_email}</td>
						<td className="answer">
							<input type="text" value={email} style={{ width: 200 }} ref={this.email}
								onChange={(e) => updateField('email', e.target.value)} />
						</td>
					</tr>
					<tr>
						<td>{i18n.phrase_login_username}</td>
						<td className="answer">
							<input type="text" value={username} style={{ width: 140 }} ref={this.username}
								onChange={(e) => updateField('username', e.target.value)} />
						</td>
					</tr>
					<tr>
						<td>{i18n.phrase_login_password}</td>
						<td className="answer">
							<input type="password" value={password} style={{ width: 140 }} ref={this.password}
								onChange={(e) => updateField('password', e.target.value)} />
						</td>
					</tr>
					<tr>
						<td>{i18n.phrase_re_enter_password}</td>
						<td className="answer">
							<input type="password" value={password2} style={{ width: 140 }} ref={this.password2}
								onChange={(e) => updateField('password2', e.target.value)} />
						</td>
					</tr>
					</tbody>
				</table>

				<p>
					<Button type="submit">{i18n.phrase_create_account}</Button>
				</p>
			</form>
		);
	}
}

export default withRouter(Step5);
