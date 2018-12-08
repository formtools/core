import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../store';
import { selectors as initSelectors } from '../../store/init';
import { selectors as i18nSelectors } from "../../store/i18n";
import * as selectors from './ManageComponentsContainer.selectors';

import { actionCreators } from '../../store/components/actions';
import * as componentSelectors from '../../store/components/selectors';
import CircularProgress from '@material-ui/core/CircularProgress';
import ManageComponents from "../../components/ManageComponents/ManageComponents";


class ManageComponentsContainer extends Component {
	constructor (props) {
		super(props);

		// display all sections as selected by default, but check query string to see if a specific section should be
		// displayed alone
		const urlParams = new URLSearchParams(location.search);
		const selectedSection = urlParams.get('section');
		props.selectComponentTypeSections(selectedSection ? [selectedSection] : ['module', 'theme', 'api', 'core']);
	}

	componentDidUpdate (prevProps) {
		if (!prevProps.initialized && this.props.initialized) {
			this.props.getInstalledComponents();
			this.props.getManageComponentsList();
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
	i18n: i18nSelectors.getI18n(state),
	selectedComponentTypeSections: componentSelectors.getSelectedComponentTypeSections(state),
	selectedComponents: componentSelectors.getSelectedComponentForSelectedSections(state)
});

const mapDispatchToProps = (dispatch) => ({
	getInstalledComponents: () => actionCreators.getInstalledComponents(),
	getManageComponentsList: () => dispatch(actionCreators.getManageComponentsList()),
	toggleComponentTypeSection: (section) => dispatch(actionCreators.toggleComponentTypeSection(section)),
	selectComponentTypeSections: (sections) => dispatch(actionCreators.selectComponentTypeSections(sections)),
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
