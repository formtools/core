import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../store';
import { selectors as initSelectors } from '../../store/init';

// import * as actions from './actions';
// import InstallationComponents from '../../components/InstallationComponents/InstallationComponents';

class ManageModulesContainer extends Component {

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
	initialized: initSelectors.getInitialized(state),

// 	dataLoaded: selectors.isDataLoaded(state),
// 	isEditing: selectors.isEditing(state),
// 	isDownloading: selectors.isDownloading(state),
// 	showDetailedDownloadLog: selectors.showDetailedDownloadLog(state),
// 	downloadComplete: selectors.downloadComplete(state),
//     isShowingComponentInfoModal: selectors.showComponentInfoModal(state),
// 	api: selectors.getAPI(state),
// 	i18n: coreSelectors.getI18n(state),
// 	allThemes: selectors.getThemesArray(state),
// 	allModules: selectors.getModulesArray(state),
//     selectedComponents: selectors.getSelectedComponents(state),
//     selectedComponentTypeSection: selectors.getSelectedComponentTypeSection(state),
// 	selectedModuleFolders: selectors.getSelectedModuleFolders(state),
// 	selectedThemeFolders: selectors.getSelectedThemeFolders(state),
//     allModulesSelected: selectors.allModulesSelected(state),
//     isAPISelected: selectors.isAPISelected(state),
//     modalInfo: selectors.getComponentInfoModalInfo(state),
// 	numDownloaded: selectors.getNumDownloaded(state),
// 	totalNumToDownload: selectors.getTotalNumToDownload(state),
// 	downloadLog: selectors.getDownloadLog(state)
});

const mapDispatchToProps = (dispatch) => ({
// 	onEditComponentList: () => dispatch(actions.editSelectedComponentList()),
//  onCancelEditComponentList: () => dispatch(actions.cancelEditSelectedComponentList()),
// 	saveSelectedComponentList: () => dispatch(actions.saveSelectedComponentList()),
	getInstalledComponents: () => dispatch(actionCreators.getInstalledComponents()),
//     toggleComponent: (componentTypeSection, folder) => dispatch(actions.toggleComponent(componentTypeSection, folder)),
//     onSelectComponentTypeSection: (section) => dispatch(actions.selectComponentTypeSection(section)),
//     toggleAllModulesSelected: () => dispatch(actions.toggleAllModulesSelected()),
//     onShowComponentInfo: (componentInfo) => dispatch(actions.showComponentInfo(componentInfo)), // TODO rename
//     onCloseComponentInfo: () => dispatch(actions.closeComponentInfo()),
// 	toggleShowDetailedDownloadLog: () => dispatch(actions.toggleShowDetailedDownloadLog()),
//     onPrevNext: (dir) => dispatch(actions.onPrevNext(dir)),
// 	onSubmit: () => dispatch(actions.downloadCompatibleComponents())
});

const ConnectedManageModulesContainer = connect(
	mapStateToProps,
	mapDispatchToProps
)(ManageModulesContainer);

export default (
	<Provider store={store}>
		<ConnectedManageModulesContainer />
	</Provider>
);
