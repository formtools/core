import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../store';
import { selectors as initSelectors } from '../../store/init';
import { actionCreators } from '../../store/components/actions';


class ManageComponentsContainer extends Component {
	componentWillUpdate (nextProps) {
		if (nextProps.initialized && !this.props.initialized) {
			this.props.getInstalledComponents();
		}
	}
	render () {
		return (
			<div>...</div>
		);
	}
}

const mapStateToProps = (state) => ({
	initialized: initSelectors.getInitialized(state)
});

const mapDispatchToProps = (dispatch) => ({
	getInstalledComponents: () => actionCreators.getInstalledComponents(),
});

const ConnectedManageModulesContainer = connect(
	mapStateToProps,
	mapDispatchToProps
)(ManageComponentsContainer);

export default (
	<Provider store={store}>
		<ConnectedManageModulesContainer />
	</Provider>
);
