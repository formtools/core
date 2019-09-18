import React from 'react';
import { connect } from 'react-redux';
import { actions, selectors } from '../store/';
import Step2 from './Step5.component';
import { selectors as i18nSelectors } from '../../store/i18n';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
	firstName: selectors.getFirstName(state),
	lastName: selectors.getLastName(state),
	email: selectors.getEmail(state),
	username: selectors.getUsername(state),
	password: selectors.getPassword(state),
	password2: selectors.getPassword2(state)
});

const mapDispatchToProps = (dispatch) => ({
	updateField: (field, value) => dispatch(actions.updateAccountField(field, value)),
	saveAdminAccount: () => dispatch(actions.saveAdminAccount())
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
