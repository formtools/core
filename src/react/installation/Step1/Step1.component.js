import React from 'react';
import { withRouter } from 'react-router-dom';
import Dropdown from '../../components/general/Dropdown';
import { decodeEntities } from '../../helpers';

const Step1 = ({ i18n, language, availableLanguages, onSelectLanguage, history }) => {
	const onSubmit = (e) => {
		e.preventDefault();
		history.push('/step2');
	};

	const submitBtnLabel = decodeEntities(i18n.word_continue_rightarrow);

	return (
		<form method="post" onSubmit={onSubmit}>
			<p>
				Select your language and let's get started.
			</p>

			<section style={{ width: 300 }}>
				<Dropdown
					data={availableLanguages}
					defaultValue={language}
					onChange={onSelectLanguage}
				/>
			</section>

			<p>
				<input type="submit" value={submitBtnLabel} />
			</p>
		</form>
	);
};

export default withRouter(Step1);
























