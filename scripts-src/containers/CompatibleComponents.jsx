import React, { Component } from 'react';
import { actions, component } from '../components/CompatibleComponents';


class CompatibleComponentsContainer extends Component {

	// the moment the component mounts we make a request for the component data
	componentWillMount () {
		this.props.dispatch(actions.getCompatibleComponents());
	}
	render () {

	}
}

export default CompatibleComponentsContainer;
