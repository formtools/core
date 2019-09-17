import React from 'react';
import { connect } from 'react-redux';
import { actions, selectors } from '../store/';
import Step2 from './Step5.component';

const mapStateToProps = (state) => ({
	firstName: selectors.getFirstName(state),
	lastName: selectors.getLastName(state),
	email: selectors.getEmail(state),
	username: selectors.getUsername(state),
	password: selectors.getPassword(state)
});

const mapDispatchToProps = (dispatch) => ({
	updateField: (field, value) => dispatch(actions.updateAccountField(field, value))
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
