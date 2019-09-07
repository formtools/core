import React from 'react';
import { withRouter } from 'react-router-dom';
import { generalUtils } from '../../utils';
import styles from '../Page/Page.scss';
import Button from '../../components/Buttons';


const Step2 = ({ i18n, language, availableLanguages, onSelectLanguage, history }) => {
	const onSubmit = (e) => {
		e.preventDefault();
		history.push('/step6');
	};

	const submitBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

	// TODO check for scenario where account created.
	return (
		<form onSubmit={onSubmit}>

			<h2>{i18n.phrase_create_admin_account}</h2>

			<p>
				{i18n.text_create_admin_account}
			</p>

			<table cellPadding="0" className={styles.info}>
				<tr>
					<td width="160">{i18n.phrase_first_name}</td>
					<td className="answer">
						<input type="text" name="first_name" value="" style={{ width: 200 }} />
					</td>
				</tr>
				<tr>
					<td>{i18n.phrase_last_name}</td>
					<td className="answer">
						<input type="text" name="last_name" value="" style={{ width: 200 }} />
					</td>
				</tr>
				<tr>
					<td>{i18n.word_email}</td>
					<td className="answer">
						<input type="text" name="email" value="" style={{ width: 200 }} />
					</td>
				</tr>
				<tr>
					<td>{i18n.phrase_login_username}</td>
					<td className="answer">
						<input type="text" name="username" value="" style={{ width: 140 }} />
					</td>
				</tr>
				<tr>
					<td>{i18n.phrase_login_password}</td>
					<td className="answer">
						<input type="password" name="password" value="" style={{ width: 140 }} />
					</td>
				</tr>
				<tr>
					<td>{i18n.phrase_re_enter_password}</td>
					<td className="answer">
						<input type="password" name="password_2" value="" style={{ width: 140 }} />
					</td>
				</tr>
			</table>

			<p>
				<Button type="submit">{i18n.phrase_create_account}</Button>
			</p>

		</form>
	);
};

export default withRouter(Step2);
