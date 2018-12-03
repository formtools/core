import React, { Component } from 'react';
import * as helpers from '../../helpers';
import ComponentList from '../ComponentList/ComponentList';
import EditableComponentList from '../EditableComponentList/EditableComponentList';
import styles from './InstallationComponents.scss';
import ComponentDialog from '../Dialogs/ComponentDialog';
import CircularProgress from '@material-ui/core/CircularProgress';


/**
 * Used on the main Modules page within Form Tools to let administrators add, update and remove modules.
 */
class ManageComponents extends Component {

    getModal () {
        const { isShowingComponentInfoModal, onCloseComponentInfo, onPrevNext, isEditing, modalInfo,
	        toggleComponent, selectedComponentTypeSection, i18n } = this.props;

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
                docLink={`https://docs.formtools.org/modules/${modalInfo.folder}/`}
                i18n={i18n} />
        )
    }

	getSelectedComponentList () {
		const { onEditComponentList, onShowComponentInfo, selectedComponents, onSubmit, i18n } = this.props;

		return (
			<div>
				<h2>{i18n.phrase_choose_components}</h2>
				<p>
					{i18n.text_selected_components_info}
				</p>

                {this.getModal()}

				<ComponentList components={selectedComponents} i18n={i18n} isEditing={false}
                    onShowComponentInfo={onShowComponentInfo} />

				<p>
					<input type="button" onClick={onEditComponentList} value={i18n.word_customize} />
                    <span className={styles.delimiter}>|</span>
					<input type="button" onClick={onSubmit} value={helpers.decodeEntities(i18n.word)} />
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
	                {i18n.phrase_choose_components} &raquo; {i18n.word_customize}
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

	getDownloadPage () {
    	const { i18n, numDownloaded, totalNumToDownload, downloadLog, downloadComplete, showDetailedDownloadLog,
	        toggleShowDetailedDownloadLog } = this.props;
		const spinnerStyles = {
			color: '#21aa1e',
			margin: '-3px 0 0 10px',
			float: 'right'
		};

		const loadingSpinner = (downloadComplete) ? null : <CircularProgress style={spinnerStyles} size={30} thickness={3} />;
		const continueButton = (!downloadComplete) ? null :
			<p>
				<input type="button" value={helpers.decodeEntities(i18n.word_continue_rightarrow)}
					onClick={() => { window.location='step7.php'; }} />
			</p>;

		const downloadedMsg = helpers.replacePlaceholders(i18n.phrase_downloaded_X_of_Y, [
			`<b>${numDownloaded}</b>`,
			`<b>${totalNumToDownload}</b>`
		]);

		return (
			<div>
				<h2>
					{i18n.phrase_choose_components} &raquo; {i18n.word_installing}
				</h2>

				<div style={{ display: 'inline-block', padding: '12px 0', height: 25 }}>
					{loadingSpinner}
					<span dangerouslySetInnerHTML={{ __html: downloadedMsg }} />
				</div>

				<div className={styles.downloadLogContainer}>
					<div className={styles.downloadLogHeader}>
						<h3>Download Log</h3>
						<div>
							<input type="checkbox" id="showDetailedLog" checked={showDetailedDownloadLog}
								onChange={toggleShowDetailedDownloadLog} />
							<label htmlFor="showDetailedLog">{i18n.phrase_show_details}</label>
						</div>
					</div>
					<div className={styles.downloadLog} dangerouslySetInnerHTML={{ __html: downloadLog }}></div>
				</div>

				{continueButton}
			</div>
		);
	}

	render () {
		const { initialized, dataLoaded, dataLoadError, error, isEditing, isDownloading, downloadComplete } = this.props;

		if (!initialized || !dataLoaded) {
			return null;
		} else if (dataLoadError) {
			return <p>Error loading... {error}</p>;
		} else if (isDownloading || downloadComplete) {
			return this.getDownloadPage()
		}

		return (isEditing) ? this.getEditableComponentList() : this.getSelectedComponentList();
	}
}

export default ManageComponents;
