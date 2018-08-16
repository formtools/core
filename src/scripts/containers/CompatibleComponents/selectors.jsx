import { createSelector } from 'reselect';

export const isDataLoaded = (state) => state.compatibleComponents.loaded;
export const getSearchFilter = (state) => state.compatibleComponents.searchFilter;
export const getModules = (state) => state.compatibleComponents.modules;
export const getThemes = (state) => state.compatibleComponents.themes;
export const getVisibleModulesByFolder = (state) => state.compatibleComponents.visibleModulesByFolder;
//export const getVisibleThemesByFolder = (state) => state.compatibleComponents.visibleThemesByFolder;
export const getAPI = (state) => state.compatibleComponents.api;
export const getSelectedModuleFolders = (state) => state.compatibleComponents.selectedModuleFolders;
export const getSelectedThemeFolders = (state) => state.compatibleComponents.selectedThemeFolders;
export const isEditing = (state) => state.compatibleComponents.isEditing;

export const getVisibleModules = createSelector(
	getVisibleModulesByFolder,
	getModules,
	(folders, modules) => folders.map((folder) => modules[folder])
);

// export const getVisibleThemes = createSelector(
// 	getVisibleThemesByFolder,
// 	getThemes,
// 	(folders, themes) => folders.map((folder) => themes[folder])
// );

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

// convenience method to return a flat, ordered array of all selected components in a standardized structure
export const getSelectedComponents = (state) => {
	const components = [];
	const api = getAPI(state);

	if (api.selected) {
		components.push({
            type: 'api',
			name: 'API',
			repo: '',
			desc: '',
			version: '',
			last_updated: ''
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