import React, { Component } from 'react';
import { actions, CompatibleComponents } from '../components/CompatibleComponents';
import { Provider, connect } from 'react-redux';
import store from '../store';


class CompatibleComponentsContainer extends Component {

	// the moment the component mounts we make a request for the component data
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
	modules: state.compatibleComponents.modules
});

const mapDispatchToProps = (dispatch) => ({
	getCompatibleComponents: () => dispatch(actions.getCompatibleComponents()),
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
