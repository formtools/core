import { createSelector } from 'reselect';

export const isDataLoaded = (state) => state.compatibleComponents.loaded;
export const getSearchFilter = (state) => state.compatibleComponents.searchFilter;
export const getModules = (state) => state.compatibleComponents.modules;
export const getThemes = (state) => state.compatibleComponents.themes;
export const getVisibleModulesByFolder = (state) => state.compatibleComponents.visibleModulesByFolder;
export const getVisibleThemesByFolder = (state) => state.compatibleComponents.visibleThemesByFolder;
export const getAPI = (state) => state.compatibleComponents.api;
export const getSelectedModuleFolders = (state) => state.compatibleComponents.selectedModuleFolders;
export const getSelectedThemeFolders = (state) => state.compatibleComponents.selectedThemeFolders;

export const getVisibleModules = createSelector(
	getVisibleModulesByFolder,
	getModules,
	(folders, modules) => folders.map((folder) => modules[folder])
);

export const getVisibleThemes = createSelector(
	getVisibleThemesByFolder,
	getThemes,
	(folders, themes) => folders.map((folder) => themes[folder])
);
