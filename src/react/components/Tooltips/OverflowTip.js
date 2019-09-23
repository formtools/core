import React, { Component } from 'react';
import { withStyles } from '@material-ui/core/styles';
import Tooltip from '@material-ui/core/Tooltip';
import Fade from '@material-ui/core/Fade';

const StyledTooltip = withStyles(() => ({
	tooltip: {
		fontSize: 12,
		backgroundColor: '#505951',
		maxWidth: 'none'
	},
}))(Tooltip);

// known issue is that this doesn't automatically recompute whether the div is currently overflowed. The material UI
// API is kind of a pain here
class OverflowTip extends Component {
	constructor(props) {
		super(props);
		this.state = {
			overflowed: false
		};
		this.textElement = React.createRef();
	}

	componentDidMount () {
		this.setState({
			isOverflowed: this.textElement.current.scrollWidth > this.textElement.current.clientWidth
		});
	}

	render () {
		const { isOverflowed } = this.state;
		return (
			<StyledTooltip
				title={this.props.children}
				disableHoverListener={!isOverflowed}
				TransitionComponent={Fade} TransitionProps={{ timeout: 600 }}>
				<div
					ref={this.textElement}
					style={{
						whiteSpace: 'nowrap',
						overflow: 'hidden',
						textOverflow: 'ellipsis'
					}}>
					{this.props.children}
				</div>
			</StyledTooltip>
		);
	}
}

export default OverflowTip;