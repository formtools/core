import { createSelector } from 'reselect';
import { convertHashToArray } from '../../helpers';
import { selectors as constantSelectors } from '../constants';
import { getComponentNameFromIdentifier } from './helpers';

export const isDataLoaded = (state) => state.compatibleComponents.loaded;
export const getModules = (state) => state.compatibleComponents.modules;
export const getThemes = (state) => state.compatibleComponents.themes;
export const isAPISelected = (state) => state.compatibleComponents.apiSelected;

export const getSelectedModuleFolders = (state) => state.compatibleComponents.selectedModuleFolders;
export const getSelectedThemeFolders = (state) => state.compatibleComponents.selectedThemeFolders;
export const isEditing = (state) => state.compatibleComponents.isEditing;
export const showComponentInfoModal = (state) => state.compatibleComponents.showComponentInfoModal;
export const getSelectedComponentTypeSection = (state) => state.compatibleComponents.selectedComponentTypeSection;
export const getAPI = (state) => state.compatibleComponents.api;
export const getCore = (state) => state.compatibleComponents.core;
export const getChangelogs = (state) => state.compatibleComponents.changelogs;
export const getComponentInfoModalContent = (state) => state.compatibleComponents.componentInfoModalContent;

// downloading
export const isDownloading = (state) => state.compatibleComponents.isDownloading;
export const downloadComplete = (state) => state.compatibleComponents.downloadComplete;
export const showDetailedDownloadLog = (state) => state.compatibleComponents.showDetailedDownloadLog;
export const getDownloadedComponents = (state) => state.compatibleComponents.downloadedComponents;


export const getDownloadLog = (state) => {
	const components = getDownloadedComponents(state);
	const showDetails = showDetailedDownloadLog(state);
	const modules = getModules(state);
	const themes = getThemes(state);

	let log = '';
	Object.keys(components).forEach((component) => {

		// if we don't have a response for this particular component, show nothing...
		if (components[component].downloadSuccess === null) {
			return;
		}

		if (log !== '') {
			log += '<hr size="1">';
		}

		const componentName = getComponentNameFromIdentifier(component, modules, themes);
		log += `<h2>${componentName}</h2>`;

		if (showDetails && components[component].log.length > 0) {
			log += components[component].log.join('<br />');
		}

		if (components[component].downloadSuccess) {
			log += '<div class="downloadSuccess">✓ Downloaded</div>';
		} else {
			log += '<div class="downloadError">✗ Download Problem</div>';
		}
	});
	return log;
};

// converts the hash of modules to an array
export const getModulesArray = createSelector(
    getModules,
    convertHashToArray
);

export const getThemesArray = createSelector(
    getThemes,
    convertHashToArray
);

const getSelectedModules = createSelector(
	getSelectedModuleFolders,
	getModules,
    (folders, modules) => folders.map((folder) => modules[folder])
);

const getSelectedThemes = createSelector(
    getSelectedThemeFolders,
    getThemes,
    (folders, themes) => folders.map((folder) => themes[folder])
);

// convenience method to return a flat, ordered array of all selected components in a standardized structure. Used on
// the non-editable list
export const getSelectedComponents = (state) => {
    var constants = constantSelectors.getConstants(state);
	const components = [{
	    folder: 'core',
        name: 'Core',
        version: constants.core_version,
        type: 'core'
    }];

    if (isAPISelected(state)) {
		components.push({
            ...getAPI(state),
            type: 'api'
        });
	}

	getSelectedModules(state).forEach((module) => {
		components.push({ ...module, type: 'module' });
	});

    getSelectedThemes(state).forEach((theme) => {
        components.push({ ...theme, type: 'theme' });
    });

	return components;
};

export const allModulesSelected = createSelector(
    getModulesArray,
    getSelectedModuleFolders,
    (modules, folders) => modules.length === folders.length
);


/**
 * Returns all data needed for the component info modal, used on both the view and edit component list, including
 * prev & next links.
 *
 * @return object {
 *   isLoading: true|false,
 *   title: '',
 *   content: ''
 * }
 */
export const getComponentInfoModalInfo = createSelector(
    getComponentInfoModalContent,
    getCore,
    getAPI,
    getModules,
    getThemes,
    getChangelogs,
    isEditing,
    getSelectedComponentTypeSection,
    getSelectedComponents,
    (componentInfoModalContent, core, api, modules, themes, changelogs, isEditing, selectedComponentTypeSection, selectedComponents) => {
        const { componentType, folder } = componentInfoModalContent;
        const changelogLoaded = changelogs.hasOwnProperty(folder);

        const modalInfo = {
            title: '',
            type: componentType,
            folder,
            loaded: changelogLoaded,
	        isSelected: selectedComponents.find((row) => row.folder === folder) !== undefined,
            prevLinkEnabled: true,
            nextLinkEnabled: true
        };

        if (componentType === 'module') {
            modalInfo.title = modules[folder].name;
            modalInfo.desc = modules[folder].desc;
        } else if (componentType === 'theme') {
            modalInfo.title = themes[folder].name;
            modalInfo.desc = themes[folder].desc;
        } else if (componentType === 'api') {
            modalInfo.title = 'API';
            modalInfo.desc = api.desc;
        } else if (componentType === 'core') {
            modalInfo.title = 'Form Tools Core';
            modalInfo.desc = core.desc;
        }

        let list = [];
        if (isEditing) {
            let listMap = {
                modules: modules,
                themes: themes
            };

            if (listMap.hasOwnProperty(selectedComponentTypeSection)) {
                list = convertHashToArray(listMap[selectedComponentTypeSection]);
            } else {
                list = [{ folder: 'api'}];
            }
        } else {
            list = selectedComponents;
        }

        const index = list.findIndex(i => i.folder === folder);
        if (index === 0) {
            modalInfo.prevLinkEnabled = false;
        }
        if (list.length === 1 || list[list.length-1].folder === folder) {
            modalInfo.nextLinkEnabled = false;
        }

        modalInfo.data = changelogLoaded ? changelogs[folder] : [];

        return modalInfo;
    }
);


export const getPrevNextComponent = createSelector(
    getComponentInfoModalContent,
    getSelectedComponentTypeSection,
    getAPI,
    getModulesArray,
    getThemesArray,
    isEditing,
    getSelectedComponents,
    (componentInfoModalContent, editingComponentTypeSection, api, modules, themes, isEditing, selectedComponents) => {
        const prevNext = {
            prev: null,
            next: null
        };

        let list = [];
        if (isEditing) {
            let listMap = {
                modules: modules,
                themes: themes
            };
            if (listMap.hasOwnProperty(editingComponentTypeSection)) {
                list = listMap[editingComponentTypeSection];
            } else {
                list = [{ folder: 'api' }];
            }
        } else {
            list = selectedComponents;
        }

        const index = list.findIndex(i => i.folder === componentInfoModalContent.folder);

        if (index > 0) {
            const prev = list[index-1];
            prevNext.prev = {
                componentType: prev.type,
                folder: prev.folder
            };
        }
        if (index < list.length - 1) {
            const next = list[index+1];
            prevNext.next = {
                componentType: next.type,
                folder: next.folder
            };
        }

        return prevNext;
    }
);


// used to show the "downloaded `N` of ..."
export const getNumDownloaded = createSelector(
	getDownloadedComponents,
	(components) => Object.keys(components).filter((key) => components[key].downloadSuccess !== null).length
);

// used to show the "downloaded ... of `N`"
export const getTotalNumToDownload = createSelector(
	getDownloadedComponents,
	(components) => Object.keys(components).length
);

