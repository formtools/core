import { createSelector } from "reselect";

export const searchFilter = (state) => state.compatibleComponents.searchFilter;
export const modules = (state) => state.compatibleComponents.modules;
export const themes = (state) => state.compatibleComponents.themes;
export const visibleModulesByFolder = (state) => state.compatibleComponents.visibleModulesByFolder;
export const visibleThemesByFolder = (state) => state.compatibleComponents.visibleThemesByFolder;


export const getVisibleModules = createSelector(
	visibleModulesByFolder,
	modules,
	(folders, modules) => folders.map((folder) => modules[folder])
);

export const getVisibleThemes = createSelector(
	visibleThemesByFolder,
	themes,
	(folders, themes) => folders.map((folder) => themes[folder])
);
