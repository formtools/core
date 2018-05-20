import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import { createSelector } from 'reselect';
import init from '../components/Init';
import { actions, selectors, CompatibleComponents } from '../components/CompatibleComponents';
import store from '../core/store';


class CompatibleComponentsContainer extends Component {

	componentWillUpdate (nextProps) {
		if (nextProps.initialized && !this.props.initialized) {
			this.props.getCompatibleComponents();
		}
	}

	render () {
		return (
			<CompatibleComponents {...this.props} />
		);
	}
}

const mapStateToProps = (state) => ({
	initialized: init.selectors.getInitialized(state),
	dataLoaded: selectors.isDataLoaded(state),
	api: selectors.getAPI(state),
	i18n: init.selectors.getI18n(state),
	themes: selectors.getVisibleThemes(state),
	modules: selectors.getVisibleModules(state)
});

const mapDispatchToProps = (dispatch) => ({
	getCompatibleComponents: () => dispatch(actions.getCompatibleComponents()),
	onSearchFilter: (str) => dispatch(actions.updateSearchFilter(str)),
	toggleAPI: (folder) => dispatch(actions.toggleAPI(folder)),
	toggleModule: (folder) => dispatch(actions.toggleModule(folder)),
	toggleTheme: (folder) => dispatch(actions.toggleTheme(folder)),
	onSubmit: () => dispatch(actions.downloadCompatibleComponents())
});


const ConnectedCompatibleComponentsContainer = connect(
	mapStateToProps,
	mapDispatchToProps
)(CompatibleComponentsContainer);

export default (
	<Provider store={store}>
		<ConnectedCompatibleComponentsContainer />
	</Provider>
);
