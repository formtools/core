import React, { Component } from 'react';
<<<<<<< HEAD
import { actions, selectors, CompatibleComponents } from '../components/CompatibleComponents';
import { Provider, connect } from 'react-redux';
import { createSelector } from 'reselect';
=======
import { actions, reducers, CompatibleComponents } from '../components/CompatibleComponents';
import { Provider, connect } from 'react-redux';
>>>>>>> 3a620d4b5cf0f858ffe4b80c13798d2b8f561eab
import store from '../store';


class CompatibleComponentsContainer extends Component {
<<<<<<< HEAD
	componentWillMount () {
		this.props.getCompatibleComponents();
	}

	render () {
		return (
			<CompatibleComponents {...this.props} />
=======

	// the moment the component mounts we make a request for the component data
	componentWillMount () {
		//this.props.dispatch(actions.getCompatibleComponents());
	}
	render () {
		return (
			<CompatibleComponents {...props} />
>>>>>>> 3a620d4b5cf0f858ffe4b80c13798d2b8f561eab
		);
	}
}

<<<<<<< HEAD
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
=======
const mapStateToProps = (state) => {

};

const mapDispatchToProps = (state) => {

};

const ConnectedCompatibleComponentsContainer = connect(
	mapStateToProps
>>>>>>> 3a620d4b5cf0f858ffe4b80c13798d2b8f561eab
)(CompatibleComponentsContainer);

export default (
	<Provider store={store}>
		<ConnectedCompatibleComponentsContainer />
	</Provider>
);
