import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import styles from '../Layout/Layout.scss';
import Button from '../../components/Buttons';
import { NotificationPanel } from '../../components';
import InstallationComponents from '../InstallationComponents/InstallationComponents';


class Step5 extends Component {
	constructor (props) {
		super(props);;
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
