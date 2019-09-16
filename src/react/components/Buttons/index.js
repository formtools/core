import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import MButton from '@material-ui/core/Button';

// primary buttons styles for default green theme only
const useStyles = makeStyles((theme) => ({
	primary: {
		margin: '8px 4px 8px 0',
		boxShadow: 'none',
		textTransform: 'none',
		letterSpacing: 'normal',
		background: 'linear-gradient(80deg, #5FB66F 30%, #54a863 90%)',
		padding: '4px 12px',
		'&:hover': {
			background: 'linear-gradient(80deg, #54a863 30%, #459353 90%)'
		}
	},

	danger: {
		margin: '8px 4px 8px 0',
		boxShadow: 'none',
		textTransform: 'none',
		letterSpacing: 'normal',
		background: 'linear-gradient(80deg, #ce0e0e 30%, #b70707 90%)',
		padding: '4px 12px',
		'&:hover': {
			background: 'linear-gradient(80deg, #b70707 30%, #ad0606 90%)'
		}
	},

	info: {
		margin: '8px 4px 8px 0',
		boxShadow: 'none',
		textTransform: 'none',
		letterSpacing: 'normal',
		background: 'linear-gradient(80deg, #d5eaef 30%, #ccdee2 90%)',
		padding: '4px 12px',
		'&:hover': {
			background: 'linear-gradient(80deg, #ccdee2 30%, #c3d4d8 90%)'
		}
	},

	label: {
		color: 'white',
	},

	labelDark: {
		color: '#3d4344'
	}
}));

const Button = ({ children, buttonType, ...otherProps }) => {
	const styles = useStyles();
	const label = (buttonType === 'info') ? styles.labelDark : styles.label;

	return (
		<MButton
			classes={{
				root: styles[buttonType],
				label: label
			}}
			variant="contained"
			{...otherProps}>
			{children}
		</MButton>
	);
};
Button.defaultProps = {
	buttonType: 'primary' // 'primary', 'error'
};

export default Button;