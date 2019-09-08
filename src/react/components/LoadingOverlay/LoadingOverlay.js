import React, { Component } from 'react';
import { withStyles } from '@material-ui/core/styles';
import CircularProgress from '@material-ui/core/CircularProgress';
import styles from './LoadingOverlay.scss';

const spinnerSize = 50;
const fadeOutTime = 600;

// primary buttons styles for default green theme only
const spinnerStyles = (theme) => ({
	colorPrimary: {
		color: '#ffffff',
		opacity: 0.5
	}
});


class LoadingOverlay extends Component {
	constructor (props) {
		super(props);
		this.state = {
			hidden: true
		};
	}

	componentDidUpdate (prevProps, prevState, snapshot) {

		// remove element after the a fade out is complete
		if (prevProps.visible === true && !this.props.visible) {
			setTimeout(() => {
				this.setState({ hidden: true });
			}, fadeOutTime);
			return;
		}

		if (!prevProps.visible && this.props.visible) {
			this.setState({ hidden: false });
		}
	}

	render () {
		const { visible, classes } = this.props;

		if (this.state.hidden) {
			return null;
		}

		const cls = visible ? styles.fadeIn : styles.fadeOut;
		return (
			<div className={cls}>
				<div className={styles.overlay}/>
				<div style={{
					position: 'absolute',
					left: '50%',
					top: '50%',
					marginLeft: -(spinnerSize / 2),
					marginTop: -(spinnerSize / 2),
					zIndex: 3
				}}>
					<CircularProgress size={spinnerSize} thickness={4} classes={classes} />
				</div>
			</div>
		);
	}
}

export default withStyles(spinnerStyles)(LoadingOverlay);
