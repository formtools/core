import React from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { selectors as i18nSelectors } from '../../store/i18n';
import { actions as initActions, selectors as initSelectors } from '../../store/init';
import { selectors } from '../store/';
import Step1 from './Step1.component';

const mapStateToProps = (state) => ({
	i18n: i18nSelectors.getI18n(state),
	availableLanguages: initSelectors.getLanguageList(state),
	language: selectors.getLanguage(state)
	// globalError: initSelectors.getGlobalError(state)
});

const mapDispatchToProps = (dispatch) => ({
	onSelectLanguage: ({ value }) => dispatch(initActions.selectLanguage(value))
});

export default withRouter(connect(
	mapStateToProps,
	mapDispatchToProps
)(Step1));
