import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import styles from '../Layout/Layout.scss';
import Button from '../../components/Buttons';
import { NotificationPanel } from '../../components';


class Step6 extends Component {
	constructor (props) {
		super(props);
		this.notificationPanel = React.createRef();
	}

	componentDidMount () {
		const { i18n } = this.props;
		this.notificationPanel.current.add({
			msg: i18n.text_ft_installed,
			msgType: 'notify',
			showCloseIcon: false
		});
	}

	login () {
		window.location = '../';
	}

	render () {
		const { i18n } = this.props;

		return (
			<>
				<h2>{i18n.phrase_clean_up}</h2>

				<NotificationPanel ref={this.notificationPanel} />

				<p>
					<Button onClick={this.login}>{i18n.text_log_in_to_ft} &raquo;</Button>
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
	}
};

export default withRouter(Step6);
