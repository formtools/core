import React, { Component } from 'react';
import { actions, selectors, CompatibleComponents } from '../components/CompatibleComponents';
import { Provider, connect } from 'react-redux';
import { createSelector } from 'reselect';
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
	initialized: state.init.initialized,
	dataLoaded: state.compatibleComponents.loaded,
	api: state.compatibleComponents.api,
	themes: state.compatibleComponents.themes,
	modules: selectors.getVisibleModules(state)
});

const mapDispatchToProps = (dispatch) => ({
	getCompatibleComponents: () => dispatch(actions.getCompatibleComponents()),
	updateSearchFilter: (str) => dispatch(actions.updateSearchFilter(str)),
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
