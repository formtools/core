import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import CodeMirror from 'react-codemirror';
require('codemirror/mode/php/php');
require('codemirror/lib/codemirror.css');
import Button from '../../components/Buttons';

import styles from '../Page/Page.scss';

class Step4 extends Component {

	constructor (props) {
		super(props);

		this.state = {
			errorCreatingConfigFile: false
		};

		this.onSuccess = this.onSuccess.bind(this);
		this.onError = this.onError.bind(this);
		this.createFile = this.createFile.bind(this);
	}

	createFile () {
		this.props.createConfigFile(this.onSuccess, this.onError);
	}

	onSuccess () {

	}

	onError (e) {
		if (e.error === 'error_creating_config_file') {
			this.setState({
				errorCreatingConfigFile: true
			});
		}
	}

	getContent () {
//		({ history, errorCreatingConfigFile, createConfigFile })

		const { i18n, configFile, configFileGenerated } = this.props;

		if (this.state.errorCreatingConfigFile) {
			return (
				<>
					<div className="margin_bottom_large notify">
						{i18n.text_config_file_not_created}
					</div>
					<p>
						{i18n.text_config_file_not_created_instructions}
					</p>

					<textarea name="content" className={styles.configFileContents}>{configFile}</textarea>
					<p>
						<input type="submit" value={i18n.word_continue_rightarrow} />
					</p>
				</>
			);
		}

		if (!configFileGenerated) {
			return (
				<>
					<p dangerouslySetInnerHTML={{ __html: i18n.text_install_create_config_file }} />

					<CodeMirror value={configFile} className={styles.configFileContents} options={{ mode: 'php', readOnly: 'nocursor' }} />

					<p>
						<Button onClick={this.createFile}>{i18n.phrase_create_file}</Button>
					</p>
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
	};

	render () {
		const { i18n } = this.props;
		return (
			<>
				<h2>{i18n.phrase_create_config_file}</h2>
				{this.getContent()}
			</>
		);
	}
}

export default withRouter(Step4);
