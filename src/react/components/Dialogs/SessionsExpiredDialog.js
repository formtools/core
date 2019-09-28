import React from 'react';
import { withStyles } from '@material-ui/core/styles';
import Dialog from '@material-ui/core/Dialog';
import MuiDialogTitle from '@material-ui/core/DialogTitle';
import MuiDialogContent from '@material-ui/core/DialogContent';
import MuiDialogActions from '@material-ui/core/DialogActions';
import Button from '../../components/Buttons';

const styles = () => ({
	root: {
		margin: 0,
		padding: 0,
		textAlign: 'left'
	}
});

const DialogTitle = withStyles(styles)(props => {
	const { children, classes } = props;
	return (
		<MuiDialogTitle disableTypography className={classes.root}>
			<h1 style={{ borderBottom: 0, padding: '5px 15px' }}>{children}</h1>
		</MuiDialogTitle>
	);
});

const DialogContent = withStyles(theme => ({
	root: {
		padding: theme.spacing(2),
		textAlign: 'left'
	}
}))(MuiDialogContent);

const SessionsExpiredDialog = ({ onClose, open, i18n }) => (
	<Dialog open={open}>
		<DialogTitle>{i18n.phrase_session_expired}</DialogTitle>
		<DialogContent dividers>
			{i18n.text_installation_session_expired}
		</DialogContent>
		<MuiDialogActions>
			<Button onClick={onClose}>
				{i18n.word_restart}
			</Button>
		</MuiDialogActions>
	</Dialog>
);

export default SessionsExpiredDialog;