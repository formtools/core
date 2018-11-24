import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../store';
import { selectors as initSelectors } from '../../store/init';
import { selectors as i18nSelectors } from '../../store/i18n';
import { actionCreators, selectors } from '../../store/compatibleComponents';
import InstallationComponents from '../../components/InstallationComponents/InstallationComponents';


class InstallationComponentsContainer extends Component {
	componentWillUpdate (nextProps) {
		if (nextProps.initialized && !this.props.initialized) {
			this.props.getCompatibleComponents();
		}
	}
	render () {
		return (
			<InstallationComponents {...this.props} />
		);
	}
}

const mapStateToProps = (state) => ({

	// init
	initialized: initSelectors.getInitialized(state),
	i18n: i18nSelectors.getI18n(state),

	// compatible components
	dataLoaded: selectors.isDataLoaded(state),
	isEditing: selectors.isEditing(state),
	isDownloading: selectors.isDownloading(state),
	showDetailedDownloadLog: selectors.showDetailedDownloadLog(state),
	downloadComplete: selectors.downloadComplete(state),
    isShowingComponentInfoModal: selectors.showComponentInfoModal(state),
	api: selectors.getAPI(state),
	allThemes: selectors.getThemesArray(state),
	allModules: selectors.getModulesArray(state),
    selectedComponents: selectors.getSelectedComponents(state),
    selectedComponentTypeSection: selectors.getSelectedComponentTypeSection(state),
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
	getCompatibleComponents: () => dispatch(actionCreators.getCompatibleComponents()),
    toggleComponent: (componentTypeSection, folder) => dispatch(actionCreators.toggleComponent(componentTypeSection, folder)),
    onSelectComponentTypeSection: (section) => dispatch(actionCreators.selectComponentTypeSection(section)),
    toggleAllModulesSelected: () => dispatch(actionCreators.toggleAllModulesSelected()),
    onShowComponentInfo: (componentInfo) => dispatch(actionCreators.showComponentInfo(componentInfo)), // TODO rename
    onCloseComponentInfo: () => dispatch(actionCreators.closeComponentInfo()),
	toggleShowDetailedDownloadLog: () => dispatch(actionCreators.toggleShowDetailedDownloadLog()),
    onPrevNext: (dir) => dispatch(actionCreators.onPrevNext(dir)),
	onSubmit: () => dispatch(actionCreators.downloadCompatibleComponents())
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
