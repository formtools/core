import React, { Component } from 'react';
import { actions, selectors, CompatibleComponents } from '../components/CompatibleComponents';
import { Provider, connect } from 'react-redux';
import { createSelector } from 'reselect';
import store from '../store';


class CompatibleComponentsContainer extends Component {
	componentWillMount () {
		this.props.getCompatibleComponents();
	}

	render () {
		return (
			<CompatibleComponents {...this.props} />
		);
	}
}

const mapStateToProps = (state) => ({
	initialized: false,
	api: state.compatibleComponents.api,
	themes: state.compatibleComponents.themes,
	modules: selectors.getVisibleModules(state)
});

const mapDispatchToProps = (dispatch) => ({
	getCompatibleComponents: () => dispatch(actions.getCompatibleComponents()),
	onSearchFilter: (str) => dispatch(actions.updateSearchFilter(str)),
	toggleRow: (id) => dispatch(actions.toggleRow(id)),
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
