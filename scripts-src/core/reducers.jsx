import * as actions from './actions';

export const init = (state = {
	initialized: false,
	i18n: null
}, action) => {

	if (action.type === actions.INIT_DATA_LOADED) {
		return Object.assign({}, state, {
			initialized: true,
			i18n: action.i18n,
			isAuthenticated: action.is_logged_in,
			constants: {
				root_url: action.constants.root_url,
				root_dir: action.constants.root_dir
			}
		});
	} else if (action.type === actions.INIT_DATA_ERROR_LOADING) {
		return Object.assign({}, state, {
			errorInitializing: false
		});
	} else {
		return state;
	}
};
