import React from 'react';
import { connect } from 'react-redux';
import { actionCreators, selectors } from '../../store/components';
import InstallationComponents from './InstallationComponents.component';


const mapStateToProps = (state) => ({
	dataLoaded: selectors.isCompatibleComponentsDataLoaded(state),
	isEditing: selectors.isEditing(state),
	isDownloading: selectors.isDownloading(state),
	showDetailedDownloadLog: selectors.showDetailedDownloadLog(state),
	downloadComplete: selectors.downloadComplete(state),
    isShowingComponentInfoModal: selectors.showInfoModal(state),
	api: selectors.getCompatibleAPI(state),
	allThemes: selectors.getCompatibleThemesArray(state),
	allModules: selectors.getCompatibleModulesArray(state),
    selectedComponents: selectors.getSelectedComponents(state),
    selectedComponentTypeSections: selectors.getSelectedComponentTypeSections(state),
	selectedModuleFolders: selectors.getSelectedModuleFolders(state),
	selectedThemeFolders: selectors.getSelectedThemeFolders(state),
    allModulesSelected: selectors.allModulesSelected(state),
    isAPISelected: selectors.isAPISelected(state),
    modalInfo: selectors.getComponentInfoModalInfo(state),
	numDownloaded: selectors.getNumDownloaded(state),
	totalNumToDownload: selectors.getTotalNumToDownload(state),
	downloadLog: selectors.getDownloadLog(state)
});

const mapDispatchToProps = (dispatch) => ({
	onEditComponentList: () => dispatch(actionCreators.editSelectedComponentList()),
    onCancelEditComponentList: () => dispatch(actionCreators.cancelEditSelectedComponentList()),
	saveSelectedComponentList: () => dispatch(actionCreators.saveSelectedComponentList()),
    toggleComponent: (componentTypeSection, folder) => dispatch(actionCreators.toggleComponent(componentTypeSection, folder)),
    onSelectComponentTypeSection: (section) => dispatch(actionCreators.selectComponentTypeSection(section)),
    toggleAllModulesSelected: () => dispatch(actionCreators.toggleAllModulesSelected()),
    showInfoModal: (componentInfo) => dispatch(actionCreators.showInfoModal(componentInfo)),
    closeInfoModal: () => dispatch(actionCreators.closeInfoModal()),
	toggleShowDetailedDownloadLog: () => dispatch(actionCreators.toggleShowDetailedDownloadLog()),
    onPrevNext: (dir) => dispatch(actionCreators.onPrevNext(dir)),
	onSubmit: () => dispatch(actionCreators.downloadCompatibleComponents())
});

export default connect(
	mapStateToProps,
	mapDispatchToProps
)(InstallationComponents);
