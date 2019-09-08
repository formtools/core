import React from 'react';
import { connect } from 'react-redux';
import { selectors as initSelectors } from '../../store/init';
import { selectors as i18nSelectors } from '../../store/i18n';
import { selectors as constantSelectors } from '../../store/constants';
import { selectors } from '../store';
import PageComponent from './Page.component';

const mapStateToProps = (state) => ({
	initialized: initSelectors.isInitialized(state),
	i18n: i18nSelectors.getI18n(state),
	constants: constantSelectors.getConstants(state),
	loading: selectors.isLoading(state)
});

export default connect(
	mapStateToProps
)(PageComponent);
