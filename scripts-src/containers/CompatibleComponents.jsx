import React, { Component } from 'react';
import { actions, reducers, CompatibleComponents } from '../components/CompatibleComponents';
import { Provider, connect } from 'react-redux';
import store from '../store';


class CompatibleComponentsContainer extends Component {

	// the moment the component mounts we make a request for the component data
	componentWillMount () {
		//this.props.dispatch(actions.getCompatibleComponents());
	}
	render () {
		return (
			<CompatibleComponents {...props} />
		);
	}
}

const mapStateToProps = (state) => {

};

const mapDispatchToProps = (state) => {

};

const ConnectedCompatibleComponentsContainer = connect(
	mapStateToProps
)(CompatibleComponentsContainer);

export default (
	<Provider store={store}>
		<ConnectedCompatibleComponentsContainer />
	</Provider>
);
