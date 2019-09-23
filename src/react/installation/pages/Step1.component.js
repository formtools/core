import React from 'react';
import Dropdown from '../../components/Dropdown';
import Button from '../../components/Buttons';
import { generalUtils } from '../../utils';

const Step1 = ({ i18n, history, language, availableLanguages, onSelectLanguage }) => {
	const onSubmit = (e) => {
		e.preventDefault();
		history.push('/step2');
	};

	const updatePageTitle = (i18n) => {
		document.title = i18n.phrase_ft_installation;
	};

	const submitBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

	return (
		<form method="post" onSubmit={onSubmit}>
			<h2>{i18n.word_welcome}</h2>

			<p dangerouslySetInnerHTML={{ __html: i18n.text_installation_intro }} />

			<section style={{ width: 300, marginBottom: 5 }}>
				<Dropdown
					data={availableLanguages}
					selected={language}
					onChange={({ value }) => onSelectLanguage(value, updatePageTitle)}
				/>
			</section>

			<p>
				<Button type="submit">{submitBtnLabel}</Button>
			</p>
		</form>
	);
};

export default Step1;
























