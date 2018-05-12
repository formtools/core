import * as actions from './actions';

const compatibleComponents = (state = {
	loaded: false,
	errorLoading: false,
	error: '',
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

		default:
			return state;
	}
};

export {
	compatibleComponents
};
