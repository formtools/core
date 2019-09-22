import React, { Component } from 'react';
import Tooltip from '@material-ui/core/Tooltip';

class OverflowTip extends Component {
	constructor(props) {
		super(props);
		this.textElement = React.createRef();
	}

	//componentDidMount

	render () {
		return (
			<div ref={this.textElement}>{this.props.text}</div>
		);
	}
}

export default OverflowTip;