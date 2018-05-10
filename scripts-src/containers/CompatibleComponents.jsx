import React, { Component } from 'react';
import { actions } from '../components/CompatibleComponents';


class CompatibleComponentsContainer extends Component {

	// the moment the component mounts we make a request for the component data
	componentWillMount () {
		this.props.dispatch(actions.getCompatibleComponents());
	}
	render () {
		return (
			<div></div>
		);
	}
}

export default CompatibleComponentsContainer;
