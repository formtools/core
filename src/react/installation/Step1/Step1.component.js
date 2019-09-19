import React from 'react';
import Dropdown from '../../components/Dropdown';
import Button from '../../components/Buttons';

import { generalUtils } from '../../utils';

const Step1 = ({ i18n, history, language, availableLanguages, onSelectLanguage }) => {
	const onSubmit = (e) => {
		e.preventDefault();
		history.push('/step2');
	};

	const submitBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

	return (
		<form method="post" onSubmit={onSubmit}>
			<h2>{i18n.word_welcome}</h2>
			<p>
				Select your language and let's get started.
			</p>

			<section style={{ width: 300, marginBottom: 5 }}>
				<Dropdown
					data={availableLanguages}
					selected={language}
					onChange={onSelectLanguage}
				/>
			</section>

			<p>
				<Button type="submit">{submitBtnLabel}</Button>
			</p>
		</form>
	);
};

export default Step1;
























