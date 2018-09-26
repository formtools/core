import React, { Component } from 'react';
import * as helpers from '../../core/helpers';
import ComponentList from '../ComponentList/ComponentList';
import EditableComponentList from '../EditableComponentList/EditableComponentList';
import styles from './CompatibleComponents.scss';
import ComponentDialog from '../Dialogs/ComponentDialog';
import Changelog from './Changelog';
import CircularProgress from '@material-ui/core/CircularProgress';
import * as selectors from "../../containers/CompatibleComponents/selectors";


class CompatibleComponents extends Component {

    getModal () {
        const { isShowingComponentInfoModal, onCloseComponentInfo, onPrevNext, isEditing, modalInfo,
	        toggleComponent, selectedComponentTypeSection, i18n } = this.props;
        const hasDocLink = modalInfo.type !== 'theme';
        let docLink = null;

        // feels like this should be provided by the data source. If not, maybe put this info in the selector?
        if (hasDocLink) {
            if (modalInfo.type === 'module') {
                docLink = `https://docs.formtools.org/modules/${modalInfo.folder}/`;
            } else if (modalInfo.type === 'api') {
                docLink = `https://docs.formtools.org/api/v2/`;
            } else if (modalInfo.type === 'core') {
                docLink = 'https://docs.formtools.org/';
            }
        }

        return (
            <ComponentDialog
                open={isShowingComponentInfoModal}
                onClose={onCloseComponentInfo}
                toggleComponent={() => toggleComponent(selectedComponentTypeSection, modalInfo.folder)}
                isLoading={!modalInfo.loaded}
                isEditing={isEditing}
                title={modalInfo.title}
                desc={modalInfo.desc}
                isSelected={modalInfo.isSelected}
                prevLinkEnabled={modalInfo.prevLinkEnabled}
                nextLinkEnabled={modalInfo.nextLinkEnabled}
                onPrevNext={onPrevNext}
                content={<Changelog data={modalInfo.data} i18n={i18n} />}
                hasDocLink={hasDocLink}
                docLink={docLink}
                i18n={i18n} />
        )
    }

	getSelectedComponentList () {
		const { onEditComponentList, onShowComponentInfo, selectedComponents, onSubmit, i18n } = this.props;

		return (
			<div>
				<h2>{i18n.phrase_selected_components}</h2>
				<p>
					{i18n.text_selected_components_info}
				</p>

                {this.getModal()}

				<ComponentList components={selectedComponents} i18n={i18n} isEditing={false}
                    onShowComponentInfo={onShowComponentInfo} />

				<p>
					<input type="button" onClick={onEditComponentList} value={i18n.word_customize} />
                    <span className={styles.delimiter}>|</span>
					<input type="button" onClick={onSubmit} value={helpers.decodeEntities(i18n.word_continue_rightarrow)} />
				</p>
			</div>
		);
	}

	getEditableComponentList () {
        const { onCancelEditComponentList, selectedComponentTypeSection, allModules, allThemes, allModulesSelected,
            onSelectComponentTypeSection, selectedModuleFolders, selectedThemeFolders, toggleComponent,
            toggleAllModulesSelected, api, isAPISelected, saveSelectedComponentList, onShowComponentInfo, i18n } = this.props;

        return (
            <div>
                <h2>
	                {i18n.phrase_selected_components} &raquo; {i18n.word_customize}
                </h2>

                {this.getModal()}

                <EditableComponentList
                    selectedComponentTypeSection={selectedComponentTypeSection}
                    onSelectComponentSection={onSelectComponentTypeSection}
                    toggleComponent={toggleComponent}
                    modules={allModules}
                    themes={allThemes}
                    api={api}
                    i18n={i18n}
                    selectedModuleFolders={selectedModuleFolders}
                    selectedThemeFolders={selectedThemeFolders}
                    isAPISelected={isAPISelected}
                    allModulesSelected={allModulesSelected}
                    toggleAllModulesSelected={toggleAllModulesSelected}
                    onShowComponentInfo={onShowComponentInfo} />

                <p>
                    <input type="button" value={i18n.phrase_save_changes} onClick={saveSelectedComponentList}/>
                    <span className={styles.delimiter}>|</span>
                    <input type="button" onClick={(e) => { e.preventDefault(); onCancelEditComponentList(); }} value={i18n.word_cancel} />
                </p>
            </div>
        );
	}

	getDownloadingPage () {
    	const { i18n, numDownloaded, totalNumToDownload } = this.props;
		const spinnerStyles = {
			color: '#21aa1e',
			margin: '-3px 0 0 10px',
			float: 'right'
		};

    	return (
			<div>
				<h2>
					{i18n.phrase_selected_components} &raquo; {i18n.word_installing}
				</h2>

				<div style={{ display: 'inline-block' }}>
					<CircularProgress style={spinnerStyles} size={30} thickness={3} />
					Downloading <b>{numDownloaded}</b> of <b>{totalNumToDownload}</b> components. Please wait.
				</div>

			</div>
		);
	}

	render () {
		const { initialized, dataLoaded, dataLoadError, error, isEditing, isDownloading } = this.props;

		if (!initialized || !dataLoaded) {
			return null;
		} else if (dataLoadError) {
			return <p>Error loading... {error}</p>;
		} else if (isDownloading) {
			return this.getDownloadingPage()
		}

		return (isEditing) ? this.getEditableComponentList() : this.getSelectedComponentList();
	}
}

export default CompatibleComponents;
