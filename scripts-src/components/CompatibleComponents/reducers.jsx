import * as actions from './actions';

const compatibleComponents = (state = {
	loaded: false,
	errorLoading: false,
	error: '',
	searchFilter: '',
	core: {},
	api: {},
	modules: [],
	themes: []
}, action) => {

	switch (action.type) {
		case actions.COMPATIBLE_COMPONENTS_LOADED:
			return Object.assign({}, state, {
				loaded: true,
				api: {
					selected: true
				},
				modules: action.modules.map(({ name, desc, folder, repo, version }) => ({
					name,
					desc,
					folder,
					repo,
					version: version.version,
					selected: C.PRESELECTED_MODULES.indexOf(folder) !== -1
				})),
				themes: action.themes.map(({ name, desc, folder, repo, version }) => ({
					name,
					desc,
					folder,
					repo,
					version: version.version,
					selected: C.PRESELECTED_THEMES.indexOf(folder) !== -1
				})),
			});

		case actions.UPDATE_SEARCH_FILTER:
			return Object.assign({}, state, {
				searchFilter: action.searchFilter
			});

		case actions.TOGGLE_API:
			return Object.assign({}, state, {
				api: Object.assign({}, state.api, {
					selected: !state.api.selected
				})
			});

		case actions.TOGGLE_MODULE:
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
