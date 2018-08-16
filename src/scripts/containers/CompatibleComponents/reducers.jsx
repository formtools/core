import * as actions from './actions';
import C from '../../core/constants';
import { removeFromArray } from '../../core/helpers';

export default (state = {
	loaded: false,
	errorLoading: false,
	error: '',
	isEditing: false,
	searchFilter: '',
	core: {},
	api: {},
	modules: {},
	visibleModulesByFolder: [],
	themes: {},
	visibleThemesByFolder: [],
    selectedModuleFolders: [],
    selectedThemeFolders: []
}, action) => {

	switch (action.type) {

		// converts the list of modules and themes to an object 	with (unique) folder names as keys
		case actions.COMPATIBLE_COMPONENTS_LOADED:
			const modules = {};
			const visibleModulesByFolder = [];

			action.modules.forEach(({ name, desc, folder, repo, version }) => {
				modules[folder] = {
					name, desc, folder, repo,
					version: version.version
				};
				visibleModulesByFolder.push(folder);
			});

			const themes = {};
			const visibleThemesByFolder = [];
			action.themes.forEach(({ name, desc, folder, repo, version }) => {
				themes[folder] = {
					name, desc, folder, repo,
					version: version.version
				};
				visibleThemesByFolder.push(folder);
			});

			return Object.assign({}, state, {
				loaded: true,
				modules,
				themes,
				visibleThemesByFolder,
				visibleModulesByFolder,
				selectedModuleFolders: C.PRESELECTED_MODULES,
				selectedThemeFolders: C.PRESELECTED_THEMES
			});

		// updating the search filter also updates the list of visible modules by folder
		case actions.UPDATE_SEARCH_FILTER:
			const re = new RegExp(action.searchFilter.toLowerCase());

			const sortedFilteredModules = Object.keys(state.modules)
				.map(id => state.modules[id])
				.filter((item) => {
					return re.test(item.name.toLowerCase()) || re.test(item.desc.toLowerCase());
				})
				.sort((a) => {
					// return matches on the module name first
					return (re.test(a.name.toLowerCase())) ? -1 : 1;
				})
				.map((module) => module.folder);

			return Object.assign({}, state, {
				searchFilter: action.searchFilter,
				visibleModulesByFolder: sortedFilteredModules
			});

		case actions.TOGGLE_API:
			return Object.assign({}, state, {
				api: Object.assign({}, state.api, {
					selected: !state.api.selected
				})
			});

		case actions.TOGGLE_MODULE:
			return {
				...state,
				selectedModuleFolders: selectedModulesReducer(state.selectedModuleFolders, action)
			};

		case actions.TOGGLE_THEME:
			return {
				...state,
				themes: themesReducer(state.themes, action)
			};

		case actions.EDIT_SELECTED_COMPONENT_LIST:
			return {
				...state,
				isEditing: true
			};

		case actions.CANCEL_EDIT_SELECTED_COMPONENT_LIST:
			return {
				...state,
				isEditing: false
			};
	}

	return state;
};

const selectedModulesReducer = (state = [], action) => {
	if (action.type === actions.TOGGLE_MODULE) {
		if (state.includes(action.folder)) {
			return removeFromArray(state, action.folder);
		} else {
			return [...state, action.folder];
		}
	}
	return state;
};

const themesReducer = (state = {}, action) => {
	if (action === actions.TOGGLE_MODULE) {
		return {
			...state,
			[action.folder]: {
				selected: !state[action.folder].selected,
				...state[action.folder]
			},
		}
	}
	return state;
};











