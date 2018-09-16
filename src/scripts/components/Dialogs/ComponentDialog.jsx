import React, { Component } from 'react';
import PropTypes from 'prop-types';
import Dialog from '@material-ui/core/Dialog';
import DialogActions from '@material-ui/core/DialogActions';
import DialogContent from '@material-ui/core/DialogContent';
import DialogTitle from '@material-ui/core/DialogTitle';
import Button from '@material-ui/core/Button';
import CircularProgress from '@material-ui/core/CircularProgress';
import IconButton from '@material-ui/core/IconButton';
import styles from './ComponentDialog.scss';
import { Book } from '../Icons/Icons';
import Tooltip from '@material-ui/core/Tooltip';


class ComponentDialog extends Component {
	constructor (props) {
		super(props);
		this.onKeypress = this.onKeypress.bind(this);
	}

	state = {
		scroll: 'paper'
	};

	// N.B. This component is always rendered to allow the clean fade in and out, so this is misleading note
	componentWillMount () {
		document.addEventListener('keydown', this.onKeypress);
	}

	componentWillUnmount () {
		document.removeEventListener('keydown', this.onKeypress);
	}

	onKeypress (e) {
		e = e || window.event;

		const { open, onPrevNext } = this.props;
		if (!open) {
			return;
		}

		if (e.keyCode === 39) {
			onPrevNext('next');
		} else if (e.keyCode === 37) {
			onPrevNext('prev');
		}
	}

	getContent () {
		const { isLoading, content } = this.props;

		if (isLoading) {
			return (
				<CircularProgress style={{ color: '#21aa1e' }} size={50} thickness={3} />
			);
		}

		return content;
	}

	getDesc () {
		if (!this.props.isLoading) {
			return (
				<DialogContent className={styles.componentDesc}>
					<div dangerouslySetInnerHTML={{ __html: this.props.desc }}/>
				</DialogContent>
			);
		}
		return null;
	}

	getInstallCheckbox () {
		const { isEditing, isSelected, toggleComponent, i18n } = this.props;
		if (!isEditing) {
			return null;
		}

		return (
			<span className={styles.installBlock}>
			    <input type="checkbox" id="installComponent" checked={isSelected} onChange={toggleComponent} />
			    <label htmlFor="installComponent">{i18n.phrase_install_component}</label>
		    </span>
		);
	}

	render () {
		const { open, onClose, prevLinkEnabled, nextLinkEnabled, onPrevNext, isLoading, hasDocLink, docLink, i18n } = this.props;

		const iconButtonProps = {
			className: styles.docButtonLink
		};
		if (hasDocLink) {
			iconButtonProps.target = '_blank';
			iconButtonProps.href = docLink;
		}

		return (
			<Dialog className={styles.dialog}
			        open={open}
			        classes={{ paper: styles.paper }}
			        onClose={onClose}
			        scroll={this.state.scroll}
			        maxWidth="md"
			        aria-labelledby="scroll-dialog-title">

				<DialogTitle id="scroll-dialog-title">
					{this.props.title}
					{this.getInstallCheckbox()}

					<Tooltip title={i18n.phrase_view_documentation} placement="left">
						<IconButton {...iconButtonProps}>
							<Book color="#999999"/>
						</IconButton>
					</Tooltip>
				</DialogTitle>

				{this.getDesc()}
				<DialogContent
					classes={{ root: isLoading ? styles.contentRoot : null }}>{this.getContent()}</DialogContent>
				<DialogActions className={styles.buttonRow}>
					<div className={styles.prevNextNav}>
						<Button onClick={() => onPrevNext('prev')} color="primary" disabled={!prevLinkEnabled}>
							<span dangerouslySetInnerHTML={{ __html: i18n.phrase_prev_arrow }} />
						</Button>
						<Button onClick={() => onPrevNext('next')} color="primary" disabled={!nextLinkEnabled}>
							<span dangerouslySetInnerHTML={{ __html: i18n.phrase_next_arrow }} />
						</Button>
					</div>
					<Button onClick={onClose} color="primary">{i18n.word_close}</Button>
				</DialogActions>
			</Dialog>
		);
	}
}

ComponentDialog.propTypes = {
	open: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
	isLoading: PropTypes.bool.isRequired,
	title: PropTypes.string.isRequired,
	content: PropTypes.element.isRequired,
	nextLinkLabel: PropTypes.string,
	prevLinkLabel: PropTypes.string,
	onPrevNextClick: PropTypes.func
};

export default ComponentDialog;