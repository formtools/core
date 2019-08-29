import React from 'react';
import { connect } from 'react-redux';
import { selectors as initSelectors } from '../../store/init';
import { selectors as i18nSelectors } from '../../store/i18n';
import { selectors as constantSelectors } from '../../store/constants';
import Page from './Page';

const PageComponent = ({ children, ...otherProps }) => {
	if (!otherProps.initialized) {
		return null;
	}
	return (
		<Page {...otherProps}>{children}</Page>
	);
};

const mapStateToProps = (state) => ({
	initialized: initSelectors.getInitialized(state),
	i18n: i18nSelectors.getI18n(state),
	constants: constantSelectors.getConstants(state)
});

export default connect(
	mapStateToProps
)(PageComponent);
