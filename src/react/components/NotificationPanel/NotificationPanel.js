import React, { Component } from 'react';
import styles from './NotificationPanel.scss';
import { HighlightOff } from '@material-ui/icons';

// rather than force ever consumer to have to handle the removal scenario (onClose), this removes itself when the user
// clicks "x". So consumers always output just <Notification /> and use a ref to push to the content imperatively
class NotificationPanel extends Component {
	constructor(props) {
		super(props);
		this.state = {
			visible: false,
			closing: false,
			messages: []
		};
	}

	add (message) {
		this.setState((state) => ({
			visible: true,
			messages: [
				...state.messages,
				message
			]
		}));
	}

	render () {
		const { visible, messages } = this.state;

		if (!visible || !messages.length) {
			return null;
		}

		const { msg, msgType } = messages[messages.length-1];

		return (
			<div className={styles[msgType]}>
				<div>
					<HighlightOff fontSize="small" htmlColor="#0058db" />
					{msg}
				</div>
			</div>
		);
	}
}

export default NotificationPanel;
