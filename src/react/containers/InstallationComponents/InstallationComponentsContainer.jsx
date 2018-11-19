import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../store';
import { selectors as initSelectors } from '../../store/init';
import { selectors as i18nSelectors } from '../../store/i18n';
import {
	actions as compatibleComponentsActions,
	selectors as compatibleComponentsSelectors
} from '../../store/compatibleComponents';

import CompatibleComponents from '../../components/InstallationComponents/InstallationComponents';


class InstallationComponentsContainer extends Component {
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

	// init
	initialized: initSelectors.getInitialized(state),
	i18n: i18nSelectors.getI18n(state),

	// compatible components
	dataLoaded: compatibleComponentsSelectors.isDataLoaded(state),
	isEditing: compatibleComponentsSelectors.isEditing(state),
	isDownloading: compatibleComponentsSelectors.isDownloading(state),
	showDetailedDownloadLog: compatibleComponentsSelectors.showDetailedDownloadLog(state),
	downloadComplete: compatibleComponentsSelectors.downloadComplete(state),
    isShowingComponentInfoModal: compatibleComponentsSelectors.showComponentInfoModal(state),
	api: compatibleComponentsSelectors.getAPI(state),
	allThemes: compatibleComponentsSelectors.getThemesArray(state),
	allModules: compatibleComponentsSelectors.getModulesArray(state),
    selectedComponents: compatibleComponentsSelectors.getSelectedComponents(state),
    selectedComponentTypeSection: compatibleComponentsSelectors.getSelectedComponentTypeSection(state),
	selectedModuleFolders: compatibleComponentsSelectors.getSelectedModuleFolders(state),
	selectedThemeFolders: compatibleComponentsSelectors.getSelectedThemeFolders(state),
    allModulesSelected: compatibleComponentsSelectors.allModulesSelected(state),
    isAPISelected: compatibleComponentsSelectors.isAPISelected(state),
    modalInfo: compatibleComponentsSelectors.getComponentInfoModalInfo(state),
	numDownloaded: compatibleComponentsSelectors.getNumDownloaded(state),
	totalNumToDownload: compatibleComponentsSelectors.getTotalNumToDownload(state),
	downloadLog: compatibleComponentsSelectors.getDownloadLog(state)
});

const mapDispatchToProps = (dispatch) => ({
	onEditComponentList: () => dispatch(compatibleComponentsActions.editSelectedComponentList()),
    onCancelEditComponentList: () => dispatch(compatibleComponentsActions.cancelEditSelectedComponentList()),
	saveSelectedComponentList: () => dispatch(compatibleComponentsActions.saveSelectedComponentList()),
	getCompatibleComponents: () => dispatch(compatibleComponentsActions.getCompatibleComponents()),
    toggleComponent: (componentTypeSection, folder) => dispatch(compatibleComponentsActions.toggleComponent(componentTypeSection, folder)),
    onSelectComponentTypeSection: (section) => dispatch(compatibleComponentsActions.selectComponentTypeSection(section)),
    toggleAllModulesSelected: () => dispatch(compatibleComponentsActions.toggleAllModulesSelected()),
    onShowComponentInfo: (componentInfo) => dispatch(compatibleComponentsActions.showComponentInfo(componentInfo)), // TODO rename
    onCloseComponentInfo: () => dispatch(compatibleComponentsActions.closeComponentInfo()),
	toggleShowDetailedDownloadLog: () => dispatch(compatibleComponentsActions.toggleShowDetailedDownloadLog()),
    onPrevNext: (dir) => dispatch(compatibleComponentsActions.onPrevNext(dir)),
	onSubmit: () => dispatch(compatibleComponentsActions.downloadCompatibleComponents())
});

const ConnectedCompatibleComponentsContainer = connect(
	mapStateToProps,
	mapDispatchToProps
)(InstallationComponentsContainer);

export default (
	<Provider store={store}>
		<ConnectedCompatibleComponentsContainer />
	</Provider>
);
