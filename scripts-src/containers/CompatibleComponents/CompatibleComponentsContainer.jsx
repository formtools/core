import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../core/store';
import * as coreSelectors from '../../core/selectors';
import * as actions from './actions';
import * as selectors from './selectors';
import CompatibleComponents from '../../components/CompatibleComponents';


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
	initialized: coreSelectors.getInitialized(state),
	dataLoaded: selectors.isDataLoaded(state),
	api: selectors.getAPI(state),
	i18n: coreSelectors.getI18n(state),
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
