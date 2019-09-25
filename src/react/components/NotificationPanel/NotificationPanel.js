import React, { Component } from 'react';
import { HighlightOff } from '@material-ui/icons';
import Collapse from '@material-ui/core/Collapse';
import { C } from '../../constants';
import styles from './NotificationPanel.scss';


// a self-contained notification panel. Consumers just always output this to the rendered output and use a ref to
// push messages to it. This keeps things as simple as possible & not require every consumer have to handle the show/hide
// logic. Users can also just pass a msg, msgType props to pass a static method.
class NotificationPanel extends Component {
	constructor(props) {
		super(props);
		let messages = [];
		this.state = {
			visible: false,
			closing: false,
			messages
		};
		this.closePanel = this.closePanel.bind(this);
	}

	componentDidMount () {
		const { msg, msgType, showCloseIcon } = this.props;
		if (msg && msgType) {
			this.add({ msg, msgType, showCloseIcon });
		}
	}

	add (message) {
		const msg = Object.assign({ msg: '', msgType: 'notify', showCloseIcon: true }, message);
		this.setState((state) => ({
			visible: true,
			messages: [
				...state.messages,
				msg
			]
		}));
	}

	// hard clear
	clear () {
		this.setState({ visible: false });
	}

	closePanel () {
		this.setState({ closing: true });

		setTimeout(() => {
			this.setState({
				closing: false,
				visible: false
			});
		}, C.NOTIFICATION_SPEED);
	}

	render () {
		const { visible, closing, messages } = this.state;

		if (!visible || !messages.length) {
			return null;
		}

		const { msg, msgType, showCloseIcon } = messages[messages.length-1];

		return (
			<Collapse in={!closing} timeout={C.NOTIFICATION_SPEED}>
				<div className={`${styles.notification} ${styles[msgType]}`}>
					<div>
						{showCloseIcon && <span onClick={this.closePanel}><HighlightOff fontSize="small" /></span>}
						<div dangerouslySetInnerHTML={{ __html: msg }} />
					</div>
				</div>
			</Collapse>
		);
	}
}

export default NotificationPanel;


















