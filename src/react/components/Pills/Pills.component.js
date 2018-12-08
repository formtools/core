import React, { Component } from 'react';
import PropTypes from 'prop-types';
import styles from './Pills.scss';



export class Pills extends Component {
	constructor (props) {
		super(props);
		this.onPillClick = this.onPillClick.bind(this);
	}

	onPillClick (e) {
		const li = e.target.closest('li');
		if (li) {
			this.props.onClick(li.getAttribute('data-section'));
		}
	}

	render () {
		const { children, style, selected } = this.props;
		const childrenWithProps = React.Children.map(children, (child) =>
			React.cloneElement(child, { selected: selected.indexOf(child.props.id) !== -1 })
		);
		return (
			<ul className={styles.pills} onClick={this.onPillClick} style={style}>{childrenWithProps}</ul>
		);
	}
}
Pills.propTypes = {
	style: PropTypes.object
};
Pills.defaultProps = {
	style: {}
};


export const Pill = ({ id, selected, children }) => (
	<li data-section={id} className={selected ? styles.selected : ''}>{children}</li>
);
Pill.propTypes = {
	id: PropTypes.string.isRequired,
	//selected: PropTypes.bool.isRequired // interesting, propTypes complains here
};
