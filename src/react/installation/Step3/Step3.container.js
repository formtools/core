import React from 'react';
import { connect } from 'react-redux';
import { selectors as i18nSelectors } from '../../store/i18n';
import { selectors as constantSelectors } from '../../store/constants';
import { actions, selectors } from '../store/';
import Step2 from './Step3.component';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
	constants: constantSelectors.getConstants(state),
	language: selectors.getLanguage(state),
	dbHostname: selectors.getDbHostname(state),
	dbName: selectors.getDbName(state),
	dbPort: selectors.getDbPort(state),
	dbUsername: selectors.getDbUsername(state),
	dbPassword: selectors.getDbPassword(state),
	dbTablePrefix: selectors.getDbTablePrefix(state),
	tablesCreated: selectors.isTablesCreated(state)
});

const mapDispatchToProps = (dispatch) => ({
	updateField: (field, value) => dispatch(actions.updateDatabaseField(field, value)),
	saveDbSettings: (onSuccess, onError, overwrite = false) => dispatch(actions.saveDbSettings(onSuccess, onError, overwrite))
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(Step2);
