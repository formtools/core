import React from 'react';
import Page from '../Page/Page.container';

{/*<select name="lang_file" className="margin_right">*/}
	{/*<option value="{$row->code}" {if $lang == $row->code} selected{/if}>{$row->lang}</option>*/}
{/*</select>*/}

const Step1 = ({ i18n }) => {
	const onSubmit = () => {

	};

	return (
		<Page>
			<form method="post" onSubmit={onSubmit}>
				<table cellSpacing="0" cellPadding="0">
					<tr>
						<td width="100" className="label">{i18n.word_language}</td>
						<td>

						</td>
						<td>
							<input type="submit" name="select_language" value={i18n.word_select}/>
						</td>
					</tr>
				</table>

				<p>
					<input type="submit" name="next" value={i18n.word_continue_rightarrow}/>
				</p>
			</form>
		</Page>
	);
};

export default Step1;