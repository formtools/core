import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import InstallationComponents from '../InstallationComponents/InstallationComponents.container';


class Step5 extends Component {
	constructor (props) {
		super(props);
	}

	render () {
		return (
			<InstallationComponents />
		);
	}
}

export default withRouter(Step5);
