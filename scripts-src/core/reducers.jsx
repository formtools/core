import * as actions from './actions';

export const init = (state = {}, action) => {

	if (action.type === actions.INIT_DATA_LOADED) {
		const { i18n, is_logged_in, constants } = action;

		return Object.assign({}, state, {
			initialized: true,
			i18n: i18n,
			isAuthenticated: is_logged_in,
			constants: {
				root_url: constants.root_url,
				root_dir: constants.root_dir,
				data_source_url: constants.data_source_url,
				core_version: constants.core_version
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
