import React from 'react';
import { connect } from 'react-redux';
import { selectors as initSelectors } from '../../store/init';
import { selectors as i18nSelectors } from '../../store/i18n';
import { selectors as constantSelectors } from '../../store/constants';
import { selectors, actions } from '../store';
import PageComponent from './Page.component';
import { ERRORS } from '../../constants';

const mapStateToProps = (state) => ({
	initialized: initSelectors.isInitialized(state),
	i18n: i18nSelectors.getI18n(state),
	constants: constantSelectors.getConstants(state),
	loading: selectors.isLoading(state),
	sessionsExpiredError: initSelectors.getGlobalError(state) === ERRORS.SESSIONS_EXPIRED
});

const mapDispatchToProps = (dispatch) => ({
	restartInstallation: (history) => dispatch(actions.restartInstallation(history))
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(PageComponent);
