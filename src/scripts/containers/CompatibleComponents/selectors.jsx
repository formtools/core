import { createSelector } from 'reselect';
import { convertHashToArray } from '../../core/helpers';

export const isDataLoaded = (state) => state.compatibleComponents.loaded;
export const getModules = (state) => state.compatibleComponents.modules;
export const getThemes = (state) => state.compatibleComponents.themes;
export const getSelectedModuleFolders = (state) => state.compatibleComponents.selectedModuleFolders;
export const getSelectedThemeFolders = (state) => state.compatibleComponents.selectedThemeFolders;
export const isEditing = (state) => state.compatibleComponents.isEditing;
export const showComponentInfoModal = (state) => state.compatibleComponents.showComponentInfoModal;
export const getSelectedComponentTypeSection = (state) => state.compatibleComponents.selectedComponentTypeSection;
export const getAPI = (state) => state.compatibleComponents.api;
export const isAPISelected = (state) => state.compatibleComponents.apiSelected;


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
	const components = [];

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