import React from 'react';
import Dropdown from '../../components/general/Dropdown';
import { decodeEntities } from '../../helpers';

const Step1 = ({ i18n, availableLanguages }) => {
	const onSubmit = () => {

	};

	const onChange = () => {

	};

	const submitBtnLabel = decodeEntities(i18n.word_continue_rightarrow);

	return (
		<form method="post" onSubmit={onSubmit}>
			<table cellSpacing="0" cellPadding="0">
				<tbody>
				<tr>
					<td width="100" className="label">{i18n.word_language}</td>
					<td>
						<Dropdown data={availableLanguages} onChange={onChange} />
					</td>
					<td>
						<input type="submit" name="select_language" value={i18n.word_select}/>
					</td>
				</tr>
				</tbody>
			</table>

			<p>
				<input type="submit" name="next" value={submitBtnLabel}/>
			</p>
		</form>
	);
};

export default Step1;
























