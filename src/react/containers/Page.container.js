import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../store';
import { selectors as initSelectors } from '../store/init';
import { selectors as i18nSelectors } from '../store/i18n';
import Page from '../containers/Page.container';

class PageContainer extends Component {
	componentWillUpdate (nextProps) {
		if (nextProps.initialized && !this.props.initialized) {
			this.props.getInstallationComponentList();
		}
	}
	render () {
		return (
			<Page {...this.props} />
		);
	}
}

const mapStateToProps = (state) => ({
	initialized: initSelectors.getInitialized(state),
	i18n: i18nSelectors.getI18n(state),
});

const ConnectedPageContainer = connect(
	mapStateToProps
)(PageContainer);

export default (
	<Provider store={store}>
		<ConnectedPageContainer />
	</Provider>
);