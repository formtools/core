import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../core/store';
import * as coreSelectors from '../../core/selectors';
import * as actions from './actions';
import * as selectors from './selectors';
import CompatibleComponents from '../../components/CompatibleComponents/CompatibleComponents';


class CompatibleComponentsContainer extends Component {
	componentWillUpdate (nextProps) {
		if (nextProps.initialized && !this.props.initialized) {
			this.props.getCompatibleComponents();
		}
	}
	render () {
		return (
			<CompatibleComponents {...this.props} />
		);
	}
}

const mapStateToProps = (state) => ({
	initialized: coreSelectors.getInitialized(state),
	dataLoaded: selectors.isDataLoaded(state),
	isEditing: selectors.isEditing(state),
    isShowingComponentInfoModal: selectors.showComponentInfoModal(state),
	api: selectors.getAPI(state),
	i18n: coreSelectors.getI18n(state),
	allThemes: selectors.getThemesArray(state),
	allModules: selectors.getModulesArray(state),
    selectedComponents: selectors.getSelectedComponents(state),
    selectedComponentTypeSection: selectors.getSelectedComponentTypeSection(state),
	selectedModuleFolders: selectors.getSelectedModuleFolders(state),
	selectedThemeFolders: selectors.getSelectedThemeFolders(state),
    allModulesSelected: selectors.allModulesSelected(state),
    isAPISelected: selectors.isAPISelected(state),
});

const mapDispatchToProps = (dispatch) => ({
	onEditComponentList: () => dispatch(actions.editSelectedComponentList()),
    onCancelEditComponentList: () => dispatch(actions.cancelEditSelectedComponentList()),
	saveSelectedComponentList: () => dispatch(actions.saveSelectedComponentList()),
	getCompatibleComponents: () => dispatch(actions.getCompatibleComponents()),
    toggleComponent: (componentTypeSection, folder) => dispatch(actions.toggleComponent(componentTypeSection, folder)),
    onSelectComponentTypeSection: (section) => dispatch(actions.selectComponentTypeSection(section)),
    toggleAllModulesSelected: () => dispatch(actions.toggleAllModulesSelected()),
    onShowComponentInfo: () => dispatch(actions.showComponentInfo()), // TODO rename
    onCloseComponentInfo: () => dispatch(actions.closeComponentInfo()),
	onSubmit: () => dispatch(actions.downloadCompatibleComponents())
});

const ConnectedCompatibleComponentsContainer = connect(
	mapStateToProps,
	mapDispatchToProps
)(CompatibleComponentsContainer);

export default (
	<Provider store={store}>
		<ConnectedCompatibleComponentsContainer />
	</Provider>
);
