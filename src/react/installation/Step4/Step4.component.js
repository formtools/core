import React from 'react';
import { withRouter } from 'react-router-dom';
import CodeMirror from 'react-codemirror';
require('codemirror/mode/php/php');
require('codemirror/lib/codemirror.css');
import Button from '../../components/Buttons';

import styles from '../Page/Page.scss';

const Step4 = ({ i18n, history, configFileGenerated, errorCreatingConfigFile, configFile }) => {
	const onSubmit = (e) => {
		e.preventDefault();
		history.push('/step5');
	};

	const getContent = () => {
		let fake = `<?php

$one = 1;
$two = 2;
$three = "three";

`;

		if (!configFileGenerated) {
			return (
				<>
					<p dangerouslySetInnerHTML={{ __html: i18n.text_install_create_config_file }} />

					<CodeMirror value={fake} className={styles.configFileContents} options={{ mode: 'php' }} readOnly />

					<form name="display_config_content_form" action="" method="post">
						<p>
							<Button type="submit">{i18n.phrase_create_file}</Button>
						</p>
					</form>
				</>
			);
		}

		if (configFileGenerated) {
			return (
				<>
					<p className="margin_bottom_large notify">
						{i18n.text_config_file_created}
					</p>

					<form action="" method="post">
						<p>
							<input type="submit" name="next" value={i18n.word_continue_rightarrow} />
						</p>
					</form>
				</>
			);
		}

		if (errorCreatingConfigFile) {
			return (
				<>
					<div className="margin_bottom_large notify">
						{$LANG.text_config_file_not_created}
					</div>
					<p>
						{$LANG.text_config_file_not_created_instructions}
					</p>

					<form name="display_config_content_form" action="" method="post">
						<textarea name="content" className={configFileContents}>{configFile}</textarea>
						<p>
							<input type="submit" value={i18n.word_continue_rightarrow} />
						</p>
					</form>
				</>
			);
		}
	};

	return (
		<>
			<h2>{i18n.phrase_create_config_file}</h2>

			include file='messages.tpl'

			{getContent()}
		</>
	);
};

export default withRouter(Step4);
