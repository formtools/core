import * as actions from './actions';

export const constants = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADING) {
		return {
			root_url: constants.root_url,
			root_dir: constants.root_dir,
			data_source_url: constants.data_source_url,
			core_version: constants.core_version
		};
	}
	return state;
};

export const userInfo = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.userInfo;
	}
	return state;
};

export const i18n = (state = {}, action) => {
	if (action.type === actions.INIT_DATA_LOADED) {
		return action.i18n;
	}
	return state;
};

export const init = (state = {
	initialized: false,
	errorInitializing: false,
	isAuthenticated: false
}, action) => {

	if (action.type === actions.INIT_DATA_LOADED) {
		return Object.assign({}, state, {
			initialized: true,
			isAuthenticated: actions.is_logged_in
		});
	} else if (action.type === actions.INIT_DATA_ERROR_LOADING) {
		return Object.assign({}, state, {
			initialized: true,
			errorInitializing: false
		});
	} else {
		return state;
	}
};
