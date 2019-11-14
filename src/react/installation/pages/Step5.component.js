import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import InstallationComponents from '../InstallationComponents/InstallationComponents.container';


class Step5 extends Component {
	constructor (props) {
		super(props);
	}

	render () {
		const { errorLoading } = this.props;

		if (errorLoading) {
			return (
				<div>blah.</div>
			);
		}

		return (
			<InstallationComponents />
		);
	}
}

export default withRouter(Step5);
