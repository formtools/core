import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import CodeMirror from 'react-codemirror';
require('codemirror/mode/php/php');
require('codemirror/lib/codemirror.css');
import Button from '../../components/Buttons';
import { NotificationPanel } from '../../components';
import styles from '../Layout/Layout.scss';
import { generalUtils } from '../../utils';

class Step4 extends Component {

	constructor (props) {
		super(props);

		this.state = {
			errorCreatingConfigFile: false
		};

		this.notificationPanel = React.createRef();

		this.onError = this.onError.bind(this);
		this.nextPage = this.nextPage.bind(this);
		this.createFile = this.createFile.bind(this);
	}

	createFile () {
		this.props.createConfigFile(this.onSuccess, this.onError);
	}

	checkFileExists () {

	}

	nextPage () {
		this.props.history.push('/step5');
	}

	onError (e) {
		const { i18n } = this.props;

		if (e.error === 'error_creating_config_file') {
			this.setState({
				errorCreatingConfigFile: true
			});
			this.notificationPanel.current.add({ msg: i18n.text_config_file_not_created, msgType: 'error' });
		}
	}

	getContent () {
		const { i18n, configFile, configFileCreated } = this.props;

		const continueBtnLabel = generalUtils.decodeEntities(i18n.word_continue_rightarrow);

		if (this.state.errorCreatingConfigFile) {
			return (
				<>
					<p dangerouslySetInnerHTML={{ __html: i18n.text_config_file_not_created_instructions }} />
					<CodeMirror value={configFile} className={styles.configFileContents} options={{ mode: 'php', readOnly: 'nocursor' }} />
					<p>
						<Button onClick={this.checkFileExists}>{i18n.phrase_check_file_exists}</Button>
					</p>
				</>
			);
		}

		if (!configFileCreated) {
			return (
				<>
					<p dangerouslySetInnerHTML={{ __html: i18n.text_install_create_config_file }} />
					<CodeMirror value={configFile} className={styles.configFileContents} options={{ mode: 'php', readOnly: 'nocursor' }} />
					<p>
						<Button onClick={this.createFile}>{i18n.phrase_create_file}</Button>
					</p>
				</>
			);
		} else {
			return (
				<>
					<p style={{ marginBottom: 0 }} dangerouslySetInnerHTML={{ __html: i18n.text_config_file_created }} />
					<p>
						<Button onClick={this.nextPage}>{continueBtnLabel}</Button>
					</p>
				</>
			);
		}
	};

	render () {
		const { i18n } = this.props;
		return (
			<>
				<h2>{i18n.phrase_create_config_file}</h2>
				<NotificationPanel ref={this.notificationPanel} />
				{this.getContent()}
			</>
		);
	}
}

export default withRouter(Step4);
