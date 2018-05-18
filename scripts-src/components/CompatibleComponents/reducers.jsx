import * as actions from './actions';
import C from '../../core/constants';

const compatibleComponents = (state = {
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
					version: version.version,
					selected: C.PRESELECTED_THEMES.indexOf(folder) !== -1
				};
				visibleThemesByFolder.push(folder);
			});

			return Object.assign({}, state, {
				loaded: true,
				api: {
					selected: true
				},
				modules,
				visibleModulesByFolder,
				themes,
				visibleThemesByFolder
			});

		case actions.UPDATE_SEARCH_FILTER:
			const re = new RegExp(action.searchFilter.toLowerCase());

			const sortedFilteredModules = modules.filter((item) => {
				return re.test(item.name.toLowerCase()) || re.test(item.desc.toLowerCase());
			}).sort((a) => {
				// return matches on the module name first
				return (re.test(a.name.toLowerCase())) ? -1 : 1;
			}).map((module) => module.folder);

			return Object.assign({}, state, {
				searchFilter: action.searchFilter,
				visibleModules: sortedFilteredModules
			});

		case actions.TOGGLE_API:
			return Object.assign({}, state, {
				api: Object.assign({}, state.api, {
					selected: !state.api.selected
				})
			});

		case actions.TOGGLE_MODULE:
//			const index = state.modules.findIndex((i) => i.folder === action.folder);
//			list.splice(index, 1, action.object)
//			return state.set('list_of_objects', list)

			break;

		case actions.TOGGLE_THEME:
			break;

		default:
			return state;
	}
};

export {
	compatibleComponents
};
