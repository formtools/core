import React, { Component } from 'react';
import { Provider, connect } from 'react-redux';
import store from '../../core/store';
import { selectors as coreSelectors } from '../../core/store/init';

// import * as actions from './actions';
// import * as selectors from './selectors';
// import InstallationComponents from '../../components/InstallationComponents/InstallationComponents';
//
//

class ManageModulesContainer extends Component {
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

// const mapStateToProps = (state) => ({
// 	initialized: initSelectors.getInitialized(state),
//
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
// });
//
// const mapDispatchToProps = (dispatch) => ({
// 	onEditComponentList: () => dispatch(actions.editSelectedComponentList()),
//     onCancelEditComponentList: () => dispatch(actions.cancelEditSelectedComponentList()),
// 	saveSelectedComponentList: () => dispatch(actions.saveSelectedComponentList()),
// 	getCompatibleComponents: () => dispatch(actions.getCompatibleComponents()),
//     toggleComponent: (componentTypeSection, folder) => dispatch(actions.toggleComponent(componentTypeSection, folder)),
//     onSelectComponentTypeSection: (section) => dispatch(actions.selectComponentTypeSection(section)),
//     toggleAllModulesSelected: () => dispatch(actions.toggleAllModulesSelected()),
//     onShowComponentInfo: (componentInfo) => dispatch(actions.showComponentInfo(componentInfo)), // TODO rename
//     onCloseComponentInfo: () => dispatch(actions.closeComponentInfo()),
// 	toggleShowDetailedDownloadLog: () => dispatch(actions.toggleShowDetailedDownloadLog()),
//     onPrevNext: (dir) => dispatch(actions.onPrevNext(dir)),
// 	onSubmit: () => dispatch(actions.downloadCompatibleComponents())
// });
//
// const ConnectedCompatibleComponentsContainer = connect(
// 	mapStateToProps,
// 	mapDispatchToProps
// )(CompatibleComponentsContainer);

// export default (
// 	<Provider store={store}>
// 		<ConnectedCompatibleComponentsContainer />
// 	</Provider>
// );

export default (
	<div></div>
);

