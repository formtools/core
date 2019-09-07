import React from 'react';
import { makeStyles } from '@material-ui/core/styles';
import MButton from '@material-ui/core/Button';

// primary buttons styles for default green theme only
const useStyles = makeStyles((theme) => ({
	root: {
		margin: '8px 0',
		boxShadow: 'none',
		textTransform: 'none',
		letterSpacing: 'normal',
		background: 'linear-gradient(80deg, #5FB66F 30%, #54a863 90%)',
		padding: '4px 12px',

		'&:hover': {
			background: 'linear-gradient(80deg, #54a863 30%, #459353 90%)'
		}
	},
	label: {
		color: 'white',
	}
}));

const Button = ({ children, ...otherProps }) => {
	const { root, label } = useStyles();

	return (
		<MButton
			classes={{ root, label }}
			variant="contained"
			{...otherProps}>
			{children}
		</MButton>
	);
};

export default Button;