import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../store';
import { selectors as initSelectors } from '../../store/init';
import { actionCreators } from '../../store/components/actions';
import * as componentSelectors from '../../store/components/selectors';
import * as selectors from './ManageComponentsContainer.selectors';
import CircularProgress from '@material-ui/core/CircularProgress';
import ManageComponents from "../../components/ManageComponents/ManageComponents";


class ManageComponentsContainer extends Component {
	componentWillUpdate (nextProps) {
		if (nextProps.initialized && !this.props.initialized) {
			this.props.getInstalledComponents();
			this.props.getCompatibleComponents();
		}
	}
	render () {
		if (!this.props.isLoaded) {
			return (
				<CircularProgress style={{ color: '#21aa1e', marginTop: 20 }} size={30} thickness={3} />
			);
		}

		return (
			<ManageComponents {...this.props} />
		);
	}
}

const mapStateToProps = (state) => ({
	initialized: initSelectors.getInitialized(state),
	isLoaded: selectors.isLoaded(state),
	selectedComponentTypeSections: componentSelectors.getSelectedComponentTypeSections(state)
});


const mapDispatchToProps = (dispatch) => ({
	getInstalledComponents: () => actionCreators.getInstalledComponents(),
	getCompatibleComponents: () => dispatch(actionCreators.getCompatibleComponents())
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
