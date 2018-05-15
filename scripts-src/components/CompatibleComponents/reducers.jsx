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
				api: action.api,
				modules: action.modules,
				themes: action.themes
			});

		case actions.UPDATE_SEARCH_FILTER:
			return Object.assign({}, state, {
				searchFilter: action.searchFilter
			});

		default:
			return state;
	}
};

export {
	compatibleComponents
};
