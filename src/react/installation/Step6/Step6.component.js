import React from 'react';
import { withRouter } from 'react-router-dom';
import styles from '../Page/Page.scss';
import Button from '../../components/Buttons';


const Step6 = ({ i18n }) => {
	const onSubmit = (e) => {
		e.preventDefault();
	};

	return (
		<>
			<h2>{i18n.phrase_clean_up}</h2>

			<p className="notify">
				{i18n.text_ft_installed}
			</p>

			<p>
				<Button>{i18n.text_log_in_to_ft} &raquo;</Button>
			</p>

			<div className={styles.divider} />

			<p><b>{i18n.phrase_getting_started.toUpperCase()}</b></p>
			<ul>
				<li>
					<a href="https://docs.formtools.org/tutorials/adding_first_form/" target="_blank">{i18n.text_tutorial_adding_first_form}</a>
				</li>
				<li>
					<a href="https://docs.formtools.org/" target="_blank">{i18n.text_review_user_doc}</a>
				</li>
			</ul>
		</>
	);
};

export default withRouter(Step6);
