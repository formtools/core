import * as actions from './actions';
import C from '../../core/constants';

export default (state = {
	loaded: false,
	errorLoading: false,
	error: '',
	searchFilter: '',
	core: {},
	api: {},
	modules: {},
	visibleModulesByFolder: [],
	themes: {},
	visibleThemesByFolder: []
}, action) => {

	switch (action.type) {

		// converts the list of modules and themes to an object 	with (unique) folder names as keys
		case actions.COMPATIBLE_COMPONENTS_LOADED:
			const modules = {};
			const visibleModulesByFolder = [];

			action.modules.forEach(({ name, desc, folder, repo, version }) => {
				modules[folder] = {
					name, desc, folder, repo,
					version: version.version,
					selected: C.PRESELECTED_MODULES.indexOf(folder) !== -1
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
				visibleModulesByFolder,
				selectedModuleFolders: C.PRESELECTED_MODULES,
				themes,
				visibleThemesByFolder,
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
				selectedModuleFolders: selectedModulesReducer(state.selectedModuleFolders, action),
				...state
			};

		case actions.TOGGLE_THEME:
			return {
				themes: themesReducer(state.themes, action),
				...state
			};
	}

	return state;
};

const selectedModulesReducer = (state = [], action) => {
	if (action.type === actions.TOGGLE_MODULE) {
		if (state.includes(action.folder)) {
			return helpers.removeFromArray(state, action.folder);
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











